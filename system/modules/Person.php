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

class Person extends ModuleGroup {
    private $modules;
    private $authz;
    public function __construct($modules) {
        $this->modules = $modules;
        $this->authz = Init::get('Authorization');
        if ($this->isUioOrUit(INST) && $this->authz->is_personal()
            || INST == 'hine' && Init::get('Bofh')->isPersonal()) {
            $modules->addGroup($this);
        }
    }

    public function getName() {
        return 'person';
    }

    public function getInfoPath() {
        return array('person');
    }

    public function getSubgroups() {
        if (INST == 'uio') {
            if ($this->authz->can_set_display_name()) {
                return array('', 'name', 'primary');
            } else {
                return array('', 'primary');
            }
        }
        elseif (INST == 'uit') {
                return array('', 'primary');
        }
        return array();
    }

    public function getHiddenRoutes() {
        return array();
    }

    public function getShortcuts() {
        if(INST == 'uit') {
            return array(array('person/primary', txt('home_shortcuts_change_pri_affiliation')));
        } elseif ($this->authz->can_set_display_name()) {
            return array(array('person/name', txt('home_shortcuts_change_pri_email_addr')));
        } else{
            return array();
        }
        
    }

    public function display($path) {
        if (!$path) {
            return $this->index();
        }
        switch ($path[0]) {
        case '': case 'index':
            return $this->index();
        case 'name':
            return $this->personname();
        case 'primary':
            return $this->primary();
        }
    }

    public function index() {
        /**
         * Getting all the person_info, sorted
         *
         * This function is only returning the info you would
         * get from person_info in jbofh.
         */
        function getPersonInfo() {

            $userName = Init::get('User')->getUserName();
            $Bofh = Init::get("Bofh");
            $p = $Bofh->getDataClean('person_info', $userName);

            //all the values should come in arrays:
            foreach($p as $k=>$v) {
                if(!is_array($v)) $p[$k] = array($v);
            }

            //affiliation_1 should come first in affiliation
            if(!empty($p['affiliation_1'])) {
                if(!empty($p['affiliation'])) {
                    array_unshift($p['affiliation'], $p['affiliation_1'][0]);
                } else {
                    $p['affiliation'] = $p['affiliation_1'];
                }

                unset($p['affiliation_1']);
            }

            //source_system_1 should come first in source_system
            if(!empty($p['source_system_1'])) {
                if(!empty($p['source_system'])) {
                    array_unshift($p['source_system'], $p['source_system_1'][0]);
                } else {
                    $p['source_system'] = $p['source_system_1'];
                }

                unset($p['source_system_1']);
            }

            //get fnr from bofh using person_get_id
            if(!empty($p['extid'])) {
                foreach($p['extid'] as $k=>$f) {
                    if($f == 'NO_BIRTHNO') {
                        $fnr = $Bofh->getDataClean('person_get_id', $userName, $f, $p['extid_src'][$k]);
                        $p['fnr'][$k] = $fnr['ext_id_value'];
                        $p['fnr_src'][$k] = $fnr['source_system'];
                    }
                }
            }

            return $p;
        }

        $User = Init::get('User');
        $Bofh = Init::get('Bofh');

        $personinfo = getPersonInfo();

        $cache = $Bofh->getCache();
        $aff_descs = $cache['affiliation_desc'];
        $source_system_descs = $cache['source_systems'];

        $dl = View::createElement('dl', null, 'class="complicated"');
        $dl->addData(txt('bofh_info_name'), $personinfo['name']);
        $dl->addData(txt('bofh_info_birth'), $personinfo['birth']);

        //affiliations
        if(!empty($cache['affiliations'])) {
            $affs = array();
            foreach($cache['affiliations'] as $key=>$aff) {
                //adding descriptions
                $aff['source_system_desc'] = $source_system_descs[$aff['source_system']];
                $aff['affiliation_desc']   = $aff_descs[$aff['affiliation']];
                $aff['status_desc']        = $aff_descs[$aff['affiliation'].'/'.$aff['status']];
                $affs[] = txt('bofh_info_person_affiliation_value', $aff);
            }
            $dl->addData(txt('bofh_info_person_affiliations'), View::createElement('ul', $affs));
        }


        //names
        if(!empty($personinfo['names'])) {
            foreach($personinfo['names'] as $k=>$n) {
                $names[] = txt('bofh_info_name_value', array(
                    'name'                  => $n,
                    'source_system'         => $personinfo['name_src'][$k],
                    'source_system_desc'    => $source_system_descs[$personinfo['name_src'][$k]]
                ));
            }
        }
        if(!empty($names)) $dl->addData(txt('bofh_info_names'), View::createElement('ul', $names));


        //fnr
        if(!empty($personinfo['fnr'])) {
            foreach($personinfo['fnr'] as $k=>$f) {
                $fnr[] = txt('bofh_info_fnr_value', array('fnr'=> $f,
                    'source_system'         => $personinfo['fnr_src'][$k],
                    'source_system_desc'    => $source_system_descs[$personinfo['fnr_src'][$k]]
                ));
            }
        }
        if(!empty($fnr)) $dl->addData(txt('bofh_info_fnr'), View::createElement('ul', $fnr));

        // contact info
        if (!empty($personinfo['contact'])) {
            foreach ($personinfo['contact'] as $k => $contact) {
                $contactinfo[] = txt('bofh_info_contact_value', array('contact'=> $contact,
                    'source_system'         => $personinfo['contact_src'][$k],
                    'type'                  => $personinfo['contact_type'][$k],
                    'source_system_desc'    => $source_system_descs[$personinfo['contact_src'][$k]]
                ));
            }
        }
        if(!empty($contactinfo)) {
            $dl->addData(txt('bofh_info_contact'), View::createElement('ul', $contactinfo));
        }


        $View = Init::get('View');
        $View->addTitle(txt('PERSON_TITLE'));
        $View->addElement('h1', txt('PERSON_TITLE'));


        $View->addElement($dl);
        $View->start();

        $View->add(txt('person_howto_change'));
    }

