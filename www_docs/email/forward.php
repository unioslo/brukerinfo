<?php
require_once '../init.php';
$Init = new Init();
$User = new User();
$Bofh = new Bofhcom();

$View = View::create();


$forwards = getForwards();
$keeplocal = (isset($forwards['local']) ? true : false);

//make new forward-form
$newForm = new BofhForm('addForwarding');
$newForm->addElement('text', 'address', txt('email_forward_form_address'), array('maxlength' => 255));
$newForm->addElement('checkbox', 'keep', null, txt('email_forward_form_keep'));
$newForm->addElement('submit', null, txt('email_forward_form_submit'));

// Define filters and validation rules
$newForm->addRule('address', txt('email_forward_form_address_required'), 'required');
$newForm->setDefaults(array('keep'=>$keeplocal));

// Adding a forward
if($newForm->validate()) {

    $newForm->freeze();
    $newForm->process('addForward');
    View::forward('email/forward.php');
}


// Form for making local copy
$addLocal = new BofhForm('addLocal');
$addLocal->addElement('html', View::createElement('p', txt('email_forward_addlocal')));
$addLocal->addElement('submit', null, txt('email_forward_addlocal_submit'));

if($addLocal->validate()) {

    $res = $Bofh->run_command('email_add_forward', $User->getUsername(), 'local');
    View::addMessage($res);
    View::addMessage(txt('action_delay_email'));
    View::forward('email/forward.php');

}




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

    $View->addElement('p', txt('email_forward_delete_confirm_intro', array('target'=>$del)));
    $View->addElement($confirm);

    die;
}




$View->start();
$View->addElement('h1', txt('EMAIL_FORWARD_TITLE'));
$View->addElement('p',  txt('EMAIL_FORWARD_INTRO'));

if($forwards) {
    $View->addElement('raw', '<form method="post" class="inline" action="email/forward.php">');
    $table = View::createElement('table', null);

    foreach($forwards as $k=>$v) {
        if ($k == 'local') {
            $name = txt('email_forward_local') . " $v";
        } else {
            $name = "$k $v";
        }
        $table->addData(array( $name, 
            '<input type="submit" class="submit_warn" name="del['.$k.']" value="'.txt('email_forward_delete_submit').'">'));
    }

    $table->setHead('Forwarding address:', null);
    $View->addElement($table);
    $View->addElement('raw', '</form>');
} else {

}

$View->addElement('h2', txt('email_forward_new_title'));
$View->addElement($newForm);


if(!$keeplocal && $forwards) {
    $View->addElement('h2', txt('email_forward_addlocal_title'));

    $View->addElement($addLocal);
}

$View->addElement('p', txt('ACTION_DELAY_email'), 'class="ekstrainfo"');


/**
 * Seems like the way to get the list of forwards is
 * through email_info, in [forward_1] and [forward].
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

/**
 * Adding a forward address.
 * This function is called through BofhForm: $newForm->process()
 * and the values are therefore stored as
 * $values = array:
 *   'address'   = the adress to forward to
 *   ['keep']    = if local copy or not
 */
function addForward($values) {

    global $Bofh;
    global $User;

    if(!empty($values['address'])) {
        try {
            $res = $Bofh->run_command('email_add_forward', $User->getUsername(), $values['address']);

            View::addMessage($res);
            View::addMessage(txt('action_delay_email'));

        } catch(Exception $e) {
            Bofhcom::viewError($e);
            return;
        }
    }

    global $keeplocal;

    //setting the local copy on of off
    if(!empty($values['keep']) && !$keeplocal) {
        try {
            $res = $Bofh->run_command('email_add_forward', $User->getUsername(), 'local');
            View::addMessage($res);
        } catch(Exception $e) {
            Bofhcom::viewError($e);
        }
    } elseif(empty($values['keep']) && $keeplocal) {
        try {
            $res = $Bofh->run_command('email_remove_forward', $User->getUsername(), 'local');
            View::addMessage($res);
        } catch(Exception $e) {
            Bofhcom::viewError($e);
        }
    }
}

?>
