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
$Bofh = Init::get('Bofh');

// getting the users groups
$adm_groups = getAdmGroups();
$normal_groups = getGroups();

// the group types which are handled here, other types (e.g. hosts) are ignored
$acceptable_group_types = array('group', 'account', 'person');

$View = Init::get('View');
$View->addTitle(txt('GROUPS_TITLE'));

if (!empty($_GET['group'])) { // SHOW A SPECIFIC GROUP
    if (!isset($normal_groups[$_GET['group']]) && !isset($adm_groups[$_GET['group']])) {
        View::forward('groups/', txt('groups_group_unknown'), View::MSG_WARNING);
    }
    $group = $Bofh->getDataClean('group_info', $_GET['group']);
    if (!$group) {
        View::forward('groups/', txt('groups_group_unknown'), View::MSG_WARNING);
    }

    $groupname = ($group['name']);
    $moderator = (isset($adm_groups[$groupname]) ? true : false); // can user moderate group or not

    if ($moderator) {
        // adding new members
        $newMember = groupmemberForm($groupname);
        if ($newMember->validate()) {
            $antall = $newMember->process('groupmemberFormProcess');
            View::forward('groups/?group='.$groupname);
        }

        // deleting member
        if (isset($_POST['del'])) {
            //to separate the types, the del-arrays first dimension refers to the type, e.g:
            // $_POST['del']['group']['info']['groupname']
            // $_POST['del']['account']['info']['username']
            // $_POST['del']['person']['info']['full name']
            //
            // $_POST['del']['person|group|account']['id']['id']
            //
            // This gives the the form:
            // <!--Array
            // (
            //   [del] => Array
            //     (
            //       [account] => Array
            //         (
            //           [341386] => joakimsh
            //         )
            //
            //       [person] => Array
            //         (
            //           [338378] => Eirik Vikan
            //         )
            //     )
            // )

            $delConfirm = formConfirmDelMembers($groupname); 

            // confirmation:
            if ($delConfirm->validate()) {
                // TODO: create a process function for this

                if (!empty($_POST['del']['group'])) {
                    foreach ($delConfirm->exportValue('del[group]') as $id => $d) {
                        delMembers($groupname, 'group', array($id));
                    }
                }
                if (!empty($_POST['del']['account'])) {
                    foreach ($delConfirm->exportValue('del[account]') as $id => $d) {
                        delMembers($groupname, 'account', array($id));
                    }
                }
                if (!empty($_POST['del']['person'])) {
                    foreach ($delConfirm->exportValue('del[person]') as $id => $d) {
                        delMembers($groupname, 'person', array($id));
                    }
                }
                View::forward('groups/?group='.$groupname);
            }

            $View->start();
            $View->addElement('h1', txt('groups_members_del_title'));
            $View->addElement($delConfirm);
            die;
        }

        $descForm = formDescription($groupname, $group['description']);
        if ($descForm->validate()) {
            $descForm->process('formDescriptionProcess');
            View::forward('groups/?group='.$groupname);
        }

        // form for deleting members if too many members
        $delform = getFormDeleteMembers($groupname);
        if ($delform->validate()) {
            $delform->process('formDeleteMembersProcess');
            View::forward('groups/?group='.$groupname);
        }
    }

    $View->start();
    $View->addElement('h1', txt('group_title', array('groupname'=>$groupname)));
    $primary = View::createElement('div', null, 'class="primary"');

    $dl = View::createElement('dl');
    if ($moderator) {
        $dl->addData(txt('group_description'), $descForm);
    } else {
        $dl->addData(txt('group_description'), ($group['description']));
    }
    unset($group['description']);

    $dl->addData(txt('group_create_date'), date('Y-m-d', $group['create_date']->timestamp));
    unset($group['create_date']);

    $dl->addData(txt('group_spread'), addHelpSpread(explode(',', $group['spread'])));
    unset($group['spread']);

    $dl->addData(txt('group_owner'), $group['owner']);
    unset($group['owner']);
    unset($group['owner_type']);
    unset($group['opset']);

    if (!empty($group['members'])) {
        $dl->addData(txt('group_members'), $group['members']);
    }

    //doesn't work for now
    //$dl->addData(txt('group_members'), array(
    //    txt('group_members_groups',  array('number'=>(isset($group['c_group']) ? $group['c_group'] : 0))),
    //    txt('group_members_accounts', array('number'=>(isset($group['c_account']) ? $group['c_account'] : 0))),
    //    txt('group_members_persons',  array('number'=>(isset($group['c_person']) ? $group['c_person'] : 0)))
    //));

    //getting the number of members (to avoid long listing)
    unset($group['c_group']);
    unset($group['c_account']);
    unset($group['c_person']);

    $primary->addData($dl);

    if (isset($_GET['more'])) {
        $primary->addData(View::createElement('a', txt('general_less_details'), 'groups/?group='.$groupname));
    } else {
        $primary->addData(View::createElement('a', txt('general_more_details'), 'groups/?group='.$groupname.'&more'));
    }

    if (isset($_GET['more'])) {

        $dl2 = View::createElement('dl');
        unset($group['type']);
        unset($group['name']);
        unset($group['entity_id']);
        unset($group['visibility']);
        asort($group);

        //print out the rest of the info
        foreach ($group as $k => $v) {
            if (!$v) continue;
            if (is_object($v)) {
                $v = date('Y-m-d', $v->timestamp);
            }
            $dl2->addData(ucfirst($k), $v);
        }
        $primary->addData($dl2);
    }

    $View->addElement($primary);

    //if moderator, adds member-functionality
    if ($moderator) {
        $View->addElement('h2', txt('groups_members_title'));
        $View->addElement('p', txt('groups_members_more'));
        $View->addElement($newMember);

        //the list of members
        try {
            $members = getMembers($groupname);
            if (count($members) > 0) {

                $max = ceil(count($members)/MAX_LIST_ELEMENTS_SPLIT)-1;
                $page = (empty($_GET['page']) ? 0 : intval($_GET['page']));

                //preventing empty list
                if ($page > $max) $page = $max;

                //making pageview
                if (count($members) > MAX_LIST_ELEMENTS_SPLIT) {
                    $pagelist = View::createElement('ul', null, 'class="pagenav"');

                    if ($page > 0) {
                        $pagelist->addData(View::createElement('a', txt('navigation_first'), "groups/?group=$groupname"));
                        $pagelist->addData(View::createElement('a', txt('navigation_previous'), "groups/?group=$groupname&page=".($page-1)));
                    }
                    for($i = 0; $i <= $max; $i++) {
                        $pagelist->addData(View::createElement('a', ($i+1), "groups/?group=$groupname&page=$i"));
                    }
                    if ($page < $max) {
                        $pagelist->addData(View::createElement('a', txt('navigation_next'), "groups/?group=$groupname&page=".($page+1)));
                        $pagelist->addData(View::createElement('a', txt('navigation_last'), "groups/?group=$groupname&page=".($max)));
                    }
                    $View->addElement($pagelist);
                }

                $table = View::createElement('table', null, 'class="app-table"');
                $table->setHead(null, txt('group_members_table_name'), txt('group_members_table_type'));

                //TODO: make a class for this kind of forms...
                $View->addElement('raw', '<form method="post" action="groups/?group='.$groupname.'" class="inline">'); 


                for ($i = $page*MAX_LIST_ELEMENTS_SPLIT; ($i < count($members)) && ($i < $page*MAX_LIST_ELEMENTS_SPLIT+MAX_LIST_ELEMENTS_SPLIT) ; $i++) {
                    $table->addData(array(
                        View::createElement('td', '<input type="checkbox" name="del['.$members[$i]['type'].']['.$members[$i]['id'].']" value="'.$members[$i]['name'].'" id="mem'.$members[$i]['id'].'">', 'class="less"'),
                        '<label for="mem'.$members[$i]['id'].'">' . $members[$i]['name'] . '</label>', 
                        $members[$i]['type']
                    ));
                }

                $View->addElement($table);
                $View->addElement('p', '<input type="submit" class="submit_warn" value="'.txt('groups_members_del_submit').'">');
                $View->addElement('raw', '</form>');
            }
        } catch (XML_RPC2_FaultException $e) {
            $View->addElement('p', txt('groups_members_too_many'));
            $View->addElement(getFormDeleteMembers($groupname));
        }
    }
    die;
}

