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
 * linkmgr locallib functions
 *
 * @package    mod_linkmgr
 * @copyright  2013 Tõnis Tartes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

//Tabs
function linkmgr_tabs($currenttab, $cmid, $context) {
    
    $tabs = array();

    //View tab
    $strlink = get_string('tabview', 'linkmgr');
    $href = new moodle_url('/mod/linkmgr/view.php', array('id' => $cmid));
    $tabs[]= new tabobject('view', $href, $strlink, $strlink);
    
    //Edit tab
    if (has_capability('mod/linkmgr:manage', $context)) {
        $strlink = get_string('tabedit', 'linkmgr');
        $href = new moodle_url('/mod/linkmgr/edit.php', array('id' => $cmid));
        $tabs[]= new tabobject('edit', $href, $strlink, $strlink);
    }

    print_tabs(array($tabs), $currenttab);
    
}

//Link tree
function linkmgr_tree($linkmgrid, $editing = false) {
    global $DB;
    
    $links = $DB->get_records('linkmgr_links', array('linkmgrid' => $linkmgrid));
    if (!$links) {
        return 'No links';
    }   

    $firstlinkid = linkmgr_get_first_linkid($linkmgrid);
    
    $data = linkmgr_get_link_data($links, $firstlinkid);

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
        
        return menuitems_to_html($menuitems, 0, false, '', $linkmgrid, $editing);
    }

}

/**
 * Given an array of menu item object, this
 * method will build a list
 *
 * @param array $menuitems An array of menu item objects
 * @param int $depth Current depth for nesting lists
 * @param boolean $yui Add extra HTML and classes to support YUI menu
 * @return string
 **/
