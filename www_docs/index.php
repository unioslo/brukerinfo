<?php
// Copyright 2009-2015 University of Oslo, Norway
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

// Get the initial setup code. Should be imported by every php file under 
// www_docs. In directories below www_docs use '../' to locate it.
require_once 'init.php';

// Standard config
$Init = new Init();

// Bofh-communication
$Bofh = Init::get('Bofh');

// User-object, handling the authentication
$User = Init::get('User');

// View handles the output to html
$View = Init::get('View');

// Access control for shortcuts:
$Authz = Init::get('Authorization');

$mod = Init::get('Modules');
$mod->getPage();

?>

