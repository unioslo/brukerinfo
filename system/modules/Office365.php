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
         * to the Office365-cloud thingamabob.
         *
         * This page is only shown in the menu to users that has access to
         * Office365.
         *
         * However, if users without permission tries to log in to
         * Office365, there is no way for Office365 to determine if they don't
         * have access, or simply have not consented to create an account yet,
         * and they will be redirected here.In this scenario, a page informing
         * the users that they do not have permission to create an Office365
         * account.
         *
         */

        $redirected = (strpos($_SERVER['QUERY_STRING'], 'redirected=true') !== false) ? true : false;

        $view = Init::get('View');
        $view->addTitle(txt('office365_title'));

        if ($this->authz->has_office365()) {
            if ($redirected) {
                // Check if consent is registered in Cerebrum. Put in real check against Cerebrum when it is ready.
                if (true) {
                    // Show message that it make take a while before everything is synced.
                    $view->addElement('p', txt('office365_not_ready'));
                }
            }
            $this->displayConsentForm($view);
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

    public function displayConsentForm($view) {
        $consent_button = $view->createElement('div', null, 'id="modify-office365-consent');
        $consent_button->addData("<input type=\"submit\" name=\"test\" class=\"submit\" value=\"testtest\" />");
        $consent_form = new BofhFormUiO('office365');
        $consent_form->addElement('checkbox', 'consent', null, txt('email_forward_form_keep'));
        $consent_form->addElement($consent_button);

        if ($consent_form->validate()) {
            $view->addElement('h1', 'Form successfully submitted!');
            $view->start();
        }
        else {
            $view->addElement('h1', txt('office365_title'));
            $view->addElement('p', txt('office365_intro'));
            $view->addElement($consent_form);
            $view->start();
        }
    }

    public function displayErrorPage($view) {
        $view->addElement('h1', 'No access for you!');
        $view->addElement('p', txt('office365_no_access'));
        $view->start();
    }
}
?>
