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
 * @author    Grigory Baleevskiy (grigory@catalyst-au.net)
 * @author    Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_envbar\local\envbarlib;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

if ($hassiteconfig) {

    $ADMIN->add('localplugins', new admin_category('envbar', get_string('pluginname', 'local_envbar')));

    $envsettings = new admin_externalpage('local_envbar_settings',
        get_string('menuenvsettings', 'local_envbar', null, true),
        new moodle_url('/local/envbar/index.php'));

    $lastrefresh = new admin_externalpage('local_envbar_lastrefresh',
        get_string('menulastrefresh', 'local_envbar', null, true),
        new moodle_url('/local/envbar/last_refresh.php'));

    $presentation = new admin_settingpage('local_envbar_presentation',
            get_string('menupresentation', 'local_envbar', null, true));

    $presentation->add(new admin_setting_heading('local_envbar/envbarheading',
            get_string('envbarheading', 'local_envbar', null, true),
            ''));

    $presentation->add(new admin_setting_configtextarea('local_envbar/extracss',
            get_string('extracss', 'local_envbar', null, true),
            get_string('extracss_desc', 'local_envbar', null, true),
            envbarlib::get_default_extra_css(),
            PARAM_RAW,
            50,
            10));

    $presentation->add(new admin_setting_configtext('local_envbar/stringseparator',
            get_string('stringseparator', 'local_envbar', null, true),
            get_string('stringseparator_desc', 'local_envbar', null, true),
            '-',
            PARAM_CLEANHTML));

    $presentation->add(new admin_setting_configcheckbox('local_envbar/showconfiglink',
            get_string('showconfiglink', 'local_envbar', null, true),
            get_string('showconfiglink_desc', 'local_envbar', null, true),
            true));

    $presentation->add(new admin_setting_configcheckbox('local_envbar/showdebugging',
            get_string('showdebugging', 'local_envbar', null, true),
            get_string('showdebugging_desc', 'local_envbar', null, true),
            true));

    $presentation->add(new admin_setting_heading('local_envbar/menuheading',
            get_string('menuheading', 'local_envbar', null, true),
            ''));

    $presentation->add(new admin_setting_configcheckbox('local_envbar/enablemenu',
            get_string('enablemenu', 'local_envbar', null, true),
            get_string('enablemenu_desc', 'local_envbar', null, true),
            true));

    $presentation->add(new admin_setting_configtext('local_envbar/menuselector',
            get_string('menuselector', 'local_envbar', null, true),
            get_string('menuselector_desc', 'local_envbar', null, true),
            '.usermenu .menu',
            PARAM_RAW));

    $presentation->add(new admin_setting_configtext('local_envbar/dividerselector',
            get_string('dividerselector', 'local_envbar', null, true),
            get_string('dividerselector_desc', 'local_envbar', null, true),
            'filler',
            PARAM_RAW));

    $presentation->add(new admin_setting_heading('local_envbar/linksheading',
            get_string('linksheading', 'local_envbar', null, true),
            ''));

    $presentation->add(new admin_setting_configcheckbox('local_envbar/highlightlinks',
            get_string('highlightlinks', 'local_envbar', null, true),
            get_string('highlightlinks_desc', 'local_envbar', null, true),
            true));

    $presentation->add(new admin_setting_configcheckbox('local_envbar/highlightlinksenvbar',
            get_string('highlightlinksenvbar', 'local_envbar', null, true),
            get_string('highlightlinksenvbar_desc', 'local_envbar', null, true),
            true));

    $presentation->add(new admin_setting_heading('local_envbar/faviconheading',
            get_string('faviconheading', 'local_envbar', null, true),
            ''));

    $presentation->add(new admin_setting_configcheckbox('local_envbar/enablefaviconcolorize',
            get_string('enablefaviconcolorize', 'local_envbar', null, true),
            get_string('enablefaviconcolorize_desc', 'local_envbar', null, true),
            true));

    $presentation->add(new admin_setting_heading('local_envbar/titleheading',
            get_string('titleheading', 'local_envbar', null, true),
            ''));

    $presentation->add(new admin_setting_configcheckbox('local_envbar/enabletitleprefix',
            get_string('enabletitleprefix', 'local_envbar', null, true),
            get_string('enabletitleprefix_desc', 'local_envbar', null, true),
            true));

    $ADMIN->add('envbar', $envsettings);
    $ADMIN->add('envbar', $lastrefresh);
    $ADMIN->add('envbar', $presentation);

    $settings = null;
}
