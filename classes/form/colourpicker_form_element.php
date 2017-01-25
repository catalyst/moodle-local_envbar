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
 * Colour picker form element
 *
 * @package   local_envbar
 * @author    Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_envbar\form;

use coding_exception;
use MoodleQuickForm_text;

global $CFG;
require_once($CFG->libdir . '/form/text.php');
require_once($CFG->libdir . '/form/templatable_form_element.php');

/**
 * Form field type for choosing a framework.
 *
 * @package    tool_lp
 * @copyright  2016 Frédéric Massart - FMCorz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class colourpicker_form_element extends MoodleQuickForm_text implements \templatable {
    use \templatable_form_element;

	/**
     * constructor
     *
     * @param string $elementName (optional) name of the text field
     * @param string $elementLabel (optional) text field label
     * @param string $attributes (optional) Either a typical HTML attribute string or an associative array
     */
    public function __construct($elementName=null, $elementLabel=null, $attributes=null) {
        parent::__construct($elementName, $elementLabel, $attributes);
        $this->setType('editor');
    }

    /**
     * Returns HTML for this form element.
     *
     * @return string
     */
    public function toHtml() {

        // Add the class at the last minute.
        if ($this->get_force_ltr()) {
            if (!isset($this->_attributes['class'])) {
                $this->_attributes['class'] = 'text-ltr';
            } else {
                $this->_attributes['class'] .= ' text-ltr';
            }
        }

        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        }
        $html = $this->_getTabs() . '<input type="color"' . $this->_getAttrString($this->_attributes) . ' />';

        if ($this->_hiddenLabel){
            $this->_generateId();
            return '<label class="accesshide" for="'.$this->getAttribute('id').'" >'.
                        $this->getLabel() . '</label>' . $html;
        } else {
             return $html;
        }
    }

    /**
     * Export for template.
     *
     * @param renderer_base The renderer.
     * @return stdClass
     */
    public function export_for_template() {        
        $data = new \stdClass();
        $data->id = $this->getAttribute('id');
        $data->hiddenlabel = $this->_hiddenLabel;
        $data->html = $this->toHtml();

        return $data;
    }
}