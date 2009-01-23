<?php
require_once '../init.php';
$Init = new Init();
$User = new User();
$Bofh = new Bofhcom();


$forwards = getForwards();
$keeplocal = (isset($forwards['local']) ? true : false);
unset($forwards['local']); //easier code if local is only in $keeplocal

//make new forward-form
$newForm = new BofhForm('addForwarding');
$newForm->addElement('text', 'address', txt('email_forward_form_address'), array('maxlength' => 255));
$newForm->addElement('checkbox', 'keep', null, txt('email_forward_form_keep'));
$newForm->addElement('submit', null, txt('email_forward_form_submit'));

// Define filters and validation rules
//$newForm->addRule('address', 'Please enter the email address to set forwarding to', 'required');
$newForm->setDefaults(array('keep'=>$keeplocal));

// Try to validate the form 
if($newForm->validate()) {

    //adding new address
    if($newForm->exportValue('address')) {
        try {
            $res = $Bofh->run_command('email_add_forward', $User->getUsername(), $newForm->exportValue('address'));

            View::addMessage($res);
            View::addMessage(txt('action_delay', ACTION_DELAY_EMAIL));

        } catch(Exception $e) {
            Bofhcom::viewError($e);
        }
    }

    //setting the local copy on of off
    if($newForm->exportValue('keep') && !$keeplocal) {
        try {
            $res = $Bofh->run_command('email_add_forward', $User->getUsername(), 'local');
            View::addMessage($res);
        } catch(Exception $e) {
            Bofhcom::viewError($e);
        }

    } elseif(!$newForm->exportValue('keep') && $keeplocal) {
        try {
            $res = $Bofh->run_command('email_remove_forward', $User->getUsername(), 'local');
            View::addMessage($res);
        } catch(Exception $e) {
            Bofhcom::viewError($e);
        }
    }

    View::forward('email/forward.php');

}





$View = View::create();
$View->addTitle('Email');
$View->addTitle(txt('EMAIL_FORWARD_TITLE'));




//Deleting forwards
if(!empty($_POST['del'])) {
    if(count($_POST['del']) > 1) {
        View::forward('email/forward.php', 'Buggy data, could not continue', View::MSG_ERROR);
    }

    $del = (is_array($_POST['del']) ? key($_POST['del']) : $_POST['del']);

    if(!isset($forwards[$del])) {
        View::forward('email/forward.php', txt('email_forward_delete_unknown'), View::MSG_ERROR);
    }

    $confirm = new BofhForm('confirm');
    $confirm->addElement('submit', null, txt('email_forward_delete_confirm_submit'), 'class="submit_warn"');
    $confirm->addElement('hidden', 'del', $del);

    if($confirm->validate()) {

        try {
            $res = $Bofh->run_command('email_remove_forward', $User->getUsername(), $del);
            View::forward('email/forward.php', $res);
        } catch(Exception $e) {
            Bofhcom::viewError($e);
            View::forward('email/forward.php');
        }

    }


    $View->addTitle(txt('email_forward_delete_confirm_title'));
    $View->start();
    $View->addElement('h1', txt('email_forward_delete_confirm_title'));

    $View->addElement('p', txt('email_forward_delete_confirm_intro', $del));
    $View->addElement($confirm);

    die;
}




$View->start();
$View->addElement('h1', txt('EMAIL_FORWARD_TITLE'));
$View->addElement('p',  txt('EMAIL_FORWARD_INTRO'));

if($forwards) {
    $View->addElement('raw', '<form method="post" class="inline" action="email/forward.php">');

    $trs = array();
    foreach($forwards as $k=>$v) {
        $trs[] = View::createElement('tr', array("$k $v", '<input type="submit" class="submit_warn" name="del['.$k.']" value="Delete">'));
    }

    $table = View::createElement('table', $trs);
    $table->setHead('Forwarding address:', null);
    $View->addElement($table);
    $View->addElement('raw', '</form>');
} else {

}

$View->addElement('h2', txt('email_forward_new_title'));
$View->addElement($newForm);

$View->addElement('p', txt('ACTION_DELAY', ACTION_DELAY_EMAIL), 'class="ekstrainfo"');


/**
 * Seems like the way to get the list of forwards is
 * throug email_info, in [forward_1] and [forward].
 */
function getForwards() {

    global $User;
    global $Bofh;
    if(!isset($Bofh)) $Bofh = new Bofhcom();
    $info = $Bofh->getData('email_info', $User->getUsername());

    $forwards = array();
    foreach($info as $i) {
        if(isset($i['forward'])) {

           if(strpos($i['forward'], '+') === 0) {
               $name = 'local';
               $status = '(on)';
           } else {
               list($name, $status) = explode(' ', $i['forward']);
           }
           $forwards[$name] = $status;
        }
        if(isset($i['forward_1'])) {
            if(strpos($i['forward_1'], '+') === 0) {
                $name = 'local';
                $status = '(on)';
            } else {
                list($name, $status) = explode(' ', $i['forward_1']);
            }
            $forwards[$name] = $status;
        }
    }

    return $forwards;

}

?>
