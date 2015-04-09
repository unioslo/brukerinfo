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


/* Code to load different feature sets based on FEATURES config variable
 * or institution setting.
 *
 * This is placed in data dir because it probably contains some dependency
 * injections.
 */

function get_feature_list() {
    if (null !== FEATURES) {
        $ret = array();
        $tok = strtok(FEATURES, " \t\n\r");
        while ($tok != false) {
            $ret[] = $tok;
            $tok = strtok(" \t\n\r");
        }
        return $ret;
    } else {
        $features = array(
            'uio' => array('account', 'email', 'groups', 'guests', 'person', 
                           'printing', 'reservations'),
            'hine'=> array('account', 'email', 'groups', 'person'),
            'tsd' => array('Account_tsd', 'groups')
        );
        return $features[INST];
    }
}

function get_feature($feature, $modules) {
    $features = array(
        'home' => 'Home',
        'account' => 'Account',
        'email' => 'Email',
        'groups' => 'Groups',
        'guests' => 'Guest',
        'person' => 'Person',
        'printing' => 'Printing',
        'reservations' => 'Reservations'
    );
    if (array_key_exists($feature, $features)) {
        return (new ReflectionClass($features[$feature]))->
            newInstance($modules);
    } else {
        return (new ReflectionClass($feature))->newInstance($modules);
    }
}
