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
use core\output\pix_icon;
use core\url;
use core_user\output\user_action_menu\divider;
use core_user\output\user_action_menu\header as user_menu_header;
use core_user\output\user_action_menu\link as user_menu_link;
use Exception;
use moodle_url;
use moodle_exception;
use stdClass;

/**
 * Environment bar config.
 *
 * @package   local_envbar
 * @author    Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class envbarlib {
    /**
     * Constant string variable - <!-- ENVBARSTART -->
     *
     * @var string
     */
    const ENVBAR_START = '<!-- ENVBARSTART -->';

    /**
     * Constant string variable - <!-- ENVBAREND -->
     *
     * @var string
     */
    const ENVBAR_END = '<!-- ENVBAREND -->';

    /**
     * Boolean to check that hold status if inject has been called
     *
     * @var bool
     */
    private static $injectcalled = false;

    /**
     * Resets inject called even if it was already called before.
     *
     * @return string the injected content
     */
    public static function reset_injectcalled() {
        self::$injectcalled = false;
        return self::get_inject_code();
    }

    /**
     * Provides the default CSS code to be used in settings or when not configured.
     *
     * @return string Default extra CSS.
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

        $event = \local_envbar\event\envbar_updated::create([
            'context' => context_system::instance(),
            'objectid' => $data->id,
        ]);
        $event->trigger();

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

        $ret = $DB->delete_records('local_envbar', ['id' => $id]);

        $event = \local_envbar\event\envbar_deleted::create([
            'context' => context_system::instance(),
            'objectid' => $id,
        ]);
        $event->trigger();

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
                $record = (object) $value;
                $record->id = $key . 'LOCAL';
                $record->local = true;
                $items[$key] = $record;
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

        $keywords = ['\\', '/', '-', '.', '?', '*', '+', '^', '$'];

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
     * Function that returns the value for all hooks defined in lib.php
     *
     * @return string the additional top of body html
     */
    public static function get_inject_code() {
        global $PAGE;

        // During the initial install we don't want to break the admin gui.
        try {
            // Check if we should inject the code.
            if (!self::injection_allowed()) {
                return '';
            }

            $prodwwwroot = self::getprodwwwroot();

            // Sets the prodwwwroot in the database if it exists as a $CFG variable.
            self::update_wwwwroot_db($prodwwwroot);

            // Do not display on the production environment!
            if (self::is_prod_env($prodwwwroot)) {
                return '';
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
                $match = (object) [
                    'id' => 0,
                    'showtext' => get_string('notconfigured', 'local_envbar'),
                    'colourtext' => 'white',
                    'colourbg' => 'red',
                    'matchpattern' => '',
                    'lastrefresh' => get_config('local_envbar', 'prodlastcheck'),
                    'refreshschedule' => '',
                ];
            }

            array_push($envs, (object) [
                'id' => -1,
                'showtext' => get_string('prod', 'local_envbar'),
                'colourtext' => get_config('local_envbar', 'prodtextcolour'),
                'colourbg' => get_config('local_envbar', 'prodbgcolour'),
                'matchpattern' => rtrim(self::getprodwwwroot(), '/') . '/',
            ]);

            $renderer = $PAGE->get_renderer('local_envbar');
            return $renderer->render_envbar($match, true, $envs);
        } catch (Exception $e) {
            debugging('Exception occured while injecting our code: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        return '';
    }

    /**
     * Gets the prodwwwroot.
     * This also base64_dencodes the value to obtain it.
     *
     * @return string $prodwwwroot if it is set either in plugin config via UI or
     *         in config.php. Returns an empty string if prodwwwroot is not set anywhere.
     */
    public static function getprodwwwroot(): string {
        global $CFG;

        $prodwwwroot = base64_decode(get_config("local_envbar", "prodwwwroot"));

        if (!empty($CFG->local_envbar_prodwwwroot)) {
            $prodwwwroot = $CFG->local_envbar_prodwwwroot;
        }

        if ($prodwwwroot) {
            return $prodwwwroot;
        }

        // Not set - return empty string.
        return '';
    }

    /**
     * Sets the prodwwwroot.
     * This also base64_encodes the value to prevent datawashing from removing the values.
     *
     * @param string $prodwwwroot
     */
    public static function setprodwwwroot($prodwwwroot) {
        $prodwwwroot = rtrim($prodwwwroot, '/');
        $root = base64_encode($prodwwwroot);

        $current = get_config('local_envbar', 'prodwwwroot');
        if ($current != $root) {
            set_config('prodwwwroot', $root, 'local_envbar');
        }
    }

    /**
     * Sets the secondaryurls.
     * This also base64_encodes the value to prevent datawashing from removing the values.
     *
     * @param string $secondaryurls
     */
    public static function setprodsecondaryurls($secondaryurls) {
        $domains = explode("\n", $secondaryurls);
        $trimmeddomains = [];
        foreach ($domains as $domain) {
            $tdomain = rtrim(trim($domain), '/');
            $trimmeddomains[] = $tdomain;
        }
        $encodeddomains = base64_encode(implode("\n", $trimmeddomains));
        set_config('secondaryurls', $encodeddomains, 'local_envbar');
    }


    /**
     * Gets the secondaryurls.
     * This also base64_decodes the value to obtain it.
     *
     * @return string
     */
    public static function getprodsecondaryurls(): string {
        $domains = base64_decode(get_config("local_envbar", "secondaryurls"));
        if ($domains) {
            return $domains;
        }
        // Not set - return empty string.
        return '';
    }

    /**
     * Checks whether the current site is a production environment.
     *
     * @param string|null $prodwwwroot
     * @return bool
     */
    public static function is_prod_env(?string $prodwwwroot = null): bool {
        global $CFG;

        if (!isset($prodwwwroot)) {
            $prodwwwroot = self::getprodwwwroot();
        }

        // Compare against primary prod wwwroot.
        if ($prodwwwroot === $CFG->wwwroot) {
            return true;
        }

        // Compare against secondary prod wwwroots.
        if (!empty($CFG->allowmultipledomains)) {
            $customdomains = explode("\n", self::getprodsecondaryurls());
            foreach ($customdomains as $customdomain) {
                if ($customdomain === $CFG->wwwroot) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks if we should try to inject the envbar.
     * This prevents injecting multiple times if the call has been added to many hooks.
     *
     * @return bool
     *
     */
    public static function injection_allowed() {
        global $PAGE;

        if (self::$injectcalled) {
            return false;
        }

        // Do not inject if being called in an ajax or cli script unless it's a unit test.
        if ((CLI_SCRIPT || AJAX_SCRIPT) && !PHPUNIT_TEST) {
            return false;
        }

        // Do not inject if the page layout is set to 'embedded'.
        if ($PAGE->pagelayout === 'embedded') {
            return false;
        }

        self::$injectcalled = true;

        // Nothing preventing the injection.
        return true;
    }

    /**
     * Sets prodlastcheck with the current or a passed time.
     *
     * @param string $time current time by default
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
        $url = $prodwwwroot . "/local/envbar/service/updatelastrefresh.php";
        $params = "wwwroot=" . urlencode($CFG->wwwroot) . "&lastrefresh=" .
            urlencode($lastrefresh) . "&secretkey=" . urlencode(self::get_secret_key());
        $options = [
            'timeout' => 15,
            'connecttimeout' => 5,
        ];
        if ($debug) {
            $options['debug'] = true;
        }

        require_once($CFG->dirroot . "/lib/filelib.php");
        $curl = new \curl($options);

        try {
            $response = $curl->post($url, $params);
            $response = json_decode($response);
        } catch (Exception $e) {
            mtrace("Error contacting production, error returned was: " . $e->getMessage());
            $response = null;
        }

        if (isset($response->result) && $response->result === 'success') {
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
     * Returns the email status string to be displayed.
     *
     * @return string
     */
    public static function get_email_status_string() {
        global $CFG;

        $email = get_string('emailstatus', 'local_envbar');
        if (!empty($CFG->noemailever)) {
            $status = get_string('emailnoemailever', 'local_envbar');
        } else if ($CFG->divertallemailsto != '') {
            $status = get_string('emaildiverted', 'local_envbar');
        } else {
            $status = get_string('emailon', 'local_envbar');
        }
        $email .= \html_writer::link(new moodle_url('/admin/settings.php?section=outgoingmailconfig'), $status);

        return $email;
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

        // Do not modify config on the production environment!
        if (self::is_prod_env()) {
            return;
        }

        // If on admin pages, we do not want to do anything, as we need to avoid recursively adding config through GUI.
        // Too early to use $PAGE here.
        $cleanurl = new \moodle_url($FULLME);
        if (
            strpos($cleanurl->out(), $CFG->wwwroot . '/admin/settings.php') !== false ||
            strpos($cleanurl->out(), $CFG->wwwroot . '/admin/category.php') !== false ||
            strpos($cleanurl->out(), $CFG->wwwroot . '/admin/search.php') !== false
        ) {
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
            $match = (object) [
                'id' => 0,
                'showtext' => get_string('notconfigured', 'local_envbar'),
                'colourtext' => 'white',
                'colourbg' => 'red',
                'matchpattern' => '',
                'lastrefresh' => get_config('local_envbar', 'prodlastcheck'),
                'refreshschedule' => '',
            ];
        }

        // Email subject prefix.
        static $prefixapplied = false;
        if (get_config('local_envbar', 'enableemailprefix')) {
            // Only do something if this config exists, and we haven't already applied the
            // prefix (config() may be called more than once per request/process).
            if (isset($CFG->emailsubjectprefix) && !$prefixapplied) {
                $CFG->emailsubjectprefix = '[' . substr($match->showtext, 0, 4) . '] ' . $CFG->emailsubjectprefix;
                $prefixapplied = true;
            }
        }
    }

    /**
     * Add items to the menu navigation.
     *
     * @return array New menu items.
     */
    public static function add_menuuser(): array {
        global $PAGE;
        $here = (new moodle_url('/'))->out();
        $prodwwwroot = self::getprodwwwroot();
        // Get prod Environment.
        $prodenv = new stdClass();
        $prodenv->matchpattern = $prodwwwroot;
        $prodenv->showtext = get_string('prod', 'local_envbar');
        $envsprod[] = $prodenv;
        // Attached the list of Environments to the prod one.
        $envslistfinal = array_merge($envsprod, self::get_records());
        $navitem[] = new divider();
        $navitem[] = new user_menu_header(get_string('menuenvsettings', 'local_envbar'));
        foreach ($envslistfinal as $env) {
            $pathurl = (new moodle_url($PAGE->__get('url')))->out_as_local_url();
            $currenturl = $env->matchpattern . $pathurl;
            $pix = self::is_match($here, $env->matchpattern) ? 'e/tick' : 'spacer';
            $navitem[] = new user_menu_link(
                new url($currenturl),
                $env->showtext,
                null,
                new pix_icon($pix, ''),
                null,
                ['no-envbar-highlight'],
            );
        }

        // If not configured then don't show anything.
        if (count($navitem) == 2) {
            return [];
        }
        return $navitem;
    }

    /**
     * Gets the database record for the current non prod site.
     *
     * @return stdClass|null Environment record
     */
    public static function get_match() {
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
        return $match;
    }

    /**
     * Checks if the refresh times have been updated and, if so, forces a timestamp reset.
     *
     * @param stdClass|null $match Environment record
     * @return void
     */
    public static function check_refresh_timestamp($match = null) {
        if (!isset($match) && !$match = self::get_match()) {
            return;
        }

        // Need to update the refresh timestamp when either the refresh schedule or lastrefresh changes.
        $refreshhash = md5(($match->refreshschedule ?? '') . ($match->lastrefresh ?? 0));
        if ($refreshhash !== get_config('local_envbar', 'refreshhash')) {
            set_config('refreshhash', $refreshhash, 'local_envbar');
            self::update_next_refresh_timestamp($match);
        }
    }

    /**
     * Updates the stored timestamp for the next expected refresh. Called when last refresh is updated and when
     * the config is changed.
     *
     * @param stdClass $match Environment record
     * @return void
     * @throws \dml_exception
     */
    public static function update_next_refresh_timestamp($match) {

        $refreshschedule = $match->refreshschedule ?? '';
        if (is_numeric($refreshschedule)) {
            // Does the value look like a timestamp?
            $nextrefresh = intval($refreshschedule);
        } else if (strtotime($refreshschedule) !== false) {
            // Does the value look like a date string?
            $nextrefresh = self::calculate_next_refresh($match);
        } else {
            // Dunno just ignore it.
            $nextrefresh = null;
        }
        // Save the next refresh time as a timestamp.
        set_config('nextrefreshasts', $nextrefresh, 'local_envbar');
    }

    /**
     * Calculates the next scheduled refresh time based on the refresh schedule.
     *
     * @param stdClass $match Environment record
     * @return int|null Next refresh timestamp
     */
    public static function calculate_next_refresh($match) {
        $refreshschedule = $match->refreshschedule ?? '';
        $lastrefresh = $match->lastrefresh ?? 0;
        if (empty($refreshschedule) || empty($lastrefresh)) {
            return null;
        }

        // We want the next scheduled refresh after lastrefresh, allowing for a small buffer.
        $lastrefresh += 4 * HOURSECS;
        $nextrefresh = strtotime($refreshschedule, $lastrefresh);
        if ($nextrefresh === false) {
            return null;
        }

        // Any schedules with 'ago' cannot be adjusted to the future.
        if (strpos($refreshschedule, 'ago') !== false) {
            // Also remove the buffer.
            return strtotime($refreshschedule, $match->lastrefresh);
        }

        // Incrementally adjust the time until a date after the lastrefresh is found or cap is reached.
        $adjustedtime = $lastrefresh;
        $maxtime = $lastrefresh + YEARSECS;
        while ($adjustedtime < $maxtime) {
            if ($nextrefresh > $lastrefresh) {
                return $nextrefresh;
            }
            $adjustedtime = strtotime("+1 day", $adjustedtime);
            $nextrefresh = strtotime($refreshschedule, $adjustedtime);
        }

        // If we can't find any future value within a reasonable time, fallback to the original.
        return strtotime($refreshschedule, $lastrefresh);
    }

    /**
     * Gets a test suitable for UI display for the next expected refresh time.
     *
     * @param int $nextrefresh
     * @return string
     */
    public static function get_next_refresh_as_text(int $nextrefresh): string {
        $timetorefresh = $nextrefresh - time();
        $inpast = false;
        // Use different text if refresh is overdue.
        if ($timetorefresh < 0) {
            $inpast = true;
            $timetorefresh = -$timetorefresh;
        }
        $show = format_time($timetorefresh);

        $num = strtok($show, ' ');
        $unit = strtok(' ');
        return get_string($inpast ? 'nextrefreshwas' : 'nextrefreshin', 'local_envbar', "$num $unit");
    }
}
