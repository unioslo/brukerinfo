<?php
// Copyright 2009, 2010 University of Oslo, Norway
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

class Email extends ModuleGroup {
    private $modules;
    private $authz;
    public function __construct($modules) {
        $this->modules = $modules;
        $this->authz = Init::get("Authorization");
        if (!$this->isUioOrUit(INST) || $this->authz->has_email()) {
            $modules->addGroup($this);
        }
    }

    public function getName() {
        return 'email';
    }

    public function getInfoPath() {
        return array('email');
    }

    public function getSubgroups() {
        if (INST == 'uio') {
            $ret = array('', 'forward', 'spam');
            return $ret;
        } elseif (INST == 'hine') {
            return array('');
        } elseif (INST == 'uit'){
            $ret = array('', 'forward');
            return $ret;
        }
    }

    public function getHiddenRoutes() {
        return array();
    }

    public function getShortcuts() {
        $ret = array();
        if (INST == 'uio') {
            if ($this->authz->has_email()) {
                $ret[] = array('email/spam/', txt('home_shortcuts_spam'));
            }
        }
        return $ret;
    }


    public function display($path) {
        if (!$path) {
            return $this->index();
        }
        switch ($path[0]) {
        case '': case 'index':
            return $this->index();
        case 'forward':
            return $this->forward();
        case 'spam':
            return $this->spam();
        }
    }

