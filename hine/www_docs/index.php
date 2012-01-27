<?php
// Copyright 2012 University of Oslo, Norway
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

// Start sending the html output. Can not send out headers after this line.
$View->start();
$View->addElement('p', txt('home_intro'));

if (sizeof($Bofh->getAccounts()) > 1) {
    $View->addElement('p', txt('home_specific_account'));
}

$View->addElement('h2', txt('home_shortcuts_title'));
$View->addElement('ul', array(
    View::createElement('a', txt('home_shortcuts_password'),        'account/password.php'),
    //View::createElement('a', txt('home_shortcuts_spam'), 'email/spam.php'),
    //View::createElement('a', txt('home_shortcuts_tripnote'), 'email/tripnote.php'),
    View::createElement('a', txt('home_shortcuts_members'), 'groups/'),
));

$View->addElement('h2', txt('home_about_title'));
$View->addElement('p', txt('home_about'));

// The html is automaticly ended by $View->__destruct(), but you can force it by 
// $View->end().
?>
