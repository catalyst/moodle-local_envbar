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

namespace local_envbar\check;

use core\check\check;
use core\check\result;
use local_envbar\local\envbarlib;

/**
 * Check for refresh being overdue.
 *
 * @package local_envbar
 * @author Jason den Dulk <jasondendulk@catalyst-au.net>
 * @copyright 2025 Catalyst IT
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class refresh extends check {

    /**
     * A link to the page to handle this.
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        $url = new \moodle_url('/admin/search.php?query=nextrefresh');
        return new \action_link($url, get_string('menuenvsettings', 'local_envbar'));
    }

    /**
     * Do the check and return the results.
     *
     * @return result
     */
    public function get_result(): result {
        global $CFG;

        // Do not perform check on production site.
        if (envbarlib::getprodwwwroot() === $CFG->wwwroot) {
            return new result(result::NA, get_string('prodwwwroottext', 'local_envbar'), '');
        }

        envbarlib::check_refresh_timestamp();
        $lastrefresh = get_config('local_envbar', 'prodlastcheck');
        $nextrefresh = get_config('local_envbar', 'nextrefreshasts');

        // Notify if there is no refresh time set.
        if (empty($nextrefresh)) {
            return new result(result::OK, get_string('nextrefreshnoset', 'local_envbar'));
        }

        // Warn if the last refresh time is ahead of the expected next refresh time.
        if ($lastrefresh > $nextrefresh) {
            return new result(result::WARNING, get_string('nextrefreshbehindlast', 'local_envbar'));
        }

        $timetonextrefresh = $nextrefresh - time();

        $status = result::OK;

        // Give a warning or error if the refresh is long overdue.
        if ($timetonextrefresh < 0) {
            // Warn if 4 hours late.
            if ($timetonextrefresh < -(4 * HOURSECS)) {
                $status = result::WARNING;
            }
            // Error if 24 hours late.
            if ($timetonextrefresh < -DAYSECS) {
                $status = result::ERROR;
            }
        }

        $summary = userdate($nextrefresh, get_string('refreshedagoformat', 'local_envbar'));

        return new result($status, $summary . ' - ' . envbarlib::get_next_refresh_as_text($nextrefresh) , '');
    }
}
