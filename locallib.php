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
 * Linkset locallib functions.
 *
 * @package    mod_linkset
 * @copyright  2014 Tõnis Tartes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Show View/Edit tabs.
 * 
 * @param type $currenttab
 * @param type $cmid
 * @param type $context
 */
function linkset_tabs($currenttab, $cmid, $context) {
    
    $tabs = array();

    //View tab
    $strlink = get_string('tabview', 'linkset');
    $href = new moodle_url('/mod/linkset/view.php', array('id' => $cmid));
    $tabs[]= new tabobject('view', $href, $strlink, $strlink);
    
    //Edit tab
    if (has_capability('mod/linkset:manage', $context)) {
        $strlink = get_string('tabedit', 'linkset');
        $href = new moodle_url('/mod/linkset/edit.php', array('id' => $cmid));
        $tabs[]= new tabobject('edit', $href, $strlink, $strlink);
    }

    print_tabs(array($tabs), $currenttab);
}

/**
 * Showing the link tree.
 * 
 * @global type $DB
 * @param type $linksetid
 * @param type $editing
 * @return string
 */
function linkset_tree($linksetid, $editing = false) {
    global $DB;
    
    $links = $DB->get_records('linkset_links', array('linksetid' => $linksetid));
    if (!$links) {
        return 'No links';
    }   

    $firstlinkid = linkset_get_first_linkid($linksetid);
    
    $data = linkset_get_link_data($links, $firstlinkid);

    if (!empty($data)) {
        foreach ($data as $link) {
            $menuitem = new stdClass();
            $menuitem->id = $link[0]->linkid;
            $menuitem->title  = format_text($link[0]->value,FORMAT_HTML);
            $menuitem->url    = $link[1]->value;
            $menuitem->indent = $links[$link[0]->linkid]->indent;
            $menuitem->exclude = (isset($link[2]->name) == 'exclude' ? true : false);
            $menuitems[$link[0]->linkid] = $menuitem;
        }        
        return menuitems_to_html($menuitems, '', $linksetid, $editing);
    }
}

/**
 * Given an array of menu item object, this method will build a list.
 *
 * @param array $menuitems An array of menu item objects
 * @return string
 **/