    public function index() {
        /**
         * Gets all the info from the users mail account,
         * and does some cleaning because of weird return
         * from bofhd.
         */
        function emailinfo($username)
        {
            $Bofh = Init::get("Bofh");
            if (INST == 'hine') {
                $data = $Bofh->cleanData($Bofh->run_command('email_info', $username));
            } else {
                $data = $Bofh->getDataClean('email_info', $username);
            }

            //let valid_addr_1 be first in valid_addr list (if existing)
            if (empty($data['valid_addr'])) $data['valid_addr'] = array();
            if (!is_array($data['valid_addr'])) $data['valid_addr'] = array($data['valid_addr']);
            array_unshift($data['valid_addr'], $data['valid_addr_1']);
            unset($data['valid_addr_1']);

            if (!empty($data['forward_1'])) {
                if (empty($data['forward'])) {
                    $data['forward'] = $data['forward_1'];
                } else {
                    if (!is_array($data['forward'])) $data['forward'] = array($data['forward']);
                    array_unshift($data['forward'], $data['forward_1']);
                }
            }
            unset($data['forward_1']);

            // the filters comes in a string in an array so need to split:
            if (!empty($data['filters']) && $data['filters'] != 'None') {
                if (count($data['filters'] == 1)) {
                    $data['filters'] = explode(', ', $data['filters'][0]);
                } else {
                    //assumes that it has been fixed?
                    trigger_error('Email-filters changed - bug is fixed?', E_USER_NOTICE);
                }

            }
            return $data;
        }

        /**
         * Asks bofhd to delete a given e-mail address.
         */
        function delEmailAddress($Bofh, $User, $address)
        {
            try {
                $ret = $Bofh->run_command('email_address_remove', $User->getUsername(), $address);
                // finished with an update, just to be sure
                $Bofh->run_command('email_update', $User->getUsername());
                return $ret;
            } catch (Exception $e) {
                return false;
            }
        }
        $User = Init::get('User');
        $Bofh = Init::get('Bofh');
        $View = Init::get('View');
        $Authz = Init::get('Authorization');

        if (!$Authz->has_email()) {
            View::forward('', txt('email_info_no_account'));
        }

        try {
            $primary = emailinfo($User->getUsername());
        } catch (XML_RPC2_FaultException $e) {
            View::forward('', txt('email_info_no_account'));
        }
        unset($primary['account']);

        if ( $this->isUioOrUit(INST) && !empty($_GET['del_addr'])) {

            if (!in_array($_GET['del_addr'], $primary['deletable'])) {
                View::forward('email/', txt('email_del_invalid_addr'));
            }
            $addr = htmlspecialchars($_GET['del_addr']);

            $delform = new BofhForm('delete_email_addr', null, "email/?del_addr=$addr");
            $delform->addElement('static', 'Test');
            $delform->addGroup(array(
                $delform->createElement('submit', 'confirm', txt('email_del_submit')),
                $delform->createElement('submit', 'cancel', txt('email_del_cancel')),
            ));

            if ($delform->validate()) {
                if (!empty($_POST['cancel'])) View::forward('email/');

                if (!empty($_POST['confirm'])) {
                    if (delEmailAddress($Bofh, $User, $_GET['del_addr'])) {
                        View::addMessage(txt('email_del_success', array('address' => $addr)));
                    } else {
                        View::addMessage(txt('email_del_failed', array('address' => $addr)));
                    }
                    View::forward('email/');
                }
            }

            $View->addTitle(txt('email_del_title', array('address' => $addr)));
            $View->start();
            $View->addElement('h1', txt('email_del_title', array('address' => $addr)));
            $View->addElement('p', txt('email_del_intro', array('address' => $addr)));
            $View->addElement($delform);
            die;
        }

        $View->addTitle(txt('EMAIL_INFO_TITLE'));
        $View->start();

        $View->addElement('h1', txt('EMAIL_INFO_TITLE'));
        $View->addElement('h2', $User->getUsername());



        $prilist = View::createElement('dl', null);


        // target_type - given if mail is not account, Mailman, Sympa, pipe or RT - 
        // e.g. when mail account is "deleted"
        if (isset($primary['target_type'])) {
            $prilist->addData(txt('email_info_targettype'), $primary['target_type']);
            unset($primary['target_type']);
        }

        // default address
        if (isset($primary['def_addr'])) {
            $prilist->addData(txt('email_info_primary_addr'), $primary['def_addr']);
            unset($primary['def_addr']);
        }

        // valid addresses
        if ($this->isUioOrUit(INST)) {
            if (isset($primary['valid_addr'])) {
                if (!empty($primary['deletable'])) {
                    foreach ($primary['valid_addr'] as $id => $addr) {
                        if (in_array($addr, $primary['deletable'])) {
                            $primary['valid_addr'][$id] .= " <a href=\"email/?del_addr=$addr\">"
                                . txt('email_del_actionlink') . '</a>';
                        }

                    }
                }
                $prilist->addData(txt('email_info_valid_addr'), $primary['valid_addr']);
            }
        } elseif (INST == 'hine') {
            if (isset($primary['valid_addr'])) {
                $addresses = array();
                foreach ($primary['valid_addr'] as $key => $adr) {
                    if ($adr) {
                        $addresses[$key] = $adr;
                    }
                }
                if ($addresses) {
                    $prilist->addData(txt('email_info_valid_addr'), $primary['valid_addr']);
                }
            }
        }
        unset($primary['valid_addr']);
        unset($primary['deletable']);

        // quota
        if (isset($primary['quota_used'])) {
            $prilist->addData(txt('email_info_quota'), txt('email_info_quota_info', array(
                'quota_used'    => $primary['quota_used'], 
                'quota_max'     => $primary['quota_hard'], 
                'quota_warn'    => $primary['quota_soft'])));
            unset($primary['quota_used']);
            unset($primary['quota_hard']);
            unset($primary['quota_soft']);
        }
        else if (isset($primary['dis_quota_hard']) && isset($primary['dis_quota_soft'])) {
            $prilist->addData(txt('email_info_quota'), txt('email_info_quota_info_no_usage', array(
                'quota_max'     => $primary['dis_quota_hard'],
                'quota_warn'    => $primary['dis_quota_soft'])));
            unset($primary['dis_quota_hard']);
            unset($primary['dis_quota_soft']);
        }

        // not used
        unset($primary['target_id']);

        // forward
        if (isset($primary['forward'])) {
            $prilist->addData(txt('email_info_forward'), $primary['forward']);
            unset($primary['forward']);
        }

        $text = Init::get('Text');

        // spam level
        if (isset($primary['spam_level'])) {
            //getting the translated description
            if ($text->exists('email_spam_level_'.$primary['spam_level'], $text->getLanguage())) {
                $primary['spam_level_desc'] = txt('email_spam_level_'.$primary['spam_level']);
            }

            $prilist->addData(txt('email_info_spam_level'), 
            $primary['spam_level_desc'] . ' ('.ucfirst($primary['spam_level']).')');
            unset($primary['spam_level']);
            unset($primary['spam_level_desc']);
        }
        // spam action
        if (isset($primary['spam_action'])) {
            //getting the translated description
            if ($text->exists('email_spam_action_'.$primary['spam_action'], $text->getLanguage())) {
                $primary['spam_action_desc'] = txt('email_spam_action_'.$primary['spam_action']);
            }
            $prilist->addData(txt('email_info_spam_action'), 
            $primary['spam_action_desc'] . ' ('.ucfirst($primary['spam_action']).')');
            unset($primary['spam_action']);
            unset($primary['spam_action_desc']);
        }

        // filter
        if (!empty($primary['filters']) && $primary['filters'] != 'None') {


            //getting the description of the filters
            $filter_desc = $Bofh->getData('get_constant_description', 'EmailTargetFilter');
            //sorting the filters
            foreach($filter_desc as $f) $filter_desc[$f['code_str']] = $f['description'];

            foreach($primary['filters'] as $f) {
                //if(Text::exists("email_filter_{$f}_desc")) {
                //    $filters[] = txt("email_filter_{$f}_desc", array('bofh_desc'=>$filter_desc[$f])) . " ($f)";
                //} elseif(isset($filter_desc[$f])) {
                if (isset($filter_desc[$f])) {
                    $filters[] = $filter_desc[$f] . " ($f)";
                } else {
                    $filters[] = $f;
                    trigger_error("Unknown filter '$f' in constants EmailTargetFilter", E_USER_NOTICE);
                }
            }

            $prilist->addData(txt('email_info_filters'), $filters);
        } elseif ($this->isUioOrUit(INST)) {
            $prilist->addData(txt('email_info_filters'), null);
        }
        unset($primary['filters']);

        // server
        if (isset($primary['server'])) {
            $prilist->addData(txt('email_info_server'), $primary['server'] . ' ('.$primary['server_type'].')');
            unset($primary['server']);
            unset($primary['server_type']);
        }

        //adds the rest (if any)
        foreach ($primary as $k => $pr) {
            $titl = @txt('email_info_'.$k);
            if (!$titl) $titl = $k;
            $titl = ucfirst($k).':';
            $prilist->addData($titl, $pr);

            trigger_error('Forgot to add the value '.$k.'="'.$pr.'" in email/index', E_USER_NOTICE);
        }

        $View->addElement('div', $prilist, 'class="primary"');



        // other accounts:
        $accounts = $Bofh->getAccounts();

        if (sizeof($accounts) > 1) {
            $View->addElement('h2', txt('email_other_title'));
            $View->addElement('p', txt('email_other_intro'), 'class="ekstrainfo"');

            foreach ($accounts as $aname => $acc) {
                if (!$acc || $aname == $Bofh->getUsername()) {
                    continue;
                }
                //todo: needs to know how expire is returned to remove it from the list:
                if ($acc['expire'] && $acc['expire'] < new DateTime()) {
                    continue;
                }

                try {
                    $sec = emailinfo($aname);
                } catch (XML_RPC2_FaultException $e) {
                    continue;
                }

                $View->addElement('h3', $sec['account']);

                $info = View::createElement('dl', null, 'class="secondary"');
                $info->addData(txt('email_info_primary_addr'), $sec['def_addr']);
                $info->addData(txt('email_info_valid_addr'), $sec['valid_addr']);
                $info->addData(txt('email_info_server'), $sec['server'] . ' ('.$sec['server_type'].')');
                $View->addElement($info);
            }
        }

        $txt = txt('email_info_more_info');
        if ($txt)
            $View->addElement('ul', array(txt('email_info_more_info')), 'class="ekstrainfo"');
    }

