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
 *  Unit tests for lib functions.
 *
 * @package   local_envbar
 * @author    Dmitrii Metelkin (dmitriim@catalyst-au.net)
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_envbar\local\envbarlib;

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden');

class local_envbar_lib_test extends advanced_testcase {

    /**
     * Initial set up.
     */
    protected function setUp() {
        global $CFG;

        require_once($CFG->dirroot . '/local/envbar/lib.php');

        parent::setup();
        $this->resetAfterTest(true);
    }

    /**
     * The data to provide for testing a pattern matching.
     *
     * @return array of test cases
     */
    public function get_data_for_pattern_matching() {
        return array(
            array('https://my_moodle.com/', 'https://my_moodle.com/', true),
            array('https://my_moodle.com/', 'https://my_moodle.com', true),
            array('https://my_moodle.com/', '://my_moodle.com', true),
            array('https://my_moodle.com/', '//my_moodle.com', true),
            array('https://my_moodle.com/', 'my_moodle.com', true),
            array('https://my_moodle.com/', '/', true),
            array('https://my_moodle.com/', ':', true),
            array('https://my_moodle.com/', '.', true),
            array('https://my_moodle.com/', '.com', true),
            array('https://my_moodle.com/', '', false),
            array('https://my_moodle.com/', null, false),
            array('https://my_moodle.com/', ' ', false),
            array('https://my_moodle.com/', '     ', false),
            array('https://my_moodle.com/', 'https://my_moodle.com//', false),
            array('https://my_moodle.com/', 'http://my_moodle.com', false),
            array('https://my_moodle.com/', 'http://my_moodle.com', false),
            array('https://my_moodle.com/', '/', true),
            array('https://my_moodle.com/', 'o{2}', true),
            array('https://my_moodle.com/', 'o{3}', false),
            array('https://my_moodle3.com/', 'https://my_moodle[1,2,3].com', true),
            array('https://my_moodle6.com/', 'https://my_moodle[1-9].com', false),
            array('https://my_moodle6.com/', '([a-zA-Z](([a-zA-Z0-9-])[a-zA-Z0-9]))', false),
            array('https://my_moodle6.com/', '\D', false),
            array('\-/.?*+^$', '\-/.?*+^$', true),
        );
    }

