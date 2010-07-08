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

require_once '../init.php';
$Init = new Init();
$User = new User();
$Bofh = new Bofhcom();

$primary = $Bofh->getPrimary();

$View = View::create();
$View->addTitle(txt('ACCOUNT_PRIMARY_TITLE'));
$View->addElement('h1', txt('ACCOUNT_PRIMARY_TITLE'));


//checks first if account already is primary
if($primary == $User->getUsername()) {
    $View->start();
    $View->addElement('p', txt('account_primary_already', $User->getUsername()));
    die;
}

$form = new BofhForm('change_primary', null, null, null, 'class="submitonly"');
$form->addElement('submit', 'confirm', txt('account_primary_form_submit', $User->getUsername()), 'class="submit_warn"');


if($form->validate()) {
    if(setPrimary()) {
        View::forward('account/', txt('account_primary_success', $User->getUsername()));
    } else {
        View::addMessage(txt('account_primary_failed'));
    }
}



$View->start();
$View->addElement('p', txt('account_primary_intro'));
$View->addElement($form);


/**
 * This function tricks with the numbers for setting an account primary.
 */
function setPrimary() {

    global $User;
    $username = $User->getUsername();
    $user = null;

    global $Bofh;
    if(!isset($Bofh)) $Bofh = new Bofhcom();

    $priorities = $Bofh->getData('person_list_user_priorities', $username);
    $primary = $priorities[0];
    foreach($priorities as $p) {
        if($p['priority'] < $primary['priority']) $primary = $p;
        if(!$user && $p['uname'] == $username) $user = $p;
    }

    return $Bofh->run_command('person_set_user_priority', $username, $user['priority'], $primary['priority']-1);

}


?>
