<?php
// Copyright 2009, 2010 University of Oslo, Norway
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

class Office365 implements ModuleGroup {
    private $modules;
    public function __construct($modules) {
        $this->modules = $modules;
        //$authz = Init::get("Authorization");
        $modules->addGroup($this);
    }

    public function getName() {
        return 'office365';
    }

    public function getInfoPath() {
        return array('office365');
    }

    public function getSubgroups() {
        return array();
    }

    public function getHiddenRoutes() {
        return array();
    }

    public function getShortcuts() {
        return array();
    }

    public function display($path) {
        /**
         * Page for viewing and modifying user consent for exporting user data
         * to the Office365-cloud thingamabob.
         */


        $view = Init::get('View');
        $view->addTitle(txt('office365_title'));

        $view->start();
        $view->addElement('h1', txt('office365_title'));
        $view->addElement('p', txt('office365_test'));

    }
}
?>

