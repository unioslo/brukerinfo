<?php
require_once '../init.php';
$Init = new Init();
$User = new User();
$Bofh = new Bofhcom();


if(hasPersonal()) {
    View::forward('groups/index.php', txt('groups_personal_already'));
}



$form = new BofhForm('makePersonal', null, null, null, 'class="submitonly"');
$form->addElement('submit', 'add', 'Make personal group');




if($form->validate()) {
    if($Bofh->getData('group_personal', $User->getUsername())) {
        View::forward('groups/', txt('groups_personal_success'));
    } else {
        View::forward('groups/personal.php');
    }
}








$View = View::create();
$View->addTitle('Groups');
$View->addTitle(txt('GROUPS_PERSONAL_TITLE'));

$View->start();
$View->addElement('h1', txt('GROUPS_PERSONAL_TITLE'));
$View->addElement('p', txt('GROUPS_PERSONAL_INFO', $User->getUsername()));

$View->addElement($form);

$View->addElement('p', txt('action_delay', ACTION_DELAY), 'class="ekstrainfo"');




/**
 * Checks if the user has a personal group.
 *
 * @return True if user has personal group, false if not.
 */
function hasPersonal() {

    global $User;
    global $Bofh;

    try {
        $Bofh->run_command('group_list', $User->getUsername());
    } catch(Exception $e) {
        return false;
    }

    return true;

}
?>
