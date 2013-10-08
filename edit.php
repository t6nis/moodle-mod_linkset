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
 * Prints a particular instance of linkset with editing options
 *
 * @package    mod_linkset
 * @copyright  2013 Tõnis Tartes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/edit_form.php');

$id         = optional_param('id', 0, PARAM_INT); // Course Module ID
$linkid     = optional_param('linkid', 0, PARAM_INT);
$action     = optional_param('action', '', PARAM_ALPHA);

if ($id) {
    $cm = get_coursemodule_from_id('linkset', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $linkset = $DB->get_record('linkset', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/linkset:manage', $context);

add_to_log($course->id, 'linkset', 'edit', "edit.php?id={$cm->id}", $linkset->name, $cm->id);

/// Print the page header
$PAGE->set_title(format_string($linkset->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_url('/mod/linkset/edit.php', array('id' => $id));

//set form data
$current = new object();
$current->id = $cm->id;
$current->linksetid = $linkset->id;
$current->linkid = $linkid;

$link = null;

if (!empty($action)) {
    if (linkset_handle_edit_action($linkset, $action)) {
        redirect(new moodle_url('/mod/linkset/edit.php?id='.$cm->id));
    }
    if ($action == 'edit' and $linkid) {
        // We are editing a link
        if (!$link = $DB->get_records('linkset_link_data', array('linkid' => $linkid))) {
            error('Invalid link ID');
        }
        $names = array('linkname', 'linkurl');
        foreach($link as $key => $value) {
            if (in_array($value->name, $names)) {
                $property = $value->name;
                $current->$property = $value->value;
            } 
        }
    }
}

// Create the editing form which has dual purpose - add new 
// links of any type or edit a single link of any type
$attachmentoptions = array('subdirs' => 0, 'maxbytes' => $COURSE->maxbytes, 'maxfiles' => 1, 'accepted_types' => array('*'));

$mform = new mod_linkset_edit_form(null, array('current' => $current, 'attachmentoptions' => $attachmentoptions));

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/linkset/edit.php?id='.$cm->id));
    die;
} else if ($data = $mform->get_data()) {
    linkset_save($data);
    redirect(new moodle_url('/mod/linkset/edit.php?id='.$cm->id));
    die;
}

// Output starts here
echo $OUTPUT->header();

//Intro
if ($linkset->intro) { // Conditions to show the intro can change to look for own settings or whatever
    $intro = format_module_intro('linkset', $linkset, $cm->id);
    echo $OUTPUT->box(format_string($intro));
}

//Tabs
$currenttab = 'edit';
echo linkset_tabs($currenttab, $cm->id, $context);

//Link tree
echo $OUTPUT->box_start('boxwidthwide boxaligncenter generalbox');
echo linkset_tree($linkset->id, true);
echo $OUTPUT->box_end();

// Print the form - remember it has duel purposes
echo $OUTPUT->box_start('boxwidthwide boxaligncenter generalbox');
$mform->display();
echo $OUTPUT->box_end();

echo $OUTPUT->footer();

?>