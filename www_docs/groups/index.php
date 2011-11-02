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
$Bofh = Init::get('Bofh');

// getting the users groups
$adm_groups = getAdmGroups();
$normal_groups = getGroups();

// the group types which are handled here, other types (e.g. hosts) are ignored
$acceptable_group_types = array('group', 'account', 'person');


$View = Init::get('View');
$View->addTitle(txt('GROUPS_TITLE'));






// SHOW A SPECIFIC GROUP

if(!empty($_GET['group'])) {

    //checking if the group exists
    if(!isset($normal_groups[$_GET['group']]) && !isset($adm_groups[$_GET['group']])) {
        View::forward('groups/', txt('groups_group_unknown'), View::MSG_WARNING);
    }

    $group = $Bofh->getDataClean('group_info', $_GET['group']);

    if(!$group) {
        View::forward('groups/', txt('groups_group_unknown'), View::MSG_WARNING);
    }

    $groupname = ($group['name']);
    $moderator = (isset($adm_groups[$_GET['group']]) ? true : false); // can user moderate group or not

    if($moderator) {

        $newMember = new BofhForm('newMember', null, 'groups/?group='.$groupname);
        $newMember->addElement('text', 'acc', txt('groups_members_form_account'));
        $newMember->addElement('text', 'grp', txt('groups_members_form_group'));
        $newMember->addElement('text', 'per', txt('groups_members_form_person'));
        $newMember->addElement('html', View::createElement('ul', array(
            txt('groups_members_person_or_account'))));
        $newMember->addElement('submit', null, txt('groups_members_form_submit'));

        // adding new members
        if($newMember->validate()) {

            if($newMember->exportValue('per')) {
                $newper = preg_split('/[\s,]+/', $newMember->exportValue('per'));
                addMembers($groupname, 'person', $newper);
            }
            if($newMember->exportValue('acc')) {
                $newacc = preg_split('/[\s,]+/', $newMember->exportValue('acc'));
                addMembers($groupname, 'account', $newacc);
            }
            if($newMember->exportValue('grp')) {
                $newgrp = preg_split('/[\s,]+/', $newMember->exportValue('grp'));
                addMembers($groupname, 'group', $newgrp);
            }
            View::forward('groups/?group='.$groupname);

        }

        // deleting member
        if(isset($_POST['del'])) {

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

            $delConfirm = new BofhForm('confirmDelMembers', null, 'groups/?group='.$groupname);
            $delConfirm->addElement('html', View::createElement('p', txt('groups_members_del_confirm', array('groupname'=>$groupname))));

            $delList = array();
            foreach($_POST['del'] as $v => $delt) {
                foreach($delt as $id => $del) {
                    if($v == 'group') {
                        $delGr[] = $delConfirm->createElement('checkbox', $id, null, $del, 'checked="checked"');
                    } elseif($v == 'account') {
                        $delAc[] = $delConfirm->createElement('checkbox', $id, null, $del, 'checked="checked"');
                    } elseif($v == 'person') {
                        $delPe[] = $delConfirm->createElement('checkbox', $id, null, $del, 'checked="checked"');
                    }
                }
            }

            if(!empty($delGr)) $delConfirm->addGroup($delGr, 'del[group]',   txt('groups_members_del_groups'),   "<br>\n", true);
            if(!empty($delAc)) $delConfirm->addGroup($delAc, 'del[account]', txt('groups_members_del_accounts'), "<br>\n", true);
            if(!empty($delPe)) $delConfirm->addGroup($delPe, 'del[person]',  txt('groups_members_del_persons'),  "<br>\n", true);

            $delConfirm->addElement('hidden', 'okDeleteConfirm', true);
            $delConfirm->addElement('submit', 'okDelete', txt('groups_members_del_submit'), 'class="submit_warn"');
            $delConfirm->addElement('html', '<a href="groups/?group='.$groupname.'">'.txt('groups_members_del_cancel').'</a>');

            //confirmation:
            if($delConfirm->validate()) {
                if(!empty($_POST['del']['group'])) foreach($delConfirm->exportValue('del[group]') as $id => $d) {
                    delMembers($groupname, 'group', $id);
                }
                if(!empty($_POST['del']['account'])) foreach($delConfirm->exportValue('del[account]') as $id => $d) {
                    delMembers($groupname, 'account', $id);
                }
                if(!empty($_POST['del']['person'])) foreach($delConfirm->exportValue('del[person]') as $id => $d) {
                    delMembers($groupname, 'person', $id);
                }

                View::forward('groups/?group='.$groupname);
            }

            $View->start();
            $View->addElement('h1', txt('groups_members_del_title'));
            $View->addElement($delConfirm);
            die;
        }


        $setDesc = new BofhFormInline('setDesc', null, 'groups/?group='.$groupname);
        $setDesc->addElement('text', 'desc', null, array('value'=>($group['description']), 'style'=>'width: 50%'));
        $setDesc->addElement('submit', 'doSet', txt('group_description_submit'));

        if($setDesc->validate()) {

            try {
                $res = $Bofh->run_command('group_set_description', $groupname, $setDesc->exportValue('desc'));
                View::addMessage($res);
            } catch(Exception $e) {
                Bofhcom::viewError($e);
            }

            View::forward('groups/?group='.$groupname);

        }
    }


    $View->start();
    $View->addElement('h1', txt('group_title', array('groupname'=>$groupname)));
    $primary = View::createElement('div', null, 'class="primary"');

    $dl = View::createElement('dl');
    if($moderator) {
        $dl->addData(txt('group_description'), $setDesc);
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

    if(!empty($group['members'])) {
        $dl->addData(txt('group_members'), $group['members']);
    }
        
    //doesn't work for now
    //$dl->addData(txt('group_members'), array(
    //    txt('group_members_groups',  array('number'=>(isset($group['c_group']) ? $group['c_group'] : 0))),
    //    txt('group_members_accounts', array('number'=>(isset($group['c_account']) ? $group['c_account'] : 0))),
    //    txt('group_members_persons',  array('number'=>(isset($group['c_person']) ? $group['c_person'] : 0)))
    //));

    //getting the number of members (to avoid long listing)
    $total_members = 0;
    //$total_members += (isset($group['c_group']) ? $group['c_group'] : 0);
    //$total_members += (isset($group['c_account']) ? $group['c_account'] : 0);
    //$total_members += (isset($group['c_person']) ? $group['c_person'] : 0);


    unset($group['c_group']);
    unset($group['c_account']);
    unset($group['c_person']);




    $primary->addData($dl);

    if(isset($_GET['more'])) {
        $primary->addData(View::createElement('a', txt('general_less_details'), 'groups/?group='.$groupname));
    } else {
        $primary->addData(View::createElement('a', txt('general_more_details'), 'groups/?group='.$groupname.'&more'));
    }

    if(isset($_GET['more'])) {

        $dl2 = View::createElement('dl');
        unset($group['type']);
        unset($group['name']);
        unset($group['entity_id']);
        unset($group['visibility']);
        asort($group);

        //print out the rest of the info
        foreach($group as $k=>$v) {
            if(!$v) continue;
            $dl2->addData(ucfirst($k), $v);
        }
        $primary->addData($dl2);

    }

    $View->addElement($primary);

    //if moderator, adds member-functionality
    if($moderator) {

        $View->addElement('h2', txt('groups_members_title'));
        $View->addElement('p', txt('groups_members_more'));
        $View->addElement($newMember);

        if($total_members > MAX_LIST_ELEMENTS) {

            $View->addElement('p', txt('groups_members_too_many'));

        } else {

            //the list of members
            $members = getMembers($groupname);

            if($members) { // GROUP LIST

                $max = ceil(count($members)/MAX_LIST_ELEMENTS_SPLIT)-1;
                $page = (empty($_GET['page']) ? 0 : intval($_GET['page']));

                //preventing empty list
                if($page > $max) $page = $max;

                //making pageview
                if(count($members) > MAX_LIST_ELEMENTS_SPLIT) {
                    $pagelist = View::createElement('ul', null, 'class="pagenav"');

                    if($page > 0) {
                        $pagelist->addData(View::createElement('a', txt('navigation_first'), "groups/?group=$groupname"));
                        $pagelist->addData(View::createElement('a', txt('navigation_previous'), "groups/?group=$groupname&page=".($page-1)));
                    }
                    for($i = 0; $i <= $max; $i++) {
                        $pagelist->addData(View::createElement('a', ($i+1), "groups/?group=$groupname&page=$i"));
                    }
                    if($page < $max) {
                        $pagelist->addData(View::createElement('a', txt('navigation_next'), "groups/?group=$groupname&page=".($page+1)));
                        $pagelist->addData(View::createElement('a', txt('navigation_last'), "groups/?group=$groupname&page=".($max)));
                    }
                    $View->addElement($pagelist);
                }

                $table = View::createElement('table');
                $table->setHead(null, txt('group_members_table_name'), txt('group_members_table_type'));

                //TODO: make a class for this kind of forms...
                $View->addElement('raw', '<form method="post" action="groups/?group='.$groupname.'" class="inline">'); 


                for($i = $page*MAX_LIST_ELEMENTS_SPLIT; ($i < count($members)) && ($i < $page*MAX_LIST_ELEMENTS_SPLIT+MAX_LIST_ELEMENTS_SPLIT) ; $i++) {
                    $table->addData(array(
                        View::createElement('td', '<input type="checkbox" name="del['.$members[$i]['type'].']['.$members[$i]['id'].']" value="'.$members[$i]['name'].'" id="mem'.$members[$i]['id'].'">', 'class="less"'),
                        '<label for="mem'.$members[$i]['id'].'">' . $members[$i]['name'] . '</label>', 
                        $members[$i]['type']
                    ));
                };

                $View->addElement($table);
                $View->addElement('p', '<input type="submit" class="submit_warn" value="'.txt('groups_members_del_submit').'">');
                $View->addElement('raw', '</form>');
            }
        }

    }

    die;

}




// INDEX

$View->start();
$View->addElement('h1', txt('GROUPS_TITLE'));

// admin groups
$View->addElement('h2', txt('groups_moderative_title'));

if($adm_groups == -1) {

    $View->addElement('p', txt('groups_too_many'));

} elseif($adm_groups) {

    $table = View::createElement('table', null);

    foreach($adm_groups as $n => $g) {

        $table->addData(array(
            View::createElement('a', $n, "groups/?group=$n", 'title="Click for more info about this group"'),
            $g
            //View::createElement('a', 'Manage members', "groups/members.php?group=$n", 'title="Click for managing the members of this group"')
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



if($normal_groups) {

    $View->addElement('h2', txt('groups_others_title'));
    $othtable = View::createElement('table');
    $othtable->setHead(
        txt('groups_table_groupname'),
        txt('groups_table_description')
    );

    foreach($normal_groups as $g => $d) {
        //skip class-groups (e.g. uio:mn:ifi:inf1000:gruppe2)
        if(is_numeric(strpos($g, ':'))) continue;
        $othtable->addData(View::createElement('tr', array(
            //View::createElement('a', $g, "groups/?group=$g", 'title="Click for more info about this group"'),
            $g,
            $d
        )));
    }

    $View->addElement($othtable);
}

// recommending a personal group
if(!isset($adm_groups[$User->getUsername()])) {
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
    foreach($Bofh->getData('group_list', $group) as $u) {
        if($u['type'] == 'group') {
            $ret['groups'][] = $u['name'];
        } else {
            //todo: more types than group and account?
            $ret['members'][] = $u['name'];
        }
        if($u['op'] != 'union') trigger_error("Debugging: The group {$u['name']}'s op is not union, but {$u['type']}", E_USER_NOTICE);
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
    foreach($raw as $g) {
        $groups[$g['group']] = $g['description'];
    }

    return $groups;

}

/**
 * Gets, and sorts, all the groups the user is moderator of.
 *
 * @return Array    Normal array with just the group names
 */
function getAdmGroups() {
    global $Bofh;
    try {
        $raw = $Bofh->run_command('access_list_alterable', 'group');
    } catch(Exception $e) {
        //View::addMessage($e);
        Bofhcom::viewError($e);
        return -1;
    }

    $groups = array();
    foreach($raw as $g) {
        $groups[$g['entity_name']] = $g['description'];
    }
    // sort by keys (groupname)
    ksort($groups);
    return $groups;
}


/**
 * Gets the list of members of a group
 */
function getMembers($group) {

    global $Bofh;
    global $acceptable_group_types;

    //using group_list for now, as it separates the type
    //of members. group_list_expanded lists indirect members too, 
    //which is not what we want
    $raw = $Bofh->getData('group_list', $group);
    foreach($raw as $member) {
        if(in_array($member['type'], $acceptable_group_types)) $ret[] = $member;
    }

    return $ret;

}

/**
 * Removes member(s) from a group
 */
function delMembers($group, $type, $names) {

    if(!$group || !$names) return;
    if(!($type == 'person' || $type == 'group' || $type = 'account')) {
        trigger_error("Unknown type '$type' for addMembers", E_USER_WARNING);
        return;
    }

    global $Bofh;

    //todo: make this method handle arrays later on:
    //if(is_array($names)) {
      //  $names = implode(''
    // or in a loop
    //}

    try {
        $res = $Bofh->run_command('group_multi_remove', $type, $names, $group);
        View::addMessage($res);
        return true;
    } catch(Exception $e) {
        Bofhcom::viewError($e);
        return false;
    }

}

/**
 * Adds members to a group
 *
 * @param String    $group      The group to add the members in
 * @param String    $type       The type of members (person, account, group)
 * @param mixed     $members    An array of members to add
 */
function addMembers($group, $type, $members) {

    if(!$group || !$members) return;
    if(!($type == 'person' || $type == 'group' || $type = 'account')) {
        trigger_error("Unknown type '$type' for addMembers", E_USER_WARNING);
        return;
    }

    global $Bofh;

    foreach($members as $m) {
        try {
            $res = $Bofh->run_command('group_multi_add', $type, $m, $group);
            View::addMessage($res);
        } catch(Exception $e) {
            Bofhcom::viewError($e);
        }
    }

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
function addHelpSpread($spreads_raw) {
 
    if(is_array($spreads_raw)) {
        foreach($spreads_raw as $k => $v) {
            if($v) $spreads[$k] = addHelpSpread($v);
        }
    } else {
        $spreads_raw = trim($spreads_raw);

        global $Bofh;
        $desc = $Bofh->getSpread($spreads_raw);
        if($desc) return $desc;
        else return $spreads_raw;
    }

    return $spreads;
}

?>
