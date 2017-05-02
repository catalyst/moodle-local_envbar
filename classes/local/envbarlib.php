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
 * Environment bar config.
 *
 * @package   local_envbar
 * @author    Grigory Baleevskiy (grigory@catalyst-au.net)
 * @author    Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_envbar\local;

use cache;
use context_system;
use Exception;
use moodle_url;
use stdClass;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

class envbarlib {

    const ENVBAR_START = '<!-- ENVBARSTART -->';

    const ENVBAR_END = '<!-- ENVBAREND -->';

    private static $injectcalled = false;

    /**
     * Calls inject even if it was already called before.
     *
     * @return string the injected content
     */
    public static function reinject() {
        self::$injectcalled = false;
        return self::inject();
    }

    /**
     * Helper function to update data, or insert if it does not exist.
     * @param stdClass $data
     * @return boolean|number return value for the update
     */
    public static function update_envbar($data) {
        global $DB;

        $data = self::base64_encode_record($data);

        if (isset($data->id)) {
            $ret = $DB->update_record('local_envbar', $data);
        } else {
            // No id exists, lets insert it!
            $ret = $DB->insert_record('local_envbar', $data);
            $data->id = $ret;
        }

        $cache = cache::make('local_envbar', 'records');
        $cache->delete('records');

        return $ret;
    }

    /**
     * Helper function to delete data.
     * @param int $id
     * @return boolean|number return value for the delete
     */
    public static function delete_envbar($id) {
        global $DB;

        // The cache is assumed to be initialised as it is created in envbar_get_records.
        $cache = cache::make('local_envbar', 'records');
        $cache->delete('records');

        $ret = $DB->delete_records('local_envbar', array('id' => $id));
        return $ret;
    }

    /**
     * Helper function to base64 decode the matchpattern and showtext fields.
     * @param array $data
     * @return array $data
     */
    public static function base64_decode_records($data) {
        foreach ($data as $record) {
            $record->matchpattern = base64_decode($record->matchpattern);
            $record->showtext = base64_decode($record->showtext);
        }
        return $data;
    }

    /**
     * Helper function to base64 encode the matchpattern and showtext fields.
     * @param array $data
     * @return array $data
     */
    public static function base64_encode_record($data) {
        $data->matchpattern = base64_encode($data->matchpattern);
        $data->showtext = base64_encode($data->showtext);

        return $data;
    }

    /**
     * Find all configured environment sets
     *
     * @return array of env set records
     * @throws Exception
     */
    public static function get_records() {
        global $DB, $CFG;

        try {
            $cache = cache::make('local_envbar', 'records');
        } catch (Exception $e) {
            throw $e;
        }

        if (!$result = $cache->get('records')) {
            $result = $DB->get_records('local_envbar');
            // The data for the records is obfuscated using base64 to avoid the chance
            // of the data being 'cleaned' using either the core DB replace script, or
            // the local_datacleaner plugin, which would render this plugin useless.
            $result = self::base64_decode_records($result);
            $cache->set('records', $result);
        }

        // Add forced local envbar items from config.php.
        if (!empty($CFG->local_envbar_items)) {
            $items = $CFG->local_envbar_items;

            // Converting them to stdClass and adding a local flag.
            foreach ($items as $key => $value) {
                $value['local'] = true;
                $items[$key] = (object) $value;
            }

            $result = array_merge($items, $result);
        }

        return $result;
    }

    /**
     * Check if provided value matches provided pattern.
     *
     * @param string $value A value to check.
     * @param string $pattern A pattern to check matching against.
     *
     * @return bool True or false.
     */
    public static function is_match($value, $pattern) {

        if (empty($pattern)) {
            return false;
        }

        $keywords = array('\\', '/', '-', '.', '?', '*', '+', '^', '$');

        foreach ($keywords as $keyword) {
            // Escape special a keyword to treat it as a part of the string.
            $pattern = str_replace($keyword, '\\' . $keyword, $pattern);
        }

        if (preg_match('/' . $pattern . '/', $value)) {
            return true;
        }

        return false;
    }

    /**
     * Helper inject function that is used to set the prodwwwroot in the database if it exists as a $CFG variable.
     * When refreshing the database to another staging/development server, if this config.php file omits this value
     * then we have saved it to the database.
     *
     * @param string $prodwwwroot
     *
     * @return bool Returns true on update.
     */
    public static function update_wwwwroot_db($prodwwwroot) {
        global $CFG;

        // We will not update the db if the $CFG item is empty.
        if (empty($CFG->local_envbar_prodwwwroot)) {
            return false;
        }

        if (empty($prodwwwroot)) {
            // If the db config item is empty then we will update it.
            self::setprodwwwroot($CFG->local_envbar_prodwwwroot);
            return true;
        } else {
            $decoded = base64_decode($prodwwwroot);

            // If the db config item does not match the $CFG variable then we will also update it.
            if ($decoded !== $CFG->local_envbar_prodwwwroot) {
                self::setprodwwwroot($CFG->local_envbar_prodwwwroot);
                return true;
            }
        }

        return false;
    }

