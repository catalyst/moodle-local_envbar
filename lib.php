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
 * Environment bar library.
 *
 * @package   local_envbar
 * @author    Grigory Baleevskiy (grigory@catalyst-au.net)
 * @author    Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_envbar\local\envbarlib;

/**
 * Add cron related service status checks
 *
 * @return array of check objects
 */
function local_envbar_security_checks(): array {
    return [
        new \local_envbar\check\envage(),
        new \local_envbar\check\refresh(),
    ];
}

/**
 * This is the hook enables the plugin to insert a chunk of html at the start of the html document.
 *
 * This is a legacy callback that is used for compatibility with older Moodle versions.
 * Moodle 4.4+ will use local_envbar\hook_callbacks::before_standard_top_of_body_html_generation instead.
 */
function local_envbar_before_standard_top_of_body_html() {
    return envbarlib::get_inject_code();
}

/**
 * We need to override some settings very early in the load process.
 * This is a legacy callback that is used for compatibility with older Moodle versions.
 */
function local_envbar_after_config() {
    // Hack to avoid breaking messaging tests, as this setting defaults on.
    if (!PHPUNIT_TEST && !WS_SERVER) {
        try {
            envbarlib::config();
        } catch (Exception $e) {        // @codingStandardsIgnoreStart
            // Catch exceptions from stuff not existing during installation process, fail silently.
        }                               // @codingStandardsIgnoreEnd
    }
}
