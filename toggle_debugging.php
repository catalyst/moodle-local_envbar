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
 * Environment bar settings.
 *
 * @package   local_envbar
 * @author    Trisha Milan (trishamilan@catalyst-au.net)
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_envbar\local\envbarlib;

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/moodlelib.php');

/**
 * Toggle debugging switch
 * @return void
 */
function toggle_debugging() {
	global $CFG;
	$debug = $CFG->debug;
	$debug_config = $debug === 0 ? 32767 : 0;
	set_config('debug', $debug_config); 
}
/**
 * Redirect to current page
 * @return void
 */
function redirect_to_current_page() {
	$redirect = required_param('redirect', PARAM_URL);
	redirect($redirect);
}

toggle_debugging();
redirect_to_current_page();

