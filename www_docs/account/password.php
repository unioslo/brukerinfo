<?php
require_once '../init.php';
$Init = new Init();
$User = new User();
$Bofh = new Bofhcom();

$View = View::create();
$View->addTitle('Account');
$View->addTitle(txt('ACCOUNT_PASSWORD_TITLE'));


//the New Password form
$form = new BofhForm('changePassword');
$form->addElement('password', 'new_pass', txt('account_password_form_new'), 'id="new_pass"');
//$form->addElement('submit', null, 'Check password');
//todo: add explaination here...

$form->addElement('password', 'new_pass2', txt('account_password_form_new2'));
$form->addElement('password', 'cur_pass', txt('account_password_form_current'));
$form->addElement('submit', null, txt('account_password_form_submit'));

//Rules:
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
                    View::forward('account/');
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







$View->setFocus('new_pass');
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

    global $User;
    global $Bofh;

    try {

        $res = $Bofh->run_command('misc_verify_password', $User->getUsername(), $password);
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

    global $User;
    global $Bofh;

    try {

        $res = $Bofh->run_command('user_password', $User->getUsername(), $newpas);
        if($res) return true;

    } catch (Exception $e) {
        $errmsg = $e->getMessage();
        $errmsg = substr($errmsg, strrpos($errmsg, 'CerebrumError: ')+15);
    }

    return false;
}


?>
