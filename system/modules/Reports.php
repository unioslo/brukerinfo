<?php
// Copyright 2020 University of Oslo, Norway
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

class Reports extends ModuleGroup {
    private $modules;
    private $guests_data = array();

    public function __construct($modules) {
        $this->modules = $modules;
        $this->authz = Init::get('Authorization');
        $this->bofh = Init::get('Bofh');
        $this->user = Init::get('User');
        $this->modules->addGroup($this);
    }

    public function getName() {
        return 'reports';
    }

    public function getInfoPath() {
        return array('reports');
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
        return $this->authz->has_report_page();
    }

    public function display($path) {
        return $this->index();
    }

    public function index() {
        /**
         * Page for viewing reports.
         * 
         * Split the reports into multiple pages if more are added later.
         * Only one report for now.
         */
        $redirected = (strpos($_SERVER['QUERY_STRING'], 'redirected=true') !== false) ? true : false;

        $view = Init::get('View');
        $view->addTitle(txt('report_guests_page_title'));
        if ($this->showInMenu()) {
            $this->getGuestsData();
            $view->addElement('h1', txt('report_guests_page_title'));

            if (sizeof($this->guests_data) == 0) {
                $view->addElement('p', txt('report_guests_no_guests'));
            } else {
                $guests_table = $view->createElement('table', 'guests', 'class="app-table" id="guests"');
                $guests_table->setHead(array(
                    txt('report_guests_table_header_name'), 
                    txt('report_guests_table_header_uname'), 
                    txt('report_guests_table_header_start_date'), 
                    txt('report_guests_table_header_end_date'),
                    txt('report_guests_table_header_unit'),
                ));
                foreach ($this->guests_data as $guest) {
                    if ($guest['deleted_date']) {
                        // Mark deleted affiliations in red
                        $guests_table->addData(
                            array(
                                '<font color="red">' . $guest['name'] . '</font>',
                                '<font color="red">' . $guest['uname'] . '</font>',
                                '<font color="red">' . $guest['create_date'] . '</font>',
                                '<font color="red">' . $guest['deleted_date'] . '</font>',
                                '<font color="red">' . $guest['unit'] . '</font>',
                            )
                        );
                    } else {
                        $guests_table->addData(
                            array(
                                $guest['name'],
                                $guest['uname'],
                                $guest['create_date'],
                                $guest['deleted_date'],
                                $guest['unit'],
                            )
                        );
                    }
                }
                $view->addElement($guests_table);
            }
            $view->start();
            return;
        }

        // Render error page if user was redirected and does not have access to any consents
        if ($redirected) {
            $this->displayErrorPage($view);
            return;
        }
        // If no redirect (user tried to manually enter the route), and user
        // does not have access to any consents, forward to main page.
        View::forward('');
    }

    public function displayErrorPage($view) {
        $view->addElement('h1', txt('report_guests_no_access_title'));
        $view->addElement('p', txt('employees_only'));
        $view->start();
    }

    private function getGuestsData() {
        function sort_guests($a, $b) {
            return strcmp($a['name'], $b['name']);
        }
        try {
            $this->guests_data = $this->bofh->run_command('wofh_get_guests');
        }
        catch (XML_RPC2_FaultException $e) {
            $this->guests_data = [];
        }
        usort($this->guests_data, 'sort_guests');
    }
}
?>