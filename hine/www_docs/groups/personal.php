<?php
// Copyright 2009, 2010, 2011 University of Oslo, Norway
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

require_once '../init.php';
$Init = new Init();
$User = Init::get('User');
$Bofh = new Bofhcom();

if (hasPersonal()) {
    View::forward('groups/index.php', txt('groups_personal_already'));
}

$form = new BofhForm('makePersonal', null, null, null, 'class="submitonly"');
$form->addElement('submit', 'add', txt('groups_personal_submit'));

if ($form->validate()) {
    if ($Bofh->getData('group_personal', $User->getUsername())) {
        View::forward('groups/', txt('groups_personal_success'));
    } else {
        View::forward('groups/personal.php');
    }
}

$View = Init::get('View');
$View->addTitle('Groups');
$View->addTitle(txt('GROUPS_PERSONAL_TITLE'));

$View->start();
$View->addElement('h1', txt('GROUPS_PERSONAL_TITLE'));
$View->addElement('p', txt('GROUPS_PERSONAL_INFO', $User->getUsername()));

$View->addElement($form);

$View->addElement('p', txt('action_delay_hour'), 'class="ekstrainfo"');

/**
 * Checks if the user has a personal group.
 *
 * @return True if user has personal group, false if not.
 */
function hasPersonal()
{
    global $User;
    global $Bofh;

    try {
        $Bofh->run_command('group_list', $User->getUsername());
    } catch(Exception $e) {
        return false;
    }
    return true;
}
?>
