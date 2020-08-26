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
 * Privacy provider.
 *
 * @package   local_envbar
 * @author    Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_envbar\form;

use local_envbar\local\envbarlib;
use moodleform;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

/**
 * Form for manually editing the last refresh time and pining prod.
 *
 * @copyright Catalyst IT
 * @author    Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lastrefresh extends moodleform {

    /**
     * {@inheritDoc}
     * @see moodleform::definition()
     */
    public function definition() {
        global $CFG, $PAGE;

        $mform = $this->_form;
        $prodlastcheck = $this->_customdata["prodlastcheck"];

        $mform->addElement(
            "date_time_selector",
            "lastrefresh",
            get_string("lastrefresh", "local_envbar")
        );
        $mform->setDefault("lastrefresh", $prodlastcheck);

        $mform->addElement(
            "checkbox",
            "pingprod",
            get_string("pingprod", "local_envbar")
        );
        $mform->addHelpButton('pingprod', 'pingprod', 'local_envbar');

        $mform->addElement(
            "checkbox",
            "verbose",
            get_string("pingprodverbose", "local_envbar")
        );
        $mform->addHelpButton('verbose', 'pingprodverbose', 'local_envbar');

        $this->add_action_buttons();
    }
}

