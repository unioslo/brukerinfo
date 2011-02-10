<?php
// Copyright 2009, 2010, 2011 University of Oslo, Norway
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

require_once 'init.php';
$Init = new Init();
$User = Init::get('User', false); // do not forward
$User->logoff();

//checks if the site is locked for maintenance (or anything)
if (LOCKED) {

    $lockstr = file_get_contents(LOCK_FILE);

    $View = Init::get('View');
    $View->addTitle(txt('locked_title'));
    $View->start();
    $View->addElement('raw', nl2br($lockstr));

} else { // normal behaviour

    $logform = new BofhForm('logon');
    $logform->addElement('text', 'usi', txt('LOGON_FORM_USERNAME'), 'id="usi"');
    $logform->addElement('password', 'pasi', txt('LOGON_FORM_PASSWORD'));
    $logform->addElement('submit', null, txt('LOGON_FORM_SUBMIT'));
    //TODO: add required-rules (and more)?

    if($logform->validate()) {
        try {
            if($User->logon($logform->exportValue('usi'), $logform->exportValue('pasi'))) {
                View::forward(URL_LOGGED_IN, txt('logon_success'));
            }
            View::addMessage(txt('logon_bad_name_or_password'));
        } catch (AuthenticateConnectionException $e) {
            View::addMessage(txt('error_bofh_connection'));
        }
        View::forward(URL_LOGON);
    }


    $View = Init::get('View');
    $View->addTitle(txt('LOGON_TITLE'));
    $View->setFocus('usi');//TODO: move setfokus to Bofhform maybe?
    $View->start();

    $View->addElement('raw', txt('logon_intro'));
    $View->addElement($logform);
    $View->addElement('raw', txt('logon_outro'));

}

?>
