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

require_once($CFG->dirroot . '/mod/linkmgr/backup/moodle2/restore_linkmgr_stepslib.php'); // Because it exists (must)

class restore_linkmgr_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        $this->add_step(new restore_linkmgr_activity_structure_step('linkmgr_structure', 'linkmgr.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('linkmgr', array('intro'), 'linkmgr');
        $contents[] = new restore_decode_content('linkmgr_link_data', array('value'));
        
        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('linkmgrINDEX', '/mod/linkmgr/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('linkmgrVIEWBYID', '/mod/linkmgr/view.php?id=$1', 'course_module');        
        
        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * folder logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('linkmgr', 'add', 'view.php?id={course_module}', '{linkmgr}');
        $rules[] = new restore_log_rule('linkmgr', 'edit', 'edit.php?id={course_module}', '{linkmgr}');
        $rules[] = new restore_log_rule('linkmgr', 'view', 'view.php?id={course_module}', '{linkmgr}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('linkmgr', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
    
    /*
     * After restore id & hash updates
     */
    public function after_restore() {
        
        global $CFG, $DB;

        $id = $this->get_activityid();
        //init file storage
        $fs = get_file_storage();
        
        $cm = get_coursemodule_from_instance('linkmgr', $id);

        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        
        //get all related links
        $links = $DB->get_records('linkmgr_links', array('linkmgrid' => $id), '', 'id');

        foreach ($links as $value) {
  
            $linkurl = $DB->get_record_select('linkmgr_link_data', 'linkid = '.$value->id.' AND name = \'linkurl\'');
            //if linkmgr to an internal moodle file, update contextid and hash else no.
            if (stristr($linkurl->value, '/mod_linkmgr/file/')) {                 
                //get new pathnamehash
                $pathnamehash = $DB->get_record_select('files', 'contextid = '.$context->id.' AND itemid = '.$linkurl->linkid.' AND component = \'mod_linkmgr\' AND filearea = \'file\' AND mimetype IS NOT NULL', null, 'pathnamehash');
                //get file details
                $file_details = $fs->get_file_by_hash($pathnamehash->pathnamehash);
                //overwrite linkvalue
                $linkurl->value = $CFG->wwwroot.'/pluginfile.php/'.$context->id.'/mod_linkmgr/file/'.$linkurl->linkid.'/'.$file_details->get_filename();
                //update link data
                $DB->update_record('linkmgr_link_data', $linkurl);
            }
            
        }
        
    }
    
}