function menuitems_to_html($menuitems, $depth = 0, $yui = false, $indent = '', $linkmgrid, $editing = false) {

    global $OUTPUT, $CFG;
    
    $action = optional_param('action', '', PARAM_ALPHA);
    $cmid = required_param('id', PARAM_INT);
    $context = get_context_instance(CONTEXT_MODULE, $cmid);
    $html = '';
    
    if ($action == 'move') {
        $moveid     = required_param('linkid', PARAM_INT);
        $alt        = s(get_string('movehere'));
        $movewidget = html_writer::link(
                        new moodle_url('/mod/linkmgr/edit.php?id='.$cmid.'&amp;action=movehere&amp;linkid='.$moveid.'&amp;sesskey='.sesskey().'&amp;after=%d'),
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
        $table->head  = array(get_string('movingcancel', 'linkmgr', $CFG->wwwroot.'/mod/linkmgr/edit.php?id='.$cmid));
        $table->wrap  = array('nowrap');
        $table->data[] = array($movewidget);
    } else {
        if (!$editing) {
            $table->head  = array(get_string('links_header', 'linkmgr'));
            $table->align = array('left');
            $table->size  = array('*');
            $table->wrap  = array('nowrap');
        } else {
            $table->head  = array(get_string('actions', 'linkmgr'), get_string('rendered', 'linkmgr'));
            $table->align = array('left', 'left', '');
            $table->size  = array('50px', '*', '*');
            $table->wrap  = array('nowrap', 'nowrap', 'nowrap');
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
            if (has_capability('mod/linkmgr:manage', $context)) {
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
                    $alt = s(get_string($widget, 'linkmgr'));
                        if ($widget == 'right') {
                            $indent = $link->indent+1;
                            $widgets[] = html_writer::link(
                                            new moodle_url('/mod/linkmgr/edit.php?id='.$cmid.'&amp;indent='.$indent.'&amp;action='.$widget.'&amp;linkid='.$link->id.'&amp;sesskey='.sesskey()),
                                            $OUTPUT->pix_icon('t/right', $alt)
                                        );
                        } else if ($widget == 'left') {
                            $indent = $link->indent-1;
                            $widgets[] = html_writer::link(
                                            new moodle_url('/mod/linkmgr/edit.php?id='.$cmid.'&amp;indent='.$indent.'&amp;action='.$widget.'&amp;linkid='.$link->id.'&amp;sesskey='.sesskey()),
                                            $OUTPUT->pix_icon('t/left', $alt)
                                        );
                        } else {
                            $widgets[] = html_writer::link(
                                            new moodle_url('/mod/linkmgr/edit.php?id='.$cmid.'&amp;action='.$widget.'&amp;linkid='.$link->id.'&amp;sesskey='.sesskey()),
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
 * Build a link tag from a menu item
 *
 * @param object $menuitem Menu item object
 * @param boolean $yui Add extra HTML and classes to support YUI menu
 * @return string
 **/
function a($menuitem, $yui = false) {

    global $COURSE, $CFG;

    $menuitem->class .= 'menuitemlabel';

    if (stristr($menuitem->url, 'http://') == TRUE) {
        $protocol = 'exists';
        if (preg_match('@\b'.$protocol.'://\b@i', $menuitem->url)) {
            $menuitem->url = $CFG->wwwroot.'/file.php/'.$COURSE->id.'/'.$menuitem->url;
        }
    } else if (stristr($menuitem->url, 'https://') == TRUE) {
        $protocol = 'exists';
        if (preg_match('@\b'.$protocol.'://\b@i', $menuitem->url)) {
            $menuitem->url = $CFG->wwwroot.'/file.php/'.$COURSE->id.'/'.$menuitem->url;
        }
    } else if (stristr($menuitem->url, 'mms://') == TRUE) {
        $protocol = 'exists';
        if (!preg_match('@\b'.$protocol.'://\b@i', $menuitem->url)) {
            $menuitem->url = $menuitem->url;
        }
    } else {
        $protocol = 'https';
        if (!preg_match('@\b'.$protocol.'://\b@i', $menuitem->url)) {
            $menuitem->url = $CFG->wwwroot.'/file.php/'.$COURSE->id.'/'.$menuitem->url;
        }
    }
    
    $title = wordwrap($menuitem->title, 113, '<br />', true);

    $cmid = optional_param('id', PARAM_INT, PARAM_CLEAN);
    $context = get_context_instance(CONTEXT_MODULE, $cmid);
  
    if (!$menuitem->exclude) {
         return html_writer::link($menuitem->url, $title, array('title' => $menuitem->title, 'class' => $menuitem->class, 'target' => '_blank'));
    } else {
        if (has_capability('mod/linkmgr:manage', $context)) {
            return html_writer::link($menuitem->url, $title, array('title' => $menuitem->title, 'class' => $menuitem->class.'_hidden', 'target' => '_blank'));
        } else {
            return false;
        }
    }
}

//Saving link data
function linkmgr_save($data) {
    
    if (!empty($data->linkid)) {
        $linkid = $data->linkid;
    } else {
        $linkid = linkmgr_new_link($data->linkmgrid);
    }
    
    $names = array('linkname', 'linkurl');
    foreach ($names as $name) {
        linkmgr_save_data($data, $linkid, $name, $data->$name);
    }
    
}

//Add new link
function linkmgr_new_link($linkmgrid) {
    global $DB;
    
    $link             = new stdClass;
    $link->previd     = 0;
    $link->nextid     = 0;
    $link->linkmgrid = $linkmgrid;
    
    if ($lastid = linkmgr_get_last_linkid($link->linkmgrid)) {
        // Add new one after
        $link->previd = $lastid;
    } else {
        $link->previd = 0; // Just make sure
    }

    if (!$link->id = $DB->insert_record('linkmgr_links', $link)) {
        error('Failed to insert link');
    }
    // Update the previous link to look to the new link
    if ($link->previd) {
        if (!$DB->set_field('linkmgr_links', 'nextid', $link->id, array('id' => $link->previd))) {
            error('Failed to update link order');
        }
    }

    return $link->id;
}

/**
 * Deletes a link and all associated data
 * Also maintains ordering
 *
 * @param int $linkid ID of the link to delete
 * @return boolean
 **/
function linkmgr_delete_link($linkid) {
    global $DB;
    
    linkmgr_remove_link_from_ordering($linkid);
    
    $linkmgrid = $DB->get_record('linkmgr_links', array('id' => $linkid));

    if (!$cm = get_coursemodule_from_instance('linkmgr', $linkmgrid->linkmgrid)) {
        return false;
    }

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    // now get rid of all files
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_linkmgr', 'file', $linkmgrid->id);
    
    if (!$DB->delete_records('linkmgr_link_data', array('linkid' => $linkid))) {
        error('Failed to delete link data');
    }
    if (!$DB->delete_records('linkmgr_links', array('id' => $linkid))) {
        error('Failed to delete link data');
    }
    return true;
}

/**
 * Save a piece of link data
 *
 * @param int $linkid ID of the link that the data belongs to
 * @param string $name Name of the data
 * @param mixed $value Value of the data
 * @param boolean $unique Is the name/value combination unique?
 * @return int
 **/
function linkmgr_save_data($mod_details, $linkid, $name, $value, $unique = false) {
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

    //IF file url is enabled
    if (isset($mod_details->fileurl) && $name == 'linkurl') {

        if (!$cm = get_coursemodule_from_instance('linkmgr', $mod_details->linkmgrid)) {
            return false;
        }

        $context = get_context_instance(CONTEXT_MODULE, $cm->id);

        $draftitemid = file_get_submitted_draft_itemid('fileurl');

        file_prepare_draft_area($draftitemid, $context->id, 'mod_linkmgr', 'file', $data->linkid, array('subdirs'=>true, 'maxbytes'=>$CFG->maxbytes, 'maxfiles'=>1));

        file_save_draft_area_files($draftitemid, $context->id, 'mod_linkmgr', 'file', $data->linkid, array('subdirs'=>true, 'maxbytes'=>$CFG->maxbytes, 'maxfiles'=>1));

        $file = $fs->get_area_files($context->id, 'mod_linkmgr', 'file', $data->linkid, 'sortorder DESC, id ASC', false); // TODO: this is not very efficient!!
        //getting the filename with extension
        $file_details = $fs->get_file_by_hash(key($file));

        $fullpath = $CFG->wwwroot.'/pluginfile.php/'.$context->id.'/mod_linkmgr/file/'.$data->linkid.'/'.$file_details->get_filename();

        $data->value = $fullpath;

    }

    if ($id = $DB->get_field_select('linkmgr_link_data', 'id', $cond, $params)) {
        $data->id = $id;
        if ($DB->update_record('linkmgr_link_data', $data)) {
            $return = $id;
        }
    } else {
        $return = $DB->insert_record('linkmgr_link_data', $data);
    }

    return $return;
}

/**
 * Gets the first link ID
 *
 * @param int $linkmgrid ID of a linkmgr instance
 * @return mixed
 **/
function linkmgr_get_first_linkid($linkmgrid) {
    global $DB;
    return $DB->get_field('linkmgr_links', 'id', array('linkmgrid' => $linkmgrid, 'previd' => 0));
}

/**
 * Gets the last link ID
 *
 * @param int $linkmgrid ID of a linkmgr instance
 * @return mixed
 **/
function linkmgr_get_last_linkid($linkmgrid) {
    global $DB;
    return $DB->get_field('linkmgr_links', 'id', array('linkmgrid' => $linkmgrid, 'nextid' => 0), IGNORE_MULTIPLE);
}

/**
 * Gets link data for all passed links and organizes the records
 * in an array keyed on the linkid.
 *
 * @param array $links An array of links with the keys = linkid
 * @return array
 **/
function linkmgr_get_link_data($links, $firstlinkid) {    
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
        
        if ($data = $DB->get_records_list('linkmgr_link_data', 'linkid', array($key))) {
            
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
 * Helper function to handle edit actions
 *
 * @param object $linkmgr Page menu instance
 * @param string $action Action that is being performed
 * @return boolean If return true, then a redirect will occure (in edit.php at least)
 **/
function linkmgr_handle_edit_action($linkmgr, $action = NULL) {
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
            linkmgr_move_link($linkmgr, $linkid, $after);
            break;
        case 'delete':
            linkmgr_delete_link($linkid);
            break;
        case 'right':
            $indent = required_param('indent', PARAM_INT);
            linkmgr_indent_link('right', $linkid, $indent);
            break;
        case 'left':
            $indent = required_param('indent', PARAM_INT);
            linkmgr_indent_link('left', $linkid, $indent);
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

//Show/hide links
function link_show_hide($linkid, $action) {
    global $DB;
    
    if ($action == 'hide') {

        $value = $DB->get_field('linkmgr_link_data', 'value', array('linkid' => $linkid, 'name' => 'linkurl'));

        $data         = new stdClass;
        $data->linkid = $linkid;
        $data->name   = 'exclude';
        $data->value  = $value;

        return $DB->insert_record('linkmgr_link_data', $data);

    } else if ($action == 'show') {

        $id = $DB->get_field('linkmgr_link_data', 'id', array('linkid' => $linkid, 'name' => 'exclude'));

        return $DB->delete_records('linkmgr_link_data', array('id' => $id));
        
    } else {
        error('Invalide showhide param');
        return false;
    }

}

//Indent link
function linkmgr_indent_link($lr = '', $linkid, $indent) {
    global $DB;
    
    if ($indent >= 0 and confirm_sesskey()) {

        if (!$link = $DB->get_record("linkmgr_links", array("id" => $linkid))) {
            error("This link doesn't exist");
        }

        $link->indent = $indent;

        if ($link->indent < 0) {
            $link->indent = 0;
        }

        if (!$DB->set_field("linkmgr_links", "indent", $link->indent, array("id" => $link->id))) {
            error("Could not update the indent level on that link!");
        }
    }

}

/**
 * Move a link to a new position in the ordering
 *
 * @param object $linkmgr Page menu instance
 * @param int $linkid ID of the link we are moving
 * @param int $after ID of the link we are moving our link after (can be 0)
 * @return boolean
 **/
function linkmgr_move_link($linkmgr, $linkid, $after) {    
    global $DB;
    
    $link = new stdClass;
    $link->id = $linkid;

    // Remove the link from where it was (Critical: this first!)
    linkmgr_remove_link_from_ordering($link->id);

    if ($after == 0) {
        // Adding to front - get the first link
        if (!$firstid = linkmgr_get_first_linkid($linkmgr->id)) {
            error('Could not find first link ID');
        }
        // Point the first link back to our new front link
        if (!$DB->set_field('linkmgr_links', 'previd', $link->id, array('id' => $firstid))) {
            error('Failed to update link ordering');
        }
        // Set prev/next
        $link->nextid = $firstid;
        $link->previd = 0;
    } else {
        // Get the after link
        if (!$after = $DB->get_record('linkmgr_links', array('id' => $after))) {
            error('Invalid Link ID');
        }
        // Point the after link to our new link
        if (!$DB->set_field('linkmgr_links', 'nextid', $link->id, array('id' => $after->id))) {
            error('Failed to update link ordering');
        }
        // Set the next link in the ordering to look back correctly
        if ($after->nextid) {
            if (!$DB->set_field('linkmgr_links', 'previd', $link->id, array('id' => $after->nextid))) {
                error('Failed to update link ordering');
            }
        }
        // Set next/prev
        $link->previd = $after->id;
        $link->nextid = $after->nextid;
    }

    if (!$DB->update_record('linkmgr_links', $link)) {
        error('Failed to update link');
    }

    return true;
}

/**
 * Removes a link from the link ordering
 *
 * @param int $linkid ID of the link to remove
 * @return boolean
 **/
function linkmgr_remove_link_from_ordering($linkid) {    
    global $DB;
    
    if (!$link = $DB->get_record('linkmgr_links', array('id' => $linkid))) {
        error('Invalid Link ID');
    }
    // Point the previous link to the one after this link
    if ($link->previd) {
        if (!$DB->set_field('linkmgr_links', 'nextid', $link->nextid, array('id' => $link->previd))) {
            error('Failed to update link ordering');
        }
    }
    // Point the next link to the one before this link
    if ($link->nextid) {
        if (!$DB->set_field('linkmgr_links', 'previd', $link->previd, array('id' => $link->nextid))) {
            error('Failed to update link ordering');
        }
    }
    return true;
}