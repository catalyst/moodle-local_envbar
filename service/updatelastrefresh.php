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
 * This page is pinged to update an env lastrefresh time.
 *
 * This simple rest point was created outside of moodle's ws to avoid
 * the overhead and config that comes with it.
 *
 * @package   local_envbar
 * @author    Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_envbar\local\envbarlib;

require_once(dirname(__FILE__) . '/../../../config.php');

$wwwroot = required_param('wwwroot', PARAM_RAW);
$lastrefresh = required_param('lastrefresh', PARAM_INT);
$secretkey = required_param('secretkey', PARAM_TEXT);
$config = get_config('local_envbar');

$response = array();

if ($secretkey !== envbarlib::get_secret_key()) {
    $response['result'] = 'secretkey_invalid';
    $response['message'] = get_string('secretkey_invalid', 'local_envbar');
    echo json_encode($response);
    die;
}

// Check if any environments match the wwwroot passed.
$records = envbarlib::get_records();
foreach ($records as $env) {
    if (envbarlib::is_match($wwwroot, $env->matchpattern)) {
        $data = $env;
        break;
    }
}

if (isset($data)) {
    $data->lastrefresh = $lastrefresh;
} else {
    // This environment doesn't exist in prod, so create a default entry for it.
    $data = new stdClass();
    // ID, $data->id not set.
    $data->matchpattern = $wwwroot;
    $data->lastrefresh = $lastrefresh;
    $data->colourtext = 'white';
    $data->colourbg = 'red';

    // We have to do some matching between prod and this new environment
    // to get a difference to use as the showtext.
    // Remove http and https in case both environments are different.
    $pattern = array('/https:\/\//', '/http:\/\//');
    $replacement = array('', '');

    $here = preg_replace($pattern, $replacement, $CFG->wwwroot);
    $there = preg_replace($pattern, $replacement, $wwwroot);
    $herearray = str_split($here);
    $therearray = str_split($there);

    // Remove same letters from the end and then repeat for the front
    // e.g. catalyst and catalyst-dev will become just dev.
    for ($i = 0; $i < 2; $i++) {
        $herearray = array_reverse($herearray);
        $therearray = array_reverse($therearray);

        foreach ($therearray as $key => $letter) {
            if ($letter === $herearray[$key]) {
                unset($therearray[$key]);
            } else {
                break;
            }
        }
    }

    $data->showtext = trim(implode("", $therearray), '_-/');
}

envbarlib::update_envbar($data);
$response['result'] = 'success';
$response['message'] = get_string('lastrefresh_success', 'local_envbar');

echo json_encode($response);
