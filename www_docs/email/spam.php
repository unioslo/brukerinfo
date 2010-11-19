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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Cerebrum. If not, see <http://www.gnu.org/licenses/>.

require_once '../init.php';
$Init = new Init();
$User = Init::get('User');
$Bofh = new Bofhcom();
$View = Init::get('View');

// Getting spam settings

$sp_actions = $Bofh->getData('get_constant_description', 'EmailSpamAction');
// actions is sorted ok now, but be aware for a change in the future
$sp_lvl_raw = $Bofh->getData('get_constant_description', 'EmailSpamLevel');
//try to sort the levels at behaviour, as this is not done by bofh
$sp_levels[] = $sp_lvl_raw[0];
$sp_levels[] = $sp_lvl_raw[1];
$sp_levels[] = $sp_lvl_raw[3];
$sp_levels[] = $sp_lvl_raw[2];
// adding the rest (if any)
$i = 4;
while($i<count($sp_lvl_raw)) $sp_levels[] = $sp_lvl_raw[$i++];

// the set level and action
list($def_level, $def_action) = getSetLevelAction();

// Getting filter settings
$available_filters = availableFilters();
$active_filters = getActiveFilters();




$form = new BofhForm('setSpam');

//spam level
$levels = array();
foreach($sp_levels as $v) {
    $title = ucfirst(str_replace('_', ' ', $v['code_str']));
    $txt_name = 'email_spam_level_'.$v['code_str'];

    if (Text::exists($txt_name, $View->getLanguage(), true)) {
        $v['description'] = txt($txt_name);
    }

    $levels[] = $form->createElement('radio', 'level', null, 
        "{$v['description']} <span class=\"explain\">($title)</span>", $v['code_str']);
}
$form->addGroup($levels, 'spam_level', txt('email_spam_form_level'), "<br>\n", false);

//spam action
$actions = array();
foreach($sp_actions as $v) {
    $title = ucfirst(str_replace('_', ' ', $v['code_str']));
    $txt_name = 'email_spam_action_'.$v['code_str'];

    if (Text::exists($txt_name, $View->getLanguage(), true)) {
        $v['description'] = txt($txt_name);
    }

    $actions[] = $form->createElement('radio', 'action', null, 
        "{$v['description']} <span class=\"explain\">($title)</span>", $v['code_str']);
}
$form->addGroup($actions, 'spam_action', txt('email_spam_form_action'), "<br>\n", false);

$form->setDefaults(array(
    'level' =>$def_level,
    'action'=>$def_action
));
//todo: what to do if def_level and def_action is null?
//      set defaults to no_filter and noaction? (will be hardcoded then...)


$form->addElement('submit', null, txt('email_spam_form_submit'));

$form->addGroupRule('spam_level', txt('email_spam_rule_level_required'), 'required');
$form->addGroupRule('spam_action', txt('email_spam_rule_action_required'), 'required');



if ($form->validate()) {

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






$View->addTitle(txt('email_title'));
$View->addTitle(txt('email_spam_title'));



// making form for the filters (additional spam settings)
$filterform = new BofhForm('spamfilter');

$flist = View::createElement('table');

foreach($available_filters as $id => $filter) {

    $status     = (isset($active_filters[$id]) ? 
        txt('email_filter_disable') : txt('email_filter_enable'));
    $subclass   = (isset($active_filters[$id]) ? 
        '_warn' : '');

    // TODO: should make a template in BofhForm to handle these tables with forms
    $flist->addData(array(
        $filter['name'],
        $filter['desc'],
        "<input type=\"submit\" name=\"$id\" class=\"submit$subclass\" value=\"$status\" />"));
}



// validates and saves the setting
if ($filterform->validate()) {

    if ($filterform->process('setFilters')) {
        View::addMessage(txt('email_filter_update_success'));
    }
    //if false, this should already be handled and sent to the user by the function
    View::forward('email/spam.php');

}

$View->start();

// spam settings
$View->addElement('h1', txt('EMAIL_SPAM_TITLE'));
$View->addElement('p', txt('EMAIL_SPAM_INTRO'));
$View->addElement('div', $form, 'class="primary"');

// spam filters
$View->addTitle(txt('email_filter_title'));
$View->addElement('h2', txt('EMAIL_FILTER_TITLE'));
$View->addElement('p', txt('EMAIL_FILTER_intro'));



$filterform->addElement('html', $flist);
$View->addElement($filterform);
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
        if (!is_numeric(strpos($l, "'"))) continue;
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
        if (isset($i['spam_level'])) $level = $i['spam_level'];
        if (isset($i['spam_action'])) $action = $i['spam_action'];
    }

    return array($level, $action);

}


/**
 * This function gets all available filters from the constants EmailTargetFilter.
 */
function availableFilters() {

    global $Bofh, $View;

    $filters_raw = $Bofh->getData('get_constant_description', 'EmailTargetFilter');

    //sorting the filters
    $filters = array();
    foreach($filters_raw as $f) {
        $id = $f['code_str'];
        $txtkey_name = 'email_filter_data_'.$id;
        $txtkey_desc = 'email_filter_data_'.$id.'_desc';

        $filters[$id]['name'] = $id;
        //looking for a better name
        if (Text::exists($txtkey_name, $View->getLanguage())) {
            $filters[$id]['name'] = txt($txtkey_name);
        }

        $filters[$id]['desc'] = $f['description'];
        //looking for a better description
        if (Text::exists($txtkey_desc, $View->getLanguage())) {
            $filters[$id]['desc'] = txt($txtkey_desc, array('bofh_desc'=>$f['description']));
        }
    }

    return $filters;

}

/**
 * Gets what filters the user has active.
 */
function getActiveFilters() {

    global $User;
    global $Bofh;

    $all = $Bofh->getDataClean('email_info', $User->getUsername());

    if (empty($all['filters']) || $all['filters'] == 'None') return null;

    //the filters comes in a comma-separated string
    $rawf = explode(', ', $all['filters'][0]);
    foreach($rawf as $v) $filters[$v] = true;
    return $filters;

}



/**
 * Sets a filter on or off.
 */
function setFilters($data) {

    print_r($data);

    global $Bofh, $User;
    global $available_filters;
    global $active_filters;

    $err = false;

    // setting several in one go is supported by the loop
    foreach ($data as $filter => $value) {

        if (!isset($available_filters[$filter])) {
            View::addMessage(txt('email_filter_unknown'), View::MSG_WARNING);
            $err = true;
            continue;
        }

        // activating filter
        // TODO: comparing with text values is not recommended, change this behaviuor
        // when the template is made.
        if ($value == txt('email_filter_enable')) {
            // if already active
            if (isset($active_filters[$filter])) continue;

            try {
                $res = $Bofh->run_command('email_add_filter', $filter, $User->getUsername());
                View::addMessage($res);
            } catch(Exception $e) {
                Bofhcom::viewError($e);
                $err = true;
            }

        // disabling filter
        } else {
            // if already disabled
            if (!isset($active_filters[$filter])) continue;

            try {
                $res = $Bofh->run_command('email_remove_filter', $filter, $User->getUsername());
                View::addMessage($res);
            } catch(Exception $e) {
                Bofhcom::viewError($e);
                $err = true;
            }
        }

    }

    return $res;

}


?>
