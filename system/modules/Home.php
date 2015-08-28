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

class Home extends  ModuleGroup {
    private $modules;
    public function __construct($modules) {
        $this->modules = $modules;
        $modules->addGroup($this);
    }

    public function getName() {
        return 'home';
    }

    public function getInfoPath() {
        return array('');
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
        // Start sending the html output. Can not send out headers after this line.
        $View = Init::get("View");
        $View->start();
        $View->addElement('p', txt('home_intro'));
        if (sizeof(Init::get("Bofh")->getAccounts()) > 1) {
            $View->addElement('p', txt('home_specific_account'));
        }

        // Setup shortcuts
        $View->addElement('h2', txt('home_shortcuts_title'));
        $sc = array();
        foreach ($this->modules->listShortcuts() as $s) {
            $sc[] = View::createElement(
                'a', $s[1], "index.php/" . $s[0]
            );
        }
        $View->addElement('ul', $sc);

        $View->addElement('h2', txt('home_about_title'));
        $View->addElement('p', txt('home_about'));
    }
}
?>

