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

class Account extends ModuleGroup {
    private $modules;
    private $authz;
    public function __construct($modules) {
        $this->modules = $modules;
        $this->authz = Init::get('Authorization');
        if (INST != 'uio' || !$this->authz->is_guest()) {
            $modules->addGroup($this);
        }
    }

    public function getName() {
        return 'account';
    }

    public function getInfoPath() {
        return array('account');
    }

    public function getSubgroups() {
        if (INST == 'uio' && $this->authz->can_set_primary_account()
            || Init::get('Bofh')->isPersonal()) {

            return array('', 'primary', 'password');
        } else {
            return array('', 'password');
        }
    }

    public function getHiddenRoutes() {
        return array();
    }

    public function getShortcuts() {
        return array(array('account/password/', txt('home_shortcuts_password')));
    }

    public function display($path) {
        if (!$path) {
            return $this->index();
        }
        switch ($path[0]) {
        case '': case 'index':
            return $this->index();
        case 'primary':
            return $this->primary();
        case 'password':
            return $this->password();
        }
    }

    public function index() {
        /**
         * Adds a description onto spreads. Works with both a string and
         * array of strings.
         *
         * @param mixed     Array or string with the spreads to describe
         * @return          Returns the same as in the input, but with longer string(s)
         */
        function addHelpSpread($bofh, $spreads) {

            if(is_array($spreads)) {
                foreach($spreads as $k => $v) {
                    $spreads[$k] = addHelpSpread($bofh, $v);
                }
            } else {
                $spreads = trim($spreads);

                $desc = $bofh->getSpread($spreads);
                if($desc) $spreads = $desc;
            }

            return $spreads;
        }

        /**
         * Get a bofh-string with the persons affiliations and modify it
         * into a better presentation-form, and adds aff-definitions on it 
         * (by asking bofhcom for the descriptions).
         *
         * Todo: this function is not equal to the function in person/index.php, but
         *       they could be merged and handle different text-variations...
         *
         * TODO: should this, and all other help-functions, be in the same place somewhere?
         */
        function addHelpAffiliations($Bofh, $string) {

            //recursive
            if (is_array($string)) {
                foreach ($string as $k => $v)
                    $string[$k] = addHelpAffiliations($Bofh, $v);
                return $string;
            }

            $affs = $Bofh->getCache();
            $affs = $affs['affiliation_desc'];

            // example of a line:
            // ANSATT@150500 (Informatikk)
            // STUDENT@150000 (Mat.nat. fakultet)

            list($aff, $sted) = explode('@', trim($string), 2);
            list($stedkode, $stedkode_desc) = explode(' ', $sted, 2);

            return txt('bofh_info_account_affiliation_value', array(
                'aff'           => $aff,
                'aff_desc'      => $affs[strtoupper($aff)],
                'stedkode'      => $stedkode,
                'stedkode_desc' => $stedkode_desc
            ));

        }
        $User = Init::get('User');
        $Bofh = Init::get('Bofh');

        $userinfo = $this->getUserinfo(); 
        unset($userinfo['username']);


        $View = Init::get('View');
        $View->addTitle(txt('ACCOUNT_TITLE'));
        $View->start();

        $View->addElement('h1', txt('ACCOUNT_TITLE'));
        $View->addElement('h2', ($Bofh->getPrimary() == $User->getUsername() 
            ? txt('account_name_primary') 
            : txt('account_name_normal')
        ));



        $list[0] = View::createElement('dl', null, 'class="complicated"');


        //standard info

        //spreads
        if (!empty($userinfo['spread'])) {
            $list[0]->addData(ucfirst(txt('bofh_info_spreads')), addHelpSpread($Bofh, explode(',', $userinfo['spread'])));
            unset($userinfo['spread']);
        } else {
            if (INST != 'hine') {
                $list[0]->addData(ucfirst(txt('bofh_info_spreads')), txt('account_spreads_empty'));
            }
        }

        //afiliations
        if (!empty($userinfo['affiliations'])) {
            $list[0]->addData(ucfirst(txt('bofh_info_affiliations')), addHelpAffiliations($Bofh, explode(',', $userinfo['affiliations'])));
            unset($userinfo['affiliations']);
        } else {
            $list[0]->addData(ucfirst(txt('bofh_info_affiliations')), txt('account_affs_empty'));
        }

        //expire
        if(!empty($userinfo['expire']) && $userinfo['expire'] instanceof DateTime) {
            $list[0]->addData(ucfirst(txt('bofh_info_expire')).':', $userinfo['expire']->format(txt('date_format')));
            unset($userinfo['expire']);
        }


        if(isset($_GET['more'])) {
            $list[1] = View::createElement('a', txt('general_less_details'), 'account/');
        } else {
            $list[1] = View::createElement('a', txt('general_more_details'), 'account/?more');
        }


        //extra info

        if(isset($_GET['more'])) {
            $list[2] = View::createElement('dl', null, 'class="complicated"');
            //ksort($userinfo);
            foreach($userinfo as $k=>$v) {
                if(!$titl = @txt('bofh_info_'.$k)) { // @ prevents warnings, as data may change
                    $titl = $k; // if no given translation, just output variable name
                }
                $list[2]->addData(ucfirst($titl).':', ($v instanceof DateTime) ? $v->format(txt('date_format')) : $v);
            }
        }

        $View->addElement('div', $list, 'class="primary"');


        //other accounts
        $accounts = $Bofh->getAccounts();
        if (sizeof($accounts) > 1) {

            $View->addElement('h2', txt('account_other_title'));

            $table = View::createElement('table', null, 'class="mini"');
            $table->setHead(txt('account_other_table_name'), txt('account_other_table_expire'));

            foreach ($accounts as $aname => $acc) {
                if ($aname == $Bofh->getUsername()) {
                    continue;
                }

                //checks for expired accounts:
                if ($acc['expire'] instanceof DateTime) {
                    //older than today:
                    if ($acc['expire'] < new DateTime()) $aname = txt('account_name_deleted', array('username'=>$aname));
                    $expire = $acc['expire']->format(txt('date_format'));
                } else {
                    $expire = txt('account_other_expire_not_set');
                }

                $table->addData(View::createElement('tr', array(
                    $aname,
                    $expire
                )));
            }

            $View->addElement($table);
            $View->addElement('p', txt('account_other_info'), 'class="ekstrainfo"');

        }
    }