    public function personname() {
        /**
         * Return a form for specifying what names should go as input.
         */
        function formModName($current_addr, $current_name, $names)
        {
            $data = array();
            foreach ($names as $name => $row) {
                $data[$name] = sprintf('%s (%s)', $name, $row[1]);
            }
            ksort($data);
            $form = new BofhFormUiO('mod_name', null, 'person/name/');
            $form->addElement('select', 'name', txt('person_name_form_select'), $data);
            $form->addElement('submit', null, txt('person_name_form_submit'));

            $form->addRule('name', txt('FORM_REQUIRED'), 'required');
            $form->setDefaults(array('name' => $current_name));
            return $form;
        }

        /**
         * Process a HTML_QuickForm of email_mod_name.
         */
        function formModNameProcess($input)
        {
            global $addresses;

            if (empty($addresses[$input['name']])) {
                View::addMessage('Bogus data');
                return;
            }
            $names = $addresses[$input['name']][0];
            $last = array_pop($names);
            $first = implode(' ', $names);

            $bofh = Init::get('Bofh');
            $user = Init::get('User');
            try {
                $ret = $bofh->run_command('email_mod_name', $user->getUsername(), $first, $last);
            } catch (XML_RPC2_FaultException $e) { 
                $bofh->viewError($e);
                return;
            }
            View::addMessage(txt('person_name_success'));
        }

        /**
         * Return the users current primary e-mail address.
         */
        function getPrimaryAddress()
        {
            $bofh = Init::get('Bofh');
            $user = Init::get('User');
            $info = $bofh->getData('email_info', $user->getUsername());
            foreach ($info as $i) {
                if (!empty($i['def_addr'])) {
                    return $i['def_addr'];
                }
            }
            // TODO: how should no primary address be handled?
        }

        /**
         * Return an array with a suggestion of all e-mail addresses that can be set as 
         * primary.
         *
         * The given name(s) can be changed to any name that the person already has. The 
         * family name can not be changed by the person.
         *
         * @return  Array   The keys are the addresses, the values are arrays of names.
         */
        function getAddresses()
        {
            $bofh = Init::get('Bofh');
            $user = Init::get('User');

            $names = $bofh->getData('person_name_suggestions', 'id:'.$bofh->getCache('person_id'));
            // the raw format is:
            // array(
            //  array('first_name', 'second_name' ...),
            //  'email_address',
            // ),
            $ret = array();
            foreach ($names as $row) {
                $ret[implode(' ', $row[0])] = $row;
            }
            return $ret;
        }

        /**
         * Return how an e-mail address would look like in Cerebrum with the given 
         * names.
         *
         *  - John Doe -> john.doe
         *  - John Richard Doe -> j.r.doe
         *
         * @param  Array        Array of all given names. Family name must be last.
         * @return String       How the e-mail address would look like in Cerebrum, 
         *                      without the domain.
         */
        function makeAddress($names)
        {
            foreach ($names as $key => $name) {
                $names[$key] = strtolower($name);
            }
            if (sizeof($names) <= 2) {
                // if only one or two names, they are used directly
                return implode('.', $names);
            }
            // if more than two names, only initials are used together with family name
            $ret = array();
            $last = array_pop($names);
            foreach ($names as $name) {
                $ret[] = $name[0];
            }
            return implode('.', $ret) . '.' . $last;
        }

        /**
         * Get the primary full name for the current person.
         *
         * The name returned from $bofh->getName() is cached, so we should not use that 
         * in this case.
         */
        function getName()
        {
            $bofh = Init::get('Bofh');
            $raw = $bofh->getData('person_info', $bofh->getUsername());
            foreach ($raw as $row) {
                if (!empty($row['name'])) {
                    return trim(substr($row['name'], 0, strpos($row['name'], '[')));
                }
            }
        }
        $bofh = Init::get('Bofh');
        if (!$bofh->isEmployee()) {
            View::forward('person/', txt('EMPLOYEES_ONLY'));
        }

        global $addresses;
        $addresses = getAddresses();
        $name = getName();
        $primary = getPrimaryAddress();

        $form = formModName($primary, $name, $addresses);
        if ($form->validate()) {
            $form->process('formModNameProcess');
            View::forward('person/');
        }

        $view = Init::get('View');
        $view->start();
        $view->addElement('h1', txt('person_name_title'));
        $view->addElement('p', txt('person_name_intro')); 

        $view->addElement('p', txt('person_name_current', array('name'=>$name, 'email'=>$primary))); 
        $view->addElement($form);
    }