    /**
     * Test that local_envbar_is_match() method return correct result.
     *
     * @dataProvider get_data_for_pattern_matching
     *
     * @param string $value A value to test on.
     * @param string  $pattern A pattern to test on.
     * @param bool $expected Expected result.
     */
    public function test_pattern_matching($value, $pattern, $expected) {
        $actual = envbarlib::is_match($value, $pattern);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Check envbarlib::inject() works as expected.
     */
    public function test_inject() {
        global $CFG, $PAGE;
        $this->resetAfterTest(true);
        $PAGE->set_url(new moodle_url('/local/envbar/index.php'));

        $this->setAdminUser();

        self::assertEmpty($CFG->additionalhtmltopofbody);

        $data = new stdClass();
        $data->colourbg = '000000';
        $data->colourtext = '000000';
        $data->matchpattern = $CFG->wwwroot;
        $data->showtext = 'Test Inject';
        envbarlib::update_envbar($data);

        envbarlib::reinject();
        self::assertContains('<style>', $CFG->additionalhtmltopofbody);
        self::assertContains('<script>', $CFG->additionalhtmltopofbody);

        // Should not inject more than once with the inject() function.
        $size = strlen($CFG->additionalhtmltopofbody);

        envbarlib::inject();
        self::assertSame($size, strlen($CFG->additionalhtmltopofbody));
    }

    /**
     * Check envbarlib::clean_envbars() works as expected.
     */
    public function test_clean_envbars() {
        global $CFG, $PAGE;
        $this->resetAfterTest(true);
        $PAGE->set_url(new moodle_url('/local/envbar/index.php'));

        $this->setAdminUser();

        // Setting the database config additionalhtmltopofbody.
        $testhtml = '<a href="#">Test</a>';
        set_config('additionalhtmltopofbody', $testhtml);

        // Create basic data object to inject an envbar.
        $data = new stdClass();
        $data->colourbg = '000000';
        $data->colourtext = '000000';
        $data->matchpattern = $CFG->wwwroot;
        $data->showtext = 'Test Inject';
        envbarlib::update_envbar($data);

        $injected = envbarlib::reinject();

        // An envbar is not in the database get_config('additionalhtmltopofbody').
        $additional = get_config('core', 'additionalhtmltopofbody');
        self::assertEquals($testhtml, $additional);

        // An envbar now exists in $CFG->additionalhtmltopofbody with our $testhtml.
        self::assertContains($injected, $CFG->additionalhtmltopofbody);

        // Which equals the $testhtml . $injected.
        self::assertEquals($testhtml . $injected, $CFG->additionalhtmltopofbody);

        // Which has no trace of any $injected.
        self::assertNotContains($injected, $additional);

        $cleaned = envbarlib::clean_envbars();
        self::assertEquals($testhtml, $cleaned);

        // Lets put the envbar back in, and saved it to the DB.
        $injected = envbarlib::reinject();
        $additional = get_config('core', 'additionalhtmltopofbody');
        set_config('additionalhtmltopofbody', $additional . $injected);

        // With an envbar saved to the db, lets clean it out.
        $cleaned = envbarlib::clean_envbars();
        self::assertEquals($testhtml, $cleaned);
        self::assertEquals($testhtml, $CFG->additionalhtmltopofbody);
    }

    /**
     * Test is_secret_key_overridden() function.
     */
    public function test_is_secret_key_overridden() {
        global $CFG;

        $this->resetAfterTest();

        $CFG->local_envbar_secretkey = 'test';
        $this->assertTrue(envbarlib::is_secret_key_overridden());

        $CFG->local_envbar_secretkey = '';
        $this->assertFalse(envbarlib::is_secret_key_overridden());

        $CFG->local_envbar_secretkey = array();
        $this->assertFalse(envbarlib::is_secret_key_overridden());

        $CFG->local_envbar_secretkey = array(1);
        $this->assertFalse(envbarlib::is_secret_key_overridden());

        $CFG->local_envbar_secretkey = new StdClass();
        $this->assertFalse(envbarlib::is_secret_key_overridden());

        unset($CFG->local_envbar_secretkey);
        $this->assertFalse(envbarlib::is_secret_key_overridden());
    }

    /**
     * Test get_secret_key().
     */
    public function test_get_secret_key() {
        global $CFG;

        $this->resetAfterTest();

        $this->assertEmpty(envbarlib::get_secret_key());

        $CFG->local_envbar_secretkey = 'overridden';
        set_config('secretkey', 'configured', 'local_envbar');
        $this->assertEquals('overridden', envbarlib::get_secret_key());

        unset($CFG->local_envbar_secretkey);
        $this->assertEquals('configured', envbarlib::get_secret_key());
    }

    /**
     * Test get_toggled_debug_config().
     */
    public function test_get_toggle_debug_config() {
        global $CFG;

        $this->resetAfterTest();
        $data = new StdClass();
        $array = array('DEBUG_DEVELOPER', 'DEBUG_NORMAL');
        $this->assertEquals(DEBUG_NORMAL, envbarlib::get_toggle_debug_config(100));
        $this->assertEquals(DEBUG_NORMAL, envbarlib::get_toggle_debug_config('DEVELOPER'));
        $this->assertEquals(DEBUG_NORMAL, envbarlib::get_toggle_debug_config($data));
        $this->assertEquals(DEBUG_NORMAL, envbarlib::get_toggle_debug_config($array));
        $this->assertEquals(DEBUG_NORMAL, envbarlib::get_toggle_debug_config(DEBUG_DEVELOPER));
        $this->assertEquals(DEBUG_DEVELOPER, envbarlib::get_toggle_debug_config(DEBUG_NORMAL));
    }

    /**
     * Test get_debug_display_config().
     */
    public function test_get_debug_display_config() {
        $this->resetAfterTest();
        $data = new StdClass();
        $array = array('DEBUG_DEVELOPER', 'DEBUG_NORMAL');
        $this->assertEquals(0, envbarlib::get_debug_display_config(1));
        $this->assertEquals(0, envbarlib::get_debug_display_config('DEVELOPER'));
        $this->assertEquals(0, envbarlib::get_debug_display_config($data));
        $this->assertEquals(0, envbarlib::get_debug_display_config($array));
        $this->assertEquals(0, envbarlib::get_debug_display_config(DEBUG_NORMAL));
        $this->assertEquals(1, envbarlib::get_debug_display_config(DEBUG_DEVELOPER));
    }

    /**
     * Test get_debugging_status_string().
     */
    public function test_get_debugging_status_string() {
        global $CFG;

        $this->resetAfterTest();

        $CFG->debug = 100;
        $this->assertEquals('Debugging Off', envbarlib::get_debugging_status_string());

        $CFG->debug = 'DEVELOPER';
        $this->assertEquals('Debugging Off', envbarlib::get_debugging_status_string());

        $CFG->debug = DEBUG_NORMAL;
        $this->assertEquals('Debugging Off', envbarlib::get_debugging_status_string());

        $CFG->debug = DEBUG_DEVELOPER;
        $this->assertEquals('Debugging On', envbarlib::get_debugging_status_string());
    }

    /**
     * Test get_debug_toggle_string().
     */
    public function test_get_debug_toggle_string() {
        global $CFG;

        $this->resetAfterTest();

        $CFG->debug = 100;
        $this->assertEquals('Turn On', envbarlib::get_debug_toggle_string());

        $CFG->debug = 'DEVELOPER';
        $this->assertEquals('Turn On', envbarlib::get_debug_toggle_string());

        $CFG->debug = DEBUG_NORMAL;
        $this->assertEquals('Turn On', envbarlib::get_debug_toggle_string());

        $CFG->debug = DEBUG_DEVELOPER;
        $this->assertEquals('Turn Off', envbarlib::get_debug_toggle_string());
    }

    /**
     * Test set_debug_config().
     */
    public function test_set_debug_config() {
        global $DB;

        $this->resetAfterTest();
        $data = new StdClass();
        $array = array('DEBUG_DEVELOPER', 'DEBUG_NORMAL');
        // Check debugging config in the DB because Moodle forces to use debugging in unit tests.
        $debug = $DB->get_field('config', 'value', ['name' => 'debug']);
        $debugdisplay = $DB->get_field('config', 'value', ['name' => 'debugdisplay']);
        $this->assertEquals(0, $debug);
        $this->assertEquals(0, $debugdisplay);

        envbarlib::set_debug_config(100);
        $debug = $DB->get_field('config', 'value', ['name' => 'debug']);
        $debugdisplay = $DB->get_field('config', 'value', ['name' => 'debugdisplay']);
        $this->assertEquals(DEBUG_NORMAL, $debug);
        $this->assertEquals(0, $debugdisplay);

        envbarlib::set_debug_config('DEVELOPER');
        $debug = $DB->get_field('config', 'value', ['name' => 'debug']);
        $debugdisplay = $DB->get_field('config', 'value', ['name' => 'debugdisplay']);
        $this->assertEquals(DEBUG_NORMAL, $debug);
        $this->assertEquals(0, $debugdisplay);

        envbarlib::set_debug_config($data);
        $debug = $DB->get_field('config', 'value', ['name' => 'debug']);
        $debugdisplay = $DB->get_field('config', 'value', ['name' => 'debugdisplay']);
        $this->assertEquals(DEBUG_NORMAL, $debug);
        $this->assertEquals(0, $debugdisplay);

        envbarlib::set_debug_config($array);
        $debug = $DB->get_field('config', 'value', ['name' => 'debug']);
        $debugdisplay = $DB->get_field('config', 'value', ['name' => 'debugdisplay']);
        $this->assertEquals(DEBUG_NORMAL, $debug);
        $this->assertEquals(0, $debugdisplay);

        envbarlib::set_debug_config(DEBUG_DEVELOPER);
        $debug = $DB->get_field('config', 'value', ['name' => 'debug']);
        $debugdisplay = $DB->get_field('config', 'value', ['name' => 'debugdisplay']);
        $this->assertEquals(DEBUG_NORMAL, $debug);
        $this->assertEquals(0, $debugdisplay);

        envbarlib::set_debug_config(DEBUG_NORMAL);
        $debug = $DB->get_field('config', 'value', ['name' => 'debug']);
        $debugdisplay = $DB->get_field('config', 'value', ['name' => 'debugdisplay']);
        $this->assertEquals(DEBUG_DEVELOPER, $debug);
        $this->assertEquals(1, $debugdisplay);
    }

}
