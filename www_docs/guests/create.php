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

$View->addTitle(txt('guest_title'));
if (!$Bofh->isEmployee()) View::forward('', txt('employees_only'));

$guestform = create_guest_form();
if ($guestform->validate()) {
    if ($ret = $guestform->process('create_guest')) {
        View::forward('guests/create.php', $ret);
    }
}
$View->setFocus('#guest_fname');

// Present page
$View->start();
$View->addElement('h1', txt('guest_new_title'));
$View->addElement('p', txt('guest_new_intro'));
$View->addElement($guestform);

/**
 * Creates an HTML-form for creating guest users.
 * 
 * @return string HTML form element.
 */
function create_guest_form() {

    // Create guest form
    $form = new BofhFormUiO('new_guest');
    $form->setAttribute('class', 'app-form-big');
    $form->addElement('text', 'g_fname', txt('guest_new_form_fname'), 'id="guest_fname"');
    $form->addElement('text', 'g_lname', txt('guest_new_form_lname'));
    $form->addElement('text', 'g_contact', txt('guest_new_form_contact'));

    // Radio-buttons
    $duration = array(7, 30, 90, 180, 365);
    $radio = array();
    foreach ($duration as $opt) {
        $radio[] = $form->createElement('radio', null, null, txt('guest_new_form_days', array('days'=>$opt)), $opt);
    }
    $form->addGroup($radio, 'g_days', txt('guest_new_form_duration'), '<br />');
    $form->addElement('submit', null, txt('guest_new_form_submit'));
    
    // Inputs that require content
    $form->addRule('g_fname', txt('guest_new_form_fname_req'), 'required');
    $form->addRule('g_lname', txt('guest_new_form_lname_req'), 'required');
    $form->addRule('g_days',  txt('guest_new_form_duration_req'), 'required');

    // Limit name lengths. It should be possible to make a rule spanning
    // both inputs, with a validation rule callback function, but it's 
    // easier to enforce max limits of 255 chars for fname and lname 
    // (cerebrum limitation of 512 chars, bofhd will throw an error if 
    // fname+lname > 512 - 1 chars).
    $form->addRule('g_fname', txt('guest_new_form_name_fmt', array('min'=>2, 'max'=>255)), 'rangelength', array(2,255));
    $form->addRule('g_lname', txt('guest_new_form_name_fmt', array('min'=>1, 'max'=>255)), 'rangelength', array(1,255));

    // Require 8 digit phone number, if entered
    $form->addRule('g_contact', txt('guest_new_form_contact_fmt'), 'regex', '/^[\d]{8}$/');

    // Trim all input prior to validation, and set radio button default
    $form->applyFilter('__ALL__', 'trim');
    $form->setDefaults(array('g_days'=>30));

    return $form;
}


/**
 * Creates a new guest user using BofhCom.
 * 
 * @param array $data Array with the form data from BofhFormUiO('new_guest') to 
 *                    process
 *
 * @return string An HTML element with information about the new guest account, 
 *                and a form button to show a printer friendly password sheet.
 *         boolean false on failure.
 */
function create_guest($data) {
    $bofh = Init::get('Bofh');
    try {
        $res = $bofh->run_command('guest_create', $data['g_days'], $data['g_fname'], $data['g_lname'], 'guest', $data['g_contact']);
    } catch (XML_RPC2_FaultException $e) {
        // Error message. Not translated or user friendly, but this shouldn't happen at all: 
        View::addMessage(htmlspecialchars($e->getMessage()), View::MSG_WARNING);
        return false;
    }

    // Success, SMS sent to user
    if (!empty($res['sms_to'])) {
        return txt('guest_created_sms', array('uname'=>$res['username'],'mobile'=>$res['sms_to']));
    }

    // SMS not set (mobile not given). Fetch password for display
    try {
        $pw = $bofh->getCachedPassword($res['username']);
    } catch (XML_RPC2_FaultException $e) {
        return txt('guest_created_pw', array('uname'=>$res['username'], 'password'=>''));
    } 

    $msg = txt('guest_created_pw', array('uname'=>$res['username'], 'password'=>$pw));
    $pwbutton = new BofhFormInline('password_sheet', 'post', 'guests/print.php', '_blank');
    $pwbutton->addElement('hidden', 'u', $res['username']);
    $pwbutton->addElement('submit', null, txt('guest_pw_letter_button'));
    $msg .= $pwbutton;
    return $msg;
}

?>

