<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_entities\local;

use cache;

/**
 * Keyless address → coordinates geocoding via OpenStreetMap's Nominatim, with aggressive caching.
 *
 * Results (hits and misses) are cached long-term keyed by the address, so a given address is
 * geocoded at most once — respecting Nominatim's usage policy. Callers degrade gracefully to a plain
 * "open in OpenStreetMap" link when no coordinates are available.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class osm_geocoder {

    /** @var string Sentinel cached for addresses that could not be geocoded (negative cache). */
    const NOT_FOUND = 'none';

    /**
     * Returns {lat, lon} for an address, or null if it cannot be geocoded.
     *
     * @param array $address address parts (streetname, streetnumber, postcode, city, country)
     * @return \stdClass|null object with ->lat and ->lon, or null
     */
    public static function get_coordinates(array $address): ?\stdClass {
        $query = self::build_query($address);
        if ($query === '') {
            return null;
        }

        $cache = cache::make('local_entities', 'geocode');
        $key = sha1($query);
        $cached = $cache->get($key);
        if ($cached !== false) {
            return ($cached === self::NOT_FOUND) ? null : $cached;
        }

        // Never reach out to the network during automated tests; rely on the cache / link fallback.
        if ((defined('PHPUNIT_TEST') && PHPUNIT_TEST) || (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING)) {
            return null;
        }

        $result = self::fetch_from_nominatim($query);
        if ($result instanceof \stdClass) {
            $cache->set($key, $result);
            return $result;
        }
        if ($result === false) {
            // Definitive "no match" for a valid request: cache it so we don't ask again.
            $cache->set($key, self::NOT_FOUND);
            return null;
        }
        // Transient failure (rate limit / timeout / error response): do NOT cache, so it retries.
        return null;
    }

    /**
     * Builds a single-line geocoding query from address parts.
     *
     * @param array $address
     * @return string
     */
    public static function build_query(array $address): string {
        $street = trim(($address['streetname'] ?? '') . ' ' . ($address['streetnumber'] ?? ''));
        $parts = array_filter([
            $street,
            trim(($address['postcode'] ?? '') . ' ' . ($address['city'] ?? '')),
            $address['country'] ?? '',
        ], static fn($p) => trim((string)$p) !== '');
        return implode(', ', $parts);
    }

    /**
     * Queries Nominatim for the first match.
     *
     * @param string $query
     * @return \stdClass|false|null coords on success, false for a valid "no match", null on a
     *                              transient failure (rate limit / timeout / error response)
     */
    protected static function fetch_from_nominatim(string $query) {
        global $CFG;

        $url = self::build_request_url($query);

        $curl = new \curl();
        $response = $curl->get($url, [], [
            'CURLOPT_TIMEOUT' => 5,
            'CURLOPT_CONNECTTIMEOUT' => 5,
            // Nominatim requires an identifying User-Agent referencing the operator.
            'CURLOPT_USERAGENT' => 'Moodle local_entities (' . $CFG->wwwroot . ')',
        ]);

        $httpcode = $curl->get_info()['http_code'] ?? 0;
        if ($curl->get_errno() || empty($response) || (int)$httpcode !== 200) {
            return null; // Transient (network/HTTP error, rate limit) — caller will not cache this.
        }
        return self::parse_nominatim_response((string)$response);
    }

    /**
     * Build the Nominatim request URL for a query (pure; no network).
     *
     * RFC3986 encoding (spaces as %20): Nominatim rejects the default '+'-encoded spaces with
     * HTTP 400 "Nothing to search for.".
     *
     * @param string $query
     * @return string
     */
    public static function build_request_url(string $query): string {
        return 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'format' => 'jsonv2',
            'limit' => 1,
            'q' => $query,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Interpret a raw Nominatim response body (pure; no network).
     *
     * Nominatim returns a JSON array of matches; on errors/rate-limits it may return an object or
     * non-JSON instead.
     *
     * @param string $response raw response body
     * @return \stdClass|false|null coords on a usable match, false for a valid "no match"
     *                              (→ negative-cache), null for a non-array/garbage response (→ transient)
     */
    public static function parse_nominatim_response(string $response) {
        $data = json_decode($response);
        if (!is_array($data)) {
            return null; // Not a valid result set (object/garbage) → treat as transient, do not cache.
        }
        if (!isset($data[0]) || !is_object($data[0]) || !isset($data[0]->lat, $data[0]->lon)) {
            return false; // Valid response, but no usable match → cache as "not found".
        }
        return (object)['lat' => (float)$data[0]->lat, 'lon' => (float)$data[0]->lon];
    }

    /**
     * Returns the OpenStreetMap embeddable-iframe src for the given coordinates.
     *
     * @param \stdClass $coords object with ->lat and ->lon
     * @return string
     */
    public static function embed_url(\stdClass $coords): string {
        // Small bounding box around the point so the marker sits centred at a street-level zoom.
        $delta = 0.0025;
        $bbox = implode(',', [
            $coords->lon - $delta,
            $coords->lat - $delta,
            $coords->lon + $delta,
            $coords->lat + $delta,
        ]);
        return 'https://www.openstreetmap.org/export/embed.html?' . http_build_query([
            'bbox' => $bbox,
            'layer' => 'mapnik',
            'marker' => $coords->lat . ',' . $coords->lon,
        ]);
    }

    /**
     * Returns a link to the full OpenStreetMap page for the coordinates.
     *
     * @param \stdClass $coords
     * @return string
     */
    public static function osm_link(\stdClass $coords): string {
        return 'https://www.openstreetmap.org/?' . http_build_query([
            'mlat' => $coords->lat,
            'mlon' => $coords->lon,
        ]) . '#map=18/' . $coords->lat . '/' . $coords->lon;
    }
}
