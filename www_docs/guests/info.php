<?php
// Copyright 2012-2014 University of Oslo, Norway
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

$View->addTitle(txt('guest_title'));

// Determine username of the guest user to show
if ($Authz->is_guest()) {
    $guest = $Bofh->getUsername();
} elseif ($Authz->can_create_guests()) {
    $guest = (!empty($_POST['g_uname'])) ? $_POST['g_uname'] : (
        (!empty($_GET['guest'])) ? $_GET['guest'] : false);
} else {
    View::forward('', txt('guests_create_no_access'));
}

// No guest data
if (empty($guest)) {
    View::forward('guests/', txt('guest_no_username'), View::MSG_ERROR);
}

// Clean input string (XSS)
if (!preg_match("/^[-A-Za-z0-9]+$/", $guest)) {
    View::forward(
        'guests/',
        txt('guest_unknown_username', array('uname'=>htmlspecialchars($guest))),
        View::MSG_ERROR
    );
}

// Create forms (reset password, delete guest account)
// A hidden field with the guest username, common for both forms
$hidden = array('g_uname' => $guest);
$delform = buildButtonForm('del_guest', txt('guest_btn_deactivate'), $hidden);
$passform = buildButtonForm('new_pass', txt('guest_btn_resetpw'), $hidden);

// Try to process POST requests if they validate
if ($Authz->can_create_guests()) {
    if ($delform->validate()) {
        if ($ret = $delform->process('bofhDisableGuest')) {
            $View->addMessage($ret);
        }
    } elseif ($passform->validate()) {
        if ($ret = $passform->process('bofhResetPassword')) {
            $View->addMessage($ret);
        }
    }
}

// Get information about the guest
$guestdata = $Bofh->getData('guest_info', $guest);
if (empty($guestdata)) {
    View::forward(
        'guests/',
        txt('guest_unknown_username', array('uname'=>$guest)),
        View::MSG_ERROR
    );
}
$guestinfo = array_pop($guestdata);

$created = ($guestinfo['created']) ? $guestinfo['created'] : new DateTime('@0');
$expires = ($guestinfo['expires']) ? $guestinfo['expires'] : new DateTime('@0');
$is_active = ($expires > new DateTime);

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

// Present page.
$View->start();

$View->addElement('h2', txt('guest_info_top', array('str'=>$guestinfo['name'])));
$View->addElement($infolist);

// Add buttons
if ($Authz->can_create_guests() && $is_active) {
    $View->addElement('div', $delform, 'style="float: left;"');
    if (!empty($guestinfo['contact'])) {
        $View->addElement('div', $passform, 'style="float: left;"');
    }
}

/**
 * Build a simple form that consists of a button, and a series of hidden fields.
 *
 * @param string $name   The ID to give the form
 * @param string $label  The button label
 * @param array  $hidden An array of hidden fields, each entry is a
 *                       <name> => <value> mapping.
 *
 * @return BofhFormInline The form
 */
function buildButtonForm($name, $label, array $hidden)
{
    $form = new BofhFormInline($name);
    foreach ($hidden as $name => $value) {
        $form->addElement('hidden', $name, $value);
    }
    $form->addElement('submit', null, $label);
    return $form;
}

/**
 * Generates a new random password for a guest, using BofhCom. PEAR Quickform
 * handler for 'new_guest_password'.
 *
 * @param array $data Array with the form data from 'new_guest_password'
 *
 * @return string|boolean A text (potentially HTML) that explains the change
 *                        that was performed.
 *                        Returns false on failure.
 */
function bofhResetPassword($data)
{
    $bofh = Init::get('Bofh');
    try {
        $res = $bofh->run_command('guest_reset_password', $data['g_uname']);
    } catch (XML_RPC2_FaultException $e) {
        // Not translated or user friendly, but this shouldn't happen at all:
        $bofh->viewError($e);
        return false;
    }
    return txt(
        'guest_new_password', array(
            'uname' => $res['username'], 'mobile' => $res['mobile']
        )
    );
}

/**
 * Deactivates a guest user by using BofhCom. PEAR Quickform handler for
 * 'deactivate_guest'.
 *
 * @param array $data Array with the form data from 'deactivate_guest'
 *
 * @return string|boolean A text (potentially HTML) that explains the change
 *                        that was performed.
 *                        Returns false on failure.
 */
function bofhDisableGuest($data)
{
    $bofh = Init::get('Bofh');
    try {
        $bofh->run_command('guest_remove', $data['g_uname']);
    } catch (XML_RPC2_FaultException $e) {
        // Not translated or user friendly, but this shouldn't happen at all:
        $bofh->viewError($e);
        return false;
    }
    return txt('guest_deactivated', array('uname'=>$data['g_uname']));
}
?>
