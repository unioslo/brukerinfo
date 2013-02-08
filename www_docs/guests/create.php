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

// Javascript HTML tag to show/hide elements in the form
$View->addElement(
    'raw', <<<SCRIPT
<script type="text/javascript">
    function update_phone_input() {
        var mobile_input = $('#mobile_input').parent().parent();
        if ($('#mobile_yes').prop('checked')) {
            mobile_input.show();
        } else {
            mobile_input.hide();
        }
    }
    // Set initial state:
    update_phone_input();
</script>
SCRIPT
);


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

    // Radio-buttons
    $duration = array(7=>txt('general_timeinterval_week', array('num'=>1)), 
                      30=>txt('general_timeinterval_month', array('num'=>1)), 
                      90=>txt('general_timeinterval_months', array('num'=>3)), 
                      180=>txt('general_timeinterval_months', array('num'=>6)), 
                      365=>txt('general_timeinterval_year', array('num'=>1))
                  );
    $radio = array();
    foreach ($duration as $val=>$text) {
        //$radio[] = View::createElement('radio', null, null, txt('guest_new_form_days', array('days'=>$opt)), $opt);
        $radio[] = BofhFormUiO::createElement('radio', null, null, $text, $val);
    }
    $form->addGroup($radio, 'g_days', txt('guest_new_form_duration'), '<br />');

    $no = BofhFormUiO::createElement('radio', null, null, txt('guest_new_form_nosms'), 'n', array('onchange'=>'update_phone_input();'));
    $yes = BofhFormUiO::createElement('radio', null, null, txt('guest_new_form_sms'), 'y', array('id'=>'mobile_yes', 'onchange'=>'update_phone_input();'));

    $form->addGroup(array($no, $yes), 'g_notify', txt('guest_new_form_send_sms'), '<br />');
    $form->addElement('text', 'g_contact', txt('guest_new_form_contact'), array('id'=>'mobile_input'));

    $form->addElement('submit', null, txt('guest_new_form_submit'));
    
    // Inputs that require content
    $form->addRule('g_fname', txt('guest_new_form_fname_req'), 'required');
    $form->addRule('g_lname', txt('guest_new_form_lname_req'), 'required');
    $form->addRule('g_days',  txt('guest_new_form_duration_req'), 'required');
    $form->addRule('g_notify', txt('guest_new_form_notify_req'), 'required');

    // Limit name lengths. It should be possible to make a rule spanning
    // both inputs, with a validation rule callback function, but it's 
    // easier to enforce max limits of 255 chars for fname and lname 
    // (cerebrum limitation of 512 chars, bofhd will throw an error if 
    // fname+lname > 512 - 1 chars).
    $form->addRule('g_fname', txt('guest_new_form_name_fmt', array('min'=>2, 'max'=>255)), 'rangelength', array(2,255));
    $form->addRule('g_lname', txt('guest_new_form_name_fmt', array('min'=>1, 'max'=>255)), 'rangelength', array(1,255));

    // Require 8 digit phone number, if entered
    // FIXME: The error message will show next to the radio buttons, because 
    // g_notify is first in the array. If we swap around the order of the 
    // array, the rule won't be checked if g_contact is empty. This is because 
    // addRule doesn't handle arrays properly: addGroupRule should be used for 
    // this purpose. However, addGroupRule adds UI and other logical constraits 
    // that we don't want.  Maybe this will work better with QuickForm 2?
    $form->addRule(array('g_notify', 'g_contact'), txt('guest_new_form_contact_fmt'), 'callback', 'check_mobile');
    
    // Trim all input prior to validation, and set radio button default
    $form->applyFilter('__ALL__', 'trim');
    $form->setDefaults(array('g_days'=>30, 'g_notify'=>'n'));

    return $form;
}


/**
 * Callback function for controlling that the cell phone number is entered 
 * correctly, if the option for sending SMS is selected
 *
 * @param array $data Array containing the arguments for g_nofity and g_contact:
 *                    $data[0] will contain the value of g_notify ('y' , 'n' or 
 *                    an empty array (no radio button selected))
 *                    $data[1] will contain the value of g_contact.
 *
 * @return boolean   true if mobile number is valid, or if selected to _not_ 
 *                   send SMS.  Otherwise false
 */
function check_mobile($data) {
    $notify = $data[0];
    $number = $data[1];
    if ($notify == 'y' and preg_match('/^[\d]{8}$/', $number)) {
        return true;
    } elseif ($notify == 'n') {
        return true;
    }
    // else
    return false;
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

    // Clear g_contact if g_notify is set to 'n' (Happens if the user fills in a number, and then chooses 'no'
    if ($data['g_notify'] != 'y') {
        $data['g_contact'] = '';
    }
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

