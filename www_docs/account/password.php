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
$User = Init::get('User');
$Bofh = new Bofhcom();

$View = Init::get('View');
$View->addTitle('Account');
$View->addTitle(txt('ACCOUNT_PASSWORD_TITLE'));


// The password change form
$form = new BofhFormUiO('changePassword');
$form->setAttribute('class', 'app-form-big');

$form->addElement('password', 'cur_pass', txt('account_password_form_current'));
$form->addElement('html', '<hr />');
$form->addElement('password', 'new_pass', txt('account_password_form_new'), 'id="new_pass"');
$form->addElement('password', 'new_pass2', txt('account_password_form_new2'));

$form->addElement('submit', null, txt('account_password_form_submit'));

// Validation rules
$form->addRule('new_pass', txt('account_password_rule_new_required'), 'required');

// no more rules here, wants to validate the password first, before checking rest

if($form->validate()) {

    $pasw_msg = validatePassword($form->exportValue('new_pass'), $errmsg);
    //$pasw_msg now contains either TRUE or a string explaining what is wrong with the password
    if($pasw_msg === true) {

        //the password is valid, now check the rest
        
        if($form->exportValue('new_pass') == $form->exportValue('new_pass2')) {

            //check original password
            if(verifyPassword($form->exportValue('cur_pass'))) {

                if(changePassword($form->exportValue('new_pass'), $form->exportValue('cur_pass'), $errmsg)) {
                    View::addMessage(txt('account_password_success'));
                    View::addMessage(txt('action_delay_hour'));
                    View::forward('account/index.php');
                } else {
                    //have to send errors manually to the form, (e.g. check for old passwords)
                    $form->setElementError('new_pass', $errmsg);
                }

            } else {
                $form->setElementError('cur_pass', txt('account_password_error_current'));
            }
        } else {
            $form->setElementError('new_pass2', txt('account_password_error_match'));
        }

    } else {
        // if the new password is wrong
        $form->setElementError('new_pass', $pasw_msg);
    }


}

//TODO: this should be included in the HTML_Quickform_password class, passwords 
//      should not be written directly in the html!
$pa = $form->getElement('new_pass');
$pa->setValue(null);

$pa = $form->getElement('new_pass2');
$pa->setValue(null);

$pa = $form->getElement('cur_pass');
$pa->setValue(null);







$View->setFocus('#cur_pass');
$View->start();
$View->addElement('h1', txt('ACCOUNT_PASSWORD_TITLE'));
$View->addElement('raw', txt('ACCOUNT_PASSWORD_INTRO'));


//TODO: add some javascript for checking the password without updating the page
$View->addElement($form);


$View->addElement('p', txt('account_password_moreinfo'), 'class="ekstrainfo"');






/**
 * Checks if the given password is secure enough to be used.
 */
function validatePassword($password, &$returnmsg = null) {

    global $Bofh;

    try {

        $res = $Bofh->run_command('misc_check_password', $password);
        if($res) return true;

    } catch (Exception $e) {

        $returnmsg = $e->getMessage();
        return substr($returnmsg, strrpos($returnmsg, 'CerebrumError: ')+15);

    }
}


/** 
 * Checks if the given password is the users correct password
 */
function verifyPassword($password) {

    global $Bofh;

    try {

        $res = $Bofh->run_command('misc_verify_password', Init::get('User')->getUsername(), $password);
        //TODO: the text may change... get smarter way...
        if($res === 'Password is correct') return true;

    } catch (Exception $e) {
        return false;
    }

}

/**
 * Changes the users password.
 */
function changePassword($newpas, $curpas, &$errmsg = null) {

    global $Bofh;

    try {

        $res = $Bofh->run_command('user_password', Init::get('User')->getUsername(), $newpas);
        if($res) return true;

    } catch (Exception $e) {
        $errmsg = $e->getMessage();
        $errmsg = substr($errmsg, strrpos($errmsg, 'CerebrumError: ')+15);
    }

    return false;
}


?>
