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

namespace local_envbar;

use local_envbar\local\envbarlib;
use stdClass;

/**
 * Unit testing class for envbar_lib
 */
final class lib_test extends \advanced_testcase {
    /**
     * Initial set up.
     */
    protected function setUp(): void {
        global $CFG;

        require_once($CFG->dirroot . '/local/envbar/lib.php');
        parent::setUp();

        // Switch on for envbar unit tests.
        set_config('enablemenu', true, 'local_envbar');
        set_config('allowmultipledomains', false);
        $this->resetAfterTest(true);
    }

    /**
     * The data to provide for testing a pattern matching.
     *
     * @return array of test cases
     */
    public static function get_data_for_pattern_matching(): array {
        return [
            ['https://my_moodle.com/', 'https://my_moodle.com/', true],
            ['https://my_moodle.com/', 'https://my_moodle.com', true],
            ['https://my_moodle.com/', '://my_moodle.com', true],
            ['https://my_moodle.com/', '//my_moodle.com', true],
            ['https://my_moodle.com/', 'my_moodle.com', true],
            ['https://my_moodle.com/', '/', true],
            ['https://my_moodle.com/', ':', true],
            ['https://my_moodle.com/', '.', true],
            ['https://my_moodle.com/', '.com', true],
            ['https://my_moodle.com/', '', false],
            ['https://my_moodle.com/', null, false],
            ['https://my_moodle.com/', ' ', false],
            ['https://my_moodle.com/', '     ', false],
            ['https://my_moodle.com/', 'https://my_moodle.com//', false],
            ['https://my_moodle.com/', 'http://my_moodle.com', false],
            ['https://my_moodle.com/', 'http://my_moodle.com', false],
            ['https://my_moodle.com/', '/', true],
            ['https://my_moodle.com/', 'o{2}', true],
            ['https://my_moodle.com/', 'o{3}', false],
            ['https://my_moodle3.com/', 'https://my_moodle[1,2,3].com', true],
            ['https://my_moodle6.com/', 'https://my_moodle[1-9].com', false],
            ['https://my_moodle6.com/', '([a-zA-Z](([a-zA-Z0-9-])[a-zA-Z0-9]))', false],
            ['https://my_moodle6.com/', '\D', false],
            ['\-/.?*+^$', '\-/.?*+^$', true],
        ];
    }

    /**
     * The env swapper feature introduced in issue #215 can cause core unit tests to fail. The setting is enablemenu is switched to
     * off for unit testing. Test that it's been switched on for these unit tests.
     * @covers \local_envbar\local\envbarlib
     */
    public function test_usermenu_has_been_set_to_true_in_setup(): void {
        $config = get_config('local_envbar');

        $this->assertNotEmpty($config->enablemenu);
        $this->assertTrue((bool) $config->enablemenu);
    }

