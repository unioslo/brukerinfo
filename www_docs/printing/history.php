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

// The default history length, in days
$def_length = 14;
$wraplength = 50;

require_once '../init.php';
$Init = new Init();
$User = Init::get('User');
$Bofh = new Bofhcom();
$View = Init::get('View');

$form = createForm($def_length);

$details = false;
$length = $def_length;
if ($form->validate()) {
    $details = (bool) $form->exportValue('details');
    $length  = (int)  $form->exportValue('length');
}

$View->addTitle(txt('printing_history_title'));
$View->start();
$View->addElement('h1', txt('printing_history_title'));
$View->addElement($form);

try {
    $history = getHistory($length); 
    if ($history) {
        $View->addElement(createHistoryTable($history, $details));
    } else {
        $View->addElement('p', txt('printing_history_empty'));
    }
} catch (XML_RPC2_FaultException $e) {
    $View->addElement('p', $Bofh->getData('pquota_info', $User->getUsername()));
}
$View->addElement('ul', array(txt('printing_moreinfo_printing')), 'class="ekstrainfo"');


// TODO: move (some of) the following functions into its own class in 'system/'?  
// E.g. 'BofhHandler', or 'BofhHelper', for convenience methods for handling 
// bofh results.

/**
 * Gets the user's history from bofhd.
 *
 * @param   int     $length     Number of days back the history should go.
 * @return  Array               pquota history data.
 *
 * @throw   XML_RPC2_FaultException E.g. if the user is missing a pquota.
 */
function getHistory($length)
{
    global $Bofh;
    return array_reverse($Bofh->run_command('pquota_history', 
        Init::get('User')->getUsername(), intval($length)));
}

/**
 * Creates an HTML form for what print history to view.
 */
function createForm($def_length)
{
    // Move these variables to config.php?
    $lengths    = array(7, 14, 30, 100, 200, 365);

    $length[2147483647] = txt('printing_history_form_unlimited');
    foreach ($lengths as $l) {
        $length[$l] = txt('printing_history_form_days', array('length'=>$l)) . ($l == $def_length ? ' '.txt('printing_history_form_default') : '');
    }

    $form = new BofhFormInline('changeHistory');
    $form->addElement('select', 'length', txt('printing_history_form_when'), $length);
    $form->addElement('checkbox', 'details', txt('printing_history_form_details'), txt('printing_history_form_details_button'));
    $form->addElement('submit', null, txt('printing_history_form_submit'));

    $form->setDefaults(array('length' => $def_length));
    return $form;
}

/**
 * Creates a table out of given history.
 *
 * @param   Array       $history    The print history data.
 * @param   bool        $details    Present more details.
 * @returns HTML_Table  To be added to $View or printed.
 */
function createHistoryTable($history, $details = false)
{
    $table = View::createElement('table');
    if ($details) {
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

    foreach ($history as $h) {
        switch ($h['transaction_type']) {
            case 'print':
                $line = translateTransactionPrint($h);
                break;
            case 'undo':
                $line = translateTransactionUndo($h);
                break;
            case 'balance':
            case 'free':
                $line = translateTransactionUpdate($h);
                break;
            case 'pay':
                $line = translateTransactionPay($h);
                break;
            default:
                trigger_error('unknown transaction_type='.$h['transaction_type'] . ' in jobid='.$h['job_id'], E_USER_WARNING);
        }

        if (!$details) {
            $line['time'] = date('Y-m-d', $h['tstamp']->timestamp);
            unset($line['printer']);
            unset($line['job_id']);
        }
        $table->addData(View::createElement('tr', $line));
    }
    return $table;
}

/**
 * Translates a pquota transaction.
 */
function translateTransaction($data)
{
    $line['job_id']  = $data['job_id'];
    $line['time']    = date('Y-m-d H:i:s', $data['tstamp']->timestamp);
    $line['printer'] = '';
    $line['info']    = '';
    $line['in']      = '';
    $line['out']     = '';
    return $line;
}

/**
 * Translates a pquota transaction of the 'print' type.
 */
function translateTransactionPrint($data)
{
    global $wraplength;
    $line = translateTransaction($data);
    $line['info'] = '<strong>Print:</strong> ' . wordwrap($data['job_name'], $wraplength, "\n", true);
    $line['out'] = View::createElement('td', '<span class="negative">'.$data['pageunits_total'].'</span>', 'class="num"');
    $line['printer'] = $data['printer_queue'];
    return $line;
}

/**
 * Translates a pquota transaction of the 'undo' type.
 */
function translateTransactionUndo($data)
{
    $line = translateTransaction($data);
    $line['info'] = '<strong>Undo:</strong> ' . $data['description'];
    $line['in'] = View::createElement('td', '<span class="positive">'.$data['pages'].'</span>', 'class="num"');
    
    //TODO: this should be checked
    if ($data['pageunits_total'] != $data['pages']) {
        trigger_error('In undo, pageunits_total='
            . $data['pageunits_total'] . ', but pages=' . $data['pages']
            . ', (jobid='.$data['job_id'].')', E_USER_NOTICE);
    }
    $line['printer'] = $data['printer_queue'];
    return $line;
}

/**
 * Translates a pquota transaction of the types 'free' and 'balance'.
 */
function translateTransactionUpdate($data)
{
    $line = translateTransaction($data);

    $line['info'] = '<strong>'. $data['update_program'] . ':</strong> ' . $data['description'];
    $line['in'] = View::createElement('td', '<span class="positive">' . ($data['pageunits_free'] + $data['pageunits_accum']) . '</span>', 'class="num"');

    if ($data['pageunits_free'] + $data['pageunits_accum'] != $data['pages']) {
        trigger_error('In free printing, pageunits_free='
            . $data['pageunits_free'] . '+ pageunits_accum='
            . $data['pageunits_accum'] .', but pages='.$data['pages']
            . ', (jobid='.$data['job_id'].')', E_USER_NOTICE);
    }
    return $line;
}

/**
 * Translates a pquota transaction of the type 'pay'.
 */
function translateTransactionPay($data)
{
    $line = translateTransaction($data);

    $line['info'] = View::createElement('ul', array(
        '<strong>Payment:</strong> ' . $data['description'],
        'Bank-id: ' . $data['bank_id']),
        'Time paid: '. date('Y.m.d H:i', $data['payment_tstamp']->timestamp)
    );
    $line['in'] = View::createElement('td', 'kr ' . number_format($data['kroner'], 2, txt('dec_format'), ' '), 'class="num" colspan="2"');
    unset($line['out']);

    if ($details) $line['printer'] = '&nbsp;';
    return $line;
}

?>