// INDEX

$View->start();
$View->addElement('h1', txt('GROUPS_TITLE'));

// admin groups
$View->addElement('h2', txt('groups_moderative_title'));

if ($adm_groups == -1) {
    $View->addElement('p', txt('groups_too_many'));
} elseif ($adm_groups) {
    $table = View::createElement('table', null, 'class="app-table"');
    foreach ($adm_groups as $name => $description) {
        $table->addData(array(
            View::createElement('a', $name, "groups/?group=$name", 'title="Click for more info about this group"'),
            $description,
        ));
    }
    $table->setHead(
        txt('groups_table_groupname'),
        txt('groups_table_description')
        //'Action:'
    );
    $View->addElement($table);
} else {
    $View->addElement('p', txt('groups_empty_mod_list'));
}

if ($normal_groups) {
    $View->addElement('h2', txt('groups_others_title'));
    $othtable = View::createElement('table', null, 'class="app-table"');
    $othtable->setHead(
        txt('groups_table_groupname'),
        txt('groups_table_description')
    );

    foreach ($normal_groups as $name => $description) {
        // TODO: should we skip class-groups (e.g. uio:mn:ifi:inf1000:gruppe2) or not?
        if (is_numeric(strpos($name, ':'))) {
            continue;
        }
        $othtable->addData(View::createElement('tr', array(
            $name,
            $description,
        )));
    }
    $View->addElement($othtable);
}

