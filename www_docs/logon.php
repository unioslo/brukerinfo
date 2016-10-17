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
$bofh = Init::get('Bofh');
$View = Init::get('View');

$logform = new BofhFormUiO('logon', null, 'logon.php');
$logform->setAttribute('class', 'app-form-big');
$logform->addElement('text',     'usi',  txt('logon_form_username'), 'id="usi"');
$logform->addElement('password', 'pasi', txt('logon_form_password'));
$logform->addElement('submit',   null,   txt('logon_form_submit'));
//TODO: add required-rules (and more)?

if ($logform->validate()) {
    try {
        $username = $logform->exportValue('usi');
        if (defined('LOGON_USERNAME_TOLOWER') && LOGON_USERNAME_TOLOWER) {
            $username = strtolower($username);
        }
        if ($User->logon($username, $logform->exportValue('pasi'))) {
            if (!empty($_SESSION['UserForward'])) {
                $base = parse_url(BASE_URL);
                $url = sprintf('%s://%s%s', $base['scheme'], $base['host'], 
                    $_SESSION['UserForward']
                );
                $_SESSION['UserForward'] = null;
                View::forward($url);
            }
            View::forward(URL_LOGGED_IN);
        }
        View::addMessage(txt('logon_bad_name_or_password'));
    } catch (UserBlockedException $e) {
        View::addMessage(txt('logon_blocked'));
    } catch (AuthenticateConnectionException $e) {
        View::addMessage(txt('error_bofh_connection'));
    }
    View::forward(URL_LOGON);
}

$View->addTitle(txt('LOGON_TITLE'));
$View->setFocus('#usi'); //TODO: move setfokus to Bofhform maybe?
$View->start();

$View->addElement('raw', txt('logon_intro'));
$View->addElement($logform);
$View->addElement('raw', txt('logon_outro'));

?>
