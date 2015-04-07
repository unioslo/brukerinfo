<?php
// Copyright 2015 University of Oslo, Norway
// 
// This file is part of Cerebrum.
// 
// Cerebrum is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// 
// Cerebrum is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with Cerebrum. If not, see <http://www.gnu.org/licenses/>.

/* Class to handle modules. A module is a logical grouping of functionality.
 *
 * The general idea is to use $_SERVER['PATH_INFO'] (e.g.
 * http://brukerinfo.uio.no/index.php/group_name -> "/group_name")
 * and map this to subpages.
 */
class Modules {

    protected $groups;
    protected $mapping;

    public function __construct() {
        $this->groups = array();
    }

    private function getMapping() {
        if (!isset($this->mapping)) {
            $this->mapping = array();
            foreach ($this->groups as $group) {
                foreach ($group->getInfoPath() as $path) {
                    $this->mapping[$path] = $group;
                    if ($path != '') {
                        foreach ($group->getSubGroups() as $grp) {
                            $this->mapping["$path/$grp"] = $group;
                        }
                    }
                }
            }
        }
        return $this->mapping;
    }
    /* Groups are normally on the pages represented by tabbing look and feel.
     * Return array(name => link)
     */
    public function listGroups() {
        if (!Init::get("Authorization")->is_authenticated()) {
            return;
        }
        $mapping = $this->getMapping();
        return $this->groups;
    }

    public function listSubgroups($group) {
        return $group->getSubgroups();
    }

    /* Shortcuts go on the front page, but depends on the groups */
    public function listShortcuts() {
        $shortcuts = array();
        foreach ($this->groups as $grp) {
            $shortcuts = array_merge($shortcuts, $grp->getShortcuts());
        }
        return $shortcuts;
    }

    public function getCurrentGroup($path) {
        $parts = explode("/", $path);
        array_shift($parts);
        if (!isset($this->mapping)) {
            $this->getMapping();
        }
        $grname = count($parts) > 0 ? $parts[0] :'';
        return $this->mapping[$grname];
    }

    public function getPage($path) {
        $parts = explode("/", $path);
        array_shift($parts);
        $grp = $this->getCurrentGroup($path);
        array_shift($parts);
        if ($parts && !in_array($parts[0], $grp->getSubgroups())) {
            View::forward('', txt('error_subgroup_no_route'));
        }
        return $grp->display($parts);
    }

    public function addGroup($group) {
        $this->groups[] = $group;
    }
}

