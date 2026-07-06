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

namespace local_envbar\event;

use core\event\base;
use moodle_url;

/**
 * Event triggered when an envbar environment record is created or updated.
 *
 * @package   local_envbar
 * @author    2026 Waleed ul hassan (waleed.hassan@catalyst-eu.net)
 * @copyright Catalyst IT EU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class envbar_updated extends base {
    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['objecttable'] = 'local_envbar';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventenvbarupdated', 'local_envbar');
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' created or updated the envbar record with id '$this->objectid'.";
    }

    /**
     * Returns relevant URL.
     *
     * @return moodle_url
     */
    public function get_url() {
        return new moodle_url('/local/envbar/index.php');
    }
}
