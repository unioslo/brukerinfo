<?php
// Copyright 2009-2016 University of Oslo, Norway
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

class Groups extends ModuleGroup {
    private $modules;
    private $authz;
    public function __construct($modules) {
        $this->modules = $modules;
        $this->authz = Init::get('Authorization');
        if (!($this->isUioOrUit(INST) && $this->authz->is_guest())){
            $modules->addGroup($this);
        }
    }

    public function getName() {
        return 'groups';
    }

    public function getInfoPath() {
        return array('groups');
    }

    public function getSubgroups() {
        $ret = array();
        if ( INST == 'uio' && $this->authz->can_create_groups()) {
                $ret[] = '';
                $ret[] = 'memberships';
                $ret[] = 'new';
        }
        return $ret;
    }

    public function getHiddenRoutes() {
        return array('personal');
    }

    public function getShortcuts() {
        if (INST == 'uio' && $this->authz->can_create_groups()) {
            return array(
                array('groups/', txt('home_shortcuts_members')),
                array('groups/new/', txt('home_shortcuts_group_request'))
            );
        } elseif (INST == 'uit'){
            return array();
        }
        return array(
            array('groups/', txt('home_shortcuts_members')),
        );
    }

    public function display($path) {
        if (!$path) {
            return $this->index();
        }
        switch ($path[0]) {
        case '': case 'index':
            return $this->index();
        case 'new':
            return $this->newgrp();
        case 'memberships':
            return $this->group_memberships();
        case 'personal':
            return $this->personal();
        }
    }

    public function index() {
        global $groupname, $acceptable_group_types;
        $User = Init::get('User');
        $Bofh = Init::get('Bofh');

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
                Bofhcom::viewError($e);
                return -1;
            }

