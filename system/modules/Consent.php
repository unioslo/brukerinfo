<?php
// Copyright 2018 University of Oslo, Norway
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

class Consent extends ModuleGroup {
    private $modules;
    private $consentData = array();

    public function __construct($modules) {
        $this->modules = $modules;
        $this->authz = Init::get('Authorization');
        $this->bofh = Init::get('Bofh');
        $this->user = Init::get('User');
        $this->modules->addGroup($this);
    }

    public function getName() {
        return 'consent';
    }

    public function getInfoPath() {
        return array('consent');
    }

    public function getSubgroups() {
        return array();
    }

    public function getHiddenRoutes() {
        $this->getConsentData();
        $ret = array_keys($this->consentData);
        return $ret;
    }

    public function getShortcuts() {
        return array();
    }

    public function showInMenu() {
        return $this->authz->has_consent_page();
    }

    public function display($path) {
        /**
         * Page for viewing and modifying user consent
         */

        if (!$path) {
            return $this->index();
        } else if ($path[0] == '') {
            return $this->index();
        } else {
            return $this->consentPage($path[0]);
        }   
    }

    public function index() {
        /**
         * Page for viewing avalable consents and the current consent status.
         */
        $redirected = (strpos($_SERVER['QUERY_STRING'], 'redirected=true') !== false) ? true : false;

        $view = Init::get('View');

        $view->addHead('<script type="text/javascript" src="uio_design/consent.js"></script>');
        $view->addTitle(txt('consent_page_title'));

        if ($this->showInMenu()) {
            $this->getConsentData();
            $view->addElement('h1', txt('consent_page_title'));

            $flist = $view->createElement('table', 'consent', 'class="app-table" id="consent"');
            $flist->setHead(array(
                txt('consent_tablehead_name'), 
                txt('consent_tablehead_desc'), 
                txt('consent_tablehead_status'), 
                '',
            ));
        
            foreach ($this->consentData as $consent) {
                if ($consent['has_consented']) {
                    $status = txt('consent_status_given');
                } else {
                    $status = txt('consent_status_not_given');
                }

                $name = $consent['consent_name'];
                $buttonText = txt('consent_edit_button');
                $button = "<input type=\"submit\" name=\"submit\" class=\"submit\" value=\"$buttonText\" />";
                $edit_consent = $view->createElement('a', $button, 'consent/' . $name, 'class=app-form');
                $flist->addData(
                    array(
                        txt($this->createTxtName($name, 'name')),
                        txt($this->createTxtName($name, 'table_desc')),
                        $status,
                        $edit_consent,
                    )
                );
            }
            $view->addElement($flist);
            $view->start();
            return;
        }

        // Render error page if user was redirected and does not have access to any consents
        if ($redirected) {
            $this->displayErrorPage($view, null);
            return;
        }
        // If no redirect (user tried to manually enter the route), and user
        // does not have access to any consents, forward to main page.
        View::forward('');

    }

    public function consentPage($consent_type) {

        $redirected = (strpos($_SERVER['QUERY_STRING'], 'redirected=true') !== false) ? true : false;
        $view = Init::get('View');

        $view->addHead('<script type="text/javascript" src="uio_design/consent.js"></script>');
        
        if ($this->showInMenu()) {
            $this->getConsentData();
            if (array_key_exists($consent_type, $this->consentData)) {
                $consent = $this->consentData[$consent_type];

                $consentForm = $this->createConsentForm($view, $redirected, $consent);
                $this->showConsentForm($view, $redirected, $consent);
                $view->addElement($consentForm);
                $this->prossessConsentForm($consentForm, $consent);
                $view->start();
                return;
            }
        }

        // Render error page if user was redirected and does not have access to any consents
        if ($redirected) {
            $this->displayErrorPage($view, $consent_name);
            return;
        }
        // If no redirect (user tried to manually enter the route), and
        // does not have access to any consents, forward to main page.d
        View::forward('');
    }


    public function createConsentForm($view, $redirected, $consent) {
        $name = $consent['consent_name'];
        $consentForm = new BofhFormUiO('consent-' . $name, null, 'consent/' . $name);
        $consentForm->setAttribute('class', 'app-form-big');
        $name_long = txt($this->createTxtName($name, 'name'));

        if ($consent['has_consented']) {
            $consent_text = txt($this->createTxtName($name, 'remove_consent_text'), array('name' => $name_long));
        } else {
            $consent_text = txt($this->createTxtName($name, 'give_consent_text'), array('name' => $name_long));
        }
        $consentBox = $consentForm->createElement('checkbox', 'consent-' . $name , null, $consent_text, array(
                'id' => 'consent-' . $name . '-checkbox',
                'require' => 'require'
            )
        );
        $consentForm->addElement($consentBox);

        $buttonText = ($consent['has_consented']) ? txt('consent_confirm_revoke') : txt('consent_confirm_give');
        $consentForm->addElement('submit', null, $buttonText, array(
                'id' => 'consent-' . $name . '-submit',
                'disabled' => 'disabled',
                'name' => 'submit'
            )
        );

        return $consentForm;
    }