    public function forward() {
        /**
         * Seems like the way to get the list of forwards is
         * through email_info, in [forward_1] and [forward].
         */
        function getForwards()
        {
            $User = Init::get("User");
            $Bofh = Init::get('Bofh');
            $info = $Bofh->getData('email_info', $User->getUsername());

            $forwards = array();
            foreach ($info as $i) {
                if (isset($i['forward'])) {
                    if (strpos($i['forward'], '+') === 0) {
                        $name = 'local';
                        $status = '(on)';
                    } else {
                        list($name, $status) = explode(' ', $i['forward']);
                    }
                    $forwards[$name] = $status;
                }
                if (isset($i['forward_1'])) {
                    if (strpos($i['forward_1'], '+') === 0) {
                        $name = 'local';
                        $status = '(on)';
                    } else {
                        list($name, $status) = explode(' ', $i['forward_1']);
                    }
                    $forwards[$name] = $status;
                }
            }
            return $forwards;
        }

        /**
         * Adding a forward address.
         * This function is called through BofhForm: $newForm->process()
         * and the values are therefore stored as
         * $values = array:
         *   'address'   = the adress to forward to
         *   ['keep']    = if local copy or not
         */
        function addForward($values)
        {
            $User = Init::get("User");
            $Bofh = Init::get('Bofh');

            if (!empty($values['address'])) {
                try {
                    $res = $Bofh->run_command('email_forward_add', $User->getUsername(), $values['address']);
                    View::addMessage($res);
                    View::addMessage(txt('action_delay_email'));
                } catch(Exception $e) {
                    Bofhcom::viewError($e);
                    return;
                }
            }

            global $keeplocal;

            //setting the local copy on of off
            if (!empty($values['keep']) && !$keeplocal) {
                try {
                    $res = $Bofh->run_command('email_forward_local_delivery', $User->getUsername(), 'on');
                    View::addMessage($res);
                } catch(Exception $e) {
                    Bofhcom::viewError($e);
                }
            } elseif (empty($values['keep']) && $keeplocal) {
                try {
                    $res = $Bofh->run_command('email_forward_local_delivery', $User->getUsername(), 'off');
                    View::addMessage($res);
                } catch(Exception $e) {
                    Bofhcom::viewError($e);
                }
            }
        }
        $User = Init::get('User');
        $Bofh = Init::get('Bofh');
        $View = Init::get('View');
        $Authz = Init::get('Authorization');

        if (!$Authz->has_email()) {
            View::forward('', txt('email_info_no_account'));
        }

        $forwards = getForwards();
        global $keeplocal;
        $keeplocal = (isset($forwards['local']) ? true : false);

        //make new forward-form
        $newForm = new BofhFormUiO('addForwarding', null, 'email/forward/');
        $newForm->setAttribute('class', 'app-form-big');
        $newForm->addElement('text', 'address', txt('email_forward_form_address'), array('maxlength' => 255));
        if (INST != 'uit'){
            $newForm->addElement('checkbox', 'keep', null, txt('email_forward_form_keep'));
        }
        $newForm->addElement('submit', null, txt('email_forward_form_submit'));

        // Define filters and validation rules
        $newForm->addRule('address', txt('email_forward_form_address_required'), 'required');
        $newForm->setDefaults(array('keep'=>$keeplocal));

        // Adding a forward
        if ($newForm->validate()) {
            $newForm->freeze();
            $newForm->process('addForward');
            View::forward('email/forward/');
        }

        // Form for making local copy
        $addLocal = new BofhFormUiO('addLocal', null, 'email/forward/');
        $addLocal->setAttribute('class', 'app-form-big');
        $addLocal->addElement('html', View::createElement('p', txt('email_forward_addlocal')));
        $addLocal->addElement('submit', null, txt('email_forward_addlocal_submit'));

        if ($addLocal->validate()) {
            $res = $Bofh->run_command('email_forward_local_delivery', $User->getUsername(), 'on');
            View::addMessage($res);
            View::addMessage(txt('action_delay_email'));
            View::forward('email/forward/');
        }

        $View->addTitle(txt('email_title'));
        $View->addTitle(txt('EMAIL_FORWARD_TITLE'));

        //Deleting forwards
        if (!empty($_POST['del'])) {
            if (count($_POST['del']) > 1) {
                trigger_error('was?', E_USER_WARNING);
                View::forward('email/forward/', 'Buggy data, could not continue', View::MSG_ERROR);
            }

            $del = (is_array($_POST['del']) ? key($_POST['del']) : $_POST['del']);

            if(!isset($forwards[$del])) {
                View::forward('email/forward/', txt('email_forward_delete_unknown'), View::MSG_ERROR);
            }

            $confirm = new BofhFormUiO('confirm', null, 'email/forward/');
            $confirm->setAttribute('class', 'app-form-big');
            $confirm->addElement('submit', null, txt('email_forward_delete_confirm_submit'), 'class="submit"');
            $confirm->addElement('hidden', 'del', $del);

            if ($confirm->validate()) {
                try {
                    if ($del === 'local') {
                        $res = $Bofh->run_command('email_forward_local_delivery', $User->getUsername(), 'off');
                        View::forward('email/forward/', $res);
                    }
                    else {
                        $res = $Bofh->run_command('email_forward_remove', $User->getUsername(), $del);
                        View::forward('email/forward/', $res);
                    }
                } catch(Exception $e) {
                    Bofhcom::viewError($e);
                    View::forward('email/forward/');
                }
            }

            $View->addTitle(txt('email_forward_delete_confirm_title'));
            $View->start();
            $View->addElement('h1', txt('email_forward_delete_confirm_title'));

            $View->addElement('p', txt('email_forward_delete_confirm_intro', array('target'=>$del)));
            $View->addElement($confirm);
            die;
        }

        $View->start();
        $View->addElement('h1', txt('EMAIL_FORWARD_TITLE'));
        $View->addElement('p',  txt('EMAIL_FORWARD_INTRO'));

        if ($forwards) {
            $View->addElement('raw', '<form method="post" class="app-form" action="email/forward/">');
            $table = View::createElement('table', null, 'class="app-table"');

            foreach ($forwards as $k => $v) {
                if ($k == 'local') {
                    $name = txt('email_forward_local') . " $v";
                } else {
                    $name = "$k $v";
                }
                $table->addData(array(
                    $name, 
                    '<input type="submit" class="submit" name="del['.$k.']" value="'.txt('email_forward_delete_submit').'">',
                ));
            }

            $table->setHead('Forwarding address:', null);
            $View->addElement($table);
            $View->addElement('raw', '</form>');
        } else {

        }

        $View->addElement('h2', txt('email_forward_new_title'));
        $View->addElement($newForm);

        if (!$keeplocal && $forwards) {
            $View->addElement('h2', txt('email_forward_addlocal_title'));

            $View->addElement($addLocal);
        }

        $View->addElement('p', txt('ACTION_DELAY_email'), 'class="ekstrainfo"');
    }

