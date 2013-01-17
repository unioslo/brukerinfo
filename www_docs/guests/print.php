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
 * Printer-friendly page for displaying a guest users username and password.
 */
require_once '../init.php';
require 'guest_helper_func.php';

$Init = new Init();
$User = Init::get('User');
$Bofh = Init::get('Bofh');

// For simplicity
$guest = $_POST['u'];

// For Quickform token/validate
$form = new BofhFormInline('password_sheet');

if (!$Bofh->isEmployee()) {
    View::forward('', txt('employees_only'));
} elseif (empty($guest)) {
    View::forward('guests/', txt('guest_no_username'), View::MSG_ERROR);
} elseif ($form->validate()) {
    if (!$pw = get_cached_password($guest)) {
        View::forward('guests/', txt('guest_pw_not_cached'), View::MSG_ERROR);
    } else {
        echo txt('guest_pw_letter', array('uname'=>$guest, 'password'=>$pw));
    }
} else {
    // You shouldn't be here at all
    View::forward('');
}

?>
