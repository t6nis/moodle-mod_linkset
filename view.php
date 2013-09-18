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
 * Prints a particular instance of linkmgr
 *
 * @package    mod_linkmgr
 * @copyright  2013 Tõnis Tartes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = optional_param('id', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('linkmgr', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $linkmgr = $DB->get_record('linkmgr', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = get_context_instance(CONTEXT_MODULE, $cm->id);

add_to_log($course->id, 'linkmgr', 'view', "view.php?id={$cm->id}", $linkmgr->name, $cm->id);

/// Print the page header
$PAGE->set_title(format_string($linkmgr->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_cm($cm);
$PAGE->set_url('/mod/linkmgr/view.php', array('id' => $cm->id));

// Output starts here
echo $OUTPUT->header();

if ($linkmgr->intro) { // Conditions to show the intro can change to look for own settings or whatever
    echo $OUTPUT->box(format_module_intro('linkmgr', $linkmgr, $cm->id), 'generalbox mod_introbox', 'linkmgrintro');
}

//Tabs
$currenttab = 'view';
echo linkmgr_tabs($currenttab, $cm->id, $context);

//Link tree
echo $OUTPUT->box_start('boxwidthwide boxaligncenter generalbox');
echo linkmgr_tree($linkmgr->id);
echo $OUTPUT->box_end();

// Finish the page
echo $OUTPUT->footer();