<?php
// Copyright 2011 University of Oslo, Norway
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

/**
 * Common helper functions for the guest pages
 */
require_once '../init.php';


/**
 * Gets cached password for a user from BofhCom, using misc_list_passwords
 * 
 * @param string $username username to find password for
 *
 * @return string Returns a string with the cached password, or false if no
 *                passwords were found.
 */
function get_cached_password($username) {
    $bofh = Init::get('Bofh');
    try {
        $cache = $bofh->run_command('misc_list_passwords', 'skjerm');
        //The passwords are listed in order of when they were cached. If 
        //multiple passwords exist in store_state for a user, then the newest 
        //one will be the last one. We need to reverse the array, so that we 
        //will find the newest one first:
        $cache_r = array_reverse($cache);
        foreach ($cache_r as $c) {
            if ($c['operation'] == 'user_passwd' and $c['account_id'] == $username) {
                return $c['password'];
            }
        }
    } catch (XML_RPC2_FaultException $e) {
        return false;
    }
    return false;
}


/**
 * Parses DateTime objects returned from BofhCom. 
 * 
 * @param stdObject $datetime The XML_RPC2 representation of a DateTime object.
 *
 * @return string Returns an date-string formatted as 'yyyy-mm-dd', or false if
 *                the conversion failed (not a DateTime object representation).
 */
function parseDateTime($datetime) {

    if ($datetime instanceof stdClass) {
        switch($datetime->xmlrpc_type) {
            case 'datetime':
                return new DateTime("@".$datetime->timestamp);
                //return date('Y-m-d', $datetime->timestamp);
            default:
                return false;
        }
    }
    return false;
}

?>
