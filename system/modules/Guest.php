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

class Guest implements ModuleGroup {
    private $modules;
    private $authz;
    private $name;
    public function __construct($modules, $override_name=false) {
        $this->modules = $modules;
        $this->authz = Init::get("Authorization");
        if ($override_name) {
            $this->name = $override_name;
            $modules->addGroup($this);
        } elseif ($this->authz->is_guest()) {
            $this->name = 'guest_info';
            $modules->addGroup($this);
            $pass = new Guest($modules, 'guest_password');
        } elseif ($this->authz->can_create_guests()) {
            $this->name = 'guests';
            $modules->addGroup($this);
        }
    }

    public function getName() {
        return $this->name;
    }

    public function getInfoPath() {
        return array($this->name);
    }

    public function getSubgroups() {
        if ($this->authz->is_guest()) {
            return array();
        }
        return array('', 'create');
    }

    public function getShortcuts() {
        return array();
    }

    public function getHiddenRoutes() {
        return array();
    }

    public function display($path) {
        if ($this->authz->is_guest()) {
            if ($this->name == 'guest_info') {
                return $this->info();
            } else {
                return $this->doprint();
            }
        }
        if (!$path) {
            return $this->index();
        }
        switch ($path[0]) {
        case '': case 'index':
            if ($this->authz->is_guest()) {
                return $this->info();
            }
            return $this->index();
        case 'create':
            return $this->create();
        }
    }


    public function index() {
        /**
         * Sort by name for the multidimensional array $guests. Case-insensitive. 
         *
         * @param array $guest_a One element from the $guests array
         * @param array $guest_b One element from the $guests array
         *
         * @return int  -1 if $guest_a <  $guest_b
         *               0 if $guest_a == $guest_b
         *               1 if $guest_a >  $guest_b
         */
        function sort_by_name($guest_a, $guest_b) {
            return strcmp(strtolower($guest_a['name']), strtolower($guest_b['name']));
        }
        /**
         * Page for viewing and modifying reservations.
         */
        $User = Init::get('User');
        $Bofh = Init::get('Bofh');
        $View = Init::get('View');
        $Authz = Init::get('Authorization');

        if (!$Authz->can_create_guests()) {
            View::forward('', txt('guests_create_no_access'));
        }

        $View->addTitle(txt('guest_title'));
        if (!$Bofh->isEmployee()) View::forward('', txt('employees_only'));

        $show_expired = isset($_GET['show-expired']);

        // Run bofhd-command guest_list <operator>
        $guests = $Bofh->getData('guest_list');

        $View->start();
        $View->addElement('h1', txt('guest_title'));
        $View->addElement('p', txt('guest_intro'));

        // Create html tables
        $active_guests = $View->createElement('table', null, 'class="app-table"');
        $active_guests->setHead(
            array(
                txt('guest_list_col_username'),
                txt('guest_list_col_name'),
                txt('guest_list_col_end_date'),
            )
        );
        $inactive_guests = $View->createElement('table', null, 'class="app-table"');
        $inactive_guests->setHead(
            array(
                txt('guest_list_col_username'),
                txt('guest_list_col_name'),
                txt('guest_list_col_end_date'),
            )
        );


        // Sort guests:
        usort($guests, 'sort_by_name');


        // TODO: Sort by username? Status? Time left?
        // Add guests to table
        foreach ($guests as $i => $guest) {
            $data = array(
                View::createElement('a', $guest['username'], "index.php/guests/info/?guest=".$guest['username']),
                $guest['name'],
                (!empty($guest['expires'])) ? $guest['expires']->format('y-m-d') : '',
            );
            if ($guest['status'] == 'active') {
                $active_guests->addData($data);
            } else {
                $inactive_guests->addData($data);
            }
        }

        // Show list of active guest users
        $View->addElement('h2', txt('guest_list_active'));
        if ($active_guests->rowCount() > 0) {
            $View->addElement($active_guests);
        } else {
            $View->addElement('p', txt('guest_list_active_empty'));
        }
    }

