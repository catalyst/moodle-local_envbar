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

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/../../lib/behat/lib.php');

/**
 * This is the hook enables the plugin to insert a chunk of html at the start of the html document.
 */
function local_envbar_before_standard_top_of_body_html() {
    // Hack to avoid breaking behat tests.
    if (!behat_is_test_site()) {
        return envbarlib::get_inject_code();
    }
}

/**
 * We need to override some settings very early in the load process.
 */
function local_envbar_after_config() {
    // Hack to avoid breaking messaging tests, as this setting defaults on.
    if (!PHPUNIT_TEST) {
        try {
            envbarlib::config();
        } catch (Exception $e) {        // @codingStandardsIgnoreStart
            // Catch exceptions from stuff not existing during installation process, fail silently.
        }                               // @codingStandardsIgnoreEnd
    }
}
