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
include 'guest_helper_func.php';

$Init = new Init();
$User = Init::get('User');
$Bofh = Init::get('Bofh');
$View = Init::get('View');

$View->addTitle(txt('guest_title'));
if (!$Bofh->isEmployee()) View::forward('', txt('employees_only'));

// Run bofhd-command guest_list <operator>
$guests = $Bofh->getData('guest_list');

$View->start();
$View->addElement('h1', txt('guest_title'));
$View->addElement('p', txt('guest_intro'));

// Create html table
$guesttable = $View->createElement('table', null, 'class="app-table"');
$guesttable->setHead(
    array(
        txt('guest_list_col_username'),
        txt('guest_list_col_name'),
        txt('guest_list_col_end_date'),
        txt('guest_list_col_status'),
    )
);

// TODO: Sort by username? Status? Time left?
// Add guests to table
foreach ($guests as $i => $guest) {
    $created = parseDateTime($guest['created']);
    $expires = parseDateTime($guest['expires']);
    $guesttable->addData(
        array(
            View::createElement('a', $guest['username'], "guests/info.php?guest=".$guest['username']),
            $guest['name'],
            $expires->format('Y-m-d'),
            txt('GUEST_STATUS_'.$guest['status']),
        )
    );
}

// Add table to View
if (empty($guests)) {
    $View->addElement('h2', txt('guest_list_empty'));
} else {
    $View->addElement('h2', txt('guest_list_personal'));
    $View->addElement($guesttable);
}

?>