            $groups = array();
            foreach ($raw as $g) {
                $groups[$g['entity_name']] = $g;
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
                $Bofh = Init::get("Bofh");
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
                View::addMessage('No group name given.', View::MSG_ERROR);
                return;
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
                View::addMessage("Bogus group '$groupname', can't remove members",
                    View::MSG_ERROR);
                return;
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
            $form->addElement('submit', 'okDelete', txt('groups_members_del_submit'), 'class="submit"');
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

        function formExpires($groupname, $expiredate)
        {
            $form = new BofhFormInline('setExpire', null, 'groups/?group='.$groupname);
            $form->addElement('static', null, '<input class="flatpickr flatpickr-input" type="text" id="groupDatePicker" value="'.$expiredate.'">');
            $form->addElement('submit', 'datepicker-subm', txt('group_expires_submit'));
            return $form;
        }

        function formExpiresProcess($input)
        {
            global $groupname;
            $bofh = Init::get('Bofh');
            $input_date = date('Y-m-d');
            $one_year = date('Y-m-d', strtotime('+1 years'));
            if (isset($_POST["_qf__setExpire"])) {
                $input_date = date('Y-m-d', strtotime($_POST["_qf__setExpire"]));
            }
            if ($input_date > $one_year || $input_date < date('Y-m-d')) {
                View::addMessage('Max one year into the future');
                return false;
            }
            try {
                $res = $bofh->run_command('group_set_expire', $groupname, $input_date);
                View::addMessage($res);
                return true;
            } catch(Exception $e) {
                Bofhcom::viewError($e);
                return false;
            }
        }

        /**
         * Process the deletion or undeleting the group
         */
        function formDeleteGroupProcess($input){
            global $groupname;
            $bofh = Init::get('Bofh');
            try {
                if (isset($input['okReactivateConfirm'])) {
                    $res = $bofh->run_command('group_set_expire', $groupname);
                } else {
                    $res = $bofh->run_command('group_delete', $groupname);
                }
                View::addMessage($res);
                return true;
            } catch(Exception $e) {
                Bofhcom::viewError($e);
                return false;
            }
        }

        $adm_groups = getAdmGroups();

        // the group types which are handled here, other types (e.g. hosts) are ignored
        $acceptable_group_types = array('group', 'account', 'person');

        $View = Init::get('View');
        $View->addTitle(txt('GROUPS_TITLE'));

        if (!empty($_GET['group'])) { // SHOW A SPECIFIC GROUP
            if (!isset($manual_groups[$_GET['group']]) && !isset($adm_groups[$_GET['group']])) {
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

                // form for deleting a group.
                $delGroupForm = new BofhFormUiO('delGroup', null, 'groups/?group='.$groupname);
                $delGroupForm->setAttribute('class', 'app-form-big');

                if ($delGroupForm->validate()) {
                    // delete and undelete the group
                    $delGroupForm->process('formDeleteGroupProcess');

                    View::forward('groups/?group='.$groupname);
                }

                $expire_date = htmlspecialchars("<not set>");
                if (isset($group['expire_date'])) {
                    $date = $group['expire_date'];
                    $expire_date = $date->format('Y-m-d');
                }

                $expireForm = formExpires($groupname, $expire_date);
                if ($expireForm->validate()) {
                    $expireForm->process('formExpiresProcess');
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

            if (isset($group['expire_date'])){
                if ($group['expire_date'] < new DateTime()) {
                    $dl->addData('Status:', '<font color="red">' . txt('groups_delete_status') . '</font>');
                }
            }

            $dl->addData(txt('group_create_date'), ($group['create_date']) ? $group['create_date']->format(txt('date_format')) : '');
            unset($group['create_date']);

            $dl->addData(txt('group_spread'), addHelpSpread(explode(',', $group['spread'])));
            unset($group['spread']);

            $dl->addData(txt('group_owner'), $group['owner']);
            unset($group['owner']);
            unset($group['owner_type']);
            unset($group['opset']);

            if ($moderator && isset($group['expire_date'])) {
                $dl->addData(txt('group_expires'), $expireForm);
            }

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
                    if ($v instanceof DateTime) {
                        $v = $v->format(txt('date_format'));
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
                        $table->setHead(null, txt('group_members_table_user_name'), txt('group_members_table_full_name'), txt('group_members_table_type'));

                        //TODO: make a class for this kind of forms...
                        $View->addElement('raw', '<form method="post" action="groups/?group='.$groupname.'" class="app-form">');


                        for ($i = $page*MAX_LIST_ELEMENTS_SPLIT; ($i < count($members)) && ($i < $page*MAX_LIST_ELEMENTS_SPLIT+MAX_LIST_ELEMENTS_SPLIT) ; $i++) {
                            $table->addData(array(
                                View::createElement('td', '<input type="checkbox" name="del['.$members[$i]['type'].']['.$members[$i]['id'].']" value="'.$members[$i]['name'].'" id="mem'.$members[$i]['id'].'">', 'class="less"'),
                                '<label for="mem'.$members[$i]['id'].'">' . $members[$i]['user_name'] .'</label>',
                                $members[$i]['full_name'],
                                $members[$i]['type']
                            ));
                        }

                        $View->addElement($table);
                        $View->addElement('p', '<input type="submit" class="submit" value="'.txt('groups_members_del_submit').'">');
                        $View->addElement('raw', '</form>');
                    }
                } catch (XML_RPC2_FaultException $e) {
                    $View->addElement('p', txt('groups_members_too_many'));
                    $View->addElement(getFormDeleteMembers($groupname));
                }
            }
            // If moderated, enable group deletion
            if ($moderator) {
                $View->addElement('h2', txt('groups_delete_title'));

                if (isset($group['expire_date'])) {
                    $View->addElement('p', txt('groups_delete_expired_text'));
                    $delGroupForm->addElement('hidden', 'okReactivateConfirm', true);
                    $delGroupForm->addElement('submit', null, txt('groups_delete_undelete_button_text'));
                    $View->addElement($delGroupForm);
                } else {
                    $View->addElement('p', txt('groups_delete_text'));
                    $delGroupForm->addElement('hidden', 'okDeleteConfirm', true);
                    $delGroupForm->addElement('submit', null, txt('groups_delete_button_text'));
                    $View->addElement($delGroupForm);
                }
            }
            die;
        }

        // INDEX

        $View->start();
        $View->addElement('h1', txt('GROUPS_TITLE'));

        if ($adm_groups == -1) {
            $View->addElement('p', txt('groups_too_many'));
        } elseif ($adm_groups) {
            $table = View::createElement('table', null, 'class="app-table"');
            foreach ($adm_groups as $name => $group) {
                $table->addData(array(
                    View::createElement('a', $name, "groups/?group=$name", 'title="Click for more info about this group"'),
                    $group['description'], $group['expire_date'] ? $group['expire_date']->format('Y-m-d') : 'Not set',
                ));
            }
            $table->setHead(
                txt('groups_table_groupname'),
                txt('groups_table_description'),
                txt('groups_table_expires')
                //'Action:'
            );
            $View->addElement($table);
        } else {
            if (INST != 'uit') {
                $View->addElement('p', txt('groups_empty_mod_list'));
            }
        }

        // recommending a personal group
        if (INST == 'uio' && !isset($adm_groups[$User->getUsername()])) {
            try {
                $prs = $Bofh->run_command('group_info', $User->getUsername());
            } catch (XML_RPC2_FaultException $e) {
                $View->addElement('p', txt('groups_no_personal'));
            }
        }
    }