    public function spam() {
        /**
         * Finds the different action choises to put on spam.
         * Works in searching way in the help-text today...
         */
        function spamActions($Bofh) {
            $raw = $Bofh->help('arg_help', 'spam_action');
            //is something like this:
            //Choose one of
            //          'dropspam'    Reject messages classified as spam
            //          'spamfolder'  Deliver spam to a separate IMAP folder
            //          'noaction'    Deliver spam just like legitimate email

            $actions = array();
            foreach (explode("\n", $raw) as $l) {

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
        function getSetLevelAction($User, $Bofh) {
            $info = $Bofh->getData('email_info', $User->getUsername());
            $level = null;
            $action = null;

            foreach ($info as $i) {
                if (isset($i['spam_level'])) $level = $i['spam_level'];
                if (isset($i['spam_action'])) $action = $i['spam_action'];
            }

            return array($level, $action);

        }


        /**
         * This function gets all available filters from the constants EmailTargetFilter.
         */
        function availableFilters($Bofh, $View) {

            $text = Init::get('Text');

            $filters_raw = $Bofh->getData('get_constant_description', 'EmailTargetFilter');

            // Array of filters names. These filters will not be displayed to the user.
            // TODO: Move this to the config file.
            $hidden_filters = array('internalonly');

            //sorting the filters
            $filters = array();
            foreach ($filters_raw as $f) {
                $id = $f['code_str'];

                if (in_array($id, $hidden_filters)) {
                    // Skip hidden filter
                    continue;
                }

                $txtkey_name = 'email_filter_data_'.$id;
                $txtkey_desc = 'email_filter_data_'.$id.'_desc';

                $filters[$id]['name'] = $id;
                //looking for a better name
                if ($text->exists($txtkey_name, $text->getLanguage())) {
                    $filters[$id]['name'] = txt($txtkey_name);
                }

                $filters[$id]['desc'] = $f['description'];
                //looking for a better description
                if ($text->exists($txtkey_desc, $text->getLanguage())) {
                    $filters[$id]['desc'] = txt($txtkey_desc, array('bofh_desc'=>$f['description']));
                }
            }

            return $filters;

        }

        /**
         * Gets what filters the user has active.
         */
        function getActiveFilters($User, $Bofh) {
            $all = $Bofh->getDataClean('email_info', $User->getUsername());

            if (empty($all['filters']) || $all['filters'] == 'None') return null;

            //the filters comes in a comma-separated string
            $rawf = explode(', ', $all['filters'][0]);
            foreach ($rawf as $v) $filters[$v] = true;
            return $filters;

        }



        /**
         * Sets a filter on or off.
         */
        function setFilters($data)
        {
            global $available_filters, $active_filters;
            $Bofh = Init::get("Bofh");
            $User = Init::get("User");

            $err = false;

            // setting several in one go is supported by the loop
            foreach ($data as $filter => $value) {

                if (!isset($available_filters[$filter])) {
                    View::addMessage(txt('email_filter_unknown'), View::MSG_WARNING);
                    trigger_error("Filter $filter doesn't exist in 'available_filters'", E_USER_NOTICE);
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
                        $res = $Bofh->run_command('email_spam_filter_add', $filter, $User->getUsername());
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
                        $res = $Bofh->run_command('email_spam_filter_remove', $filter, $User->getUsername());
                        View::addMessage($res);
                    } catch(Exception $e) {
                        Bofhcom::viewError($e);
                        $err = true;
                    }
                }

            }
            return $err;
        }
        $User = Init::get('User');
        $Bofh = Init::get('Bofh');
        $View = Init::get('View');
        $text = Init::get('Text');
        $Authz = Init::get('Authorization');

        if (!$Authz->has_email()) {
            View::forward('', txt('email_info_no_account'));
        }

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
        while ($i < count($sp_lvl_raw)) {
            $sp_levels[] = $sp_lvl_raw[$i++];
        }

        // the set level and action
        list($def_level, $def_action) = getSetLevelAction($User, $Bofh);

        // Getting filter settings
        global $available_filters, $active_filters;
        $available_filters = availableFilters($Bofh, $View);
        $active_filters = getActiveFilters($User, $Bofh);




        $form = new BofhFormUiO('setSpam', null, 'email/spam/');
        $form->setAttribute('class', 'app-form-big');

        //spam level
        $levels = array();
        foreach ($sp_levels as $v) {
            $title = ucfirst(str_replace('_', ' ', $v['code_str']));
            $txt_name = 'email_spam_level_'.$v['code_str'];

            if ($text->exists($txt_name, $text->getLanguage(), true)) {
                $v['description'] = txt($txt_name);
            }

            $levels[] = $form->createElement('radio', 'level', null, 
                "{$v['description']} <span class=\"explain\">($title)</span>", $v['code_str']);
        }
        $form->addGroup($levels, 'spam_level', txt('email_spam_form_level'), "<br>\n", false);

        //spam action
        $actions = array();
        foreach ($sp_actions as $v) {
            $title = ucfirst(str_replace('_', ' ', $v['code_str']));
            $txt_name = 'email_spam_action_'.$v['code_str'];

            if ($text->exists($txt_name, $text->getLanguage(), true)) {
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
                View::forward('email/spam/');

            } catch(Exception $e) {
                Bofhcom::viewError($e);
            }
        }






        $View->addTitle(txt('email_title'));
        $View->addTitle(txt('email_spam_title'));



        // making form for the filters (additional spam settings)
        $filterform = new BofhFormUiO('spamfilter', null, 'email/spam/');

        $flist = View::createElement('table', null, 'class="app-table"');

        foreach ($available_filters as $id => $filter) {
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
            View::forward('email/spam/');

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
    }
}
?>