    /**
     * Page for viewing and modifying reservations.
     */
    private function create() {
        /**
         * Creates an HTML-form for creating guest users.
         *
         * @return string HTML form element.
         */
        function buildForm()
        {
            /**
             * Creates a new guest user using BofhCom.
             *
             * @param array $data Array with the form data to process
             *
             * @return string|null An HTML element with information about the new guest
             *                     account, or null on failure.
             */
            function bofhCreateGuest($data)
            {
                $bofh = Init::get('Bofh');

                try {
                    $res = $bofh->run_command(
                        'guest_create', $data['g_days'], $data['g_fname'], $data['g_lname'],
                        'guest', $data['g_contact']
                    );
                } catch (XML_RPC2_FaultException $e) {
                    // Error. Not translated or user friendly, but this shouldn't happen at all:
                    //View::addMessage(htmlspecialchars($e->getMessage()), View::MSG_WARNING);
                    BofhCom::viewError($e);
                    return null;
                }

                return txt(
                    'guest_created_sms',
                    array('uname'=>$res['username'],'mobile'=>$res['sms_to'])
                );
            }
            // Create guest form
            $form = new BofhFormUiO('new_guest');
            $form->setAttribute('class', 'app-form-big');

            /* First and last name */
            $form->addElement(
                'text', 'g_fname', txt('guest_new_form_fname'), 'id="guest_fname"'
            );
            $form->addElement('text', 'g_lname', txt('guest_new_form_lname'));

            /* Expire date selections */
            $duration = array(  7=>txt('general_timeinterval_week', array('num'=>1)),
                30=>txt('general_timeinterval_month', array('num'=>1)),
                90=>txt('general_timeinterval_months', array('num'=>3)),
                180=>txt('general_timeinterval_months', array('num'=>6)),
            );
            $radio = array();
            foreach ($duration as $val=>$text) {
                $radio[] = BofhFormUiO::createElement('radio', null, null, $text, $val);
            }
            $form->addGroup($radio, 'g_days', txt('guest_new_form_duration'), '<br />');

            $form->addElement('text', 'g_contact', txt('guest_new_form_contact'));
            $form->addElement('submit', null, txt('guest_new_form_submit'));

            // Inputs that require content
            $form->addRule('g_fname', txt('guest_new_form_fname_req'), 'required');
            $form->addRule('g_lname', txt('guest_new_form_lname_req'), 'required');
            $form->addRule('g_days',  txt('guest_new_form_duration_req'), 'required');
            $form->addRule('g_contact', txt('guest_new_form_contact_req'), 'required');

            /* Limit name lengths
             * Cerebrum limitation of 512 chars, bofhd will throw an error if
             * fname+lname > 512 - 1 chars.
             * It's easier to enforce max limits of 255 chars for fname and lname...
             */
            // Lenghts, field_name => (min, max)
            $namelengths = array(
                'g_fname' => array(2, 255),
                'g_lname' => array(1, 255),
            );
            foreach ($namelengths as $field => $lim) {
                $form->addRule(
                    $field,
                    txt('guest_new_form_name_fmt', array('min'=>$lim[0], 'max'=>$lim[1])),
                    'rangelength', $lim
                );
            }

            // Require 8 digit phone number
            $form->addRule(
                'g_contact', txt('guest_new_form_contact_fmt'), 'regex', '/^[\d]{8}$/'
            );

            // Trim all input prior to validation, and set radio button default
            $form->applyFilter('__ALL__', 'trim');
            $form->setDefaults(array('g_days'=>30));

            return $form;
        }

        $User = Init::get('User');
        $Bofh = Init::get('Bofh');
        $View = Init::get('View');
        $Authz = Init::get('Authorization');

        /* Has access to create guests? */
        if (!$Authz->can_create_guests()) {
            View::forward('', txt('guests_create_no_access'));
        }

        $View->addTitle(txt('guest_title'));

        $guestform = buildForm();
        if ($guestform->validate()) {
            if ($ret = $guestform->process('bofhCreateGuest')) {
                View::forward('index.php/guests/create/', $ret);
            }
            // else, error. The bofhCreateGuest function should add an error message to
            // the page.
        }
        $View->setFocus('#guest_fname');

        // Present page
        $View->start();
        $View->addElement('h1', txt('guest_new_title'));
        $View->addElement('p', txt('guest_new_intro'));
        $View->addElement($guestform);


    }

