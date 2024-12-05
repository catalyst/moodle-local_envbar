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

namespace local_envbar;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/envbar/lib.php');

use context_system;
use core\hook\output\before_standard_top_of_body_html_generation;
use core_user\hook\extend_user_menu;
use local_envbar\local\envbarlib;

/**
 * Hook callbacks for local_envbar.
 *
 * @package   local_envbar
 * @author    Benjamin Walker (benjaminwalker@catalyst-au.net)
 * @copyright 2024 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * This is the hook enables the plugin to insert a chunk of html at the start of the html document.
     *
     * @param before_standard_top_of_body_html_generation $hook
     */
    public static function before_standard_top_of_body_html_generation(before_standard_top_of_body_html_generation $hook): void {
        // Get code to inject.
        $hook->add_html(envbarlib::get_inject_code());
    }

    /**
     * This is the hook enables the plugin to add one or more menu item.
     *
     * @param extend_user_menu $hook
     */
    public static function extend_user_menu(extend_user_menu $hook): void {
        global $CFG;
        $prodwwwroot = envbarlib::getprodwwwroot();

        // Do not display on the production environment!
        if ($prodwwwroot === $CFG->wwwroot) {
            return;
        }

        // If the prodwwwroot is not set, only show the bar to admin users.
        if (empty($prodwwwroot)) {
            if (!has_capability('moodle/site:config', context_system::instance())) {
                return;
            }
        }

        // Get items to add.
        $navitems = envbarlib::add_menuuser();
        foreach ($navitems as $item) {
            $hook->add_navitem($item);
        }
    }

    /**
     * Listener for the after_config hook.
     *
     * @param \core\hook\after_config $hook
     */
    public static function after_config(\core\hook\after_config $hook): void {
        global $CFG;

        if (during_initial_install() || isset($CFG->upgraderunning)) {
            // Do nothing during installation or upgrade.
            return;
        }

        local_envbar_after_config();
    }
}