function menuitems_to_html($menuitems, $indent = '', $linksetid, $editing = false) {

    global $OUTPUT, $CFG;
    
    $action = optional_param('action', '', PARAM_ALPHA);
    $cmid = required_param('id', PARAM_INT);
    $context = context_module::instance($cmid);
    $html = '';
    
    if ($action == 'move') {
        $moveid     = required_param('linkid', PARAM_INT);
        $alt        = s(get_string('movehere'));
        $movewidget = html_writer::link(
                        new moodle_url('/mod/linkset/edit.php?id='.$cmid.'&amp;action=movehere&amp;linkid='.$moveid.'&amp;sesskey='.sesskey().'&amp;after=%d'),
                        html_writer::tag('img', '', array('src' => $OUTPUT->pix_url('movehere')))
                    );
        $move = true;
    } else {
        $move = false;
    }
    
    $table              = new html_table();
    $table->id          = 'link-table';
    $table->width       = '80%';
    $table->tablealign  = 'center';
    $table->cellpadding = '5px';
    $table->cellspacing = '0';
    $table->data        = array();
    
    if ($move) {
        $table->head  = array(get_string('movingcancel', 'linkset', $CFG->wwwroot.'/mod/linkset/edit.php?id='.$cmid));
        $table->data[] = array($movewidget);
    } else {
        if (!$editing) {
            $table->head  = array(get_string('links_header', 'linkset'));
            $table->align = array('left');
            $table->size  = array('*');
        } else {
            $table->head  = array(get_string('actions', 'linkset'), get_string('rendered', 'linkset'));
            $table->align = array('left', 'left', '');
            $table->size  = array('95px', '*', '*');
        }
    } 
        
    foreach ($menuitems as $key => $link) {

        $item = a($link);        
        $indent = ($link->indent > 0 ? $link->indent : '');

        if (!empty($indent)) {
            $indent = $OUTPUT->spacer(array('height' => 12, 'width' => (20 * $link->indent)), false);
        }

        if (!$link->exclude) {
            $html = $indent.$item;
        } else {
            if (has_capability('mod/linkset:manage', $context)) {
                $html = $indent.$item;
            } else {
                continue;
            }
        }

        if ($move) {
            if ($moveid != $key) {
                $table->data[] = array($html);
                $table->data[] = array(sprintf($movewidget, $key));
            }
        } else {
            $widgets = array();
            if ($editing) {
                foreach (array('move', 'edit', 'delete', 'left', 'right', ($link->exclude ? 'show' : 'hide')) as $widget) {
                    $alt = s(get_string($widget, 'linkset'));
                        if ($widget == 'right') {
                            $indent = $link->indent+1;
                            $widgets[] = html_writer::link(
                                            new moodle_url('/mod/linkset/edit.php?id='.$cmid.'&amp;indent='.$indent.'&amp;action='.$widget.'&amp;linkid='.$link->id.'&amp;sesskey='.sesskey()),
                                            $OUTPUT->pix_icon('t/right', $alt)
                                        );
                        } else if ($widget == 'left') {
                            $indent = $link->indent-1;
                            $widgets[] = html_writer::link(
                                            new moodle_url('/mod/linkset/edit.php?id='.$cmid.'&amp;indent='.$indent.'&amp;action='.$widget.'&amp;linkid='.$link->id.'&amp;sesskey='.sesskey()),
                                            $OUTPUT->pix_icon('t/left', $alt)
                                        );
                        } else {
                            $widgets[] = html_writer::link(
                                            new moodle_url('/mod/linkset/edit.php?id='.$cmid.'&amp;action='.$widget.'&amp;linkid='.$link->id.'&amp;sesskey='.sesskey()),
                                            $OUTPUT->pix_icon('t/'.$widget, $alt)
                                        );
                        }
                }
                $table->data[] = array(implode('&nbsp;', $widgets), $html);
            } else {
                $table->data[] = array($html);
            }
        }
    }
    
    return html_writer::table($table);
}


/**
 * Build a link tag from a menu item.
 *
 * @param object $menuitem Menu item object
 * @param boolean $yui Add extra HTML and classes to support YUI menu
 * @return string
 **/
function a($menuitem) {
    
    global $COURSE, $CFG;
    
    // 02.09.2014 - Dont delete this..
    /*if (stristr($menuitem->url, 'http://') == TRUE) {
        $protocol = 'http';
        if (preg_match('@\b'.$protocol.'://\b@i', $menuitem->url)) {
            $menuitem->url = $CFG->wwwroot.'/file.php/'.$COURSE->id.'/'.$menuitem->url;
        }
    } else if (stristr($menuitem->url, 'https://') == TRUE) {
        $protocol = 'https';
        if (preg_match('@\b'.$protocol.'://\b@i', $menuitem->url)) {
            $menuitem->url = $CFG->wwwroot.'/file.php/'.$COURSE->id.'/'.$menuitem->url;
        }
    } else if (stristr($menuitem->url, 'mms://') == TRUE) {
        $protocol = 'mms';
        if (!preg_match('@\b'.$protocol.'://\b@i', $menuitem->url)) {
            $menuitem->url = $menuitem->url;
        }
    } else {
        $protocol = 'https';
        if (!preg_match('@\b'.$protocol.'://\b@i', $menuitem->url)) {
            $menuitem->url = $CFG->wwwroot.'/file.php/'.$COURSE->id.'/'.$menuitem->url;
        }
    }*/
    
    $title = wordwrap($menuitem->title, 210, '<br />', true);

    $cmid = optional_param('id', PARAM_INT, PARAM_CLEAN);
    $context = context_module::instance($cmid);
  
    if (!$menuitem->exclude) {
         return html_writer::link($menuitem->url, $title, array('title' => $menuitem->title, 'class' => 'menuitemlabel', 'target' => '_blank'));
    } else {
        if (has_capability('mod/linkset:manage', $context)) {
            return html_writer::link($menuitem->url, $title, array('title' => $menuitem->title, 'class' => 'menuitemlabel_hidden', 'target' => '_blank'));
        } else {
            return false;
        }
    }
}

