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

    $kroner = number_format($printerinfo['kroner'], 2, txt('dec_format'), ' ');

    //Todo: make this from addElement instead? If got time...
    $tabl = $View->createElement('table', null, 'class="mini"');
    $tabl->addData(array(View::createElement('th', txt('printing_table_free')),
        null,
        View::createElement('td', $printerinfo['free_quota'], 'class="num"'),
        null));

    $tabl->addData(array(View::createElement('th', txt('printing_table_paid')),
        '+',
        View::createElement('td', $printerinfo['paid_quota'], 'class="num"'),
        View::createElement('td', txt('printing_table_paid_value', array('kroner'=>$kroner)),
            'class="num"')));

    $tabl->addData(array(View::createElement('th', txt('printing_table_total')),
        View::createElement('td', '=', 'class="num_ans"'),
        View::createElement('td', $printerinfo['tot_available'], 'class="num_ans"'),
        null));

    $printlist[] = txt('printing_moreinfo_pay');
    $printlist[] = txt('printing_moreinfo_prices');

    $View->addElement($tabl);
}

$printlist[] = txt('printing_moreinfo_printing');
if(!empty($printlist)) $View->addElement('ul', $printlist, 'class="ekstrainfo"');

?>
