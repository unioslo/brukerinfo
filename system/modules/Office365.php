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

class Office365 extends ModuleGroup {
    private $modules;

    public function __construct($modules) {
        $this->modules = $modules;
        $this->authz = Init::get('Authorization');
        $this->bofh = Init::get('Bofh');
        $this->user = Init::get('User');
        $this->modules->addGroup($this);
    }

    public function getName() {
        return 'office365';
    }

    public function getInfoPath() {
        return array('office365');
    }

    public function getSubgroups() {
        return array();
    }

    public function getHiddenRoutes() {
        return array();
    }

    public function getShortcuts() {
        return array();
    }

    public function showInMenu() {
        return $this->authz->has_office365();
    }

    public function display($path) {
        /**
         * Page for viewing and modifying user consent for exporting user data
         * to Office 365
         *
         * This page is only shown in the menu to users that has access to
         * Office365.
         *
         * However, if users without access tries to log in to
         * Office 365, there is no way for Office 365 to determine if they don't
         * have access, or simply have not consented to create an account yet,
         * and they will be redirected here. In this scenario, a page informing
         * the users that they do not have permission to create an Office365
         * account will appear.
         *
         */

        $redirected = (strpos($_SERVER['QUERY_STRING'], 'redirected=true') !== false) ? true : false;

        $view = Init::get('View');
        $view->addHead('<script type="text/javascript" src="uio_design/office365.js"></script>');
        $view->addTitle(txt('office365_title'));

        if ($this->authz->has_office365()) {
            $this->displayConsentForm($view, $redirected);
            return;
        }

        // Render error page if user was redirected and does not have Office365
        if ($redirected) {
            $this->displayErrorPage($view);
            return;
        }
        // If no redirect (user tried to manually enter the route), and user
        // does not have Office365, forward to main page.
        View::forward('index.php/');
    }

    public function displayConsentForm($view, $redirected) {
        $has_consented = $this->userConsentRegistered();
        $consentForm = new BofhFormUiO('office365');
        $consentForm->setAttribute('class', 'app-form-big');
        $consentBox = $consentForm->createElement('checkbox', 'consent', null, txt('office365_consent_text'), array(
                'id' => 'consent-checkbox'
            )
        );

        if ($has_consented) {
            $consentBox->setChecked(true);
        }
        else {
            $consentBox->setChecked(false);
        }
        $consentForm->addElement($consentBox);

        $buttonText = ($has_consented) ? txt('office365_confirm_revoke_consent') : txt('office365_confirm_give_consent');

        $consentForm->addElement('submit', null, $buttonText, array(
                'id' => 'consent-submit',
                'disabled' => 'disabled',
                'name' => 'submit'
            )
        );

        $view->addElement('h1', txt('office365_title'));
        $view->addElement('p', txt('office365_intro'));

        if ($consentForm->validate()) {
            $consentCheckbox = $consentForm->getElement('consent');
            $consentSubmit = $consentForm->getElement('submit');
            if ($consentCheckbox->getChecked()) {
                try {
                    $this->bofh->run_command('consent_set', 'person:' . $this->user->getUsername(), 'office365');
                    View::addMessage(txt('office365_consent_registered'));
                    // Text on submit-button is not updated after POST
                    $consentSubmit->setValue(txt('office365_confirm_revoke_consent'));
                }
                catch (Exception $e) {
                    Bofhcom::viewError($e);
                }
            }
            else {
                try {
                    $this->bofh->run_command('consent_unset', 'person:' . $this->user->getUsername(), 'office365');
                    View::addMessage(txt('office365_consent_revoked'));
                    // Text on submit-button is not updated after POST
                    $consentSubmit->setValue(txt('office365_confirm_give_consent'));
                }
                catch (Exception $e) {
                    Bofhcom::viewError($e);
                }
            }
        }

        if ($redirected) {
            if ($has_consented) {
                // Show message that it make take a while before everything is synced.
                View::addMessage(txt('office365_not_ready'));
            }
        }

        $view->addElement($consentForm);
        $view->start();
    }

    public function displayErrorPage($view) {
        $view->addElement('h1', txt('office365_no_access_title'));
        $view->addElement('p', txt('office365_no_access_text'));
        $view->start();
    }

    private function userConsentRegistered() {
        try {
            $has_consented = false;
            $consents = $this->bofh->run_command('consent_info', 'person:' . $this->user->getUsername());
            foreach ($consents as $consent) {
                if ($consent['consent_name'] == 'office365') {
                    $has_consented = true;
                }
            }
            return $has_consented;
        }
        catch (XML_RPC2_FaultException $e) {
            // Person has no registered consents at all
            return false;
        }
    }
}
?>
