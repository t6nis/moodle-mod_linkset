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
 * Restore functionality
 *
 * @package    mod_linkmgr
 * @copyright  2013 Tõnis Tartes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class restore_linkmgr_activity_structure_step extends restore_activity_structure_step {
 
    protected function define_structure() {
 
        $paths = array();
        
        $userinfo = $this->get_setting_value('userinfo'); // are we including userinfo?
 
        $paths[] = new restore_path_element('linkmgr', '/activity/linkmgr');
        $paths[] = new restore_path_element('linkmgr_links', '/activity/linkmgr/linkmgr_links/link');
        $paths[] = new restore_path_element('linkmgr_link_data', '/activity/linkmgr/linkmgr_links/link/linkmgr_link_data/data');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }
 
    protected function process_linkmgr($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
 
        $data->timemodified = $this->apply_date_offset($data->timemodified);
 
        $newitemid = $DB->insert_record('linkmgr', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }
 
    protected function process_linkmgr_links($data) {
        global $DB;
 
        $data = (object)$data;
        $oldid = $data->id;
 
        $data->linkmgrid = $this->get_new_parentid('linkmgr');
 
        $newitemid = $DB->insert_record('linkmgr_links', $data);
        $this->set_mapping('linkmgr_links', $oldid, $newitemid, true);
    }
 
    protected function process_linkmgr_link_data($data) {
        global $DB;
 
        $data = (object)$data;
        $oldid = $data->id;

        $data->linkid = $this->get_new_parentid('linkmgr_links');

        $newitemid = $DB->insert_record('linkmgr_link_data', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder)
    }
 
    protected function after_execute() {
        global $DB;

        $this->add_related_files('mod_linkmgr', 'intro', null);
        
        // Remap all the restored prevpageid and nextpageid now that we have all the pages and their mappings
        $rs = $DB->get_recordset('linkmgr_links', array('linkmgrid' => $this->task->get_activityid()),
                                 '', 'id, previd, nextid');
        foreach ($rs as $page) {
            $page->previd = (empty($page->previd)) ? 0 : $this->get_mappingid('linkmgr_links', $page->previd);
            $page->nextid = (empty($page->nextid)) ? 0 : $this->get_mappingid('linkmgr_links', $page->nextid);
            $DB->update_record('linkmgr_links', $page);
        }
        
        $rs->close();
        
        $this->add_related_files('mod_linkmgr', 'file', 'linkmgr_links');
        
    }
}

?>