/**
 * Saving link data.
 * 
 * @param type $data
 */
function linkset_save($data) {

    if (!empty($data->linkid)) {
        $linkid = $data->linkid;
        linkset_save_link($data, true);
    } else {
        $linkid = linkset_save_link($data);
    }
    
    $names = array('linkname', 'linkurl');
    foreach ($names as $name) {
        linkset_save_data($data, $linkid, $name, $data->$name);
    }    
}

/**
 * Saving to linkset_links.
 * 
 * @global type $DB
 * @param type $linksetid
 * @return type
 */
function linkset_save_link($data, $update = false) {
    global $DB;
    
    $link = new stdClass;
    $link->linksetid = $data->linksetid;
    $link->urltype = $data->urltype;
    
    if ($update == true && !empty($data->linkid)) {
        $link->id = $data->linkid;
        if (!$DB->update_record('linkset_links', $link)) {
            error('Failed to update link');
        }
    } else {
        $link->previd     = 0;
        $link->nextid     = 0;
        if ($lastid = linkset_get_last_linkid($link->linksetid)) {
            // Add new one after
            $link->previd = $lastid;
        }
        if (!$link->id = $DB->insert_record('linkset_links', $link)) {
            error('Failed to insert link');
        }
        // Update the previous link to look to the new link
        if ($link->previd) {
            if (!$DB->set_field('linkset_links', 'nextid', $link->id, array('id' => $link->previd))) {
                error('Failed to update link order');
            }
        }
    }

    return $link->id;
}

/**
 * Deletes a link and all associated data also maintains ordering.
 *
 * @param int $linkid ID of the link to delete
 * @return boolean
 */
function linkset_delete_link($linkid) {
    global $DB;
    
    linkset_remove_link_from_ordering($linkid);
    
    $linksetid = $DB->get_record('linkset_links', array('id' => $linkid));

    if (!$cm = get_coursemodule_from_instance('linkset', $linksetid->linksetid)) {
        return false;
    }

    $context = context_module::instance($cm->id);

    // now get rid of all files
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_linkset', 'file', $linksetid->id);
    
    if (!$DB->delete_records('linkset_link_data', array('linkid' => $linkid))) {
        error('Failed to delete link data');
    }
    if (!$DB->delete_records('linkset_links', array('id' => $linkid))) {
        error('Failed to delete link data');
    }
    
    return true;
}

/**
 * Save a piece of link data.
 *
 * @param int $linkid ID of the link that the data belongs to
 * @param string $name Name of the data
 * @param mixed $value Value of the data
 * @param boolean $unique Is the name/value combination unique?
 * @return int
 */
function linkset_save_data($mod_details, $linkid, $name, $value, $unique = false) {
    global $DB, $CFG;

    $fs = get_file_storage();

    $return = false;

    $data         = new stdClass;
    $data->linkid = $linkid;
    $data->name   = $name;
    $data->value  = $value;

    if ($unique) {
        $fieldname  = 'value';
        $fieldvalue = $data->value;
    } else {
        $fieldname = $fieldvalue = '';
    }

    $cond = "linkid = :linkid AND name = :name";
    $params = array('linkid'=>$linkid, 'name' => $name);

    // If file url is enabled.
    if (isset($mod_details->fileurl_filemanager) && $name == 'linkurl') {

        if (!$cm = get_coursemodule_from_instance('linkset', $mod_details->linksetid)) {
            return false;
        }
        
        if ($mod_details->urltype > 1) {
            $context = context_module::instance($cm->id);       
            $draftitemid = file_get_submitted_draft_itemid('fileurl_filemanager');
            file_prepare_draft_area($draftitemid, $context->id, 'mod_linkset', 'file', $data->linkid, array('subdirs'=>true, 'maxbytes'=>$CFG->maxbytes, 'maxfiles'=>1));
            file_save_draft_area_files($draftitemid, $context->id, 'mod_linkset', 'file', $data->linkid, array('subdirs'=>true, 'maxbytes'=>$CFG->maxbytes, 'maxfiles'=>1));
            $file = $fs->get_area_files($context->id, 'mod_linkset', 'file', $data->linkid, 'sortorder DESC, id ASC', false);
            // Getting the filename with extension.
            $file_details = $fs->get_file_by_hash(key($file));
            $fullpath = $CFG->wwwroot.'/pluginfile.php/'.$context->id.'/mod_linkset/file/'.$data->linkid.'/'.$file_details->get_filename();
            $data->value = $fullpath;
        } else {
            $data->value = $value;
        }
    }

    if ($id = $DB->get_field_select('linkset_link_data', 'id', $cond, $params)) {
        $data->id = $id;
        if ($DB->update_record('linkset_link_data', $data)) {
            $return = $id;
        }
    } else {
        $return = $DB->insert_record('linkset_link_data', $data);
    }

    return $return;
}

