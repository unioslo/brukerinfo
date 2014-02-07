<?php
// Copyright 2009, 2010, 2011 University of Oslo, Norway
// 
// This file is part of Cerebrum.
// 
// Cerebrum is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// 
// Cerebrum is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with Cerebrum. If not, see <http://www.gnu.org/licenses/>.

require_once '../init.php';
$Init = new Init();
$User = Init::get('User');
$Bofh = new Bofhcom();
$View = Init::get('View');
$text = Init::get('Text');
$Authz = Init::get('Authorization');

if (!$Authz->has_imap()) {
    // This is very temporary
    View::forward('', 'IMAP: '.txt('email_info_no_account'));
}

$form = formAddTripnote();
if ($form->validate()) {
    if ($form->process('formAddTripnoteProcess')) {
        View::forward('email/tripnote.php', txt('email_tripnote_new_success'));
    }
}

$tripnotes = getTripnotes();
list($activenotes, $oldnotes) = sortTripnotes($tripnotes);

$View->addTitle(txt('email_title'));
$View->addTitle(txt('email_tripnote_title'));

//TODO: cause of lack of time, this form isn't implementet correct 
//      in BofhFormInline()-class, but done manually:
//$delform = new BofhFormInline('delTripnote');
//$dels = array()...
if (!empty($_POST['confirmed_del'])) {
    $del = $_POST['confirmed_del'];
    if (!isset($tripnotes[$del])) {
        View::forward('email/tripnote.php', 'Found no tripnotes starting at '.htmlspecialchars($del), View::MSG_WARNING);
    }

    try {
        $res = $Bofh->run_command('email_remove_tripnote', $User->getUsername(), $del);
        View::forward('email/tripnote.php', $res);
    } catch(Exception $e) {
        Bofhcom::viewError($e);
    }
}
if (!empty($_POST['del'])) {
    if (count($_POST['del']) > 1) {
        View::forward('email/tripnote.php', 'Warning, bad data, could not continue.', View::MSG_ERROR);
    }

    $del = key($_POST['del']);

    if (!isset($tripnotes[$del])) {
        View::forward('email/tripnote.php', 'Unknown out of office message.', View::MSG_WARNING);
    }

    $View->addTitle(txt('email_tripnote_delete_title'));
    $View->start();
    $View->addElement('h1', txt('email_tripnote_delete_title'));
    $View->addElement('p', txt('email_tripnote_delete_confirm'));

    $dl = $View->createElement('dl');
    $dl->addData(txt('email_tripnote_starting'),   ($tripnotes[$del]['start_date']) ? $tripnotes[$del]['start_date']->format('Y-m-d') : '');
    $dl->addData(txt('email_tripnote_ending'),     ($tripnotes[$del]['end_date']) ? $tripnotes[$del]['end_date']->format('Y-m-d') : '');
    $dl->addData(txt('email_tripnote_message'),    nl2br($tripnotes[$del]['text']));
    $View->addElement($dl);

    $confirm = new BofhFormUiO('confirm');
    $confirm->addElement('hidden', 'confirmed_del', $del);
    $confirm->addElement('submit', null, txt('email_tripnote_delete_submit'), 'class="submit_warn"');
    $View->addElement($confirm);
    die;
}

$View->start();
$View->addElement('h1', txt('EMAIL_TRIPNOTE_TITLE'));
$View->addElement('p', txt('email_tripnote_intro'));

if ($activenotes) {
    $View->addElement('h2', txt('email_tripnote_active_title'));
    $View->addElement('raw', '<form method="post" action="email/tripnote.php" class="inline app-form">'); //Todo: depreciated, but out of time
    $table = $View->createElement('table', null, 'class="app-table"');
    $table->setHead(array(
        txt('email_tripnote_starting'), 
        txt('email_tripnote_ending'),
        txt('email_tripnote_message'),
        null,
    ));
    foreach ($activenotes as $tnote) {
        $start = ($tnote['start_date']) ? $tnote['start_date']->format('Y-m-d') : '';

        $data = array();
        $data[] = View::createElement('td', $start);
        $data[] = View::createElement('td', ($tnote['end_date']) ? $tnote['end_date']->format('Y-m-d') : '');
        $data[] = View::createElement('td', nl2br($tnote['text']));
        $data[] = View::createElement('td', '<input type="submit" class="submit_warn" name="del['.$start.']" value="'.txt('email_tripnote_list_delete').'">');

        $table->addData($View->createElement('tr', $data));
    }
    $View->addElement($table);
    $View->addElement('raw', '</form>');
}

$View->addElement($form);