    /**
     * Page for viewing and modifying reservations.
     */
    public function info() {
        /**
         * Build a simple form that consists of a button, and a series of hidden fields.
         *
         * @param string $name   The ID to give the form
         * @param string $label  The button label
         * @param array  $hidden An array of hidden fields, each entry is a
         *                       <name> => <value> mapping.
         *
         * @return BofhFormInline The form
         */
        function buildButtonForm($name, $label, array $hidden)
        {
            $form = new BofhFormInline($name);
            foreach ($hidden as $name => $value) {
                $form->addElement('hidden', $name, $value);
            }
            $form->addElement('submit', null, $label);
            return $form;
        }

        /**
         * Generates a new random password for a guest, using BofhCom. PEAR Quickform
         * handler for 'new_guest_password'.
         *
         * @param array $data Array with the form data from 'new_guest_password'
         *
         * @return string|boolean A text (potentially HTML) that explains the change
         *                        that was performed.
         *                        Returns false on failure.
         */
        function bofhResetPassword($data)
        {
            $bofh = Init::get('Bofh');
            try {
                $res = $bofh->run_command('guest_reset_password', $data['g_uname']);
            } catch (XML_RPC2_FaultException $e) {
                // Not translated or user friendly, but this shouldn't happen at all:
                $bofh->viewError($e);
                return false;
            }
            return txt(
                'guest_new_password', array(
                    'uname' => $res['username'], 'mobile' => $res['mobile']
                )
            );
        }

        /**
         * Deactivates a guest user by using BofhCom. PEAR Quickform handler for
         * 'deactivate_guest'.
         *
         * @param array $data Array with the form data from 'deactivate_guest'
         *
         * @return string|boolean A text (potentially HTML) that explains the change
         *                        that was performed.
         *                        Returns false on failure.
         */
        function bofhDisableGuest($data)
        {
            $bofh = Init::get('Bofh');
            try {
                $bofh->run_command('guest_remove', $data['g_uname']);
            } catch (XML_RPC2_FaultException $e) {
                // Not translated or user friendly, but this shouldn't happen at all:
                $bofh->viewError($e);
                return false;
            }
            return txt('guest_deactivated', array('uname'=>$data['g_uname']));
        }

        $User = Init::get('User');
        $Bofh = Init::get('Bofh');
        $View = Init::get('View');
        $Authz = Init::get('Authorization');

        $View->addTitle(txt('guest_title'));

        // Determine username of the guest user to show
        if ($Authz->is_guest()) {
            $guest = $Bofh->getUsername();
        } elseif ($Authz->can_create_guests()) {
            $guest = (!empty($_POST['g_uname'])) ? $_POST['g_uname'] : (
                (!empty($_GET['guest'])) ? $_GET['guest'] : false);
        } else {
            View::forward('', txt('guests_create_no_access'));
        }

        // No guest data
        if (empty($guest)) {
            View::forward('index.php/guests/', txt('guest_no_username'), View::MSG_ERROR);
        }

        // Clean input string (XSS)
        if (!preg_match("/^[-A-Za-z0-9]+$/", $guest)) {
            View::forward(
                'index.php/guests/',
                txt('guest_unknown_username', array('uname'=>htmlspecialchars($guest))),
                View::MSG_ERROR
            );
        }

        // Create forms (reset password, delete guest account)
        // A hidden field with the guest username, common for both forms
        $hidden = array('g_uname' => $guest);
        $delform = buildButtonForm('del_guest', txt('guest_btn_deactivate'), $hidden);
        $passform = buildButtonForm('new_pass', txt('guest_btn_resetpw'), $hidden);

        // Try to process POST requests if they validate
        if ($Authz->can_create_guests()) {
            if ($delform->validate()) {
                if ($ret = $delform->process('bofhDisableGuest')) {
                    $View->addMessage($ret);
                }
            } elseif ($passform->validate()) {
                if ($ret = $passform->process('bofhResetPassword')) {
                    $View->addMessage($ret);
                }
            }
        }

        // Get information about the guest
        $guestdata = $Bofh->getData('guest_info', $guest);
        if (empty($guestdata)) {
            View::forward(
                'index.php/guests/',
                txt('guest_unknown_username', array('uname'=>$guest)),
                View::MSG_ERROR
            );
        }
        $guestinfo = array_pop($guestdata);

        $created = ($guestinfo['created']) ? $guestinfo['created'] : new DateTime('@0');
        $expires = ($guestinfo['expires']) ? $guestinfo['expires'] : new DateTime('@0');
        $is_active = ($expires > new DateTime);

        $infolist = View::createElement('dl', null);
        //$infolist->addData(txt('guest_info_name'), $guestinfo['name']);
        $infolist->addData(txt('guest_info_username'), $guestinfo['username']);
        $infolist->addData(txt('guest_info_contact'), $guestinfo['contact']);
        $infolist->addData(txt('guest_info_responsible'), $guestinfo['responsible']);
        $infolist->addData(txt('guest_info_created'), $created->format('Y-m-d'));
        $infolist->addData(txt('guest_info_expired'), $expires->format('Y-m-d'));
        $infolist->addData(txt('guest_info_status'), txt('guest_status_'.$guestinfo['status']));
        if ($is_active) {
            $infolist->addData(txt('guest_info_days_left'), $expires->diff(new DateTime())->days);
        }

        // Present page.
        $View->start();

        $View->addElement('h2', txt('guest_info_top', array('str'=>$guestinfo['name'])));
        $View->addElement($infolist);

        // Add buttons
        if ($Authz->can_create_guests() && $is_active) {
            $View->addElement('div', $delform, 'style="float: left;"');
            if (!empty($guestinfo['contact'])) {
                $View->addElement('div', $passform, 'style="float: left;"');
            }
        }

    }

    public function doprint() {
        $User = Init::get('User');
        $Bofh = Init::get('Bofh');
        $Authz = Init::get('Authorization');

        if (!$Authz->can_create_guests()) {
            View::forward('', txt('guests_create_no_access'));
        }

        // For simplicity
        $guest = $_POST['u'];

        // For Quickform token/validate
        $form = new BofhFormInline('password_sheet');

        if (!$Bofh->isEmployee()) {
            View::forward('', txt('employees_only'));
        } elseif (empty($guest)) {
            View::forward('index.php/guests/', txt('guest_no_username'), View::MSG_ERROR);
        } elseif ($form->validate()) {
            try {
                $pw = $Bofh->getCachedPassword($guest);
            } catch (Exception $e) {
                $pw = false;
            }
            if (!$pw) {
                View::forward('index.php/guests/', txt('guest_pw_not_cached'), View::MSG_ERROR);
            } else {
                echo txt('guest_pw_letter', array('uname'=>$guest, 'password'=>$pw));
            }
        } else {
            // You shouldn't be here at all
            View::forward('');
        }
    }
}
?>
