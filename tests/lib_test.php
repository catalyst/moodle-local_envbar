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

}
