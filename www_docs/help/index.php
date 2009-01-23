<?php
require_once '../init.php';
$Init = new Init();
$User = new User();


$View = View::create();
$View->addTitle('Help');

$View->start();

$View->addElement('h1', txt('help_title'));
$View->addElement('p', txt('help_go'));

?>