    /**
     * Gets the user_info from Bofhcom, and 
     * removes the unecessary info.
     */
    function getUserinfo($username = null) {

        if(!$username) {
            $username = Init::get('User')->getUsername();
        }

        $Bofh = new Bofhcom();
        $info = $Bofh->getDataClean('user_info', $username);

        //removing
        unset($info['entity_id']);
        unset($info['owner_type']);
        unset($info['owner_id']);

        //removing null-elements:
        foreach($info as $k=>$v) {
            if(!$v) unset($info[$k]);
        }

        return $info;

    }

    public function primary() {
        /**
         * This function tricks with the numbers for setting an account primary.
         */
        function setPrimary($Bofh) {

            $username = Init::get('User')->getUsername();
            $user = null;

            if(!isset($Bofh)) $Bofh = new Bofhcom();

            $priorities = $Bofh->getData('person_list_user_priorities', $username);
            $primary = $priorities[0];
            foreach($priorities as $p) {
                if($p['priority'] < $primary['priority']) $primary = $p;
                if(!$user && $p['uname'] == $username) $user = $p;
            }

            return $Bofh->run_command('person_set_user_priority', $username, $user['priority'], $primary['priority']-1);

        }

        $User = Init::get('User');
        $Bofh = new Bofhcom();

        $primary = $Bofh->getPrimary();

        $View = Init::get('View');
        $View->addTitle(txt('ACCOUNT_PRIMARY_TITLE'));
        $View->addElement('h1', txt('ACCOUNT_PRIMARY_TITLE'));


        //checks first if account already is primary
        if($primary == $User->getUsername()) {
            $View->start();
            $View->addElement('p', txt('account_primary_already'));
            die;
        }

        $form = new BofhFormUiO('change_primary', null, 'account/primary/', null, 'class="app-form-big submitonly"');
        $form->addElement('submit', 'confirm', txt('account_primary_form_submit'), 
            'class="submit"'
        );
        if($form->validate()) {
            if(setPrimary($Bofh)) {
                View::forward('account/', txt('account_primary_success'));
            } else {
                View::addMessage(txt('account_primary_failed'));
            }
        }



        $View->start();
        $View->addElement('p', txt('account_primary_intro'));
        $View->addElement($form);
    }