    public function newgrp() {
        /**
         * Send the group_request function to bofhd.
         * Data is gotten through HTML_QuickForm and
         * is therefore in an array.
         */

        function request_group($data) {
            $Bofh = Init::get("Bofh");
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
                $ret = $Bofh->run_command(
                    'group_request',
                    $data['gr_name'],
                    $data['gr_desc'],
                    $data['spreads'],
                    $data['gr_mod']
                );
                if ($ret == NULL) {
                    throw new Exception(
                        "bofhd emrror: No request will be sent.");
                }
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
        $User = Init::get('User');
        $Bofh = new Bofhcom();
        $View = Init::get('View');

        // Only employees are allowed to use this command
        if(!$Bofh->isEmployee()) View::forward('groups/', txt('employees_only'));

        // group spreads possible to use
        global $spreads;
        $spreads = getSpreads();

        // the request form
        $newform = new BofhFormUiO('newGroup', null, 'groups/new/');
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
        $newform->addRule('gr_name', txt('latin1_only_required'), 'latin1_only');
        $newform->addRule('gr_desc', txt('latin1_only_required'), 'latin1_only');

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
    }


    public function group_memberships() {

        $User = Init::get('User');
        $Bofh = new Bofhcom();

        /**
         * Gets, and sort, all the groups a user is in.
         *
         * @return  Array   Normal array with groups info dicts
         */
        function getGroups()
        {
            global $User;
            global $Bofh;
            $raw = $Bofh->getData('wofh_all_group_memberships', $User->getUsername());

            $groups = array();
            foreach ($raw as $g) {
                $groups[$g['group']] = $g;
            }

            return $groups;
        }

        /**
         * Helper function used to filter groups on a there group type.
         *
         * @param groups    Array of groups.
         * @param types     Array of one or more groupe type strings.
         */
        function filter_groups_on_types($groups, $types)
        {
            $filtered_groups = array_filter(
                $groups, function($group) use ($types){
                    return in_array($group['group_type'], $types);
                }
            );

            # sort by keys (groupname)
            ksort($filtered_groups);
            return $filtered_groups;
        }

        /**
         * Filters a array of groups on automatic group types.
         *
         * Groups with type 'lms-group' are removed as well.
         *
         * @param groups    Array of groups.
         * @return Array    Array with only automatic groups.
         */
        function getAutomaticGroups($groups)
        {
            $automatic_group_types = ['affiliation-group', 'virtual-group'];
            return filter_groups_on_types($groups, $automatic_group_types);
        }

        /**
         * Filters a array of groups on manual group types.
         *
         * @param groups    Array of groups.
         * @return Array    Array with only manual groups.
         */
         function getManualGroups($groups)
        {
            $manual_group_types = ['internal-group', 'personal-group', 'unknown-group', 'manual-group'];
            return filter_groups_on_types($groups, $manual_group_types);
        }


        // getting the users groups
        $all_groups = getGroups();
        $automatic_groups = getAutomaticGroups($all_groups);
        $manual_groups = getManualGroups($all_groups);


        $View = Init::get('View');
        $View->addTitle('Mine grupper!!');
        $View->start();

        $View->addElement('h1', txt('groups_title_memberships'));

        if ($automatic_groups) {
            $View->addElement('h2', txt('groups_title_automatic'));
            $table = View::createElement('table', null, 'class="app-table"');
            $table->setHead(
                txt('groups_table_groupname'),
                txt('groups_table_description')
            );

            foreach ($automatic_groups as $name => $group) {
                $table->addData(View::createElement('tr', array(
                    $name,
                    $group['description']
                )));
            }
            $View->addElement($table);
        }

        if ($manual_groups) {
            if (INST != 'uit') {
                $View->addElement('h2', txt('groups_title_manual'));
            }
            $View->addElement('p', txt('groups_manual_contact'), 'class="ekstrainfo"');
            $table = View::createElement('table', null, 'class="app-table"');
            $table->setHead(
                txt('groups_table_groupname'),
                txt('groups_table_description')
            );

            foreach ($manual_groups as $name => $group) {
                $table->addData(View::createElement('tr', array(
                    $name,
                    $group['description']
                )));
            }
            $View->addElement($table);
        }

    }

    public function personal() {
        /**
         * Checks if the user has a personal group.
         *
         * @return True if user has personal group, false if not.
         */
        function hasPersonal()
        {
            global $User;
            global $Bofh;

            try {
                $Bofh->run_command('group_list', $User->getUsername());
            } catch(Exception $e) {
                return false;
            }
            return true;
        }

        $User = Init::get('User');
        $Bofh = new Bofhcom();

        /**
        If not allowed to create groups, redirect back to groups/
        */
        if (!$this->authz->can_create_groups()) {
            View::forward('groups/', null);
        }

        if (hasPersonal()) {
            View::forward('groups/', txt('groups_personal_already'));
        }

        $form = new BofhForm('makePersonal', null, 'groups/personal', null, 'class="submitonly"');
        $form->addElement('submit', 'add', txt('groups_personal_submit'));

        if ($form->validate()) {
            if ($Bofh->getData('group_personal', $User->getUsername())) {
                View::forward('groups/', txt('groups_personal_success'));
            } else {
                View::forward('groups/personal/');
            }
        }

        $View = Init::get('View');
        $View->addTitle('Groups');
        $View->addTitle(txt('GROUPS_PERSONAL_TITLE'));

        $View->start();
        $View->addElement('h1', txt('GROUPS_PERSONAL_TITLE'));
        $View->addElement('p', txt('GROUPS_PERSONAL_INFO', $User->getUsername()));

        $View->addElement($form);

        $View->addElement('p', txt('action_delay_hour'), 'class="ekstrainfo"');
    }
}
?>
