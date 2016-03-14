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
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

define('PLUGIN_NAME_ENVBAR', 'local_envbar');

$envbarcolorchoices = array(
    'black' => 'black',
    'white' => 'white',
    'red' => 'red',
    'green' => 'green',
    'seagreen' => 'seagreen',
    'yellow' => 'yellow',
    'brown' => 'brown',
    'blue' => 'blue',
    'slateblue' => 'slateblue',
    'chocolate' => 'chocolate',
    'crimson' => 'crimson',
    'orange' => 'orange',
    'darkorange' => 'darkorange',
);


/**
 * Set the configuration for environment bar.
 *
 * @copyright Catalyst IT
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class envbar_config_set {
    /** @var Parameter object that contains instance configuration */
    protected $params;

    /** @var Database check flag */
    protected $dbexists = false;

    /** @var The table this plugin will use */
    const DB_TABLE = 'local_envbar_configset';

    /**
     * Environment bar constructor.
     *
     * @param array $params
     * @param boolean $dbexists
     */
    public function __construct($params = array(), $dbexists = false) {
        $this->dbexists = $dbexists;
        $this->params = array(
            'id' => 0,
            'colorbg' => 'black',
            'colortext' => 'white',
            'matchpattern' => '',
            'showtext' => '',
            'enabled' => 0
        );
        if ($params instanceof stdClass) {
            foreach (array_keys($this->params) as $key) {
                $this->params[$key] = $params->$key;
            }
        } else {
            foreach (array_keys($this->params) as $key) {
                if (isset($params[$key])) {
                    $this->params[$key] = $params[$key];
                }
            }
        }
    }

    /**
     * Gets the parameters.
     *
     * @param string $name
     */
    public function __get($name) {
        return $this->params[$name];
    }

    /**
     * Sets the name and colour.
     *
     * @param string $name
     * @param string $value
     */
    public function __set($name, $value) {
        global $envbarcolorchoices;
        switch($name) {
            case 'id':
            case 'enabled':
                $value = abs(intval($value));
                break;
            case 'colorbg':
            case 'colortext':
                if (!in_array($value, $envbarcolorchoices)) {
                    return false;
                }
                break;
            case 'matchpattern':
            case 'showtext':
                if (strlen($value) > 255) {
                    return false;
                }
                break;
            default:
                return false;
        }
        $this->params[$name] = $value;
    }

    /**
     * Checks if the match pattern is valid.
     */
    public function is_valid() {
        return ($this->matchpattern != '' && $this->id > 0);
    }

    /**
     * Returns the parameter list.
     */
    public function get_params() {
        return $this->params;
    }

    /**
     * Save records to the database.
     * @param object $DB Moodle database reference.
     */
    public function save($DB) {
        if ($this->matchpattern == '' && $this->dbexists) {
            $DB->delete_records(self::DB_TABLE, array('id' => $this->id));
        } else if ($this->is_valid()) {
            if ($this->dbexists) {
                $DB->update_record(self::DB_TABLE, (object) $this->get_params());
            } else {
                $DB->insert_record(self::DB_TABLE, (object) $this->get_params());
            }
        }
    }
}

/**
 * Configuration factory helper class to return instances and configuration objects.
 *
 * @copyright Catalyst IT
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class envbar_config_set_factory {
    /**
     * Instances of the configruation settings.
     *
     * @return array records from DB + 3 empty records
     */
    public static function instances() {
        global $DB;
        $result = array();

        $records = $DB->get_records('local_envbar_configset', array(), 'id asc');
        $maxid = 0;

        foreach ($records as $id => $set) {
            $result [$id] = new envbar_config_set($set, true);
            $maxid = max($maxid, $set->id);
        }

        for ($i = 1; $i <= 3; $i++) {
            $result [$i + $maxid] = self::new_record($i + $maxid);
        }
        return $result;
    }

    /**
     * Returns a new record of the configuration.
     *
     * @param int $id attribute of new record
     * @return envbar_config_set empty object
     */
    public static function new_record($id = 0) {
        return new envbar_config_set(array('id' => $id));
    }
}
