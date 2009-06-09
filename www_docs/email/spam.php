<?php
require_once '../init.php';
$Init = new Init();
$User = new User();
$Bofh = new Bofhcom();
$View = View::create();


$sp_actions = $Bofh->getData('get_constant_description', 'EmailSpamAction');
//actions is sorted ok now, but be aware for a change in the future

$sp_lvl_raw = $Bofh->getData('get_constant_description', 'EmailSpamLevel');
//try to sort the levels at behaviour, as this is not done by bofh
$sp_levels[] = $sp_lvl_raw[0];
$sp_levels[] = $sp_lvl_raw[1];
$sp_levels[] = $sp_lvl_raw[3];
$sp_levels[] = $sp_lvl_raw[2];

//adding the rest (if any)
$i = 4;
while($i<count($sp_lvl_raw)) $sp_levels[] = $sp_lvl_raw[$i++];

list($def_level, $def_action) = getSetLevelAction();


$form = new BofhForm('setSpam');

//spam level
$levels = array();
foreach($sp_levels as $v) {
    $title = ucfirst(str_replace('_', ' ', $v['code_str']));
    $txt_name = 'email_spam_level_'.$v['code_str'];

    if(Text::exists($txt_name, true)) {
        $v['description'] = txt($txt_name);
    }

    $r = $form->createElement('radio', 'level', null, 
        "{$v['description']} <span class=\"explain\">($title)</span>", $v['code_str']);
    $levels[] = $r;
}
$form->addGroup($levels, 'spam_level', txt('email_spam_form_level'), "<br>\n", false);


//spam action
$actions = array();
foreach($sp_actions as $v) {
    $title = ucfirst(str_replace('_', ' ', $v['code_str']));
    $txt_name = 'email_spam_action_'.$v['code_str'];

    if(Text::exists($txt_name, true)) {
        $v['description'] = txt($txt_name);
    }

    $r = $form->createElement('radio', 'action', null, 
        "{$v['description']} <span class=\"explain\">($title)</span>", $v['code_str']);
    $actions[] = $r;
}
$form->addGroup($actions, 'spam_action', txt('email_spam_form_action'), "<br>\n", false);


$form->setDefaults(array(
    'spam_level' =>array('level'=>$def_level),
    'spam_action'=>array('action'=>$def_action)
));
//todo: what to do if def_level and def_action is null?
//      set defaults to no_filter and noaction? (will be hardcoded then...)


$form->addElement('submit', null, txt('email_spam_form_submit'));

$form->addGroupRule('spam_level', txt('email_spam_rule_level_required'), 'required');
$form->addGroupRule('spam_action', txt('email_spam_rule_action_required'), 'required');



if($form->validate()) {

    $lev = $form->exportValue('spam_level');
    $lev = $lev['level'];

    $act = $form->exportValue('spam_action');
    $act = $act['action'];

    try {

        $res = $Bofh->run_command('email_spam_level', $lev, $User->getUsername());
        $res2 = $Bofh->run_command('email_spam_action', $act, $User->getUsername());

        View::addMessage($res);
        View::addMessage($res2);
        View::forward('email/spam.php');

    } catch(Exception $e) {
        Bofhcom::viewError($e);
    }

}



//TODO: want the email title as well?
$View->addTitle('Email');
$View->addTitle(txt('EMAIL_SPAM_TITLE'));


$View->start();
$View->addElement('h1', txt('EMAIL_SPAM_TITLE'));
$View->addElement('p', txt('EMAIL_SPAM_INTRO'));


$View->addElement($form);

$View->addElement('p', txt('action_delay_email'), 'class="ekstrainfo"');


/**
 * Finds the different action choises to put on spam.
 * Works in searching way in the help-text today...
 */
function spamActions() {

    global $Bofh;

    $raw = $Bofh->help('arg_help', 'spam_action');
    //is something like this:
    //Choose one of
    //          'dropspam'    Reject messages classified as spam
    //          'spamfolder'  Deliver spam to a separate IMAP folder
    //          'noaction'    Deliver spam just like legitimate email

    $actions = array();
    foreach(explode("\n", $raw) as $l) {

        //the first line is mostlikely 'Choose one of\n'
        if(!is_numeric(strpos($l, "'"))) continue;
        $l = trim($l);

        $name = substr($l, 1, strpos($l, "'", 2)-1);
        $actions[$name] = trim(substr($l, strlen($name)+2));

    }
    return $actions;
}

/**
 * Asks for the set values of spam_level and spam_action
 */
function getSetLevelAction() {

    global $User;
    global $Bofh;

    $info = $Bofh->getData('email_info', $User->getUsername());
    $level = null;
    $action = null;

    foreach($info as $i) {
        if(isset($i['spam_level'])) $level = $i['spam_level'];
        if(isset($i['spam_action'])) $action = $i['spam_action'];
    }

    return array($level, $action);

}

?>
