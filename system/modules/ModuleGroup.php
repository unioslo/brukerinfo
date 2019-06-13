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

abstract class ModuleGroup {
    abstract public function getName();
    abstract public function getInfoPath();
    abstract public function getSubgroups();
    abstract public function getHiddenRoutes();
    abstract public function getShortcuts();
    abstract public function display($path);

    // ModuleGroup will be shown in menu as default. Override in subclass
    // if needed.
    public function showInMenu() {
        return true;
    }

    public function isUioOrUit($instance) {
        return ($instance == 'uit' || $instance == 'uio');
    }
}
?>
