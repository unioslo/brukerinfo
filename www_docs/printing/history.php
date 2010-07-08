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
$User = new User();
$Bofh = new Bofhcom();
$View = View::create();

$form = new BofhFormInline('changeHistory');

//Todo: move the lengths to another place, so it can be changed in a config-file?
$lengths = array(7, 14, 30, 100, 200, 365);
$def_length = 14;
$maxlength = 50; //the max length of print-names before newlines (to prevent too wide tables)

foreach($lengths as $l) {
    $length[$l] = txt('printing_history_form_days', array('length'=>$l)) . ($l == $def_length ? ' '.txt('printing_history_form_default') : '');
}
$length[2147483647] = txt('printing_history_form_unlimited');


$form->addElement('select', 'length', txt('printing_history_form_when'), $length);
$form->addElement('checkbox', 'details', txt('printing_history_form_details'), txt('printing_history_form_details_button'));
$form->addElement('submit', null, txt('printing_history_form_submit'));

$form->setDefaults(array('length'=>$def_length));

$details = 0;
$length = $def_length;

if($form->validate()) {
    $details = intval($form->exportValue('details'));
    $length  = intval($form->exportValue('length'));
}


$View->addTitle(txt('PRINTING_HISTORY_TITLE'));
$View->start();
$View->addElement('h1', txt('PRINTING_HISTORY_TITLE'));
$View->addElement($form);

$history = getHistory($length); 
if(!$history) {
    $View->addElement('p', txt('printing_history_empty'));
} else {

    $table = View::createElement('table');

    //TODO: move the header-texts into Text
    if($details) {

        // TODO: add more details later
        //       e.g. could differ between free and paid quotas, which is interesting to some
        $table->setHead(
            txt('printing_history_list_jobid'),
            txt('printing_history_list_time'),
            txt('printing_history_list_printer'),
            txt('printing_history_list_info'),
            txt('printing_history_list_in'),
            txt('printing_history_list_out')
        );

    } else {

        $table->setHead(
            txt('printing_history_list_time'),
            txt('printing_history_list_info'),
            txt('printing_history_list_in'),
            txt('printing_history_list_out')
        );

    }

    foreach($history as $h) {

        $line = array();

        if($details) $line['job_id'] = $h['job_id'];

        if($details) {
            $line['time'] = date('Y-m-d H:i:s', $h['tstamp']->timestamp);
        } else { 
            $line['time'] = date('Y-m-d', $h['tstamp']->timestamp);
        }


        switch($h['transaction_type']) {
        case 'print':

            if($details) $line['printer'] = $h['printer_queue'];
            $line['info'] = '<strong>Print:</strong> ' . wordwrap($h['job_name'], $maxlength, "\n", true);
            $line['in'] = null;
            $line['out'] = View::createElement('td', '<span class="negative">'.$h['pageunits_total'].'</span>', 'class="num"');

            break;
        case 'undo':

            if($details) $line['printer'] = $h['printer_queue'];
            $line['info'] = '<strong>Undo:</strong> ' . $h['description'];
            $line['in'] = View::createElement('td', '<span class="positive">'.$h['pages'].'</span>', 'class="num"');
            $line['out'] = null;
            
            //todo: this is not checked yet...
            if($h['pageunits_total'] != $h['pages']) {
                //echo '<h3>Pageunit != pages</h3>';
                //echo '<pre>'; print_r($h); echo '<br></pre><br>';
                trigger_error('In undo-printing, pageunits_total='.$h['pageunits_total'].', but pages='.$h['pages'] . ', (jobid='.$h['job_id'].')', E_USER_NOTICE);
            }

            break;
        case 'balance':
        case 'free':

            if($details) $line['printer'] = null;
            $line['info'] = '<strong>'. $h['update_program'] . ':</strong> ' . $h['description'];
            $line['in'] = View::createElement('td', '<span class="positive">' . ($h['pageunits_free'] + $h['pageunits_accum']) . '</span>', 'class="num"');
            $line['out'] = null;

            if($h['pageunits_free'] + $h['pageunits_accum'] != $h['pages']) {
                trigger_error('In free printing, pageunits_free='.$h['pageunits_free'] .'+ pageunits_accum='.$h['pageunits_accum'] .', but pages='.$h['pages'] . ', (jobid='.$h['job_id'].')', E_USER_NOTICE);
            }

            break;
        case 'pay':

            if($details) $line['printer'] = '&nbsp;';
            $line['info'] = View::createElement('ul', array(
                '<strong>Payment:</strong> ' . $h['description'],
                'Bank-id: ' . $h['bank_id']),
                'Time paid: '. date('Y.m.d H:i', $h['payment_tstamp']->timestamp)
            );
            $line['in'] = View::createElement('td', 'kr '.number_format($h['kroner'], 2, txt('dec_format'), ' '), 'class="num" colspan="2"');
            unset($line['out']);


            break;
        default:

            //echo '<h3>Unknown type</h3>';
            //echo '<pre>'; print_r($h); echo '<br></pre><br>';
            trigger_error('unknown transaction_type='.$h['transaction_type'] . ' in jobid='.$h['job_id'], E_USER_WARNING);

        }


        /*
        if($h['kroner']) {
            $pre = ($h['kroner'] > 0 ? '+' : '');
            $clas = ($h['kroner'] < 0 ? 'negative' : 'positive');
            $kr = number_format($h['kroner'], 2);
            $line['paid'] = View::createElement('td', "$pre$kr kr", 'style="min-width: 6em" class="num"');
        } else $line['paid'] = null;
         */

        //making tr
        $table->addData(View::createElement('tr', $line));
        
    }


    $View->addElement($table);

}

$View->addElement('ul', array(txt('printing_moreinfo_printing')), 'class="ekstrainfo"');




function getHistory($length) {

    global $User;
    global $Bofh;

    $ret = $Bofh->getData('pquota_history', $User->getUsername(), intval($length));
    if($ret) return array_reverse($ret);

}

?>
