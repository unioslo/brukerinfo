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

$user_is_employee = $Bofh->isEmployee();
$user_is_guest    = $Bofh->hasTraits(array('guest_owner', 'guest_name'));

// Determine username of the guest user to show
if ($user_is_guest) {
    $guest = $Bofh->getUsername();
} elseif ($user_is_employee) {
    $guest = (!empty($_POST['g_uname'])) ? $_POST['g_uname'] : (
        (!empty($_GET['guest'])) ? $_GET['guest'] : false);
} else {
    View::forward('', txt('employees_only'));
}

if (empty($guest)) {
    View::forward('guests/', txt('guest_no_username'), View::MSG_ERROR);
}

// Get information about the guest
$guestdata = $Bofh->getData('guest_info', $guest);
if (empty($guestdata)) {
    View::forward('guests/', txt('guest_unknown_username', array('uname'=>$guest)), View::MSG_ERROR);
}
$guestinfo = array_pop($guestdata);


// Create list with html-formatted user information
$created = parseDateTime($guestinfo['created']);
$expires = parseDateTime($guestinfo['expires']);
$is_active = ($expires > new DateTime());

$infolist = View::createElement('dl', null);
//$infolist->addData(txt('guest_info_name'), $guestinfo['name']);
$infolist->addData(txt('guest_info_username'), $guestinfo['username']);
$infolist->addData(txt('guest_info_contact'), $guestinfo['contact']);
$infolist->addData(txt('guest_info_responsible'), $guestinfo['responsible']);
$infolist->addData(txt('guest_info_created'), $created->format('Y-m-d'));
$infolist->addData(txt('guest_info_expired'), $expires->format('Y-m-d'));
$infolist->addData(txt('guest_info_status'), txt('guest_status_'.$guestinfo['status']));
if ($is_active) {
    $infolist->addData(txt('guest_info_days_left'), $expires->diff(new DateTime())->days);
}

// Present...
$View->start();

$View->addElement('h2', txt('guest_info_top', array('str'=>$guestinfo['name'])));
$View->addElement($infolist);

// Add formsForms
if ($user_is_employee) {
    $passwordform = new BofhFormInline('new_guest_password');
    $passwordform->addElement('hidden', 'g_uname', $guestinfo['username']);
    $passwordform->addElement('submit', null, txt('guest_btn_resetpw'));

    $deactivateform = new BofhFormInline('deactivate_guest');
    $deactivateform->addElement('hidden', 'g_uname', $guestinfo['username']);
    $deactivateform->addElement('submit', null, txt('guest_btn_deactivate'));

    if ($passwordform->validate()) {
        if ($ret = $passwordform->process('reset_guest_password')) {
            View::forward("guests/info.php?guest=$guest", $ret);
        }
    } elseif ($deactivateform->validate()) {
        if ($ret = $deactivateform->process('deactivate_guest')) {
            View::forward("guests/info.php?guest=$guest", $ret);
        }
    }
    if ($is_active and $user_is_employee) {
        $View->addElement('div', $deactivateform, 'style="float: left;"');
        $View->addElement('div', $passwordform, 'style="float: left;"');
    }
}


/**
 * Generates a new random password for a guest, using BofhCom. PEAR Quickform 
 * handler for 'new_guest_password'.
 * 
 * @param array $data Array with the form data from 'new_guest_password' to 
 *                    process
 *
 * @return string An HTML element with information about the guest password, 
 *                and a form button to show a printer friendly password sheet.
 *         boolean false on failure.
 */
function reset_guest_password($data) {
    $bofh = Init::get('Bofh');
    try {
        $res = $bofh->run_command('user_password', $data['g_uname']);
        $pw = get_cached_password($data['g_uname']);
    } catch (XML_RPC2_FaultException $e) {
        // Not translated or user friendly, but this shouldn't happen at all: 
        View::addMessage(htmlspecialchars($e->getMessage()), View::MSG_WARNING);
            $bofh->viewError($e);
        return false;
    }

    $msg = txt('guest_new_password', array('uname'=>$data['g_uname'], 'password'=>$pw));
    if ($pw) {
        $pwbutton = new BofhFormInline('password_sheet', 'post', 'guests/print.php', '_blank');
        $pwbutton->addElement('hidden', 'u', $data['g_uname']);
        $pwbutton->addElement('submit', null, txt('guest_pw_letter_button'));
        $msg .= $pwbutton;
    }

    return $msg;
}

/**
 * Deactivates a guest user by using BofhCom. PEAR Quickform handler for 
 * 'deactivate_guest'.
 * 
 * @param array $data Array with the form data from 'deactivate_guest' to 
 *                    process
 *
 * @return string An HTML element with information about the guest password, 
 *                and a form button to show a printer friendly password sheet.
 *         boolean false on failure.
 */
function deactivate_guest($data) {
    $bofh = Init::get('Bofh');
    try {
        $bofh->run_command('guest_deactivate', $data['g_uname']);
    } catch (XML_RPC2_FaultException $e) {
        // Not translated or user friendly, but this shouldn't happen at all: 
        View::addMessage(htmlspecialchars($e->getMessage()), View::MSG_WARNING);
        return false;
    }

    return View::createElement('p', txt('guest_deactivated', array('uname'=>$data['g_uname'])));
}

?>
