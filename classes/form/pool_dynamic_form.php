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

namespace local_entities\form;

use context;
use context_system;
use core_form\dynamic_form;
use local_entities\persons;
use local_entities\pool\pool_access;
use local_entities\pool\pool_repository;
use local_entities\pool\pool_service;
use local_entities\pool\pool_types;
use moodle_url;

/**
 * The dialog to create or change a pool: name, description and members.
 *
 * The type of the pool decides which entities can be chosen as members. A new pool gets its type from
 * the button that opened the dialog, an existing pool keeps its type. The dialog only asks and hands
 * the answer to {@see pool_service}. Who may open it is decided by {@see pool_access}.
 *
 * @package    local_entities
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pool_dynamic_form extends dynamic_form {
    /**
     * Defines the fields. The buttons are added by the dialog.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;
        $pooltype = $this->pool_type();

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'pooltype');
        $mform->setType('pooltype', PARAM_ALPHANUMEXT);

        $mform->addElement('static', 'pooltypename', get_string('pooltype', 'local_entities'), pool_types::all()[$pooltype]);

        $mform->addElement('text', 'name', get_string('pool_name', 'local_entities'), ['size' => 40, 'maxlength' => 255]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement(
            'textarea',
            'description',
            get_string('pool_description', 'local_entities'),
            ['rows' => 3, 'cols' => 40]
        );
        $mform->setType('description', PARAM_TEXT);

        $mform->addElement(
            'autocomplete',
            'members',
            get_string('pool_members', 'local_entities'),
            pool_types::member_options($pooltype),
            [
                'multiple' => true,
                'noselectionstring' => get_string('pool_members_placeholder', 'local_entities'),
            ]
        );
        $mform->setType('members', PARAM_INT);
    }

    /**
     * Only users who may manage pools may open or send the form.
     *
     * @return void
     * @throws \required_capability_exception
     */
    protected function check_access_for_dynamic_submission(): void {
        pool_access::require_manage();
    }

    /**
     * Fills the form: empty for a new pool, otherwise with the data of the pool.
     *
     * @return void
     * @throws \moodle_exception if the pool does not exist
     */
    public function set_data_for_dynamic_submission(): void {
        $repository = new pool_repository();
        $id = $this->optional_param('id', 0, PARAM_INT);
        $data = ['id' => 0, 'pooltype' => $this->pool_type(), 'name' => '', 'description' => '', 'members' => []];

        if ($id > 0) {
            $pool = $repository->find($id);
            if ($pool === null) {
                throw new \moodle_exception('error:poolnotfound', 'local_entities');
            }
            $data = [
                'id' => $id,
                'pooltype' => (string)$pool->pooltype,
                'name' => $pool->name,
                'description' => (string)$pool->description,
                'members' => $repository->get_member_entityids($id),
            ];
        }

        $this->set_data($data);
    }

    /**
     * Saves the pool.
     *
     * @return array the id of the pool
     */
    public function process_dynamic_submission() {
        global $USER;

        $data = $this->get_data();
        $id = (new pool_service())->save_pool(
            (int)$data->id,
            (string)$data->name,
            (string)$data->description,
            array_map('intval', (array)($data->members ?? [])),
            (int)$USER->id,
            (string)$data->pooltype
        );
        return ['id' => $id];
    }

    /**
     * Checks the name: it must be filled, short enough and not used by another pool.
     *
     * @param array $data
     * @param array $files
     * @return array errors by field name
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $name = trim((string)($data['name'] ?? ''));
        if ($name === '' || \core_text::strlen($name) > 255) {
            $errors['name'] = get_string('error:poolnameinvalid', 'local_entities');
        } else if ((new pool_repository())->is_name_taken($name, (int)($data['id'] ?? 0))) {
            $errors['name'] = get_string('error:poolnameexists', 'local_entities');
        }

        return $errors;
    }

    /**
     * The context of the form: the whole site.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return context_system::instance();
    }

    /**
     * The page the form belongs to.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/local/entities/pools.php');
    }

    /**
     * The type of the pool the dialog is about: the type of an existing pool, otherwise the type the
     * button asked for. A type that is not enabled falls back to person.
     *
     * @return string
     */
    private function pool_type(): string {
        $id = $this->optional_param('id', 0, PARAM_INT);
        if ($id > 0) {
            $pool = (new pool_repository())->find($id);
            if ($pool !== null) {
                return (string)$pool->pooltype;
            }
        }

        $type = $this->optional_param('pooltype', persons::TYPE, PARAM_ALPHANUMEXT);
        return pool_types::exists($type) ? $type : persons::TYPE;
    }
}
