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
 * Check env refresh age
 *
 * @package     local_envbar
 * @author      2021 Brendan Heywood (brendan@catalyst-au.net)
 * @copyright   Catalyst IT Pty Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_envbar\check;

use core\check\check;
use core\check\result;
use local_envbar\local\envbarlib;

/**
 * Check env refresh age
 *
 * @package     local_envbar
 * @author      2021 Brendan Heywood (brendan@catalyst-au.net)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class envage extends check {

    /**
     * Constructor
     */
    public function __construct() {
        global $CFG;
        $this->id = 'envage';
        $this->name = get_string('checkenvage', 'local_envbar');
    }

    /**
     * A link to a place to action this
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \moodle_url('/local/envbar/index.php'),
            get_string('menuenvsettings', 'local_envbar'));
    }

    /**
     * Return result
     * @return result
     */
    public function get_result(): result {
        global $CFG;

        if (envbarlib::getprodwwwroot() === $CFG->wwwroot) {
            return new result(result::NA, get_string('prodwwwroottext', 'local_envbar'), '');
        }

        $lastrefresh = get_config('local_envbar', 'prodlastcheck');

        // This takes the lastrefresh timestamp and displays it similar to how it appears in
        // the headers such as 'Friday, 27 August 2021 - 144 days old.'.
        if ($lastrefresh && $lastrefresh > 0) {
            $format = get_string('strftimedatemonthabbr', 'langconfig');
            $summary = userdate($lastrefresh, get_string('strftimedaydate', 'langconfig'));

            $show = format_time(time() - $lastrefresh);
            $num = strtok($show, ' ');
            $unit = strtok(' ');
            $show = "$num $unit";
            $summary .= ' - ' . get_string('refreshedago', 'local_envbar', $show);
        } else {
            $summary = get_string('refreshednever', 'local_envbar');
        }

        return new result(result::INFO, $summary, '');
    }
}

