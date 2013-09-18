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
 * Backup functionality
 *
 * @package    mod_linkmgr
 * @copyright  2013 Tõnis Tartes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class backup_linkmgr_activity_structure_step extends backup_activity_structure_step {
    
    protected function define_structure() {
        
        // Define each element separated
        $linkmgr = new backup_nested_element('linkmgr', array('id'), array(
            'name', 'intro', 'introformat', 'timemodified'
        ));
        
        $links = new backup_nested_element('linkmgr_links');
        
        $link = new backup_nested_element('link', array('id'), array(
            'linkmgrid', 'previd', 'nextid', 'indent'
        ));
        
        $links_data = new backup_nested_element('linkmgr_link_data');
        
        $link_data = new backup_nested_element('data', array('id'), array(
            'linkid', 'name', 'value'
        ));
        
        // Build the tree
        $linkmgr->add_child($links);
        $links->add_child($link);
 
        $link->add_child($links_data);
        $links_data->add_child($link_data);

        // Define sources
        $linkmgr->set_source_table('linkmgr', array('id' => backup::VAR_ACTIVITYID));

        $link->set_source_table('linkmgr_links', array('linkmgrid' => backup::VAR_PARENTID));

        // All the rest of elements only happen if we are including user info
        $link_data->set_source_table('linkmgr_link_data', array('linkid' => backup::VAR_PARENTID));
        
        // Define file annotations
        $linkmgr->annotate_files('mod_linkmgr', 'intro', null); // This file area hasn't itemid
        $link_data->annotate_files('mod_linkmgr', 'file', null);
        
        // Return the root element (linkmgr), wrapped into standard activity structure
        return $this->prepare_activity_structure($linkmgr);
        
    }

}
?>