    public function showConsentForm($view, $redirected, $consent){
        $name = $consent['consent_name'];
        $view->addElement('h1', txt($this->createTxtName($name, 'name')));

        if ($this->isTermsOfAgreementTypePage($name)){
            // Simple terms of agreemment page
            $view->addElement('p', txt('consent_terms_of_agreement',
                              array('link' => txt($this->createTxtName($name, 'terms_of_agreement_link')))));
            $view->addElement('p', txt($this->createTxtName($name, 'intro'),
                              array('link' => txt($this->createTxtName($name, 'terms_of_agreement_link')))));
        } else {
            // More detailed consent page
            $view->addElement('div', txt($this->createTxtName($name, 'intro'),
                              array('link' => txt($this->createTxtName($name, 'info_link')))));
        }

        if ($consent['consent_date'] != null) {
            $view->addElement('p', txt('consent_registered_statustext', array('date' => $consent['consent_date'])));
        } else {
            $view->addElement('p', txt('consent_not_registered_statustext'));
        }
    }

    public function prossessConsentForm($consentForm, $consent) {
        $name = $consent['consent_name'];
        if ($consentForm->validate()) {
            $consentCheckbox = $consentForm->getElement('consent-' . $name);
            $consentCheckbox->setChecked(false);

            if (!$consent['has_consented']) {
                try {
                    $this->bofh->run_command('consent_set', 'person:' . $this->user->getUsername(), $name);
                    View::forward('consent/', txt($this->createTxtName($name, 'consent_registered')));
                } catch (Exception $e) {
                    Bofhcom::viewError($e);
                }
            } else {
                try {
                    $this->bofh->run_command('consent_unset', 'person:' . $this->user->getUsername(), $name);
                    View::forward('consent/', txt($this->createTxtName($name, 'consent_revoked'), 
                        array('link' => txt($this->createTxtName($name, 'terms_of_agreement_link')))));
                } catch (Exception $e) {
                    Bofhcom::viewError($e);
                }
            }
        }
    }

    public function displayErrorPage($view, $consent_name) {
        if ($consent_name == null) {
            $view->addElement('h1', txt('consent_no_access_title'));
            $view->addElement('p', txt('consent_no_access_any_text'));
            $view->start();
        } else {
            $view->addElement('h1', txt('consent_no_access_title'));
            $view->addElement('p', txt('consent_no_access_text', $consent_name));
            $view->start();
        }
    }

    private function createTxtName($consent_name, $string_name) {
        // Helper function used to create txt name
        // return 'consent_' . $consent_name . '_' . $string_name;
        return $consent_name . '_' . $string_name;
    }

    private function getConsentData() {
        try {
            $consentTypes = $this->bofh->run_command('consent_list');
            foreach ($consentTypes as $consentType) {
                if ($this->authz->has_consent_permissions($consentType['consent_name'])) {
                    $this->consentData[$consentType['consent_name']] = array();
                    $this->consentData[$consentType['consent_name']]['consent_type'] = $consentType['consent_type'];
                    $this->consentData[$consentType['consent_name']]['consent_name'] = $consentType['consent_name'];
                    $this->consentData[$consentType['consent_name']]['has_consented'] = false;
                }
            }

            $consents = $this->bofh->run_command('consent_info', 'person:' . $this->user->getUsername());
            // add if consent in consent_types
            foreach ($consents as $consent) {
                if ($this->authz->has_consent_permissions($consent['consent_name'])) {
                    $this->consentData[$consent['consent_name']]['has_consented'] = true;
                    $this->consentData[$consent['consent_name']]['consent_date'] = date_format($consent['consent_time_set'], 'd-m-Y H:i:s');
                }
            }
        }
        catch (XML_RPC2_FaultException $e) {
            // Person has no registered consents at all
            return false;
        }
    }

    // Terms of agreement or consent type page
    private function isTermsOfAgreementTypePage($consentName) {
        if ($consentName == 'gsuite') {
            return true;
        } else {
            return false;
        }
    }
}
?>