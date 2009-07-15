<?php
//init.php is in the doc_root - in subdirs use '../' to locate it
require_once 'init.php';

//standard config
$Init = new Init();

//getting the bofh-communication
$Bofh = new Bofhcom();

//getting the user-object
$User = new User();



//gets the html
$View = View::create();
$View->start();

//output of html
//$View->addElement('h1', txt('HOME_TITLE'));

$View->addElement('p', txt('home_intro'));

if($Bofh->getAccounts()) {
    $View->addElement('p', txt('home_specific_account', $User->getUsername()));
}

$View->addElement('h2', txt('home_shortcuts_title'));
$View->addElement('ul', array(
    View::createElement('a', txt('home_shortcuts_password'),        'account/password.php'),
    //View::createElement('a', txt('home_shortcuts_printing'),         'printing/'),
    View::createElement('a', txt('home_shortcuts_printing_history'), 'printing/history.php'),
    View::createElement('a', txt('home_shortcuts_spam'), 'email/spam.php'),
    View::createElement('a', txt('home_shortcuts_tripnote'), 'email/tripnote.php'),
    View::createElement('a', txt('home_shortcuts_members'), 'groups/')
));

$View->addElement('h2', txt('home_about_title'));
$View->addElement('p', txt('home_about'));

//the html is automaticly ended by $View->__destruct()
?>