if ($oldnotes) {
    $View->addElement('h2', txt('email_tripnote_old_title'));
    $View->addElement('raw', '<form method="post" action="email/tripnote.php" class="inline app-form">'); //Todo: deprecated, but out of time

    $table = $View->createElement('table', null, 'class="app-table"');
    $table->setHead(array(
        txt('email_tripnote_starting'),
        txt('email_tripnote_ending'),
        txt('email_tripnote_message'),
        txt('email_tripnote_status'),
        null,
    ));
    foreach (array_reverse($oldnotes) as $tnote) {
        $start = ($tnote['start_date']) ? $tnote['start_date']->format('Y-m-d') : '';
        $end   = ($tnote['end_date'])   ? $tnote['end_date']->format('Y-m-d')   : '';

        $table->addData(View::createElement('tr', array(
            $start,
            $end,
            nl2br($tnote['text']),
            '('.strtolower($tnote['enable']).')', 
            '<input type="submit" class="submit_warn" name="del['.$start.']" value="' . txt('email_tripnote_list_delete') . '">',
        )));

    }
    $View->addElement($table);
    $View->addElement('raw', '</form>');
}



/**
 * Return an array of all tripnotes for the user.
 */
function getTripnotes()
{
    $bofh = Init::get('Bofh');
    $user = Init::get('User');
    $rawnotes = $bofh->getData('email_list_tripnotes', $user->getUsername());
    if (!$rawnotes || !is_array($rawnotes)) {
        return array();
    }
    $notes = array();
    foreach ($rawnotes as $note) {
        if (!$note['start_date'] instanceof DateTime) continue;
        $id = $note['start_date']->format('Y-m-d');
        $notes[$id] = $note;
    }
    return $notes;
}

/**
 * Sort an array of tripnotes into active and inactive.
 *
 * Active tripnotes have enable status:
 *
 *  - PENDING: Not active yet
 *  - ON:      If we're in the tripnote's period
 *  - ACTIVE:  Only one of the tripnotes with status ON can be active. According 
 *             to bofhd, this is the one with the start date closest to today.
 *
 * Inactive tripnotes have enable status:
 *
 *  - OFF:     If postmasters have disabled the tripnote
 *  - OLD:     If end date is in the past
 *
 */
function sortTripnotes($notes)
{
    $active   = array();
    $inactive = array();
    if (!$notes) {
        return array(null, null);
    }
    foreach ($notes as $id => $note) {
        if ($note['enable'] === 'ACTIVE') {
            $note['text'] .= ' <strong>(active)</strong>';
            $active[$id] = $note;
        } elseif ($note['enable'] === 'ON' || $note['enable'] === 'PENDING') {
            $active[$id] = $note;
        } else {
            $inactive[$id] = $note;
        }
    }
    return array($active, $inactive);
}

/**
 * Create a form for creating a new tripnote.
 */
function formAddTripnote()
{
    $form = new BofhFormUiO('addTripnote');
    $form->addElement('header', null, txt('email_tripnote_form_title'));
    $text = Init::get('Text');

    //TODO: add today as default on start?
    $form->addElement('date', 'start', txt('email_tripnote_starting'), array(
        'format'    => 'YMd',
        'minYear'   => date('Y'),
        'maxYear'   => date('Y') + 2,
        'language'  => $text->getLanguage(),
    ));
    $form->addElement('date', 'end', txt('email_tripnote_ending'), array(
        'format'    => 'YMd',
        'minYear'   => date('Y'),
        'maxYear'   => date('Y') + 3,
        'language'  => $text->getLanguage(),
    ));
    $form->addElement('textarea', 'message', txt('email_tripnote_message'), 'rows="7"');
    $form->addElement('submit', null, txt('email_tripnote_form_submit'));

    $form->addRule('message', txt('email_tripnote_rule_message_required'), 'required');
    $form->addRule('start', 'Please enter a start-date', 'required');
    $form->addRule('end', 'Please enter an end-date', 'required');

    //TODO: check dates (add a checkdate rule)

    $form->setDefaults(array(
        'start' => date('Y-m-d', time()+3600*24*1),
        'end'   => date('Y-m-d', time()+3600*24*2),
    ));
    return $form;
}

/**
 * Process an add tripnote form.
 */
function formAddTripnoteProcess($input)
{
    $bofh = Init::get('Bofh');
    $user = Init::get('User');

    $start = $input['start'];
    $end   = $input['end'];
    // begin and end date has the format: YYYY-MM-DD--YYYY-MM-DD
    $datestring = sprintf('%s-%s-%s--%s-%s-%s', $start['Y'], $start['M'], 
        $start['d'], $end['Y'], $end['M'], $end['d']
    );
    try {
        return $bofh->run_command('email_add_tripnote', $user->getUsername(), 
            $input['message'], $datestring
        );
    } catch(Exception $e) {
        Bofhcom::viewError($e);
        return false;
    }
}

?>