// recommending a personal group
if (!isset($adm_groups[$User->getUsername()])) {
    try {
        $prs = $Bofh->run_command('group_info', $User->getUsername());
    } catch (XML_RPC2_FaultException $e) {
        $View->addElement('p', txt('groups_no_personal'));
    }
}

/**
 * Gets out the group info about a specific group.
 */
function getGroup($group)
{
    return Init::get('Bofh')->getDataClean('group_info', $group);

    //TODO: move the rest of this function to group/members.php

    //users in the group
    $ret['members'] = array();
    $ret['groups'] = array();
    foreach ($Bofh->getData('group_list', $group) as $u) {
        if ($u['type'] == 'group') {
            $ret['groups'][] = $u['name'];
        } else {
            //todo: more types than group and account?
            $ret['members'][] = $u['name'];
        }
        if ($u['op'] != 'union') trigger_error("Debugging: The group {$u['name']}'s op is not union, but {$u['type']}", E_USER_NOTICE);
    }

    $ret['name'] = $group;
    return $ret;

}

/**
 * Gets, and sort, all the groups a user is in.
 *
 * @return  Array   Normal array with just the group names
 */
function getGroups() {

    global $User;
    global $Bofh;

    $raw = $Bofh->getData('group_memberships', 'account', $User->getUsername());

    $groups = array();
    foreach ($raw as $g) {
        $groups[$g['group']] = $g['description'];
    }

    return $groups;

}

/**
 * Gets, and sorts, all the groups the user is moderator of.
 *
 * @return Array    Normal array with just the group names
 */
function getAdmGroups()
{
    $bofh = Init::get('Bofh');
    try {
        $raw = $bofh->run_command('access_list_alterable', 'group');
    } catch(XML_RPC2_FaultException $e) {
        View::addMessage($e);
        Bofhcom::viewError($e);
        throw $e;
    }

    $groups = array();
    foreach ($raw as $g) {
        $groups[$g['entity_name']] = $g['description'];
    }
    // sort by keys (groupname)
    ksort($groups);
    return $groups;
}


/**
 * Gets the list of members of a group
 */
function getMembers($group)
{
    global $acceptable_group_types;
    $Bofh = Init::get('Bofh');

    //using group_list for now, as it separates the type
    //of members. group_list_expanded lists indirect members too, 
    //which is not what we want
    $raw = $Bofh->run_command('group_list', $group);
    $ret = array();
    if ($raw) {
        foreach ($raw as $member) {
            if (in_array($member['type'], $acceptable_group_types)) {
                $ret[] = $member;
            }
        }
    }
    return $ret;
}

/**
 * Removes members from a given group
 *
 * @param  String    $group      The group to remove the members from
 * @param  String    $type       The type of members (person, account, group)
 * @param  Array     $members    An array of member ids to remove.
 * @return int                   The number of members that got removed.
 */
