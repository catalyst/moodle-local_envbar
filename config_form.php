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
 * Form for editing a configuration of the status bar
 *
 * @package   local_envbar
 * @author    Grigory Baleevskiy (grigory@catalyst-au.net)
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__).'/config.php');

/**
 * Form for editing a Enviromental bar.
 *
 * @copyright Catalyst IT
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_envbar_form extends moodleform {
    public function definition() {
        global $envbarcolorchoices;

        $mform = $this->_form;
        $counter = 1;
        foreach ($this->_customdata['sets'] as $set) {
            $mform->addElement('html', "<h4>" . get_string('set', PLUGIN_NAME_ENVBAR) . " #{$counter}</h4>");
            $mform->addElement('hidden', "id[{$set->id}]", $set->id);
            $mform->setType("id[{$set->id}]", PARAM_TEXT);

            if (!$counter != 1) {
                $mform->addElement('html', '<hr>');
            }
            $bgcolor = $mform->addElement(
                'select',
                "colorbg[{$set->id}]",
                get_string('bgcolor', PLUGIN_NAME_ENVBAR),
                $envbarcolorchoices
            );
            $bgcolor->setSelected($set->colorbg);

            $textcolor = $mform->addElement(
                'select',
                "colortext[{$set->id}]",
                get_string('text-color', PLUGIN_NAME_ENVBAR),
                $envbarcolorchoices
            );
            $textcolor->setSelected($set->colortext);

            $mform->addElement(
                'text',
                "matchpattern[{$set->id}]",
                get_string('url-match', PLUGIN_NAME_ENVBAR),
                array('placeholder' => get_string('url-match-placeholder', PLUGIN_NAME_ENVBAR))
            );
            $mform->setType("matchpattern[{$set->id}]", PARAM_URL);

            $mform->addElement(
                'text',
                "showtext[{$set->id}]",
                get_string('show-text', PLUGIN_NAME_ENVBAR),
                array('placeholder' => get_string('show-text-placeholder', PLUGIN_NAME_ENVBAR))
            );
            $mform->setType("showtext[{$set->id}]", PARAM_TEXT);

            $mform->addElement(
                'advcheckbox',
                "enabled[{$set->id}]",
                get_string('set-enabled', PLUGIN_NAME_ENVBAR),
                get_string('set-enabled-text', PLUGIN_NAME_ENVBAR),
                array(),
                array(0, 1));

            $mform->setDefault("enabled[{$set->id}]", $set->enabled ? 1 : 0);
            $mform->setDefault("showtext[{$set->id}]", $set->showtext);
            $mform->setDefault("matchpattern[{$set->id}]", $set->matchpattern);

            $counter++;
        }
        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        global $CFG, $DB, $USER;

        $errors = parent::validation($data, $files);

        return $errors;
    }

}

