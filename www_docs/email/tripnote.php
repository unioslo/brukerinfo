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



$form = new BofhForm('addTripnote');

$form->addElement('header', null, txt('email_tripnote_form_title'));

//TODO: add today as default on start?
$form->addElement('date', 'start', txt('email_tripnote_starting'), array('format'=>'YMd', 'minYear'=>date('Y'), 'maxYear'=>date('Y')+2, 'language'=>$View->getLang()));
$form->addElement('date', 'end', txt('email_tripnote_ending'), array('format'=>'YMd', 'minYear'=>date('Y'), 'maxYear'=>date('Y')+3, 'language'=>$View->getLang()));
$form->addElement('textarea', 'message', txt('email_tripnote_message'), 'rows="7"');

$form->addElement('submit', null, txt('email_tripnote_form_submit'), array('class'=>'submit'));

$form->addRule('message', txt('email_tripnote_rule_message_required'), 'required');
//TODO: move these texts to Text
$form->addRule('start', 'Please enter a start-date', 'required');
$form->addRule('end', 'Please enter an end-date', 'required');
//TODO: check date (add a checkdate rule)

$form->setDefaults(array('start'=>date('Y-m-d', time()+3600*24*1), 'end'=>date('Y-m-d', time()+3600*24*2)));

if ($form->validate()) {
    //check dates before sending to bofhd?
    
    $st = $form->exportValue('start');
    $nd = $form->exportValue('end');
    //Enter begin and end date (YYYY-MM-DD--YYYY-MM-DD) >
    $datestring = $st['Y'] . '-' . $st['M'] . '-' . $st['d'] . '--' .
        $nd['Y'] . '-' . $nd['M'] . '-' . $nd['d'];

    try {

        //todo: bofh
        $Bofh->run_command('email_add_tripnote', $User->getUsername(), $form->exportValue('message'), $datestring);

        View::forward('email/tripnote.php', txt('email_tripnote_new_success'));

    } catch(Exception $e) {

        Bofhcom::viewError($e);

    }
}



//getting all the tripnotes

$rawnotes = $Bofh->getData('email_list_tripnotes', $User->getUsername());

//sorts old tripnotes from the rest

//all the tripnotes in one list
$tripnotes = array();
//old tripnotes
$oldnotes = array();
//tripnotes that is either active or waiting to become active
$othernotes = array();

//if empty, bofhd gives out 'no tripnotes for {username}'
if($rawnotes && is_array($rawnotes)) {

    foreach($rawnotes as $tnote) {
        $id = date('Y-m-d', $tnote['start_date']->timestamp);
        $tnote['text'] = $tnote['text'];

        $tripnotes[$id] = $tnote;

        if($tnote['enable'] == 'ACTIVE' || $tnote['enable'] == 'PENDING') {
            $othernotes[$id] = $tnote;
        } else {
            $oldnotes[$id] = $tnote;
        }
    }
};


$View = View::create();
$View->addTitle('Email');
$View->addTitle(txt('EMAIL_TRIPNOTE_TITLE'));




//TODO: cause of lack of time, this form isn't implementet correct 
//      in BofhFormInline()-class, but done manually:
//$delform = new BofhFormInline('delTripnote');
//$dels = array()...
if(!empty($_POST['confirmed_del'])) {

    $del = $_POST['confirmed_del'];
    if(!isset($tripnotes[$del])) {
        View::forward('email/tripnote.php', 'Found no tripnotes starting at '.htmlspecialchars($del), View::MSG_WARNING);
    }

    try {
        $res = $Bofh->run_command('email_remove_tripnote', $User->getUsername(), $del);
        View::forward('email/tripnote.php', $res);
    } catch(Exception $e) {
        Bofhcom::viewError($e);
    }
}
if(!empty($_POST['del'])) {

    if(count($_POST['del']) > 1) {
        View::forward('email/tripnote.php', 'Warning, bad data, could not continue.', View::MSG_ERROR);
    }

    $del = key($_POST['del']);

    if(!isset($tripnotes[$del])) {
        View::forward('email/tripnote.php', 'Unknown out of office message.', View::MSG_WARNING);
    }

    $View->addTitle(txt('email_tripnote_delete_title'));
    $View->start();
    $View->addElement('h1', txt('email_tripnote_delete_title'));
    $View->addElement('p', txt('email_tripnote_delete_confirm'));

    $dl = $View->createElement('dl');
    $dl->addData(txt('email_tripnote_starting'),   date('Y-m-d', $tripnotes[$del]['start_date']->timestamp));
    $dl->addData(txt('email_tripnote_ending'),     date('Y-m-d', $tripnotes[$del]['end_date']->timestamp));
    $dl->addData(txt('email_tripnote_message'),    nl2br($tripnotes[$del]['text']));
    $View->addElement($dl);

    $confirm = new BofhForm('confirm');
    $confirm->addElement('hidden', 'confirmed_del', $del);
    $confirm->addElement('submit', null, txt('email_tripnote_delete_submit'), 'class="submit_warn"');
    $View->addElement($confirm);

    die;



}











$View->start();
$View->addElement('h1', txt('EMAIL_TRIPNOTE_TITLE'));
$View->addElement('p', txt('email_tripnote_intro'));


if($othernotes) {

    $View->addElement('h2', txt('email_tripnote_active_title'));

    $View->addElement('raw', '<form method="post" action="email/tripnote.php" class="inline">'); //Todo: depreciated, but out of time
    $table = $View->createElement('table');
    $table->setHead(txt('email_tripnote_starting'), 
        txt('email_tripnote_ending'),
        txt('email_tripnote_message'),
        null);


    foreach($othernotes as $tnote) {

        $start = date('Y-m-d', $tnote['start_date']->timestamp);

        $data = array();
        $data[] = View::createElement('td', $start);
        $data[] = View::createElement('td', date('Y-m-d', $tnote['end_date']->timestamp));
        $data[] = View::createElement('td', nl2br($tnote['text']));
        $data[] = View::createElement('td', '<input type="submit" class="submit_warn" name="del['.$start.']" value="'.txt('email_tripnote_list_delete').'">');

        $table->addData($View->createElement('tr', $data));

    }

    $View->addElement($table);
    $View->addElement('raw', '</form>');

}

$View->addElement($form);


if($oldnotes) {

    $View->addElement('h2', txt('email_tripnote_old_title'));

    $View->addElement('raw', '<form method="post" action="email/tripnote.php" class="inline">'); //Todo: depreciated, but out of time

    $table = $View->createElement('table');
    $table->setHead('Starting', 'Ending', 'Message', 'Status', null);

    foreach(array_reverse($oldnotes) as $tnote) {

        $start = date('Y-m-d', $tnote['start_date']->timestamp);
        $end   = date('Y-m-d', $tnote['end_date']->timestamp);

        $table->addData(View::createElement('tr', array(
            $start,
            $end,
            nl2br($tnote['text']),
            '('.strtolower($tnote['enable']).')', 
            '<input type="submit" class="submit_warn" name="del['.$start.']" value="Delete">'
        )));

    }

    $View->addElement($table);
    $View->addElement('raw', '</form>');

}

?>