function delMembers($group, $type, $members)
{
    if (!$group || !$members) {
        return;
    }
    global $acceptable_group_types;
    if (!in_array($type, $acceptable_group_types)) {
        trigger_error("Unknown type '$type' for addMembers", E_USER_WARNING);
        return;
    }
    $Bofh = Init::get('Bofh');
    $removed = 0;
    foreach ($members as $mem) {
        try {
            $res = $Bofh->run_command('group_multi_remove', $type, $mem, $group);
            if (strpos($res, 'OK, removed ') === 0) {
                $removed++;
            } else {
                View::addMessage($res, View::MSG_WARNING);
            }
        } catch(Exception $e) {
            Bofhcom::viewError($e);
        }
    }
    return $removed;
}

/**
 * Adds members to a group
 *
 * @param  String    $group      The group to add the members in
 * @param  String    $type       The type of members (person, account, group)
 * @param  Array     $members    An array of members to add
 *
 * @return int                   Number of members that got added. Those who 
 *                               weren't added triggers a View message.
 */
function addMembers($group, $type, $members)
{
    if (!$group || !$members) {
        return;
    }
    global $acceptable_group_types;
    if (!in_array($type, $acceptable_group_types)) {
        trigger_error("Unknown type '$type' for addMembers", E_USER_WARNING);
        return;
    }
    $Bofh = Init::get('Bofh');
    $added = 0;
    foreach ($members as $m) {
        try {
            $res = $Bofh->run_command('group_multi_add', $type, $m, $group);
            // check for other messages than plain adds
            if (strpos($res, 'OK, added ') === 0) {
                $added++;
            } else {
                View::addMessage($res, View::MSG_WARNING);
            }
        } catch(Exception $e) {
            Bofhcom::viewError($e);
        }
    }
    return $added;
}

/**
 * Adds a description onto spreads. Works with both a string and
 * array of strings.
 * TODO: should this be moved to Bofhcom, or View maybe?
 *       Used both in groups/index.php and account/index.php
 *
 * @param mixed     Array or string with the spreads to describe
 * @return          Returns the same as in the input, but with longer string(s)
 */
function addHelpSpread($spreads_raw)
{
    if (is_array($spreads_raw)) {
        foreach ($spreads_raw as $k => $v) {
            if ($v) $spreads[$k] = addHelpSpread($v);
        }
    } else {
        $spreads_raw = trim($spreads_raw);
        global $Bofh;
        $desc = $Bofh->getSpread($spreads_raw);
        return $desc ? "$desc ($spreads_raw)" : $spreads_raw;
    }
    return $spreads;
}

/**
 * Return a form for adding group members.
 *
 * @param   String  $groupname  The name of the group to handle.
 */
function groupmemberForm($groupname)
{
    $newMember = new BofhFormUiO('newMember', null, 'groups/?group='.$groupname);
    $newMember->addElement('text', 'acc', txt('groups_members_form_account'));
    $newMember->addElement('text', 'grp', txt('groups_members_form_group'));
    $newMember->addElement('text', 'per', txt('groups_members_form_person'));

    $view = Init::get('View');
    $newMember->addElement('html', $view->createElement('ul', array(
        txt('groups_members_person_or_account'),
    )));
    $newMember->addElement('submit', null, txt('groups_members_form_submit'));
    return $newMember;
}

/**
 * Process a validated groupmemberForm.
 *
 * @param   Array   $input  HTML_QuickForm formatted input, sent by process().
 */
function groupmemberFormProcess($input)
{
    global $groupname;
    if (!$groupname) {
        throw new Exception('No groupname given!');
    }
    $added = 0;
    if ($input['per']) {
        $newper = preg_split('/[\s,]+/', $input['per']);
        $added += addMembers($groupname, 'person', $newper);
    }
    if ($input['acc']) {
        $newacc = preg_split('/[\s,]+/', $input['acc']);
        $added += addMembers($groupname, 'account', $newacc);
    }
    if ($input['grp']) {
        $newgrp = preg_split('/[\s,]+/', $input['grp']);
        $added += addMembers($groupname, 'group', $newgrp);
    }
    if ($added > 0) {
        View::addMessage(txt('GROUPS_MEMBERS_ADDED_SUCCESS', array(
            'no_members' => $added,
        )));
    }
}

/**
 * Return a form for deleting members by giving explicit usernames. Used if a group 
 * is too large to list all members.
 */
