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
 * Showing linkset instance.
 *
 * @package    mod_linkset
 * @copyright  2014 Tõnis Tartes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = optional_param('id', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('linkset', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $linkset = $DB->get_record('linkset', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    error('You must specify a course_module ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/linkset:view', $context);

/// Print the page header
$PAGE->set_title(format_string($linkset->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_cm($cm);
$PAGE->set_url('/mod/linkset/view.php', array('id' => $cm->id));

// Output starts here
echo $OUTPUT->header();

if ($linkset->intro) { // Conditions to show the intro can change to look for own settings or whatever
    echo $OUTPUT->box(format_module_intro('linkset', $linkset, $cm->id), 'generalbox mod_introbox', 'linksetintro');
}

//Tabs
$currenttab = 'view';
echo linkset_tabs($currenttab, $cm->id, $context);

//Link tree
echo $OUTPUT->box_start('boxwidthwide boxaligncenter generalbox');
echo linkset_tree($linkset->id);
echo $OUTPUT->box_end();

// Finish the page
echo $OUTPUT->footer();