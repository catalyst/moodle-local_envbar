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
 * Form for editing a configuration of the status bar
 *
 * @package   local_envbar
 * @author    Grigory Baleevskiy (grigory@catalyst-au.net)
 * @author    Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_envbar\form;

use local_envbar\local\envbarlib;
use moodleform;

/**
 * Form for editing an Enviroment bar.
 *
 * @copyright Catalyst IT
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class config extends moodleform {

    /**
     * {@inheritDoc}
     * @see moodleform::definition()
     */
    public function definition() {
        global $CFG, $PAGE;

        $colours = array(
            "black",
            "white",
            "red",
            "green",
            "seagreen",
            "yellow",
            "brown",
            "blue",
            "slateblue",
            "chocolate",
            "crimson",
            "orange",
            "darkorange"
        );

        // Construct datalist HTML element for later injection.
        $datalisthtml = '<datalist id="colours">';
        foreach ($colours as $colour) {
            $datalisthtml .= '<option value="' . $colour . '">';
        }
        $datalisthtml .= '</datalist>';

        $coloursregex = implode ('\\b|', $colours);

        require_once($CFG->dirroot.'/local/envbar/renderer.php');
        $renderer = $PAGE->get_renderer('local_envbar');

        $mform = $this->_form;
        $records = $this->_customdata["records"];
        $gensecretkey = $this->_customdata["gensecretkey"];
        $rcount = count($records);

        $urlset = false;
        // If true then we will lock the url field from being edited.
        if (!empty($CFG->local_envbar_prodwwwroot)) {
            $urlset = true;
        }

        // The prodwwwroot will be the $CFG->wwwroot that is set on the production server.
        // When this is not set, a warning message will be displayed.
        // If it has been manually set as $CFG->local_envbar_prodwwwroot it will be locked from further edits.

        $wwwrootgroup = array();

        $wwwrootgroup[] =& $mform->createElement(
            "text",
            "prodwwwroot",
            get_string("prodwwwroottext", "local_envbar"),
            array("placeholder" => get_string("prodwwwrootplaceholder", "local_envbar"),
                  "id" => "prodwwwroot",
                  "size" => 40,
                  $urlset ? 'disabled' : 'enabled')
        );

        $wwwrootgroup[] =& $mform->createElement(
            "button",
            "autofill",
            get_string("prodwwwrootautobutton", "local_envbar"),
            array("onclick" => "document.getElementById('prodwwwroot').value = '$CFG->wwwroot'", $urlset ? 'disabled' : 'enabled')
        );

        $mform->addGroup($wwwrootgroup, 'wwwrootg', get_string('prodwwwroottext', 'local_envbar'), array(' '), false);

        $mform->setType("prodwwwroot", PARAM_URL);
        $mform->setDefault("prodwwwroot", envbarlib::getprodwwwroot());

        $config = get_config('local_envbar');
        $mform->addElement('text', 'prodtextcolour', get_string('prodtextcolour', 'local_envbar'),
                array('placeholder' => 'white',
                      'size' => 40));
        $mform->setType('prodtextcolour', PARAM_TEXT);
        $mform->addHelpButton('prodtextcolour', 'prodtextcolour', 'local_envbar');
        if (isset($config->prodtextcolour)) {
            $mform->setDefault('prodtextcolour', $config->prodtextcolour);
        } else {
            $mform->setDefault('prodtextcolour', 'white');
        }
        $mform->addRule(
                'prodtextcolour',
                get_string('colourerror', 'local_envbar'),
                'regex',
                '/#([a-f0-9]{3}){1,2}\b|' . $coloursregex . '\b/i',
                'client'
        );

        $mform->addElement('text', 'prodbgcolour', get_string('prodbgcolour', 'local_envbar'),
                array('placeholder' => 'red',
                      'size' => 40));
        $mform->setType('prodbgcolour', PARAM_TEXT);
        $mform->addHelpButton('prodbgcolour', 'prodbgcolour', 'local_envbar');
        if (isset($config->prodbgcolour)) {
            $mform->setDefault('prodbgcolour', $config->prodbgcolour);
        } else {
            $mform->setDefault('prodbgcolour', 'red');
        }
        $mform->addRule(
                'prodbgcolour',
                get_string('colourerror', 'local_envbar'),
                'regex',
                '/#([a-f0-9]{3}){1,2}\b|' . $coloursregex . '\b/i',
                'client'
        );

        $secretkeygroup = array();

        $secretkeygroup[] =& $mform->createElement(
            "text",
            "secretkey",
            get_string("secretkey", "local_envbar"),
            array("placeholder" => get_string("secretkeyplaceholder", "local_envbar"),
                "id" => "secretkey",
                "size" => 40,
                envbarlib::is_secret_key_overridden() ? 'disabled' : 'enabled')
        );

        $secretkeygroup[] =& $mform->createElement(
            "button",
            "secretkeygen",
            get_string("secretkeygenbutton", "local_envbar"),
            array("onclick" => "document.getElementById('secretkey').value = '$gensecretkey'",
                envbarlib::is_secret_key_overridden() ? 'disabled' : 'enabled')
        );

        $mform->addGroup($secretkeygroup, 'secretkeyg', get_string('secretkey', 'local_envbar'), array(' '), false);

        $mform->setType("secretkey", PARAM_TEXT);
        $mform->setDefault('secretkey', envbarlib::get_secret_key());
        $mform->addHelpButton('secretkeyg', 'secretkey', 'local_envbar');

        foreach ($records as $record) {

            $locked = false;

            // Local records set in config.php will be locked for editing.
            if (isset($record->local)) {
                $locked = true;

                $mform->addElement(
                    "hidden",
                    "locked[{$record->id}]",
                    $locked
                );
                $mform->setType("locked[{$record->id}]", PARAM_INT);
            }

            $id = $record->id;

            $html = $renderer->render_envbar($record, false);
            $mform->addElement('html', $html);

            $mform->addElement(
                "hidden",
                "id[{$id}]",
                $id
            );

            $mform->addElement(
                "text",
                "matchpattern[{$id}]",
                get_string("urlmatch", "local_envbar"),
                array("placeholder" => get_string("urlmatchplaceholder", "local_envbar"),
                    "size" => 40,
                    $locked ? 'disabled' : 'enabled')
            );

            $mform->addElement(
                "text",
                "showtext[{$id}]",
                get_string("showtext", "local_envbar"),
                array("placeholder" => get_string("showtextplaceholder", "local_envbar"),
                      "size" => 40,
                      $locked ? 'disabled' : 'enabled')
            );

            $mform->addElement(
                'html',
                $datalisthtml
            );

            $mform->addElement(
                "text",
                "colourtext[{$id}]",
                get_string("textcolour", "local_envbar"),
                array("placeholder" => get_string("colourplaceholder", "local_envbar"),
                    "size" => 40,
                    "list" => "colours",
                    "name" => "envcolours",
                    $locked ? 'disabled' : 'enabled')
            );

            if (!$locked) {
                $mform->addRule(
                    "colourtext[{$id}]",
                    get_string("colourerror", "local_envbar"),
                    'regex',
                    '/#([a-f0-9]{3}){1,2}\b|' . $coloursregex . '\b/i',
                    'client'
                );
            }

            $mform->addElement(
                "text",
                "colourbg[{$id}]",
                get_string("bgcolour", "local_envbar"),
                array("placeholder" => get_string("colourplaceholder", "local_envbar"),
                    "size" => 40,
                    "list" => "colours",
                    "name" => "envcolours",
                    $locked ? 'disabled' : 'enabled')
            );

            if (!$locked) {
                $mform->addRule(
                    "colourbg[{$id}]",
                    get_string("colourerror", "local_envbar"),
                    'regex',
                    '/#([a-f0-9]{3}){1,2}\b|' . $coloursregex . '\b/i',
                    'client'
                );
            }

            $mform->addElement(
                "advcheckbox",
                "delete[{$id}]",
                get_string("setdeleted", "local_envbar"),
                '',
                $locked ? array('disabled') : array(),
                array(0, 1)
            );

            $mform->setType("id[{$id}]", PARAM_INT);
            $mform->setType("matchpattern[{$id}]", PARAM_TEXT);
            $mform->addHelpButton("matchpattern[{$id}]", 'urlmatch', 'local_envbar');
            $mform->setType("showtext[{$id}]", PARAM_TEXT);
            $mform->setType("colourtext[{$id}]", PARAM_TEXT);
            $mform->setType("colourbg[{$id}]", PARAM_TEXT);

            $mform->setDefault("id[{$id}]", $record->id);
            $mform->setDefault("matchpattern[{$id}]", $record->matchpattern);
            $mform->setDefault("showtext[{$id}]", $record->showtext);
            $mform->setDefault("colourtext[{$id}]", $record->colourtext);
            $mform->setDefault("colourbg[{$id}]", $record->colourbg);
            $mform->setDefault("delete[{$id}]", 0);

        }

        // Now we set up the same fields to repeat and add elements.
        if ($rcount == 0) {
            $repeatnumber = 1;
        } else {
            $repeatnumber = 0;
        }

        $repeatarray = array();

        $repeatarray[] = $mform->createElement(
            "hidden",
            "repeatid"
        );

        $repeatarray[] = $mform->createElement(
            'html',
            $datalisthtml
        );

        $repeatarray[] = $mform->createElement(
            "text",
            "repeatmatchpattern",
            get_string("urlmatch", "local_envbar"),
            array("placeholder" => get_string("urlmatchplaceholder", "local_envbar"),
                  "size" => 40)
        );

        $repeatarray[] = $mform->createElement(
            "text",
            "repeatshowtext",
            get_string("showtext", "local_envbar"),
            array("placeholder" => get_string("showtextplaceholder", "local_envbar"),
                  "size" => 40)
        );

        $repeatarray[] = $mform->createElement(
            "text",
            "repeatcolourtext",
            get_string("textcolour", "local_envbar"),
            array("placeholder" => get_string("colourplaceholder", "local_envbar"),
                "size" => 40,
                "list" => "colours",
                "name" => "envcolours"
            )
        );

        $repeatarray[] = $mform->createElement(
            "text",
            "repeatcolourbg",
            get_string("bgcolour", "local_envbar"),
            array("placeholder" => get_string("colourplaceholder", "local_envbar"),
                "size" => 40,
                "list" => "colours",
                "name" => "envcolours")
        );

        $repeatarray[] = $mform->createElement(
            "advcheckbox",
            "repeatdelete",
            get_string("setdeleted", "local_envbar"),
            '',
            array(),
            array(0, 1)
        );

        $repeatarray[] = $mform->addElement("html", "<hr>");

        $repeatoptions = array();
        $repeatoptions["repeatid"]["default"] = "{no}";
        $repeatoptions["repeatid"]["type"] = PARAM_INT;

        $repeatoptions["repeatcolourbg"]["default"] = "red";
        $repeatoptions["repeatcolourbg"]["type"] = PARAM_TEXT;
        $repeatoptions["repeatcolourbg"]["rule"] = array(
            get_string("colourerror", "local_envbar"),
            'regex',
            '/#([a-f0-9]{3}){1,2}\b|' . $coloursregex . '\b/i',
            'client'
        );

        $repeatoptions["repeatcolourtext"]["default"] = "white";
        $repeatoptions["repeatcolourtext"]["type"] = PARAM_TEXT;
        $repeatoptions["repeatcolourtext"]["rule"] = array(
            get_string("colourerror", "local_envbar"),
            'regex',
            '/#([a-f0-9]{3}){1,2}\b|' . $coloursregex . '\b/i',
            'client'
        );

        $repeatoptions["repeatmatchpattern"]["default"] = "";
        $repeatoptions["repeatmatchpattern"]["type"] = PARAM_TEXT;
        $repeatoptions["repeatmatchpattern"]["helpbutton"] = array('urlmatch', 'local_envbar');

        $repeatoptions["repeatshowtext"]["default"] = "";
        $repeatoptions["repeatshowtext"]["type"] = PARAM_TEXT;

        $addstring = get_string("addfields", "local_envbar");
        $this->repeat_elements($repeatarray, $repeatnumber, $repeatoptions, "repeats", "envbar_add", 1, $addstring, false);

        $this->add_action_buttons();
    }
}