function getFormDeleteMembers($groupname)
{
    $form = new BofhFormUiO('deleteMembersTooMany', null, 'groups/?group='.$groupname);
    $form->addElement('text', 'accounts', txt('groups_members_form_del_account'));
    $form->addElement('text', 'groups',   txt('groups_members_form_del_group'));
    $form->addElement('text', 'persons',  txt('groups_members_form_del_person'));
    $form->addElement('submit', null, txt('groups_members_del_submit'));
    return $form;
}

/**
 * Process a delete members form by trying to remove all the members from the 
 * group.
 */
function formDeleteMembersProcess($input)
{
    global $groupname, $adm_groups;
    if (!$groupname || empty($adm_groups[$groupname])) {
        throw new Exception("Bogus group '$groupname', can't remove members");
    }
    $removed = 0;
    if ($input['persons']) {
        $persons = preg_split('/[\s,]+/', $input['persons']);
        $removed += delMembers($groupname, 'person', $persons);
    }
    if ($input['accounts']) {
        $accounts = preg_split('/[\s,]+/', $input['accounts']);
        $removed += delMembers($groupname, 'account', $accounts);
    }
    if ($input['groups']) {
        $groups = preg_split('/[\s,]+/', $input['groups']);
        $removed += delMembers($groupname, 'group', $groups);
    }
    if ($removed > 0) {
        View::addMessage(txt('GROUPS_MEMBERS_REMOVED_SUCCESS', array(
            'no_members' => $removed,
        )));
    }
    return $removed;
}

/**
 * Create a form for confirming the deletion of group members.
 *
 * TODO: the function makes use of $_POST directly, (should be changed), and 
 * is on the form:
 *  $_POST[member_type][member_id] = member_name
 *
 * Note that the function doesn't check if the given members actually are 
 * members of the group, or that the user is allowed to moderate the group.  
 * That is up to the code that calls this function, and the authorisation in
 * bofhd.
 *
 * @param  String   $groupname  The name of the group
 * @param  Array    $members    The list of members the user wants to remove.
 */
function formConfirmDelMembers($groupname, $members = null)
{
    $form = new BofhFormUiO('confirmDelMembers', null, 'groups/?group='.$groupname);
    $form->addElement('html', View::createElement('p', 
        txt('groups_members_del_confirm', array('groupname' => $groupname))
    ));

    // group the members by member type
    $delList = array();
    foreach ($_POST['del'] as $type => $members) {
        foreach ($members as $id => $name) {
            if ($type == 'group') {
                $delGr[] = $form->createElement('checkbox', $id, null, $name, 'checked="checked"');
            } elseif($type == 'account') {
                $delAc[] = $form->createElement('checkbox', $id, null, $name, 'checked="checked"');
            } elseif($type == 'person') {
                $delPe[] = $form->createElement('checkbox', $id, null, $name, 'checked="checked"');
            }
        }
    }

    if (!empty($delGr)) {
        $form->addGroup($delGr, 'del[group]',   txt('groups_members_del_groups'),   "<br>\n", true);
    }
    if (!empty($delAc)) {
        $form->addGroup($delAc, 'del[account]', txt('groups_members_del_accounts'), "<br>\n", true);
    }
    if (!empty($delPe)) {
        $form->addGroup($delPe, 'del[person]',  txt('groups_members_del_persons'),  "<br>\n", true);
    }

    $form->addElement('hidden', 'okDeleteConfirm', true);
    $form->addElement('submit', 'okDelete', txt('groups_members_del_submit'), 'class="submit_warn"');
    $form->addElement('html', '<a href="groups/?group='.$groupname.'">'.txt('groups_members_del_cancel').'</a>');
    return $form;
}

/**
 * Create a form for changing the description of a group.
 *
 * @param  String   $groupname   The name of the given group
 * @param  String   $description The current description for the group.
 */
function formDescription($groupname, $description)
{
    $form = new BofhFormInline('setDesc', null, 'groups/?group='.$groupname);
    $form->addElement('text', 'desc', null, array(
        'value' => $description,
        'style' => 'width: 50%',
    ));
    $form->addElement('submit', 'doSet', txt('group_description_submit'));
    return $form;
}

/**
 * Process the description change of the group.
 */
function formDescriptionProcess($input)
{
    global $groupname;
    $bofh = Init::get('Bofh');
    try {
        $res = $bofh->run_command('group_set_description', $groupname, $input['desc']);
        View::addMessage($res);
        return true;
    } catch(Exception $e) {
        Bofhcom::viewError($e);
        return false;
    }
}

?>
