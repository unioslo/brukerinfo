<?php
// Copyright 2011 University of Oslo, Norway
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

/**
 * Page for viewing and modifying reservations.
 */
require_once '../init.php';

$Init = new Init();
$User = Init::get('User');
$Bofh = Init::get('Bofh');
$View = Init::get('View');
$Authz = Init::get('Authorization');

if (!$Authz->can_create_guests()) {
    View::forward('', txt('guests_create_no_access'));
}

$View->addTitle(txt('guest_title'));
if (!$Bofh->isEmployee()) View::forward('', txt('employees_only'));

$show_expired = isset($_GET['show-expired']);

// Run bofhd-command guest_list <operator>
$guests = $Bofh->getData('guest_list');

$View->start();
$View->addElement('h1', txt('guest_title'));
$View->addElement('p', txt('guest_intro'));

// Create html tables
$active_guests = $View->createElement('table', null, 'class="app-table"');
$active_guests->setHead(
    array(
        txt('guest_list_col_username'),
        txt('guest_list_col_name'),
        txt('guest_list_col_end_date'),
    )
);
$inactive_guests = $View->createElement('table', null, 'class="app-table"');
$inactive_guests->setHead(
    array(
        txt('guest_list_col_username'),
        txt('guest_list_col_name'),
        txt('guest_list_col_end_date'),
    )
);


// Sort guests:
usort($guests, 'sort_by_name');


// TODO: Sort by username? Status? Time left?
// Add guests to table
foreach ($guests as $i => $guest) {
    $data = array(
        View::createElement('a', $guest['username'], "guests/info.php?guest=".$guest['username']),
        $guest['name'],
        (!empty($guest['expires'])) ? $guest['expires']->format('y-m-d') : '',
    );
    if ($guest['status'] == 'active') {
        $active_guests->addData($data);
    } else {
        $inactive_guests->addData($data);
    }
}

// Show list of active guest users
$View->addElement('h2', txt('guest_list_active'));
if ($active_guests->rowCount() > 0) {
    $View->addElement($active_guests);
} else {
    $View->addElement('p', txt('guest_list_active_empty'));
}

// If selected, show list of inactive guest users
//if ($show_expired) {
    //$View->addElement('h2', txt('guest_list_inactive'));
    //if ($inactive_guests->rowCount() > 0) {
        //$View->addElement($inactive_guests);
    //} else {
        //$View->addElement('p', txt('guest_list_inactive_empty'));
    //}
//} else {
    //$View->addElement('a', txt('guest_list_show_inactive'), 'guests/?show-expired');
//}

/**
 * Sort by name for the multidimensional array $guests. Case-insensitive. 
 *
 * @param array $guest_a One element from the $guests array
 * @param array $guest_b One element from the $guests array
 *
 * @return int  -1 if $guest_a <  $guest_b
 *               0 if $guest_a == $guest_b
 *               1 if $guest_a >  $guest_b
 */
function sort_by_name($guest_a, $guest_b) {
    return strcmp(strtolower($guest_a['name']), strtolower($guest_b['name']));
}

?>
