<?php
# Copyright 2009, 2010 University of Oslo, Norway
# 
# This file is part of Cerebrum.
# 
# Cerebrum is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# Cerebrum is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with Cerebrum. If not, see <http://www.gnu.org/licenses/>.

//init.php is in the doc_root - in subdirs use '../' to locate it
require_once 'init.php';

//standard config
$Init = new Init();

//getting the bofh-communication
$Bofh = new Bofhcom();

//getting the user-object
$User = new User();



//gets the html
$View = View::create();

$View->start();

//output of html
//$View->addElement('h1', txt('HOME_TITLE'));

$View->addElement('p', txt('home_intro'));

if($Bofh->getAccounts()) {
    $View->addElement('p', txt('home_specific_account', $User->getUsername()));
}

$View->addElement('h2', txt('home_shortcuts_title'));
$View->addElement('ul', array(
    View::createElement('a', txt('home_shortcuts_password'),        'account/password.php'),
    //View::createElement('a', txt('home_shortcuts_printing'),         'printing/'),
    View::createElement('a', txt('home_shortcuts_printing_history'), 'printing/history.php'),
    View::createElement('a', txt('home_shortcuts_spam'), 'email/spam.php'),
    View::createElement('a', txt('home_shortcuts_tripnote'), 'email/tripnote.php'),
    View::createElement('a', txt('home_shortcuts_members'), 'groups/'),
    View::createElement('a', txt('home_shortcuts_group_request'), 'groups/new.php'),
));

$View->addElement('h2', txt('home_about_title'));
$View->addElement('p', txt('home_about'));

//the html is automaticly ended by $View->__destruct()
?>