    /**
     * Helper inject function that is used in local_envbar_extend_navigation.
     *
     * @return string the injected content
     */
    public static function inject() {
        global $CFG, $PAGE;

        // During the initial install we don't want to break the admin gui.
        try {
            // Check if we should inject the code.
            if (!self::injection_allowed()) {
                return '';
            }

            // Remove envbars saved in $CFG->additionalhtmltopofbody.
            self::clean_envbars();

            $prodwwwroot = self::getprodwwwroot();

            // Sets the prodwwwroot in the database if it exists as a $CFG variable.
            self::update_wwwwroot_db($prodwwwroot);

            // Do not display on the production environment!
            if ($prodwwwroot === $CFG->wwwroot) {
                return;
            }

            // If the prodwwwroot is not set, only show the bar to admin users.
            if (empty($prodwwwroot)) {
                if (!has_capability('moodle/site:config', context_system::instance())) {
                    return '';
                }
            }

            $envs = self::get_records();
            $match = null;
            $here = (new moodle_url('/'))->out();

            // Which env matches?
            foreach ($envs as $env) {
                if (self::is_match($here, $env->matchpattern)) {
                    $match = $env;
                    break;
                }
            }

            // If we stil don't have a match then show a default warning.
            if (empty($match)) {
                $match = (object) array(
                    'id' => 0,
                    'showtext' => get_string('notconfigured', 'local_envbar'),
                    'colourtext' => 'white',
                    'colourbg' => 'red',
                    'matchpattern' => '',
                );

            }

            array_push($envs, (object) array(
                'id' => -1,
                'showtext' => get_string('prod', 'local_envbar'),
                'colourtext' => 'white',
                'colourbg' => 'red',
                'matchpattern' => self::getprodwwwroot(),
            ));

            $renderer = $PAGE->get_renderer('local_envbar');
            $html = $renderer->render_envbar($match, true, $envs);
            $CFG->additionalhtmltopofbody .= $html;

            return $html;

        } catch (Exception $e) {
            debugging('Exception occured while injecting our code: '.$e->getMessage(), DEBUG_DEVELOPER);
        }

        return '';
    }

    /**
     * Gets the prodwwwroot.
     * This also base64_dencodes the value to obtain it.
     *
     * @return string $prodwwwroot if it is set either in plugin config via UI or
     *         in config.php. Returns nothing if prodwwwroot is net set anywhere.
     */
    public static function getprodwwwroot() {
        global $CFG;

        $prodwwwroot = base64_decode(get_config("local_envbar", "prodwwwroot"));

        if (!empty($CFG->local_envbar_prodwwwroot)) {
            $prodwwwroot = $CFG->local_envbar_prodwwwroot;
        }

        if ($prodwwwroot) {
            return $prodwwwroot;
        }
    }

    /**
     * Sets the prodwwwroot.
     * This also base64_encodes the value to prevent datawashing from removing the values.
     *
     * @param string $prodwwwroot
     */
    public static function setprodwwwroot($prodwwwroot) {
        $root = base64_encode($prodwwwroot);
        set_config('prodwwwroot', $root, 'local_envbar');
    }

    /**
     * Checks if we should try to inject the additionalhtmltopofbody.
     * This prevents injecting multiple times if the call has been added to many hooks.
     * It also cleans up additionalhtmltopofbody if there are multiple envbars present.
     *
     * @return bool
     *
     */
    public static function injection_allowed() {
        if (self::$injectcalled) {
            return false;
        }

        // Do not inject if being called in an ajax or cli script unless it's a unit test.
        if ((CLI_SCRIPT or AJAX_SCRIPT) && !PHPUNIT_TEST) {
            return false;
        }

        self::$injectcalled = true;

        // Nothing preventing the injection.
        return true;
    }

    /**
     * Checks $CFG->additionalhtmltopofbody for saved environment bars and removes them.
     * We should only be temporarily injecting into that variable and not saving them to the database.
     *
     * @return string the cleaned content
     */
    public static function clean_envbars() {
        global $CFG;

        // Replace the content to clean up pages that do not have the injection. eg. the login page.
        $re = '/' . self::ENVBAR_START . '[\s\S]*' . self::ENVBAR_END . '/m';
        $replaced = preg_replace($re, '', $CFG->additionalhtmltopofbody);

        // We have removed the environment bars and any duplicates as it should be injected and not saved to $CFG.
        if ($CFG->additionalhtmltopofbody != $replaced) {
            set_config('additionalhtmltopofbody', $replaced);
            return $replaced;
        }

        return '';
    }

}
