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

admin_externalpage_setup('local_envbar_settings');

$records = envbarlib::get_records();
$gensecretkey = random_string(25);
$form = new \local_envbar\form\config(null, ['records' => $records, 'gensecretkey' => $gensecretkey]);

if ($data = $form->get_data()) {

    envbarlib::setprodwwwroot($data->prodwwwroot);
    if (!empty($CFG->allowmultipledomains)) {
        envbarlib::setprodsecondaryurls($data->secondaryurls);
    }
    set_config('prodbgcolour', $data->prodbgcolour, 'local_envbar');
    set_config('prodtextcolour', $data->prodtextcolour, 'local_envbar');
    if (!envbarlib::is_secret_key_overridden()) {
        set_config('secretkey', $data->secretkey, 'local_envbar');
    }

    if (!empty($data->id)) {

        $keys = array_keys($data->id);

        foreach ($keys as $key => $value) {

            // Do not update the database with manual set config.php items.
            if (!empty($data->locked[$value])) {
                continue;
            }

            if ($data->delete[$value] == 1) {
                envbarlib::delete_envbar($value);
            } else {
                $item = new stdClass();
                $item->id = $data->id[$value];
                $item->colourbg = $data->colourbg[$value];
                $item->colourtext = $data->colourtext[$value];
                $item->matchpattern = $data->matchpattern[$value];
                $item->matchpattern = rtrim($item->matchpattern, '/');
                $item->showtext = $data->showtext[$value];
                $item->refreshschedule = $data->refreshschedule[$value];
                // Update an item as the id has been set.
                envbarlib::update_envbar($item);
            }
        }
    }

    if (!empty($data->repeatid)) {
        $repeats = array_keys($data->repeatid);

        foreach ($repeats as $key => $value) {
            $item = new stdClass();
            // ID, $item->id not set.
            $item->colourbg = $data->repeatcolourbg[$value];
            $item->colourtext = $data->repeatcolourtext[$value];
            $item->matchpattern = $data->repeatmatchpattern[$value];
            $item->showtext = $data->repeatshowtext[$value];
            $item->refreshschedule = $data->repeatrefreshschedule[$value];

            envbarlib::update_envbar($item);
        }
    }

    redirect(new moodle_url('/local/envbar/index.php'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('header_envbar', 'local_envbar'));
echo get_string('help', 'local_envbar');

$config = get_config('local_envbar');
if (isset($config->prodlastcheck)) {
    $show = format_time(time() - $config->prodlastcheck);
    $num = strtok($show, ' ');
    $unit = strtok(' ');
    $show = "$num $unit";
    echo get_string('prodlasttimestamp', 'local_envbar', $show);
}

echo $form->display();
echo $OUTPUT->footer();

