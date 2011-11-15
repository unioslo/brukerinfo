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
$User = Init::get('User');
$Bofh = new Bofhcom();
$View = Init::get('View');

// Only employees are allowed to use this command
if(!$Bofh->isEmployee()) View::forward('groups/', txt('employees_only'));

// group spreads possible to use
$spreads = getSpreads();

// the request form
$newform = new BofhFormUiO('newGroup');
$newform->setAttribute('class', 'app-form-big');
$n = $newform->addElement('text', 'gr_name', txt('groups_new_form_name'));
$n->setAttribute('id', 'group_name');
$newform->addElement('text', 'gr_desc', txt('groups_new_form_desc'));
$newform->addElement('text', 'gr_mod',  txt('groups_new_form_moderator'));

$choosespreads = array();
foreach($spreads as $spread => $description) {
    $choosespreads[] = HTML_Quickform::createElement('checkbox', $spread, null, 
                       "$spread <span class=\"ekstrainfo\">- $description</span>");
}
$newform->addGroup($choosespreads, 'gr_spreads', 
                    txt('groups_new_form_spreads'), "<br />\n");

$newform->addElement('submit', null, txt('groups_new_form_submit'));

$newform->addRule('gr_name', txt('groups_new_form_name_required'), 'required');
$newform->addRule('gr_desc', txt('groups_new_form_desc_required'), 'required');
$newform->addRule('gr_mod', txt('groups_new_form_mod_required'), 'required');

if($newform->validate()) {
    if($ret = $newform->process('request_group')) {
        View::forward('groups/', $ret);
    }
}

$View->setFocus('#group_name');
$View->addTitle(txt('GROUPS_NEW_TITLE'));
$View->start();
$View->addElement('h1', txt('groups_new_title'));
$View->addElement('p', txt('groups_new_intro'));
$View->addElement($newform);



/**
 * Send the group_request function to bofhd.
 * Data is gotten through HTML_QuickForm and
 * is therefore in an array.
 */

function request_group($data) { 
    global $Bofh;
    global $spreads;

    if(!$Bofh->isEmployee()) return false;

    // Making all the values oneliners
    // since the commands are being sent through email and is then
    // copy-pasted through a superusers bofh-prompt, bogus commands
    // could easily be put inbetween the lines, e.g:
    //   Group name: testgroup\n group add baduser brukerreg
    // This would be easy to detect, but you don't want to risk it.
    $data = oneliners($data);

    $data['spreads'] = null;
    if (!empty($data['gr_spreads'])) {
        foreach ($data['gr_spreads'] as $key => $sp) {
            if (!isset($spreads[$key])) {
                View::addMessage(txt('groups_new_form_spread_unknown', 
                                      array('spread'=>$key)), 
                                 View::MSG_WARNING);
                return false;
            }
        }
        //getting spreads
        $data['spreads'] = implode(' ', array_keys($data['gr_spreads']));
    }

    try {
        $ret = $Bofh->run_command('group_request', 
            $data['gr_name'],
            $data['gr_desc'],
            $data['spreads'],
            $data['gr_mod']
        );
        //todo: check how this is returned - exception or string?
    } catch(Exception $e) {
        Bofhcom::viewError($e);
        return false;
    }

    return $ret;
}

/**
 * Strip newliners out of data
 */
function oneliners($data) {
    if(is_array($data)) {
        foreach($data as $k => $v) $data[$k] = oneliners($v);
    } else {
        return str_replace(array("\n","\r"), ' ', $data);
    }
    return $data;
}


/**
 * Get an array of all the spreads available for groups.
 */
function getSpreads() {

    global $Bofh;
    $raw = $Bofh->getData('spread_list');
    //$raw = $Bofh->getData('get_constant_description', 'Spread');
    $spreads = array();
    foreach($raw as $spread) {
        if ($spread['type'] == 'group' && empty($spread['auto'])) {
            $spreads[$spread['name']] = $spread['desc'];
        }
    }

    ksort($spreads);
    return $spreads;
}

?>
