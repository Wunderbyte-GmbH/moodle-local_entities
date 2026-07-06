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

/**
 * Handler for entities relations
 * @package    local_entities
 * @copyright  2021 Wunderbyte GmbH
 * @author     Thomas Winkler
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_entities;

use cache;
use cache_helper;

defined('MOODLE_INTERNAL') || die();
define('LOCAL_ENTITIES_FORM_ENTITYID', 'local_entities_entityid_');
define('LOCAL_ENTITIES_FORM_ENTITYAREA', 'local_entities_entityarea_');
define('LOCAL_ENTITIES_FORM_RELATIONID', 'local_entities_relationid_');
define('LOCAL_ENTITIES_FORM_NAME', 'local_entities_entityname_');
define('LOCAL_ENTITIES_FORM_QUANTITY', 'local_entities_quantity_');
define('LOCAL_ENTITIES_FORM_EQUIPMENT', 'local_entities_equipment_');

global $CFG;
require_once("$CFG->libdir/formslib.php");

use moodle_exception;
use MoodleQuickForm;
use stdClass;

/**
 * Control and manage option dates.
 *
 * @copyright Wunderbyte GmbH <info@wunderbyte.at>
 * @author Thomas Winkler
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entitiesrelation_handler {
    /** @var string $component */
    public $component = '';

    /** @var string $area */
    public $area = '';

    /** @var int $instanceid */
    public $instanceid = 0;

    /**
     * Constructor.
     * @param string $component
     * @param string $area
     * @param int $instanceid
     */
    public function __construct(string $component, string $area, int $instanceid = 0) {
        $this->component = $component;
        $this->area = $area;
        $this->instanceid = $instanceid;
    }

    /**
     * Add form fields to be passed on mform.
     *
     * @param MoodleQuickForm $mform
     * @param int $index // We use the index if we have more than one entity in the form.
     * @param bool $showheader true if header should be shown, false to hide header
     * @param string|null $headerlangidentifier
     * @param string|null $headerlangcomponent
     * @param int $entityid optional entity id
     *
     * @return array
     *
     */
    public function instance_form_definition(
        MoodleQuickForm &$mform,
        int $index = 0,
        bool $showheader = true,
        ?string $headerlangidentifier = null,
        ?string $headerlangcomponent = null,
        int $entityid = 0
    ) {
        global $PAGE;

        $elements = [];

        if (!empty($headerlangidentifier)) {
            $header = get_string($headerlangidentifier, $headerlangcomponent);
        } else {
            $header = get_string('addentity', 'local_entities');
        }

        if ($showheader) {
            $mform->addElement(
                'header',
                'entitiesrelation',
                '<i class="fa fa-fw fa-building" aria-hidden="true"></i>&nbsp;' .
                $header
            );
            // No hard-coded collapse: the header's expand/collapse state is handled by the form
            // (default collapse + central restore in mod_booking via restore_header_collapse_state),
            // so it persists across no-submit reloads instead of always snapping shut.
        }

        $records = \local_entities\entities::list_all_parent_entities();

        $select = [0 => get_string('none', 'local_entities')];
        foreach ($records as $record) {
            $select[$record->id] = $record->name;
        }
        $options = [
            'multiple' => false,
            'noselectionstring' => get_string('none', 'local_entities'),
            'ajax' => 'local_entities/form_entities_selector',
            'valuehtmlcallback' => function ($value) {
                global $OUTPUT;
                if (empty($value)) {
                    return get_string('none', 'local_entities');
                }
                // Full ancestor path (any depth), consistent with the suggestion list. No depth is
                // passed on purpose: the selected value stands alone, indentation would be noise.
                [, , $names] = \local_entities\entities::get_ancestor_path((int)$value);
                if (empty($names)) {
                    return get_string('none', 'local_entities');
                }
                $selfname = array_pop($names);
                $entitydata = [
                    'name' => $selfname,
                    'shortname' => $selfname,
                    'parentname' => implode(' / ', $names),
                ];
                return $OUTPUT->render_from_template('local_entities/form-entities-selector-suggestion', $entitydata);
            },
        ];

        $element = $mform->addElement(
            'autocomplete',
            LOCAL_ENTITIES_FORM_ENTITYID . $index,
            get_string('er_entitiesname', 'local_entities'),
            [],
            $options
        );

        if (!empty($entityid)) {
            $element->setValue($entityid);
        }
        $elements[] = $element;
        $elements[] = $mform->addElement('hidden', LOCAL_ENTITIES_FORM_ENTITYAREA . $index, 'optiondate');
        $mform->setType(LOCAL_ENTITIES_FORM_ENTITYAREA . $index, PARAM_TEXT);

        // Equipment selection: when the chosen location has equipment available (resolved over the
        // location hierarchy), offer a quantity field per equipment item. A no-submit button reloads
        // the form so the equipment fields follow the chosen location (Moodle dynamic_form pattern).
        if ($this->area === 'option') {
            $refreshbtn = 'btn_local_entities_equipment_' . $index;
            $mform->registerNoSubmitButton($refreshbtn);
            $elements[] = $mform->addElement(
                'submit',
                $refreshbtn,
                get_string('refreshequipment', 'local_entities'),
                ['data-entityindex' => $index]
            );
            $mform->setType($refreshbtn, PARAM_NOTAGS);

            // Read the chosen location from the form's own submitted data. In a dynamic_form the
            // no-submit ("refresh equipment") round-trip delivers the values as $ajaxformdata, not
            // via $_POST/$_GET, so the global optional_param() would see nothing and no equipment
            // fields would render. MoodleQuickForm::optional_param() checks $ajaxformdata first and
            // falls back to $_POST/$_GET, so it works both on a plain page load and on the AJAX reload.
            $locationid = $mform->optional_param(LOCAL_ENTITIES_FORM_ENTITYID . $index, 0, PARAM_INT);
            if ($locationid > 0) {
                foreach (\local_entities\entities::get_equipment_for_location($locationid) as $equipment) {
                    $fieldname = LOCAL_ENTITIES_FORM_EQUIPMENT . (int)$equipment->id;
                    $elements[] = $mform->addElement(
                        'text',
                        $fieldname,
                        get_string('equipmentquantity', 'local_entities', format_string($equipment->name))
                    );
                    $mform->setType($fieldname, PARAM_INT);
                    $mform->setDefault($fieldname, 0);
                }
            }
        }

        // Todo: Time table feature is currently not working, we need to fix this in a future release.
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /* $elements[] = $mform->addElement('button', 'openmodal_' . $index, get_string('opentimetable', 'local_entities')); */

        $PAGE->requires->js_call_amd('local_entities/handler', 'init');

        // Todo: Check if this can be removed safely.
        /* $PAGE->requires->css('/local/entities/js/main.css'); */ // phpcs:ignore Squiz.PHP.CommentedOutCode.Found

        return $elements;
    }

    /**
     * Build a human-readable availability-conflict error for one entity (room or equipment).
     *
     * Names the affected resource and, in capacity mode, states the requested amount against the
     * total capacity, so the user can tell which resource conflicts and why — not just that "there
     * is a conflict". The overlapping bookings are listed with links and prettified date ranges.
     *
     * @param int $entityid the conflicting entity (location or equipment)
     * @param int $requested the amount this booking tried to consume (participants or units)
     * @param array $conflicts the conflicting entitydate objects from entities::return_conflicts()
     * @return string the assembled HTML error message
     */
    private function format_conflict_error(int $entityid, int $requested, array $conflicts): string {
        $entity = entity::load($entityid);
        $name = format_string($entity->name);

        if (entities::get_allocation_mode($entityid) === 'capacity') {
            $message = get_string('conflictcapacity', 'local_entities', (object)[
                'name' => $name,
                'requested' => $requested,
                'capacity' => (int)($entity->__get('maxallocation') ?? 0),
            ]);
        } else {
            $message = get_string('conflictexclusive', 'local_entities', $name);
        }

        foreach ($conflicts as $conflict) {
            $link = $conflict->link->out();
            $message .= "<br><a href='$link'>" . format_string($conflict->name) . " (" .
                dates::prettify_dates_start_end($conflict->starttime, $conflict->endtime, current_language()) . ")</a>";
        }

        return $message;
    }

    /**
     * Function to validate the correct input of entity and mainly it's availability.
     * In order to work, the key "datestobook" has to be present as an array of entitydates.
     * If there is an itemid, then the dates are already booked. If itemid is 0, they are new.
     * This distinction is important to no falsly identify conflict with itself.
     *
     * @param array $data
     * @param array $errors
     * @return void
     */
    public function instance_form_validation(array $data, array &$errors) {

        // First, see if an entitiyid is set. If not, we can proceed right away.
        if (!$entityidkeys = preg_grep('/^local_entities_entityid/', array_keys($data))) {
            // For performance.
            return;
        }

        foreach ($entityidkeys as $entityidkey) {
            if (empty($data[$entityidkey])) {
                // If there is no entityid value found, we don't need to validate.
                continue;
            }

            $area = $data[$entityidkey] == "local_entities_entityid_0" ? 'option' : 'optiondate';

            // Capacity mode: tag the candidate dates with the amount this booking consumes of the
            // entity (participants or an explicitly entered quantity), so return_conflicts can sum it.
            $entityid = (int)$data[$entityidkey];
            $index = (int)substr($entityidkey, strlen(LOCAL_ENTITIES_FORM_ENTITYID));
            $consumed = entities::resolve_consumed_quantity($entityid, $data, $index);
            foreach (($data['datestobook'] ?? []) as $datetobook) {
                if (is_object($datetobook)) {
                    $datetobook->quantity = $consumed;
                }
            }

            // Now determine if there are conflicts.
            $conflicts = entities::return_conflicts(
                $entityid,
                $data['datestobook'] ?? [],
                $data['optionid'] ?? 0,
                $area
            );

            if (!empty($conflicts['conflicts'])) {
                $errors[$entityidkey] = $this->format_conflict_error($entityid, $consumed, $conflicts['conflicts']);
            }
            if (!empty($conflicts['openinghours'])) {
                $errors[$entityidkey] .= get_string('notwithinopeninghours', 'local_entities');
            }
        }

        // Validate equipment chosen for the option's location (option level): each chosen equipment
        // is re-verified against what is actually available for the location, then capacity-checked.
        if ($equipmentkeys = preg_grep('/^' . LOCAL_ENTITIES_FORM_EQUIPMENT . '/', array_keys($data))) {
            $locationid = (int)($data[LOCAL_ENTITIES_FORM_ENTITYID . 0] ?? 0);
            $available = $locationid > 0 ? entities::get_equipment_for_location($locationid) : [];

            foreach ($equipmentkeys as $eqkey) {
                $eqid = (int)substr($eqkey, strlen(LOCAL_ENTITIES_FORM_EQUIPMENT));
                $qty = (int)$data[$eqkey];
                if ($eqid <= 0 || $qty <= 0 || !isset($available[$eqid])) {
                    continue;
                }

                foreach (($data['datestobook'] ?? []) as $datetobook) {
                    if (is_object($datetobook)) {
                        $datetobook->quantity = $qty;
                    }
                }

                $conflicts = entities::return_conflicts(
                    $eqid,
                    $data['datestobook'] ?? [],
                    $data['optionid'] ?? 0,
                    'option'
                );
                if (!empty($conflicts['conflicts'])) {
                    $errors[$eqkey] = $this->format_conflict_error($eqid, $qty, $conflicts['conflicts']);
                }
            }
        }

        // Validation for entities in combination with mod_booking.
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /*if ($this->component = 'mod_booking' && $this->area = 'option') {
            $optionid = $this->instanceid;

            // In validation we need to check, if there are optiondates that have "outlier" entities.
            // If so, the outliers must be changed to the main entity before all relations can be saved.
            if (!empty($data['er_saverelationsforoptiondates']) &&
                self::option_has_dates_with_entity_outliers($optionid) &&
                empty($data['confirm:er_saverelationsforoptiondates'])) {
                    $errors['confirm:er_saverelationsforoptiondates'] =
                        get_string('error:er_saverelationsforoptiondates', 'mod_booking');
            }
        }*/
    }

    /**
     * Helper function to check if there are dates with "entity outliers"
     * (e.g. if all dates have set "Classroom" but there is a date that is
     * happening outside and has set "Park").
     * @param int $optionid
     * @return bool true if there are outliers, false if not
     */
    public static function option_has_dates_with_entity_outliers(int $optionid): bool {
        global $DB;
        // If we have "outliers" (deviating entities), we show a confirm box...
        // ...so a user does not overwrite them accidentally.
        $sql = "SELECT COUNT(DISTINCT er.entityid) numberofentities
                FROM {local_entities_relations} er
                JOIN {booking_optiondates} bod
                ON bod.id = er.instanceid
                WHERE er.area = 'optiondate'
                AND bod.optionid = :optionid";
        $params = ['optionid' => $optionid];
        $numberofentities = $DB->get_field_sql($sql, $params);
        if (!empty($numberofentities) && $numberofentities > 1) {
            return true;
        }
        return false;
    }

    /**
     * Function to delete relation between module and entities.
     * @param int $instanceid
     * @return void
     */
    public function delete_relation(int $instanceid): void {
        global $DB;

        // Purge the entity occupancy cache BEFORE removing the relation, while we can still
        // resolve the affected entity from it.
        $this->purge_dates_cache($instanceid);

        $select = sprintf("component = :component AND area = :area AND %s = :instanceid", $DB->sql_compare_text('instanceid'));
        $DB->delete_records_select('local_entities_relations', $select, [
            'component' => $this->component,
            'area' => $this->area,
            'instanceid' => $instanceid,
        ]);

        cache_helper::invalidate_by_event('purgecachedentities', ["$this->component-$this->area-$instanceid"]);
    }

    /**
     * Purge the cached occupancy dates of the entity (or entities) linked to a given item.
     *
     * Resolves this handler's ($component, $area, $instanceid) to the linked entity id(s) and
     * targeted-purges only those entity cache entries — never the whole cache. This is the entry
     * point booking-side write paths should call (e.g. from purge_cache_for_answers /
     * purge_cache_for_option) so that a booking change refreshes exactly the affected entity.
     *
     * @param int $instanceid item id; falls back to the handler's own instanceid when 0
     * @return void
     */
    public function purge_dates_cache(int $instanceid = 0): void {
        global $DB;

        $instanceid = $instanceid > 0 ? $instanceid : (int)$this->instanceid;
        if ($instanceid <= 0) {
            return;
        }

        $entityids = $DB->get_fieldset_select(
            'local_entities_relations',
            'entityid',
            'component = :component AND area = :area AND instanceid = :instanceid',
            [
                'component' => $this->component,
                'area' => $this->area,
                'instanceid' => $instanceid,
            ]
        );

        foreach (array_unique(array_map('intval', $entityids)) as $entityid) {
            entities::purge_dates_cache($entityid);
        }
    }

    /**
     * Returns the data for the form.
     * @param int $instanceid
     * @return stdClass
     */
    public function get_instance_data(int $instanceid): stdClass {
        global $DB;

        $cache = cache::make('local_entities', 'cachedentities');
        $data = $cache->get("$this->component-$this->area-$instanceid");
        if ($data !== false) {
            return $data;
        }

        $sql = "SELECT
            r.entityid AS id,
            r.id AS relationid,
            r.component,
            r.area,
            r.instanceid,
            e.name,
            e.shortname,
            e.description,
            r.timecreated,
            e.parentid,
            ea.maplink,
            ea.mapembed,
            pe.name AS parentname
        FROM {local_entities_relations} r
        JOIN {local_entities} e
            ON e.id = r.entityid
        LEFT JOIN {local_entities} pe
            ON pe.id = e.parentid
        LEFT JOIN {local_entities_address} ea
            ON ea.entityidto = e.id
        WHERE r.component = :component
        AND r.area = :area
        AND r.instanceid = :instanceid
        AND (e.entitytype <> 'equipment' OR e.entitytype IS NULL) ";

        $params = [
            'component' => $this->component,
            'area' => $this->area,
            'instanceid' => $instanceid,
        ];

        $fieldsdata = $DB->get_record_sql($sql, $params);
        if (!$fieldsdata) {
            $stdclass = new stdClass();
            $cache->set("$this->component-$this->area-$instanceid", $stdclass);
            return $stdclass;
        }
        $cache->set("$this->component-$this->area-$instanceid", $fieldsdata);
        return $fieldsdata;
    }

    /**
     * Returns entityid for a given instanceid.
     * @param int $instanceid
     * @return int entityid
     */
    public function get_entityid_by_instanceid(int $instanceid): int {
        global $DB;

        $cache = cache::make('local_entities', 'cachedentities');
        $data = $cache->get("$this->component-$this->area-$instanceid");
        if (
            $data !== false
            && isset($data->id)
        ) {
            return $data->id;
        }

        $data = $this->get_instance_data($instanceid);

        return (int)($data->id ?? 0);
    }

    /**
     * Sets the fields from entitiesrelations to the given form if entry is found in DB
     *
     * @param MoodleQuickForm $mform
     * @param stdClass $instance
     * @param int $instanceid
     * @param int $index
     * @return void
     */
    public function instance_form_before_set_data(MoodleQuickForm &$mform, stdClass $instance, $instanceid = 0, $index = 0) {
        $instanceid = !empty($instanceid) ? $instanceid : 0;
        $fromdb = $this->get_instance_data($instanceid);
        $entityid = isset($fromdb->id) ? $fromdb->id : 0;
        $entityname = isset($fromdb->name) ? $fromdb->name : "";
        $erid = isset($fromdb->relationid) ? $fromdb->relationid : 0;
        $mform->setDefaults([LOCAL_ENTITIES_FORM_RELATIONID . $index => $erid]);
        $mform->setDefaults([LOCAL_ENTITIES_FORM_ENTITYID . $index => $entityid]);
        $mform->setDefaults([LOCAL_ENTITIES_FORM_NAME . $index => $entityname]);
    }

    /**
     * Sets the fields from entitiesrelations to the given form if entry is found in DB
     *
     * @param stdClass $data
     * @param int $instanceid
     * @param int $index
     * @return void
     */
    public function values_for_set_data(stdClass &$data, $instanceid = 0, $index = 0) {
        $instanceid = !empty($instanceid) ? $instanceid : 0;
        $fromdb = $this->get_instance_data($instanceid);

        // Check for empty is important. Otherwise we overwrite form values when any nosubmit button is pressed.
        if (empty($data->{LOCAL_ENTITIES_FORM_ENTITYID . $index})) {
            $data->{LOCAL_ENTITIES_FORM_ENTITYID . $index} = isset($fromdb->id) ? $fromdb->id : 0;
        }
        if (empty($data->{LOCAL_ENTITIES_FORM_NAME . $index})) {
            $data->{LOCAL_ENTITIES_FORM_NAME . $index} = isset($fromdb->name) ? $fromdb->name : "";
        }
        if (empty($data->{LOCAL_ENTITIES_FORM_RELATIONID . $index})) {
            $data->{LOCAL_ENTITIES_FORM_RELATIONID . $index} = isset($fromdb->relationid) ? $fromdb->relationid : 0;
        }

        // Pre-fill the quantity fields of equipment already booked for this item (option level).
        if ($this->area === 'option' && !empty($instanceid)) {
            foreach ($this->get_equipment_relations((int)$instanceid) as $rel) {
                $fieldname = LOCAL_ENTITIES_FORM_EQUIPMENT . (int)$rel->entityid;
                if (empty($data->{$fieldname})) {
                    $data->{$fieldname} = (int)($rel->quantity ?? 0);
                }
            }
        }
    }

    /**
     * Returns the equipment relations (entitytype='equipment') of an item.
     *
     * @param int $instanceid
     * @return array relation records keyed by id
     */
    public function get_equipment_relations(int $instanceid): array {
        global $DB;
        $sql = "SELECT r.* FROM {local_entities_relations} r
                JOIN {local_entities} e ON e.id = r.entityid
                WHERE r.component = :component AND r.area = :area AND r.instanceid = :instanceid
                  AND e.entitytype = 'equipment'";
        return $DB->get_records_sql($sql, [
            'component' => $this->component,
            'area' => $this->area,
            'instanceid' => $instanceid,
        ]);
    }

    /**
     * Saves the given data for entitiesrelations, must be called after the instance is saved and id is present
     * Function returns id of newly created or updated entity, if present.
     * Example:
     *   if ($data = $form->get_data()) {
     *     // ... save main instance, set $data->id if instance was created.
     *     $handler->instance_form_save($data);
     *     redirect(...);
     *   }
     *
     * @param stdClass $instance
     * @param int $instanceid
     * @param int $index
     * @return int|void
     */
    public function instance_form_save(stdClass $instance, int $instanceid, int $index = 0) {
        if (empty($instanceid)) {
            throw new \coding_exception('Caller must ensure that id is already set in data before calling this method');
        }
        if (!preg_grep('/^local_entities/', array_keys((array)$instance))) {
            // If this is called with no result, we must delete the handler.
            $this->delete_relation($instanceid);
            return;
        }
        $key = LOCAL_ENTITIES_FORM_ENTITYID . $index;
        if (empty($instance->{$key})) {
            $this->delete_relation($instanceid);
            return;
        }

        $data = new stdClass();
        if (isset($instance->local_entities_relationid)) {
            $data->id = $instance->local_entities_relationid;
        }
        $data->instanceid = $instanceid;
        $data->component = $this->component;
        $data->area = $this->area;
        $data->entityid = $instance->{$key};
        $data->timecreated = time();
        // Delete er if entitiyid is set to -1.
        if ($data->entityid == -1) {
            $this->delete_relation($data->instanceid);
            return;
        }
        // Capacity mode: snapshot the consumed amount (participants or entered quantity) so conflict
        // checks of other bookings can sum it without querying the owning component.
        $data->quantity = entities::resolve_consumed_quantity((int)$data->entityid, (array)$instance, $index);
        if ($this->er_record_exists($data)) {
            $result = $this->update_db($data);
        } else {
            $result = $this->save_to_db($data);
        }

        // Also persist any equipment chosen for this location (option level).
        $this->save_submitted_equipment($instance, $instanceid, $index);

        return $result;
    }

    /**
     * This saves a new relation and creates a "fake" form to use the form_save method.
     * If an empty entityid is provided, the relation is deleted.
     *
     * @param int $instanceid
     * @param int $entityid
     * @return void
     */
    public function save_entity_relation($instanceid, $entityid) {

        if (empty($entityid)) {
            $this->delete_relation($instanceid);
            return;
        }

        $instance = new stdClass();

        $instance->{LOCAL_ENTITIES_FORM_ENTITYID . 0} = $entityid;

        $this->instance_form_save($instance, $instanceid, 0);
    }

    /**
     * Syncs the equipment relations of an item (entity-aware, by entityid), leaving the item's
     * location relation untouched. Equipment is stored as additional relations on the same
     * (component, area, instanceid) with entitytype='equipment' and a per-relation quantity.
     *
     * @param int $instanceid
     * @param array $equipment map of equipmentid => quantity (kept when quantity > 0)
     * @return void
     */
    public function save_equipment_relations(int $instanceid, array $equipment): void {
        global $DB;

        $existing = $this->get_equipment_relations($instanceid);

        $keep = [];
        foreach ($equipment as $eqid => $qty) {
            $eqid = (int)$eqid;
            $qty = (int)$qty;
            if ($eqid > 0 && $qty > 0) {
                $keep[$eqid] = $qty;
            }
        }

        foreach ($existing as $rel) {
            $eqid = (int)$rel->entityid;
            if (!isset($keep[$eqid])) {
                // No longer chosen → remove this equipment relation.
                $DB->delete_records('local_entities_relations', ['id' => $rel->id]);
                entities::purge_dates_cache($eqid);
                continue;
            }
            if ((int)($rel->quantity ?? 1) !== $keep[$eqid]) {
                $rel->quantity = $keep[$eqid];
                $DB->update_record('local_entities_relations', $rel);
            }
            entities::purge_dates_cache($eqid);
            unset($keep[$eqid]);
        }

        // Insert newly chosen equipment.
        foreach ($keep as $eqid => $qty) {
            $DB->insert_record('local_entities_relations', (object)[
                'entityid' => $eqid,
                'component' => $this->component,
                'area' => $this->area,
                'instanceid' => $instanceid,
                'timecreated' => time(),
                'quantity' => $qty,
            ]);
            entities::purge_dates_cache($eqid);
        }
    }

    /**
     * Re-resolves and saves the equipment chosen in the form for the item's location.
     *
     * Equipment is offered/selected at option level. Each submitted equipment is re-verified against
     * the equipment actually available for the chosen location (get_equipment_for_location), so a
     * stale or tampered form cannot book equipment that is not offered there.
     *
     * @param stdClass $instance submitted form data
     * @param int $instanceid
     * @param int $index entity field index (location is at this index)
     * @return void
     */
    private function save_submitted_equipment(stdClass $instance, int $instanceid, int $index): void {
        if ($this->area !== 'option') {
            return; // Equipment is selected at option level.
        }

        $locationid = (int)($instance->{LOCAL_ENTITIES_FORM_ENTITYID . $index} ?? 0);
        $available = $locationid > 0 ? entities::get_equipment_for_location($locationid) : [];

        $chosen = [];
        foreach ((array)$instance as $key => $value) {
            if (strpos($key, LOCAL_ENTITIES_FORM_EQUIPMENT) !== 0) {
                continue;
            }
            $eqid = (int)substr($key, strlen(LOCAL_ENTITIES_FORM_EQUIPMENT));
            $qty = (int)$value;
            // Re-verify: only equipment actually available for the chosen location is accepted.
            if ($eqid > 0 && $qty > 0 && isset($available[$eqid])) {
                $chosen[$eqid] = $qty;
            }
        }

        $this->save_equipment_relations($instanceid, $chosen);
    }

    /**
     * Saves relation data to DB
     *
     * @param stdClass $data
     * @return void
     */
    public function save_to_db(stdClass $data) {
        global $DB;
        $DB->insert_record('local_entities_relations', $data);
        cache_helper::purge_by_event('purgecachedentities');
        entities::purge_dates_cache((int)($data->entityid ?? 0));
    }

    /**
     * Update relation DB
     *
     * @param stdClass $data
     * @return bool
     */
    public function update_db(stdClass $data): bool {
        global $DB;
        $id = $DB->update_record('local_entities_relations', $data);
        cache_helper::purge_by_event('purgecachedentities');
        entities::purge_dates_cache((int)($data->entityid ?? 0));
        return $id;
    }
    /**
     * Checks if record exists
     *
     * @param stdClass $data
     * @return bool
     */
    public function er_record_exists(stdClass &$data): bool {
        global $DB;
        // Match the single location relation of this item, never an equipment relation: equipment is
        // stored as additional relations on the same (component, area, instanceid) and is managed
        // separately (by entityid) via save_equipment_relations(). Excluding equipment here lets the
        // location/room save update its own row even when equipment relations coexist.
        $select = sprintf(
            "component = :component AND area = :area AND %s = :instanceid
             AND entityid IN (SELECT id FROM {local_entities} WHERE entitytype <> 'equipment' OR entitytype IS NULL)",
            $DB->sql_compare_text('instanceid')
        );
        if (
            $id = $DB->get_field_select('local_entities_relations', 'id', $select, [
                'component' => $this->component,
                'area' => $this->area,
                'instanceid' => $data->instanceid,
            ])
        ) {
            $data->id = $id;
            return true;
        }
        return false;
    }

    /**
     * Get an array of all the entities with exactly this name.
     * @param string $entityname
     * @return array
     */
    public function get_entities_by_name(string $entityname) {
        global $DB;

        $sql = "SELECT * FROM {local_entities}
            WHERE " . $DB->sql_like('name', ':entityname', false);
        $params = ['entityname' => $entityname];

        // We see if there are more than one entities with the same name.
        if ($entities = $DB->get_records_sql($sql, $params)) {
            return $entities;
        } else {
            return [];
        }
    }

    /**
     * Get an array of all the entities with exactly this shortname.
     * @param string $shortname
     * @return array
     */
    public function get_entities_by_shortname(string $shortname) {
        global $DB;
        // We see if there are more than one entities with the same shortname.
        if ($entities = $DB->get_records('local_entities', ['shortname' => $shortname])) {
            return $entities;
        } else {
            return [];
        }
    }

    /**
     * Return entities by id.
     *
     * @param int $entityid
     * @return bool|array
     */
    public static function get_entities_by_id(int $entityid) {
        global $DB;

        $sql = "SELECT  e.id, ea.id as addressid, e.name, e.shortname, e.description,
                        e.timecreated, e.timemodified, e.status, e.createdby,
                        e.parentid, e.sortorder, e.cfitemid, e.openinghours,
                        e.maxallocation, e.pricefactor,
                        ea.country, ea.city, ea.postcode,
                        ea.streetname, ea.streetnumber, ea.maplink, ea.mapembed,
                        (
                            SELECT pe.name
                            FROM {local_entities} pe
                            WHERE pe.id=e.parentid) as parentname

                FROM {local_entities} e
                LEFT JOIN {local_entities_address} ea
                ON e.id = ea.entityidto
                WHERE e.id = :entityid";
        $params = ['entityid' => $entityid];

        // We might have more than one record, as there might be more than one address.
        if ($entities = $DB->get_records_sql($sql, $params)) {
            return $entities;
        } else {
            return [];
        }
    }

    /**
     * Return entity name to be used by filters.
     * Might be the parentname, if the according setting is active.
     *
     * @param int $entityid
     * @return string
     */
    public static function get_name_for_filter(int $entityid) {
        global $DB;

        $sql = "SELECT e.id, e.name, e.shortname, (
                    SELECT pe.name
                    FROM {local_entities} pe
                    WHERE pe.id=e.parentid
                ) as parentname
                FROM {local_entities} e
                WHERE e.id = :entityid";
        $params = ['entityid' => $entityid];

        // We might have more than one record, as there might be more than one address.
        if ($entities = $DB->get_records_sql($sql, $params)) {
            $entity = reset($entities);
            // If the setting to use subentity names for filter is turned on...
            // ... then we always return the actual name of the entity.
            if (get_config('local_entities', 'usesubentitynamesforfilter')) {
                return $entity->name ?? '';
            }
            // Default behavior: If we have a parent, then we return the parent's name.
            return $entity->parentname ?? $entity->name ?? '';
        } else {
            return '';
        }
    }

    /**
     * Return first address of the entity.
     * If there is more than one address, we always use the first one.
     *
     * @param int $entityid
     * @return string
     */
    public static function get_first_address_as_string(int $entityid) {
        global $DB;

        $sql = "SELECT  e.id, ea.id as addressid,
                        ea.country, ea.city, ea.postcode,
                        ea.streetname, ea.streetnumber, ea.maplink, ea.mapembed,
                        (
                            SELECT pe.name
                            FROM {local_entities} pe
                            WHERE pe.id=e.parentid) as parentname

                FROM {local_entities} e
                LEFT JOIN {local_entities_address} ea
                ON e.id = ea.entityidto
                WHERE e.id = :entityid
                LIMIT 1";
        $params = ['entityid' => $entityid];

        // We might have more than one record, as there might be more than one address.
        if ($entities = $DB->get_records_sql($sql, $params)) {
            $entity = reset($entities);
            $addressstring = '';
            if (!empty($entity->streetname)) {
                $addressstring .= $entity->streetname;
            }
            if (!empty($entity->streetnumber)) {
                $addressstring .= ' ' . $entity->streetnumber;
            }
            if (!empty($entity->postcode)) {
                $addressstring .= ' ' . $entity->postcode;
            }
            if (!empty($entity->city)) {
                $addressstring .= ' ' . $entity->city;
            }
            $addressstring = trim($addressstring);
            return $addressstring;
        } else {
            return '';
        }
    }

    /**
     * Helper function to remove all entries in local_entities_relations
     * for a specific booking instance (by bookingid).
     * @param int $bookingid the id of the booking instance
     * @return bool $success - true if successful, false if not
     */
    public static function delete_entities_relations_by_bookingid(int $bookingid): bool {
        global $DB;

        if (empty($bookingid)) {
            throw new moodle_exception('Could not clear entries from local_entities_relations because of missing booking id.');
        }

        // Initialize return value.
        $success = true;

        // Todo: In the future, we'll also need to delete relations for optiondates.

        // Get all currently existing entities relations of the booking instance.
        $existingoptions = $DB->get_records('booking_options', ['bookingid' => $bookingid], '', 'id');
        if (!empty($existingoptions)) {
            foreach ($existingoptions as $existingoption) {
                if (
                    !$DB->delete_records('local_entities_relations', [
                    'component' => 'mod_booking',
                    'area' => 'option',
                    'instanceid' => $existingoption->id,
                    ])
                ) {
                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * Returns pricefactor set in DB. Can be used for automatic pricecalculation used in booking.
     *
     * @param int $id entity id
     * @return float $pricefactor
     */
    public static function get_pricefactor_by_entityid(int $id) {
        global $DB;
        $params = ['id' => $id];
        $pricefactor = $DB->get_field_select('local_entities', 'pricefactor', 'id = :id', $params, IGNORE_MISSING);
        return $pricefactor;
    }

    /**
     * Return a modal
     *
     * @return string
     */
    private static function render_modal() {
        return '<button type="button" class="btn btn-primary" data-toggle="modal" data-bs-toggle="modal"
        data-target=".bd-example-modal-lg" data-bs-target=".bd-example-modal-lg">Large modal</button>
        <div class="modal fade bd-example-modal-lg" tabindex="-1"
        role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
        <div class="modal-content">
                ...
                </div>
            </div>
            </div>';
    }

    /**
     * Returns false if items are not similar.
     * @param array $olditem
     * @param array $newitem
     * @return bool
     */
    public static function compare_items(array $olditem, array $newitem) {

        // If the ids are both empty, we don't see a need to update.
        if (empty($olditem['entityid']) && empty($newitem['entityid'])) {
            return true;
        }

        if (
            $olditem['entityid'] != $newitem['entityid']
            || $olditem['entityarea'] != $newitem['entityarea']
        ) {
                return false;
        }
        return true;
    }
}
