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
 * linkmgr library functions
 *
 * @package    mod_linkmgr
 * @copyright  2013 Tõnis Tartes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function linkmgr_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        default: return null;
    }
}

/**
 * Saves a new instance of the linkmgr into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $linkmgr An object from the form in mod_form.php
 * @param mod_linkmgr_mod_form $mform
 * @return int The id of the newly inserted linkmgr record
 */
function linkmgr_add_instance(stdClass $linkmgr, mod_linkmgr_mod_form $mform = null) {
    global $DB;

    $linkmgr->timecreated = time();

    return $DB->insert_record('linkmgr', $linkmgr);
}

/**
 * Updates an instance of the linkmgr in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $linkmgr An object from the form in mod_form.php
 * @param mod_linkmgr_mod_form $mform
 * @return boolean Success/Fail
 */
function linkmgr_update_instance(stdClass $linkmgr, mod_linkmgr_mod_form $mform = null) {
    global $DB;

    $linkmgr->timemodified = time();
    $linkmgr->id = $linkmgr->instance;

    return $DB->update_record('linkmgr', $linkmgr);
}

/**
 * Removes an instance of the linkmgr from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function linkmgr_delete_instance($id) {    
    global $DB;
    
    $result = true;

    if (!$cm = get_coursemodule_from_instance('linkmgr', $id)) {
        return false;
    }

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    // now get rid of all files
    $fs = get_file_storage();
    $fs->delete_area_files($context->id);

    if ($links = $DB->get_records('linkmgr_links', array('linkmgrid' => $id), '', 'id')) {
        $linkids = implode(',', array_keys($links));

        $result = $DB->delete_records_select('linkmgr_link_data', "linkid IN($linkids)");

        if ($result) {
            $result = $DB->delete_records('linkmgr_links', array('linkmgrid' => $id));
        }
    }
    if ($result) {
        $result = $DB->delete_records('linkmgr', array('id' => $id));
    }
    
    return $result;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function linkmgr_user_outline($course, $user, $mod, $linkmgr) {
    return false;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $linkmgr the module instance record
 * @return void, is supposed to echp directly
 */
function linkmgr_user_complete($course, $user, $mod, $linkmgr) {
    return false;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in linkmgr activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function linkmgr_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link linkmgr_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function linkmgr_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see linkmgr_get_recent_mod_activity()}

 * @return void
 */
function linkmgr_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function linkmgr_cron () {
    return false;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function linkmgr_get_extra_capabilities() {
    return array();
}

////////////////////////////////////////////////////////////////////////////////
// Gradebook API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * Is a given scale used by the instance of linkmgr?
 *
 * This function returns if a scale is being used by one linkmgr
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $linkmgrid ID of an instance of this module
 * @return bool true if the scale is used by the given linkmgr instance
 */
function linkmgr_scale_used($linkmgrid, $scaleid) {    
    return false;
}

/**
 * Checks if scale is being used by any instance of linkmgr.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param $scaleid int
 * @return boolean true if the scale is used by any linkmgr instance
 */
function linkmgr_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * Creates or updates grade item for the give linkmgr instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $linkmgr instance object with extra cmidnumber and modname property
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return void
 */
function linkmgr_grade_item_update(stdClass $linkmgr, $grades=null) {
    return false;
}

/**
 * Update linkmgr grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $linkmgr instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @return void
 */
function linkmgr_update_grades(stdClass $linkmgr, $userid = 0) {
    return false;
}

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function linkmgr_get_file_areas($course, $cm, $context) {
    $areas = array();
    $areas['file'] = get_string('fileurl', 'mod_linkmgr');
    return $areas;
}

/**
 * File browsing support for linkmgr file areas
 *
 * @package mod_linkmgr
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function linkmgr_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG;

    if (!has_capability('moodle/course:managefiles', $context)) {
        // students can not peek here!
        return null;
    }

    $fs = get_file_storage();

    if ($filearea == 'file') {
    
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        $urlbase = $CFG->wwwroot.'/pluginfile.php';
        if (!$storedfile = $fs->get_file($context->id, 'mod_linkmgr', $filearea, 0, $filepath, $filename)) {
            if ($filepath === '/' and $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_linkmgr', $filearea, 0);
            } else {
                // not found
                return null;
            }
        }

        return new linkmgr_content_file_info($browser, $context, $storedfile, $urlbase, $areas[$filearea], true, true, true, false);
        
    }
    // note: resource_intro handled in file_browser automatically

    return null;
}

/**
 * Serves the files from the linkmgr file areas
 *
 * @package mod_linkmgr
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the linkmgr's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function linkmgr_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    if ($filearea !== 'file') {
        return false;
    }

    $fileid = (int)array_shift($args);

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_linkmgr/file/$fileid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // finally send the file
    send_stored_file($file, 360, 0, false);
}

////////////////////////////////////////////////////////////////////////////////
// Navigation API                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding linkmgr nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the linkmgr module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function linkmgr_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
}

/**
 * Extends the settings navigation with the linkmgr settings
 *
 * This function is called when the context for the page is a linkmgr module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $linkmgrnode {@link navigation_node}
 */
function linkmgr_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $linkmgrnode=null) {
}

/**
 * Return a list of page types
 *
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 * @return array
 */
function linkmgr_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-linkmgr-*'=>get_string('page-mod-linkmgr-x', 'mod_linkmgr'));
    return $module_pagetype;
}