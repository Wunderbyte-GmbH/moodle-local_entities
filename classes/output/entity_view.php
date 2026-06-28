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

namespace local_entities\output;

use context_system;
use local_entities\customfield\entities_cf_helper;
use local_entities\customfield\entities_handler;
use local_entities\local\osm_geocoder;
use local_entities\local\views\view_templates;
use local_entities\settings_manager;
use moodle_url;
use stdClass;

/**
 * Builds the rendering context for the entity detail page and resolves which view template to use.
 *
 * The context is template-agnostic: every view template (classic, image, calendar, …) receives the
 * same enriched entity object and renders the parts it needs. Extracting this out of view.php keeps
 * the page thin and lets a single, consistent context back all templates.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entity_view {

    /**
     * Resolves the active view template key.
     *
     * A valid preview key is honoured only for users who may change the view template (so a manager
     * can try a template via the URL without affecting anyone else); otherwise the baseline applies.
     * The global active-template setting is added in a later phase.
     *
     * @param string $preview optional preview template key (honoured only for permitted users)
     * @param string $entitytype the entity's type, to pick a type-specific template if configured
     * @return string the template key (without the 'local_entities/view/' prefix)
     */
    public static function resolve_active_template(string $preview = '', string $entitytype = ''): string {
        if (view_templates::exists($preview)
                && has_capability('local/entities:changeviewtemplate', context_system::instance())) {
            return $preview;
        }
        // Type-specific choice wins over the global one (which stays the fallback for all types).
        if ($entitytype !== '') {
            $typed = (string) get_config('local_entities', 'activeviewtemplate_' . $entitytype);
            if (view_templates::exists($typed)) {
                return $typed;
            }
        }
        $active = (string) get_config('local_entities', 'activeviewtemplate');
        return view_templates::exists($active) ? $active : view_templates::DEFAULT_TEMPLATE;
    }

    /**
     * Builds the map data for the detail view, or null when there is nothing to show.
     *
     * Resolution order: a manually pasted embed (any provider) → an OpenStreetMap embed geocoded
     * from the address → a plain map link. Geocoding is cached and degrades to the link when offline.
     *
     * @param stdClass $entity the (already address-resolved) entity context
     * @return stdClass|null
     */
    protected static function build_map_data(stdClass $entity): ?stdClass {
        if (empty($entity->hasaddress) || empty($entity->addresscleaned)) {
            return null;
        }
        $addr = $entity->addresscleaned[0];

        $map = new stdClass();
        if (!empty($addr->mapembed)) {
            $map->embedhtml = $addr->mapembed;
        } else {
            $coords = osm_geocoder::get_coordinates([
                'streetname' => $addr->streetname ?? '',
                'streetnumber' => $addr->streetnumber ?? '',
                'postcode' => $addr->postcode ?? '',
                'city' => $addr->city ?? '',
                'country' => $addr->country ?? '',
            ]);
            if ($coords !== null) {
                $map->osmembedurl = osm_geocoder::embed_url($coords);
                $map->osmlink = osm_geocoder::osm_link($coords);
            }
        }
        if (!empty($addr->maplink)) {
            $map->maplink = $addr->maplink;
        }

        if (empty($map->embedhtml) && empty($map->osmembedurl) && empty($map->maplink)) {
            return null;
        }
        return $map;
    }

    /**
     * Builds the on-page template switcher data, or null when the user may not change the template.
     *
     * Switching a template is a preview (a link carrying ?template=); only the explicit save button
     * (handled by the external function) persists it globally. The switcher therefore exposes the
     * template links plus, while previewing a not-yet-saved template, a save action.
     *
     * @param int $id entity id
     * @param string $renderedkey the template currently shown (possibly a preview)
     * @param string $activekey the saved template that applies to this entity's type
     * @param string $entitytype the entity's type (saving applies to all entities of this type)
     * @return array|null
     */
    public static function build_switcher(int $id, string $renderedkey, string $activekey,
            string $entitytype = ''): ?array {
        if (!has_capability('local/entities:changeviewtemplate', context_system::instance())) {
            return null;
        }

        $templates = [];
        foreach (view_templates::get_all() as $tpl) {
            $url = new moodle_url('/local/entities/view.php', ['id' => $id, 'template' => $tpl->key]);
            $templates[] = [
                'key' => $tpl->key,
                'name' => $tpl->name,
                'icon' => $tpl->icon,
                'active' => ($tpl->key === $renderedkey),
                'url' => $url->out(false),
            ];
        }

        $typename = \local_entities\local\views\entity_types::name($entitytype);

        return [
            'entityid' => $id,
            'entitytype' => $entitytype,
            'templates' => $templates,
            'ispreview' => ($renderedkey !== $activekey),
            'savekey' => $renderedkey,
            // Make it explicit that saving applies to the whole type, not just this entity.
            'savelabel' => get_string('saveviewfortype', 'local_entities', $typename),
            'baseurl' => (new moodle_url('/local/entities/view.php', ['id' => $id]))->out(false),
        ];
    }

    /**
     * One-off migration of the two legacy display checkboxes into the global active-view-template
     * setting, so existing sites keep their exact look. Only runs while the new setting is unset.
     *
     * Mapping: showpictureinsteadofcalendar=1 → image; else show_calendar_on_details_page=1 → calendar;
     * else → classic.
     *
     * @return void
     */
    public static function migrate_legacy_view_settings(): void {
        if (get_config('local_entities', 'activeviewtemplate') !== false) {
            return;
        }
        if (!empty(get_config('local_entities', 'showpictureinsteadofcalendar'))) {
            $template = 'image';
        } else if (!empty(get_config('local_entities', 'show_calendar_on_details_page'))) {
            $template = 'calendar';
        } else {
            $template = view_templates::DEFAULT_TEMPLATE;
        }
        set_config('activeviewtemplate', $template, 'local_entities');
    }

    /**
     * Builds the full rendering context (the enriched entity object) for the detail page.
     *
     * This is a faithful extraction of the logic previously inline in view.php, unchanged in
     * behaviour, so the existing templates render identically.
     *
     * @param int $id entity id
     * @return stdClass the entity object enriched with all template data
     */
    public static function build_context(int $id): stdClass {
        global $DB, $USER;

        $context = context_system::instance();
        $entity = settings_manager::get_settings($id);

        // Image / PDF in the entity's own file area.
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'local_entities', 'image', $id);
        $ispdf = false;
        $url = null;
        foreach ($files as $file) {
            $filename = $file->get_filename();
            if ($file->get_filesize() > 0) {
                $url = moodle_url::make_file_url('/pluginfile.php', '/1/local_entities/image/' . $id . '/' . $filename);
                if ($file->get_mimetype() === "application/pdf") {
                    $ispdf = true;
                }
            }
        }

        // Custom field values, grouped by category.
        $handlers = entities_cf_helper::create_std_handlers();
        if (isset($entity->cfitemid)) {
            $handlers[] = entities_handler::create((int) $entity->cfitemid);
        }
        $metadata = [];
        $metagroups = [];
        foreach ($handlers as $handler) {
            foreach ($handler->get_instance_data($id, true) as $data) {
                if (empty($data->get_value())) {
                    continue;
                }
                $field = $data->get_field();
                $catid = $field->get_category()->get('id');
                $catname = $field->get_category()->get('name');

                $meta = new stdClass();
                $meta->key = $field->get('name');
                $meta->value = $data->get_value();
                if (is_array($meta->value)) {
                    $meta->value = reset($meta->value);
                }

                $metadata[] = $meta;
                if (!isset($metagroups[$catid])) {
                    $metagroups[$catid] = (object)['name' => $catname, 'fields' => []];
                }
                $metagroups[$catid]->fields[] = $meta;
            }
        }
        $entity->hasmetadata = !empty($metadata);
        $entity->metadata = $metadata;
        $entity->metagroups = array_values($metagroups);

        // Affiliated (child) entities.
        $subentities = $DB->get_records('local_entities', ['parentid' => $id], 'name');
        $affiliation = [];
        if ($entity->hasaffiliation = !empty($subentities)) {
            foreach ($subentities as $entry) {
                $subentry = new stdClass();
                $subentry->link = new moodle_url("/local/entities/view.php", ["id" => $entry->id]);
                $subentry->name = $entry->name;
                $subentry->shortname = $entry->shortname;
                $subentry->editurl = new moodle_url('/local/entities/edit.php', ['id' => $entry->id]);
                $affiliation[] = $subentry;
            }
            $entity->affiliation = $affiliation;
        }

        // Parent entity + image/address/contacts fallbacks.
        $imagefallback = get_config('local_entities', 'fallback_image_parent');
        $contactsfallback = get_config('local_entities', 'fallback_contacts_parent');
        $addressfallback = get_config('local_entities', 'fallback_address_parent');
        $parenthascontacts = false;
        $parenthasaddress = false;

        if (!empty($entity->parentid)) {
            $parent = settings_manager::get_settings($entity->parentid);
            if (!empty($parent)) {
                $entity->parent = $parent;
                $entity->parent->link = new moodle_url("/local/entities/view.php", ["id" => $parent->id]);
                $parenthasaddress = !empty($parent->address);
                $parenthascontacts = !empty($parent->contacts);

                if (!isset($url) && $imagefallback) {
                    $files = $fs->get_area_files($context->id, 'local_entities', 'image', $parent->id);
                    foreach ($files as $file) {
                        $filename = $file->get_filename();
                        if ($file->get_filesize() > 0) {
                            $url = moodle_url::make_file_url('/pluginfile.php',
                                '/1/local_entities/image/' . $parent->id . '/' . $filename);
                        }
                    }
                }
            }
        }

        $entity->metadata = $metadata;
        $entity->description = file_rewrite_pluginfile_urls(
            $entity->description,
            'pluginfile.php',
            $context->id,
            'local_entity',
            'description',
            null
        );

        $entity->picture = !empty($url) ? $url : null;
        $entity->hasaddress = !empty($entity->address);
        $entity->hascontacts = !empty($entity->contacts);

        if ($ispdf) {
            $entity->haspdf = !empty($entity->picture);
        } else {
            $entity->haspicture = !empty($entity->picture);
        }

        if ($entity->hasaddress) {
            $entity->addresscleaned = array_values($entity->address);
        } else if ($parenthasaddress && $addressfallback) {
            $entity->hasaddress = $parenthasaddress;
            $entity->addresscleaned = array_values($parent->address);
        }
        if ($entity->hascontacts) {
            $entity->contactscleaned = array_values($entity->contacts);
        } else if ($parenthascontacts && $contactsfallback) {
            $entity->hascontacts = $parenthascontacts;
            $entity->contactscleaned = array_values($parent->contacts);
        }

        // Map data for the map view (and any template using the map partial): a manually pasted embed
        // wins; otherwise we geocode the address via OpenStreetMap; a plain link is the final fallback.
        $entity->mapdata = self::build_map_data($entity);
        $entity->hasmap = !empty($entity->mapdata);

        $entity->hasleftsidebar = $entity->hasmetadata || $entity->hasaffiliation;
        $entity->hasrightsidebar = $entity->hascontacts || $entity->hasaddress;

        $entity->canedit = has_capability('local/entities:edit', $context);
        $entity->editurl = new moodle_url('/local/entities/edit.php', ['id' => $id]);
        $entity->calendarurl = new moodle_url('/local/entities/calendar.php', ['id' => $id]);
        $entity->delurl = new moodle_url('/local/entities/entities.php', ['del' => $id, 'sesskey' => $USER->sesskey]);

        return $entity;
    }
}