/**
 * Gets the first link ID.
 *
 * @param int $linksetid ID of a linkset instance
 * @return mixed
 */
function linkset_get_first_linkid($linksetid) {
    global $DB;
    return $DB->get_field('linkset_links', 'id', array('linksetid' => $linksetid, 'previd' => 0));
}

/**
 * Gets the last link ID.
 *
 * @param int $linksetid ID of a linkset instance
 * @return mixed
 */
function linkset_get_last_linkid($linksetid) {
    global $DB;
    return $DB->get_field('linkset_links', 'id', array('linksetid' => $linksetid, 'nextid' => 0), IGNORE_MULTIPLE);
}

/**
 * Gets link data for all passed links and organizes the records in an array keyed on the linkid.
 *
 * @param array $links An array of links with the keys = linkid
 * @return array
 */
function linkset_get_link_data($links, $firstlinkid) {    
    global $DB;
    
    $organized = array();
    $linkid = $firstlinkid;
    $ordered_links = array();
    
    while ($linkid) {
        if (array_key_exists($linkid, $links)) {
            $link = $links[$linkid];
        } else {
            $link = NULL;
        }        
        $ordered_links[$linkid] = $link;
        $linkid = $link->nextid;

    }

    foreach ($ordered_links as $key => $value) {
        if ($data = $DB->get_records_list('linkset_link_data', 'linkid', array($key))) {            
            foreach ($data as $datum) {
                if (!array_key_exists($datum->linkid, $organized)) {
                    $organized[$datum->linkid] = array();
                }
                $organized[$datum->linkid][] = $datum;
            }
        }
    }
    return $organized;
}


/**
 * Helper function to handle edit actions.
 *
 * @param object $linkset instance
 * @param string $action Action that is being performed
 * @return boolean If return true, then a redirect will occure (in edit.php at least)
 */
function linkset_handle_edit_action($linkset, $action = NULL) {
    global $CFG;

    if (!confirm_sesskey()) {
        error(get_string('confirmsesskeybad', 'error'));
    }

    $linkid = required_param('linkid', PARAM_INT);

    if ($action === NULL) {
        $action = required_param('action', PARAM_ALPHA);
    }

    switch ($action) {
        case 'edit':
        case 'move':
            return false;
            break;
        case 'movehere':
            $after = required_param('after', PARAM_INT);
            linkset_move_link($linkset, $linkid, $after);
            break;
        case 'delete':
            linkset_delete_link($linkid);
            break;
        case 'right':
            $indent = required_param('indent', PARAM_INT);
            linkset_indent_link($linkid, $indent);
            break;
        case 'left':
            $indent = required_param('indent', PARAM_INT);
            linkset_indent_link($linkid, $indent);
            break;
        case 'hide':
            link_show_hide($linkid, 'hide');
            break;
        case 'show':
            link_show_hide($linkid, 'show');
            break;
        default:
            error('Inavlid action: '.$action);
            break;
    }
    
    return true;
}

