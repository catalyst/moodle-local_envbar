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
 * Environment bar setup page.
 *
 * @package   local_envbar
 * @author    Grigory Baleevskiy (grigory@catalyst-au.net)
 * @author    Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_envbar\local\envbarlib;

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

global $DB;

admin_externalpage_setup('local_envbar_lastrefresh');

$form = new \local_envbar\form\lastrefresh(null, array('prodlastcheck' => get_config('local_envbar', 'prodlastcheck')));

if ($data = $form->get_data()) {
    envbarlib::updatelastcheck($data->lastrefresh);

    // Check if we want to ping prod.
    $verbose = isset($data->verbose);
    if (isset($data->pingprod)) {
        ob_start();
        envbarlib::pingprod(true, $verbose);
        $debug = ob_get_contents();
        ob_clean();
    } else {
        redirect(new moodle_url('/local/envbar/last_refresh.php'), get_string('changessaved'));
    }

}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('header_envbar', 'local_envbar'));
echo $form->display();
if (isset($debug)) {
    echo "<pre>{$debug}</pre>";
}
echo $OUTPUT->footer();

