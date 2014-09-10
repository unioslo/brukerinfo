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

/* Has access to create guests? */
if (!$Authz->can_create_guests()) {
    View::forward('', txt('guests_create_no_access'));
}

$View->addTitle(txt('guest_title'));

$guestform = buildForm();
if ($guestform->validate()) {
    if ($ret = $guestform->process('bofhCreateGuest')) {
        View::forward('guests/create.php', $ret);
    }
    // else, error. The bofhCreateGuest function should add an error message to
    // the page.
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
function buildForm()
{
    // Create guest form
    $form = new BofhFormUiO('new_guest');
    $form->setAttribute('class', 'app-form-big');

    /* First and last name */
    $form->addElement(
        'text', 'g_fname', txt('guest_new_form_fname'), 'id="guest_fname"'
    );
    $form->addElement('text', 'g_lname', txt('guest_new_form_lname'));

    /* Expire date selections */
    $duration = array(  7=>txt('general_timeinterval_week', array('num'=>1)),
                       30=>txt('general_timeinterval_month', array('num'=>1)),
                       90=>txt('general_timeinterval_months', array('num'=>3)),
                      180=>txt('general_timeinterval_months', array('num'=>6)),
                  );
    $radio = array();
    foreach ($duration as $val=>$text) {
        $radio[] = BofhFormUiO::createElement('radio', null, null, $text, $val);
    }
    $form->addGroup($radio, 'g_days', txt('guest_new_form_duration'), '<br />');

    $form->addElement('text', 'g_contact', txt('guest_new_form_contact'));
    $form->addElement('submit', null, txt('guest_new_form_submit'));

    // Inputs that require content
    $form->addRule('g_fname', txt('guest_new_form_fname_req'), 'required');
    $form->addRule('g_lname', txt('guest_new_form_lname_req'), 'required');
    $form->addRule('g_days',  txt('guest_new_form_duration_req'), 'required');
    $form->addRule('g_contact', txt('guest_new_form_contact_req'), 'required');

    /* Limit name lengths
     * Cerebrum limitation of 512 chars, bofhd will throw an error if
     * fname+lname > 512 - 1 chars.
     * It's easier to enforce max limits of 255 chars for fname and lname...
     */
    // Lenghts, field_name => (min, max)
    $namelengths = array(
        'g_fname' => array(2, 255),
        'g_lname' => array(1, 255),
    );
    foreach ($namelengths as $field => $lim) {
        $form->addRule(
            $field,
            txt('guest_new_form_name_fmt', array('min'=>$lim[0], 'max'=>$lim[1])),
            'rangelength', $lim
        );
    }

    // Require 8 digit phone number
    $form->addRule(
        'g_contact', txt('guest_new_form_contact_fmt'), 'regex', '/^[\d]{8}$/'
    );

    // Trim all input prior to validation, and set radio button default
    $form->applyFilter('__ALL__', 'trim');
    $form->setDefaults(array('g_days'=>30));

    return $form;
}


/**
 * Creates a new guest user using BofhCom.
 *
 * @param array $data Array with the form data to process
 *
 * @return string|null An HTML element with information about the new guest
 *                     account, or null on failure.
 */
function bofhCreateGuest($data)
{
    $bofh = Init::get('Bofh');

    try {
        $res = $bofh->run_command(
            'guest_create', $data['g_days'], $data['g_fname'], $data['g_lname'],
            'guest', $data['g_contact']
        );
    } catch (XML_RPC2_FaultException $e) {
        // Error. Not translated or user friendly, but this shouldn't happen at all:
        //View::addMessage(htmlspecialchars($e->getMessage()), View::MSG_WARNING);
        BofhCom::viewError($e);
        return null;
    }

    return txt(
        'guest_created_sms',
        array('uname'=>$res['username'],'mobile'=>$res['sms_to'])
    );
}
?>