    /**
     * Test that local_envbar_is_match() method return correct result.
     *
     * @dataProvider get_data_for_pattern_matching
     *
     * @param string $value A value to test on.
     * @param string  $pattern A pattern to test on.
     * @param bool $expected Expected result.
     * @covers \local_envbar\local\envbarlib
     */
    public function test_pattern_matching($value, $pattern, $expected): void {
        $actual = envbarlib::is_match($value, $pattern);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Check envbarlib::get_inject_code() works as expected.
     * @covers \local_envbar\local\envbarlib
     */
    public function test_inject(): void {
        global $CFG, $PAGE, $OUTPUT;
        $this->resetAfterTest(true);
        $PAGE->set_url(new \moodle_url('/local/envbar/index.php'));

        $this->setAdminUser();

        $data = new stdClass();
        $data->colourbg = '000000';
        $data->colourtext = '000000';
        $data->matchpattern = $CFG->wwwroot;
        $data->showtext = 'Test Inject';
        envbarlib::update_envbar($data);

        $injected = envbarlib::reset_injectcalled();
        self::assertStringContainsString('<style>', $injected);
        self::assertStringContainsString('<script>', $injected);

        // Should not inject more than once with the inject() function.
        $size = strlen($OUTPUT->standard_top_of_body_html());
        self::assertSame($size, strlen($OUTPUT->standard_top_of_body_html()));

        // Injected should be in $OUTPUT.
        self::assertStringContainsString(envbarlib::get_inject_code(), $OUTPUT->standard_top_of_body_html());
    }

    /**
     * Check envbarlib::get_inject_code() bypass if we are in production environment.
     * @covers \local_envbar\local\envbarlib
     */
    public function test_inject_bypass(): void {
        global $CFG, $PAGE, $OUTPUT;
        $this->resetAfterTest(true);
        $PAGE->set_url(new \moodle_url('/local/envbar/index.php'));
        $this->setAdminUser();

        $data = new stdClass();
        $data->colourbg = '000000';
        $data->colourtext = '000000';
        $data->matchpattern = $CFG->wwwroot;
        $data->showtext = 'Test Inject';
        envbarlib::update_envbar($data);
        // Banner being displayed if allowmultipledomains off.
        $injected = envbarlib::reset_injectcalled();
        self::assertStringContainsString('<style>', $injected);
        self::assertStringContainsString('<script>', $injected);

        // Banner being displayed if allowmultipledomains on but url not in the secondary list.
        set_config('allowmultipledomains', true);
        $injected = envbarlib::reset_injectcalled();
        self::assertStringContainsString('<style>', $injected);
        self::assertStringContainsString('<script>', $injected);

        // Bypass if url in the secondary list.
        envbarlib::setprodsecondaryurls($CFG->wwwroot);
        $injected = envbarlib::reset_injectcalled();
        self::assertEquals('', $injected);
    }

    /**
     * Test is_secret_key_overridden() function.
     * @covers \local_envbar\local\envbarlib
     */
    public function test_is_secret_key_overridden(): void {
        global $CFG;

        $this->resetAfterTest();

        $CFG->local_envbar_secretkey = 'test';
        $this->assertTrue(envbarlib::is_secret_key_overridden());

        $CFG->local_envbar_secretkey = '';
        $this->assertFalse(envbarlib::is_secret_key_overridden());

        $CFG->local_envbar_secretkey = [];
        $this->assertFalse(envbarlib::is_secret_key_overridden());

        $CFG->local_envbar_secretkey = [1];
        $this->assertFalse(envbarlib::is_secret_key_overridden());

        $CFG->local_envbar_secretkey = new stdClass();
        $this->assertFalse(envbarlib::is_secret_key_overridden());

        unset($CFG->local_envbar_secretkey);
        $this->assertFalse(envbarlib::is_secret_key_overridden());
    }

    /**
     * Test get_secret_key().
     * @covers \local_envbar\local\envbarlib
     */
    public function test_get_secret_key(): void {
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
     * @covers \local_envbar\local\envbarlib
     */
    public function test_get_toggle_debug_config(): void {
        global $CFG;

        $this->resetAfterTest();
        $data = new stdClass();
        $array = ['DEBUG_DEVELOPER', 'DEBUG_NORMAL'];
        $this->assertEquals(DEBUG_NORMAL, envbarlib::get_toggle_debug_config(100));
        $this->assertEquals(DEBUG_NORMAL, envbarlib::get_toggle_debug_config('DEVELOPER'));
        $this->assertEquals(DEBUG_NORMAL, envbarlib::get_toggle_debug_config($data));
        $this->assertEquals(DEBUG_NORMAL, envbarlib::get_toggle_debug_config($array));
        $this->assertEquals(DEBUG_NORMAL, envbarlib::get_toggle_debug_config(DEBUG_DEVELOPER));
        $this->assertEquals(DEBUG_DEVELOPER, envbarlib::get_toggle_debug_config(DEBUG_NORMAL));
    }

    /**
     * Test get_debug_display_config().
     * @covers \local_envbar\local\envbarlib
     */
    public function test_get_debug_display_config(): void {
        $this->resetAfterTest();
        $data = new stdClass();
        $array = ['DEBUG_DEVELOPER', 'DEBUG_NORMAL'];
        $this->assertEquals(0, envbarlib::get_debug_display_config(1));
        $this->assertEquals(0, envbarlib::get_debug_display_config('DEVELOPER'));
        $this->assertEquals(0, envbarlib::get_debug_display_config($data));
        $this->assertEquals(0, envbarlib::get_debug_display_config($array));
        $this->assertEquals(0, envbarlib::get_debug_display_config(DEBUG_NORMAL));
        $this->assertEquals(1, envbarlib::get_debug_display_config(DEBUG_DEVELOPER));
    }

    /**
     * Test get_debugging_status_string().
     * @covers \local_envbar\local\envbarlib
     */
    public function test_get_debugging_status_string(): void {
        global $CFG;

        $this->resetAfterTest();

        $CFG->debug = 100;
        $this->assertEquals('Debug: Off', envbarlib::get_debugging_status_string());

        $CFG->debug = 'DEVELOPER';
        $this->assertEquals('Debug: Off', envbarlib::get_debugging_status_string());

        $CFG->debug = DEBUG_NORMAL;
        $this->assertEquals('Debug: Off', envbarlib::get_debugging_status_string());

        $CFG->debug = DEBUG_DEVELOPER;
        $this->assertEquals('Debug: On', envbarlib::get_debugging_status_string());
    }

    /**
     * Test get_email_status_string().
     * @covers \local_envbar\local\envbarlib
     */
    public function test_get_email_status_string(): void {
        global $CFG;

        $this->resetAfterTest();

        $this->assertStringContainsString('On', envbarlib::get_email_status_string());

        $CFG->divertallemailsto = 'somewhere@example.com';
        $this->assertStringContainsString('DIVERTED', envbarlib::get_email_status_string());

        $CFG->noemailever = 1;
        $this->assertStringContainsString('DISABLED', envbarlib::get_email_status_string());
    }

    /**
     * Test get_debug_toggle_string().
     * @covers \local_envbar\local\envbarlib
     */
    public function test_get_debug_toggle_string(): void {
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
     * @covers \local_envbar\local\envbarlib
     */
    public function test_set_debug_config(): void {
        global $DB;

        $this->resetAfterTest();
        $data = new stdClass();
        $array = ['DEBUG_DEVELOPER', 'DEBUG_NORMAL'];

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

    /**
     * Test refresh schedule
     *
     * @covers ::check_refresh_timestamp
     */
    public function test_check_refresh_timestamp(): void {
        global $CFG;

        $CFG->wwwroot = 'https://staging.moodle.edu';
        $env = (object) [
            'matchpattern'    => $CFG->wwwroot,
            'showtext'        => 'Staging environment',
            'colourbg'        => 'red',
            'colourtext'      => 'white',
            'refreshschedule' => 'third sunday of this month 12 pm',
        ];

        // Clone the env to avoid double encoding.
        $env->id = envbarlib::update_envbar(clone $env);

        // Confirm no update if the site has never been refreshed.
        envbarlib::check_refresh_timestamp();
        $this->assertEquals(false, get_config('local_envbar', 'nextrefreshasts'));

        // Confirm it updates after a reset.
        $lastrefresh = strtotime('2025-03-14 00:00:00');
        set_config('prodlastcheck', $lastrefresh, 'local_envbar');
        envbarlib::update_envbar(clone $env);
        envbarlib::check_refresh_timestamp();
        $this->assertEquals(strtotime('2025-03-16 12:00:00'), get_config('local_envbar', 'nextrefreshasts'));

        // Confirm it updates when refreshschedule changes.
        $env->refreshschedule = 'fourth sunday of this month 12 pm';
        envbarlib::update_envbar(clone $env);
        envbarlib::check_refresh_timestamp();
        $this->assertEquals(strtotime('2025-03-23 12:00:00'), get_config('local_envbar', 'nextrefreshasts'));

        // Confirm the calculated refresh time is in the future when the relative strtotime returns a past date.
        $env->refreshschedule = 'second sunday of this month 12 pm';
        $this->assertEquals(strtotime('2025-03-09 12:00:00'), strtotime($env->refreshschedule, $lastrefresh));
        envbarlib::update_envbar(clone $env);
        envbarlib::check_refresh_timestamp();
        $this->assertEquals(strtotime('2025-04-13 12:00:00'), get_config('local_envbar', 'nextrefreshasts'));

        // Confirm handling of a relative timestamp in the past.
        $env->refreshschedule = '10 months ago';
        envbarlib::update_envbar(clone $env);
        envbarlib::check_refresh_timestamp();
        $this->assertEquals(strtotime('10 months ago', $lastrefresh), get_config('local_envbar', 'nextrefreshasts'));

        // Confirm time strings are handled appropriately.
        $env->refreshschedule = strtotime('2025-03-09 12:00:00');
        envbarlib::update_envbar(clone $env);
        envbarlib::check_refresh_timestamp();
        $this->assertEquals($env->refreshschedule, get_config('local_envbar', 'nextrefreshasts'));

        // Confirm unix timestamps are handled appropriately.
        $env->refreshschedule = 1741485600;
        envbarlib::update_envbar(clone $env);
        envbarlib::check_refresh_timestamp();
        $this->assertEquals($env->refreshschedule, get_config('local_envbar', 'nextrefreshasts'));
    }

    /**
     * Test that update_envbar() fires an envbar_updated event on both insert and update.
     *
     * @covers \local_envbar\local\envbarlib::update_envbar
     * @covers \local_envbar\event\envbar_updated
     */
    public function test_update_envbar_triggers_event(): void {
        $this->resetAfterTest(true);

        $env = (object) [
            'matchpattern'    => 'https://staging.moodle.edu',
            'showtext'        => 'Staging environment',
            'colourbg'        => 'red',
            'colourtext'      => 'white',
            'refreshschedule' => '',
        ];

        // Insert.
        $sink = $this->redirectEvents();
        $id = envbarlib::update_envbar(clone $env);
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf(\local_envbar\event\envbar_updated::class, $event);
        $this->assertEquals($id, $event->objectid);
        $this->assertEquals('local_envbar', $event->objecttable);
        $this->assertEquals('u', $event->crud);
        $this->assertEquals(\context_system::instance(), $event->get_context());

        // Update.
        $env->id = $id;
        $env->showtext = 'Staging environment updated';

        $sink = $this->redirectEvents();
        envbarlib::update_envbar(clone $env);
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf(\local_envbar\event\envbar_updated::class, $event);
        $this->assertEquals($id, $event->objectid);
    }

    /**
     * Test that delete_envbar() fires an envbar_deleted event.
     *
     * @covers \local_envbar\local\envbarlib::delete_envbar
     * @covers \local_envbar\event\envbar_deleted
     */
    public function test_delete_envbar_triggers_event(): void {
        $this->resetAfterTest(true);

        $env = (object) [
            'matchpattern'    => 'https://staging.moodle.edu',
            'showtext'        => 'Staging environment',
            'colourbg'        => 'red',
            'colourtext'      => 'white',
            'refreshschedule' => '',
        ];
        $id = envbarlib::update_envbar(clone $env);

        $sink = $this->redirectEvents();
        envbarlib::delete_envbar($id);
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf(\local_envbar\event\envbar_deleted::class, $event);
        $this->assertEquals($id, $event->objectid);
        $this->assertEquals('local_envbar', $event->objecttable);
        $this->assertEquals('d', $event->crud);
        $this->assertEquals(\context_system::instance(), $event->get_context());
    }
}
