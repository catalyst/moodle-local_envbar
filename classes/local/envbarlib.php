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
use moodle_exception;
use stdClass;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

/**
 * Environment bar config.
 *
 * @package   local_envbar
 * @author    Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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
     * Provides the default CSS code to be used in settings or when not configured.
     */
    public static function get_default_extra_css() {
        return <<<CSS
/* Move navbar down by 50px. */
.local_envbar .navbar.navbar-fixed-top,
.local_envbar .navbar.navbar-static-top,
.local_envbar .navbar.fixed-top {
    top: 50px;
}

/* Move nav drawer down by another 50px. */
.local_envbar #nav-drawer {
    top: 100px;
}

/* Shrink nav drawer by another 50px. */
.local_envbar #nav-drawer {
    height: calc(100% - 100px);
}

/* Move message drawer down by another 50px. */
.local_envbar .message-drawer {
    top: 100px;
}

/* Shrink message drawer by another 50px. */
.local_envbar .message-drawer {
    height: calc(100% - 100px);
}

/* Move modal dialogues down by 50px. */
.local_envbar .modal-dialog {
    top: 50px;
}

/* Revert the last rule for user tour modal dialogs which are placed correctly even with the envbar. */
.local_envbar span[data-flexitour="container"] .modal-dialog {
    top: inherit;
}
CSS;
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

            $config = get_config('local_envbar');
            if (isset($config->prodlastcheck)) {
                foreach ($result as $key => $value) {
                    // If this is the env we are on we trust $config->prodlastcheck.
                    // Else we can trust lastrefresh.
                    if (self::is_match($CFG->wwwroot, $value->matchpattern)) {
                        $value->lastrefresh = $config->prodlastcheck;
                    }
                    $result[$key] = $value;
                }
            }

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
                    'lastrefresh' => get_config('local_envbar', 'prodlastcheck'),
                );

            }

            array_push($envs, (object) array(
                'id' => -1,
                'showtext' => get_string('prod', 'local_envbar'),
                'colourtext' => get_config('local_envbar', 'prodtextcolour'),
                'colourbg' => get_config('local_envbar', 'prodbgcolour'),
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

    /**
     * Sets prodlastcheck with the current or a passed time.
     *
     * @param int epoch to set prodlastcheck, current time by default
     */
    public static function updatelastcheck($time = null) {
        // Update the prodlastcheck and clear the cache to make it effective.
        $time = is_null($time) ? time() : $time;
        set_config('prodlastcheck', $time, 'local_envbar');
        $cache = cache::make('local_envbar', 'records');
        $cache->delete('records');
    }

    /**
     * Sends a post to prod to update the lastrefresh time of this environment.
     *
     * @param bool $force if true do not check prodlastping
     * @param bool $debug print curl debug if true
     */
    public static function pingprod($force = false, $debug = false) {
        global $CFG;

        $config = get_config('local_envbar');
        $prodwwwroot = self::getprodwwwroot();

        // Skip if prodwwwroot hasn't been set.
        if (empty($prodwwwroot)) {
            return;
        }

        // Skip if we've already pinged prod after the last refresh unless force is true.
        $lastrefresh = isset($config->prodlastcheck) ? $config->prodlastcheck : 0;
        $lastping = isset($config->prodlastping) ? $config->prodlastping : 0;
        if ($lastrefresh < $lastping && !$force) {
            return;
        }

        // Ping prod with the env and lastrefresh.
        $url = $prodwwwroot."/local/envbar/service/updatelastrefresh.php";
        $params = "wwwroot=".urlencode($CFG->wwwroot)."&lastrefresh=".urlencode($lastrefresh)."&secretkey=".urlencode(self::get_secret_key());
        $options = array();
        if ($debug) {
            $options['debug'] = true;
        }

        require_once($CFG->dirroot . "/lib/filelib.php");
        $curl = new \curl($options);

        try {
            $response = $curl->post($url, $params);
        } catch (Exception $e) {
            mtrace("Error contacting production, error returned was: ".$e->getMessage());
        }

        $response = json_decode($response);

        if ($response->result === 'success') {
            mtrace($response->message);
        } else {
            mtrace("Error contacting production, the lastrefresh was not updated");
        }

        // We update the lastping even if it did not work. This is only for
        // information purposes, we don't want to spam the network constantly.
        set_config('prodlastping', time(), 'local_envbar');
    }

    /**
     * Check if a secret key is overridden in config.php.
     * @return bool
     */
    public static function is_secret_key_overridden() {
        global $CFG;

        return !empty($CFG->local_envbar_secretkey) && is_string($CFG->local_envbar_secretkey);
    }

    /**
     * Returns secret key.
     *
     * @return mixed
     * @throws \dml_exception
     */
    public static function get_secret_key() {
        global $CFG;

        if (self::is_secret_key_overridden()) {
            return $CFG->local_envbar_secretkey;
        } else {
            return get_config('local_envbar', 'secretkey');
        }
    }

    /**
     * Check if the debug value is a number.
     *
     * @param  mixed $debug Debug level
     * @return boolean
     */
    protected static function is_valid_debug_value($debug) {
        return is_number($debug) && !is_object($debug) && !is_array($debug);
    }

    /**
     * Returns the toggled value of the debug config.
     *
     * @param  mixed $debug Debug level
     * @return string $debugconfig Debug level
     */
    public static function get_toggle_debug_config($debug) {
        if (self::is_valid_debug_value($debug) && $debug == DEBUG_NORMAL) {
            $debugconfig = DEBUG_DEVELOPER;
        } else {
            // Set to DEBUG_NORMAL in case there's an unknown debug level.
            $debugconfig = DEBUG_NORMAL;
        }
        return $debugconfig;
    }

    /**
     * Returns the value of the debug display.
     *
     * @param  mixed $debug Debug level
     * @return int $debugdisplay Debug display
     */
    public static function get_debug_display_config($debug) {
        if (self::is_valid_debug_value($debug) && $debug == DEBUG_DEVELOPER) {
            // Output debug messages to the browser.
            $debugdisplay = 1;
        } else {
            // Debug messages will not show on the browser.
            $debugdisplay = 0;
        }
        return $debugdisplay;
    }

    /**
     * Returns the debugging status string to be displayed.
     *
     * @return string
     */
    public static function get_debugging_status_string() {
        global $CFG;

        if (self::is_valid_debug_value($CFG->debug) && $CFG->debug == DEBUG_DEVELOPER) {
            $debuggingstr = get_string('debuggingon', 'local_envbar');
        } else {
            $debuggingstr = get_string('debuggingoff', 'local_envbar');
        }
        return $debuggingstr;
    }

    /**
     * Sets the debugconfig and debug display.
     *
     * @param mixed $debug Debug level
     */
    public static function set_debug_config($debug) {
        // Toggles the debug config and debug display.
        $debugconfig = self::get_toggle_debug_config($debug);
        $debugdisplay = self::get_debug_display_config($debugconfig);

        set_config('debug', $debugconfig);
        set_config('debugdisplay', $debugdisplay);
    }

    /**
     * Returns the debug toggle string to be displayed.
     *
     * @return string
     */
    public static function get_debug_toggle_string() {
        global $CFG;

        if (self::is_valid_debug_value($CFG->debug) && $CFG->debug == DEBUG_DEVELOPER) {
            $debugtogglestr = get_string('debugtogglelinkoff', 'local_envbar');
        } else {
            $debugtogglestr = get_string('debugtogglelinkon', 'local_envbar');
        }
        return $debugtogglestr;
    }

    /**
     * This function overrides settings inside of CFG
     *
     * @return void
     */
    public static function config() {
        global $CFG, $FULLME;
        $prodwwwroot = self::getprodwwwroot();

        // Do not modify config on the production environment!
        if ($prodwwwroot === $CFG->wwwroot) {
            return;
        }

        // If on admin pages, we do not want to do anything, as we need to avoid recursively adding config through GUI.
        // Too early to use $PAGE here.
        $cleanurl = new \moodle_url($FULLME);
        if (strpos($cleanurl->out(), $CFG->wwwroot . '/admin/settings.php') !== false ||
            strpos($cleanurl->out(), $CFG->wwwroot . '/admin/category.php') !== false ||
            strpos($cleanurl->out(), $CFG->wwwroot . '/admin/search.php') !== false) {
            return;
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

        // If we stil don't have a match then use a default environment.
        if (empty($match)) {
            $match = (object) array(
                'id' => 0,
                'showtext' => get_string('notconfigured', 'local_envbar'),
                'colourtext' => 'white',
                'colourbg' => 'red',
                'matchpattern' => '',
                'lastrefresh' => get_config('local_envbar', 'prodlastcheck'),
            );
        }

        // Email subject prefix.
        if (get_config('local_envbar', 'enableemailprefix')) {
            // Only do something if this config exists.
            if (isset($CFG->emailsubjectprefix)) {
                $origprefix = $CFG->emailsubjectprefix;
                $CFG->emailsubjectprefix = '[' . substr($match->showtext, 0, 4) . '] ' . $origprefix;
            }
        }
    }

}
