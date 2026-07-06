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

namespace local_entities;

use advanced_testcase;
use cache;
use local_entities\local\osm_geocoder;

/**
 * Tests for the keyless OSM/Nominatim geocoder — query building, response parsing, cache contract.
 *
 * Network is never hit (PHPUNIT_TEST short-circuits get_coordinates); the URL/parse helpers are pure.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_entities\local\osm_geocoder
 */
final class osm_geocoder_test extends advanced_testcase {
    /**
     * build_query assembles parts and returns '' for an empty address.
     */
    public function test_build_query(): void {
        $this->assertSame('', osm_geocoder::build_query([]));
        $query = osm_geocoder::build_query([
            'streetname' => 'Hauptstrasse',
            'streetnumber' => '1',
            'postcode' => '1010',
            'city' => 'Wien',
            'country' => 'Austria',
        ]);
        $this->assertSame('Hauptstrasse 1, 1010 Wien, Austria', $query);
    }

    /**
     * Regression (76ae03c): the request URL encodes spaces as %20 (RFC3986), never '+'.
     */
    public function test_build_request_url_uses_rfc3986_space_encoding(): void {
        $url = osm_geocoder::build_request_url('Hauptstrasse 1, 1010 Wien');
        $this->assertStringContainsString('nominatim.openstreetmap.org/search', $url);
        $this->assertStringContainsString('q=', $url);
        $this->assertStringContainsString('%20', $url, 'Spaces must be %20-encoded.');
        $this->assertStringNotContainsString('+', $url, 'Spaces must NOT be +-encoded (Nominatim rejects it).');
    }

    /**
     * parse_nominatim_response: usable match -> coords; valid-but-empty -> false; non-array/garbage -> null.
     *
     * Covers regression 2f97c1b (non-array guard) and the negative-cache vs transient distinction.
     */
    public function test_parse_nominatim_response(): void {
        $coords = osm_geocoder::parse_nominatim_response('[{"lat":"48.2","lon":"16.37"}]');
        $this->assertIsObject($coords);
        $this->assertEqualsWithDelta(48.2, $coords->lat, 0.0001);
        $this->assertEqualsWithDelta(16.37, $coords->lon, 0.0001);

        // Valid response, no usable match -> false (caller caches as NOT_FOUND).
        $this->assertFalse(osm_geocoder::parse_nominatim_response('[]'));

        // Non-array (error object) -> null (transient; caller must NOT cache).
        $this->assertNull(osm_geocoder::parse_nominatim_response('{"error":"rate limited"}'));
        // Garbage / non-JSON -> null.
        $this->assertNull(osm_geocoder::parse_nominatim_response('not json'));
    }

    /**
     * get_coordinates returns a cached hit and respects the NOT_FOUND sentinel — and an empty query is null.
     */
    public function test_get_coordinates_cache_contract(): void {
        $this->resetAfterTest();
        $cache = cache::make('local_entities', 'geocode');

        $address = ['city' => 'Wien', 'country' => 'Austria'];
        $key = sha1(osm_geocoder::build_query($address));

        // Cache hit with coordinates -> returned verbatim.
        $cache->set($key, (object)['lat' => 48.2, 'lon' => 16.37]);
        $hit = osm_geocoder::get_coordinates($address);
        $this->assertIsObject($hit);
        $this->assertEqualsWithDelta(48.2, $hit->lat, 0.0001);

        // Negative-cache sentinel -> null (no re-fetch).
        $cache->set($key, osm_geocoder::NOT_FOUND);
        $this->assertNull(osm_geocoder::get_coordinates($address));

        // Empty address -> null without touching the cache.
        $this->assertNull(osm_geocoder::get_coordinates([]));
    }

    /**
     * Regression (4d83a53): an un-cached lookup in tests returns null WITHOUT writing a cache entry,
     * so a later real lookup can still retry (no poisoning with a transient/no-network miss).
     */
    public function test_uncached_lookup_does_not_write_cache_in_tests(): void {
        $this->resetAfterTest();
        $cache = cache::make('local_entities', 'geocode');

        $address = ['city' => 'Nowhereville', 'country' => 'Austria'];
        $key = sha1(osm_geocoder::build_query($address));
        $this->assertFalse($cache->get($key), 'Precondition: nothing cached.');

        $this->assertNull(osm_geocoder::get_coordinates($address));
        $this->assertFalse($cache->get($key), 'An un-cached test lookup must NOT write a cache entry.');
    }

    /**
     * embed_url and osm_link produce sane OSM URLs for coordinates.
     */
    public function test_embed_and_osm_link(): void {
        $coords = (object)['lat' => 48.2, 'lon' => 16.37];
        $embed = osm_geocoder::embed_url($coords);
        $this->assertStringContainsString('openstreetmap.org/export/embed.html', $embed);
        $this->assertStringContainsString('marker=', $embed);

        $link = osm_geocoder::osm_link($coords);
        $this->assertStringContainsString('openstreetmap.org/?', $link);
        $this->assertStringContainsString('mlat=', $link);
    }
}
