<?php
require_once '../init.php';
$Init = new Init();
$User = new User();
$Bofh = new Bofhcom();

$printerinfo = $Bofh->getData('pquota_status', $User->getUsername());



$View = View::create();
$View->addTitle(txt('PRINTING_TITLE'));
$View->start();
$View->addElement('h1', txt('PRINTING_TITLE'));

if($printerinfo['has_quota'] == 'F' || $printerinfo['has_blocked_quota'] != 'F') {

    //$View->addElement('p', txt('printing_blocked'));
    $View->addElement('p', $Bofh->getData('pquota_info', $User->getUsername()));

} else {

    $kroner = number_format($printerinfo['kroner'], 2);

    //Todo: make this from addElement instead? If got time...
    $View->addElement('raw', '<table class="mini"> <tr>');

    $View->addElement('raw', '<th>' . txt('printing_table_free') . '</th>'); 
    $View->addElement('raw', '<td></td>');
    $View->addElement('raw', "<td class=\"num\">{$printerinfo['free_quota']}</td>");
    $View->addElement('raw', '<td></td>');
    $View->addElement('raw', '</tr><tr>');
    $View->addElement('raw', '<th>' . txt('printing_table_paid') . '</th>');
    $View->addElement('raw', '<td>+</td>');
    $View->addElement('raw', "<td class=\"num\">{$printerinfo['paid_quota']}</td>");
    $View->addElement('raw', "<td class=\"num\">(kr {$kroner})</td>");
    $View->addElement('raw', '</tr><tr>');
    $View->addElement('raw', '<th>' . txt('printing_table_total') . '</th>');
    $View->addElement('raw', '<td class="num_ans">=</td>');
    $View->addElement('raw', "<td class=\"num_ans\">{$printerinfo['tot_available']}</td>");
    $View->addElement('raw', '<td></td>');
    $View->addElement('raw', '</tr> </table>');

    $printlist[] = txt('printing_moreinfo_pay');
    $printlist[] = txt('printing_moreinfo_prices');

}

$printlist[] = txt('printing_moreinfo_printing');
if(!empty($printlist)) $View->addElement('ul', $printlist, 'class="ekstrainfo"');

?>
