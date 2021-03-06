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
 * Editing form.
 *
 * @package    mod_linkset
 * @copyright  2014 Tõnis Tartes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/linkset/locallib.php');
require_once($CFG->libdir.'/filelib.php');

class mod_linkset_edit_form extends moodleform {

    function definition() {
        
        global $CFG, $COURSE;
        
        $mform =& $this->_form;
        
        $attachmentoptions = $this->_customdata['attachmentoptions'];
        $current = $this->_customdata['current'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'linksetid');
        $mform->setType('linksetid', PARAM_INT);

        $mform->addElement('text', 'linkname', get_string('linkname', 'linkset'), array('size'=>'47'));
        $mform->setType('linkname', PARAM_TEXT);
        $mform->addRule('linkname', get_string('missinglinkname', 'linkset'), 'required', null, 'server');
        
        $mform->addElement('select', 'urltype', get_string('urltype', 'linkset'), array('1' => 'URL', '2' => 'FILE'));
        
        $mform->addElement('text', 'linkurl', get_string('linkurl', 'linkset'), array('size' => '47')); 
        $mform->setType('linkurl', PARAM_URL);
        $mform->setDefault('linkurl', 'http://');      
        $mform->addHelpButton('linkurl', 'linkurl', 'linkset');
        // Disable my control if a checkbox is checked.
        $mform->disabledIf('linkurl', 'urltype', 'eq', 2);
        
        $mform->addElement('filemanager', 'fileurl_filemanager', get_string('fileurl', 'linkset'), null, $attachmentoptions);
        $mform->disabledIf('fileurl_filemanager', 'urltype', 'eq', 1);
        
        $mform->addElement('hidden', 'linkid');
        $mform->setType('linkid', PARAM_INT);

        $this->add_action_buttons();

        $this->set_data($current);
        
    }
    
    // Validation.
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        $needles = array('http://', 'https://', 'mms://');
        $data['linkurl'] = trim(htmlspecialchars($data['linkurl']));

        if (!preg_match('/^(http|https|mms):\/\//', $data['linkurl'])) {
            $errors['linkurl'] = get_string('err_linkurl', 'linkset', $needles);
        }
        
        return $errors;
    }
}