    public function primary() {
        function process_set_primary($data)
        {
            $primary = $data['primary']['aff'];
            $bofh = Init::get('Bofh');
            $user = Init::get('User');

            try {
                $ret = $bofh->run_command('trait_set', 'entity_id:'.get_person_id(),
                    'primary_aff', 'strval='.$primary, 'date='.date('Y-m-d')
                );
            } catch (XML_RPC2_FaultException $e) { 
                trigger_error($e);
                return false;
            }
            return $ret;
        }

        // TODO: cache this?
        function get_person_id()
        {
            $bofh = Init::get('Bofh');
            $user = Init::get('User');
            $person_info = $bofh->getData('person_info', $user->getUsername());
            $person_id = null;
            foreach ($person_info as $p) {
                if (!empty($p['entity_id'])) {
                    return $p['entity_id'];
                }
            }
        }

        /**
         * Return the value of a previously chosen primary affiliation, or null if it 
         * hasn't been set before.
         */
        function get_chosen_primary()
        {
            $bofh = Init::get('Bofh');
            $traits = $bofh->getData('trait_info', 'entity_id:'.get_person_id());
            if (!is_array($traits) || empty($traits['traits'])) {
                return null;
            }
            foreach ($traits['traits'] as $trait) {
                if ($trait['trait_name'] === 'primary_aff') {
                    return $trait['strval'];
                }
            }
        }
        $User = Init::get('User');
        $View = Init::get('View');
        $View->addTitle(txt('primary_person_title'));

        $cache = Init::get('Bofh')->getCache();
        $affs  = $cache['affiliations'];

        $form = new BofhFormUiO('change_primary', null, 'person/primary/');
        $form->setAttribute('class', 'app-form-big');

        $radios = array();
        foreach ($affs as $aff) {
            // the id to store in the trait
            $id = sprintf('%s/%s@%s', $aff['affiliation'], $aff['status'], $aff['stedkode']);

            // adding human descriptions
            $aff['source_system_desc'] = $cache['source_systems'][$aff['source_system']];
            $aff['affiliation_desc']   = $cache['affiliation_desc'][$aff['affiliation']];
            $aff['status_desc']        = $cache['affiliation_desc'][$aff['affiliation'].'/'.$aff['status']];

            $human_aff = txt('bofh_info_person_affiliation_value', $aff);
            $radios[] = $form->createElement('radio', 'aff', null, $human_aff, $id);
        }
        $form->addGroup($radios, 'primary', txt('primary_person_choice'), "<br />\n");
        $form->addElement('submit', null, txt('primary_person_submit'));

        $form->addRule('primary', txt('form_required'), 'required');
        $form->setDefaults(array('primary' => array('aff' => get_chosen_primary())));

        if ($form->validate()) {
            if ($form->process('process_set_primary')) {
                View::forward('person/primary/', txt('primary_person_updated'));
            }
            View::forward('person/primary/', txt('error_bofh_error'));
        }

        $View->start();
        $View->addElement('h1', txt('primary_person_title'));
        $View->addElement('p', txt('primary_person_intro'));
        $View->addElement($form);
    }
}
?>