/**
 * Defines if link is visible or not.
 * 
 * @global type $DB
 * @param type $linkid
 * @param type $action
 * @return boolean
 */
function link_show_hide($linkid, $action) {
    global $DB;
    
    if ($action == 'hide') {

        $value = $DB->get_field('linkset_link_data', 'value', array('linkid' => $linkid, 'name' => 'linkurl'));

        $data         = new stdClass;
        $data->linkid = $linkid;
        $data->name   = 'exclude';
        $data->value  = $value;
        return $DB->insert_record('linkset_link_data', $data);
        
    } else if ($action == 'show') {
        $id = $DB->get_field('linkset_link_data', 'id', array('linkid' => $linkid, 'name' => 'exclude'));
        return $DB->delete_records('linkset_link_data', array('id' => $id));        
    } else {
        error('Invalid show/hide param');
        return false;
    }
}

/**
 * Define link indent.
 * 
 * @global type $DB
 * @param type $linkid
 * @param type $indent
 */
function linkset_indent_link($linkid, $indent) {
    global $DB;
    
    if ($indent >= 0 and confirm_sesskey()) {
        if (!$link = $DB->get_record('linkset_links', array('id' => $linkid))) {
            error('This link doesn\'t exist');
        }
        $link->indent = $indent;
        if ($link->indent < 0) {
            $link->indent = 0;
        }
        if (!$DB->set_field('linkset_links', 'indent', $link->indent, array('id' => $link->id))) {
            error('Could not update the indent level on that link!');
        }
    }
}

/**
 * Move a link to a new position in the ordering.
 *
 * @param object $linkset instance
 * @param int $linkid ID of the link we are moving
 * @param int $after ID of the link we are moving our link after (can be 0)
 * @return boolean
 */
function linkset_move_link($linkset, $linkid, $after) {    
    global $DB;
    
    $link = new stdClass;
    $link->id = $linkid;

    // Remove the link from where it was (Critical: this first!)
    linkset_remove_link_from_ordering($link->id);

    if ($after == 0) {
        // Adding to front - get the first link
        if (!$firstid = linkset_get_first_linkid($linkset->id)) {
            error('Could not find first link ID');
        }
        // Point the first link back to our new front link
        if (!$DB->set_field('linkset_links', 'previd', $link->id, array('id' => $firstid))) {
            error('Failed to update link ordering');
        }
        // Set prev/next
        $link->nextid = $firstid;
        $link->previd = 0;
    } else {
        // Get the after link
        if (!$after = $DB->get_record('linkset_links', array('id' => $after))) {
            error('Invalid Link ID');
        }
        // Point the after link to our new link
        if (!$DB->set_field('linkset_links', 'nextid', $link->id, array('id' => $after->id))) {
            error('Failed to update link ordering');
        }
        // Set the next link in the ordering to look back correctly
        if ($after->nextid) {
            if (!$DB->set_field('linkset_links', 'previd', $link->id, array('id' => $after->nextid))) {
                error('Failed to update link ordering');
            }
        }
        // Set next/prev
        $link->previd = $after->id;
        $link->nextid = $after->nextid;
    }

    if (!$DB->update_record('linkset_links', $link)) {
        error('Failed to update link');
    }

    return true;
}

/**
 * Removes a link from the link ordering.
 *
 * @param int $linkid ID of the link to remove
 * @return boolean
 */
function linkset_remove_link_from_ordering($linkid) {    
    global $DB;
    
    if (!$link = $DB->get_record('linkset_links', array('id' => $linkid))) {
        error('Invalid Link ID');
    }
    // Point the previous link to the one after this link
    if ($link->previd) {
        if (!$DB->set_field('linkset_links', 'nextid', $link->nextid, array('id' => $link->previd))) {
            error('Failed to update link ordering');
        }
    }
    // Point the next link to the one before this link
    if ($link->nextid) {
        if (!$DB->set_field('linkset_links', 'previd', $link->previd, array('id' => $link->nextid))) {
            error('Failed to update link ordering');
        }
    }
    return true;
}
