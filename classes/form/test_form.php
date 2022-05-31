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
 * Moodec entities dynamic Form
 *
 * @package     local_entities
 * @author      Thomas Winkler
 * @copyright   2021 Wunderbyte
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_entities\form;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
use stdClass;
use local_entities\entitiesrelation_handler;
use moodleform;
/**
 * Class entities_form
 *
 * @copyright   2021 Wunderbyte
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_form extends moodleform {


    public $entity;


    /**
     * @var $callingentity
     */
    public $callingentity;

    /**
     * entities_edit_product_form constructor.
     * @param mixed $entity
     */
    public function __construct($entity = null) {
        if ($entity) {
            $this->entity = $entity;
            $this->callingentity = $entity->id;
        } else {
            $this->entity = new stdClass();
            $this->entity->id = 0;
        }
        parent::__construct();
    }


    /**
     *
     * Set the entity data.
     *
     * @param mixed $defaults
     * @return mixed
     */
    public function set_data_for_dynamic_submission() {
        global $DB;
        if ($id = $this->optional_param('id', 0, PARAM_INT)) {
            $entity = $DB->get_record('local_entities', ['id' => $id]);
            $handler = \local_entities\customfield\entities_handler::create();
            $handler->instance_form_before_set_data($this->entity);
            $this->set_data($this->entity);
        }
    }



    /**
     * Get a list of all entities
     */
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('text', 'name', get_string('entity_name', 'local_entities'));
        $mform->setType('name', PARAM_TEXT);
        $foreignid = 0;
        $enitieshandler = new entitiesrelation_handler(0, $foreignid, 'mod_doof');
        $enitieshandler->instance_form_definition($mform);

        $this->add_action_buttons(false, get_string('load_child', 'mod_booking'));
        $this->add_action_buttons(false, get_string('save_entity', 'mod_booking'));
    }

    /**
     *
     * Validate the form
     *
     * @param mixed $data
     * @param mixed $files
     * @return mixed
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

    /**
     *
     * Set the page data.
     *
     * @param mixed $defaults
     * @return mixed
     */
    public function set_data($defaults) {
        $context = \context_system::instance();
        $draftideditor = file_get_submitted_draft_itemid('description');
        $defaults->description['text'] = file_prepare_draft_area($draftideditor, $context->id,
            'local_entities', 'description', 0, array('subdirs' => true), $defaults->description['text']);
        $defaults->description['itemid'] = $draftideditor;
        $defaults->description['format'] = FORMAT_HTML;

        $options = array('maxbytes' => 204800, 'maxfiles' => 1, 'accepted_types' => ['jpg, png']);
        $defaults->picture = file_prepare_standard_filemanager(
            $defaults,
            'image',
            $options,
            $context,
            'local_entities',
            'image',
            $defaults->id);

        return parent::set_data($defaults);
    }
}