    public function password() {
        /**
         * Checks if the given password is secure enough to be used.
         */
        function validatePassword($Bofh, $password, &$returnmsg = null) {
            try {

                $res = $Bofh->run_command('misc_check_password', $password);
                if($res) return true;

            } catch (Exception $e) {

                $returnmsg = $e->getMessage();
                return substr($returnmsg, strrpos($returnmsg, 'CerebrumError: ')+15);

            }
        }


        /** 
         * Checks if the given password is the users correct password
         */
        function verifyPassword($Bofh, $password) {
            try {

                $res = $Bofh->run_command('misc_verify_password', Init::get('User')->getUsername(), $password);
                //TODO: the text may change... get smarter way...
                if($res === 'Password is correct') return true;

            } catch (Exception $e) {
                return false;
            }

        }

        /**
         * Changes the users password.
         */
        function changePassword($Bofh, $newpas, $curpas, &$errmsg = null) {
            try {

                $res = $Bofh->run_command('user_password', Init::get('User')->getUsername(), $newpas);
                if($res) return true;

            } catch (Exception $e) {
                $errmsg = $e->getMessage();
                $errmsg = substr($errmsg, strrpos($errmsg, 'CerebrumError: ')+15);
            }

            return false;
        }
        $User = Init::get('User');
        $Bofh = new Bofhcom();

        $View = Init::get('View');

        $realtime_validation = (defined('REALTIME_PASSWORD_VALIDATION') && REALTIME_PASSWORD_VALIDATION);

        if ($realtime_validation) {
            $View->addHead('<script type="text/javascript" src="/forgotten/js/password_validator.js"></script>');
        }

        $View->addTitle('Account');
        $View->addTitle(txt('ACCOUNT_PASSWORD_TITLE'));


        // The password change form
        $form = new BofhFormUiO('setPassword', null, 'account/password/');
        $form->setAttribute('class', 'app-form-big');
        $form->addElement('password', 'cur_pass', txt('account_password_form_current'), 'id="cur_pass"');
        $form->addElement('html', '<hr />');
        $form->addElement('password', 'password', txt('account_password_form_new'), 'id="password"');
        $form->addElement('password', 'confirm_password', txt('account_password_form_new2'), 'id="confirm-password"');
        $form->addElement('html', '<div id="confirm-password-feedback">' . txt('account_password_error_match') . '</div>');
        $form->addElement('submit', null, txt('account_password_form_submit'), 'disabled');

        // Validation rules
        $form->addRule('password', txt('account_password_rule_new_required'), 'required');
        $form->addRule('password', txt('latin1_only_required'), 'latin1_only');
        $form->addRule('confirm_password', txt('latin1_only_required'), 'latin1_only');
        // no more rules here, wants to validate the password first, before checking rest

        if($form->validate()) {

            $pasw_msg = validatePassword($Bofh, $form->exportValue('password'), $errmsg);
            //$pasw_msg now contains either TRUE or a string explaining what is wrong with the password
            if($pasw_msg === true) {

                //the password is valid, now check the rest

                if($form->exportValue('password') == $form->exportValue('confirm_password')) {

                    //check original password
                    if(verifyPassword($Bofh, $form->exportValue('cur_pass'))) {

                        if(changePassword($Bofh, $form->exportValue('password'), $form->exportValue('cur_pass'), $errmsg)) {
                            View::addMessage(txt('account_password_success'));
                            View::addMessage(txt('action_delay_hour'));
                            View::forward('account/');
                        } else {
                            //have to send errors manually to the form, (e.g. check for old passwords)
                            $form->setElementError('password', $errmsg);
                        }

                    } else {
                        $form->setElementError('cur_pass', txt('account_password_error_current'));
                    }
                } else {
                    $form->setElementError('confirm_password', txt('account_password_error_match'));
                }

            } else {
                // if the new password is wrong
                $form->setElementError('password', $pasw_msg);
            }


        }

        //TODO: this should be included in the HTML_Quickform_password class, passwords 
        //      should not be written directly in the html!
        $pa = $form->getElement('password');
        $pa->setValue(null);

        $pa = $form->getElement('confirm_password');
        $pa->setValue(null);

        $pa = $form->getElement('cur_pass');
        $pa->setValue(null);


        $View->setFocus('#cur_pass');
        $View->start();
        $View->addElement('h1', txt('ACCOUNT_PASSWORD_TITLE'));
        $View->addElement('raw', txt('ACCOUNT_PASSWORD_INTRO'));
        $View->addElement(
            'div',
            '<div class="col-1-3" id="form"></div><div class="col-2-3" id="validation"></div>',
            'class="grid"');
        $View->addElement($form);
        $View->addElement('p', txt('account_password_moreinfo'), 'class="ekstrainfo"');
    }
}
?>
