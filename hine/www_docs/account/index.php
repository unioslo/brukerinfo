<?php
# Copyright 2009, 2010 University of Oslo, Norway
# 
# This file is part of Cerebrum.
# 
# Cerebrum is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# Cerebrum is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with Cerebrum. If not, see <http://www.gnu.org/licenses/>.

require_once '../init.php';
$Init = new Init();
$User = Init::get('User');
$Bofh = Init::get('Bofh');

$userinfo = getUserinfo(); 
unset($userinfo['username']);


$View = Init::get('View');
$View->addTitle(txt('ACCOUNT_TITLE'));
$View->start();

$View->addElement('h1', txt('ACCOUNT_TITLE'));
$View->addElement('h2', ($Bofh->getPrimary() == $User->getUsername() 
    ? txt('account_name_primary') 
    : txt('account_name_normal')
));



$list[0] = View::createElement('dl', null, 'class="complicated"');


//standard info

//spreads
$list[0]->addData(ucfirst(txt('bofh_info_spreads')), addHelpSpread(explode(',', $userinfo['spread'])));
unset($userinfo['spread']);

var_dump($userinfo);
//afiliations
if (isset($userinfo['affiliations'])) {
    $list[0]->addData(ucfirst(txt('bofh_info_affiliations')), addHelpAffiliations(explode(',', $userinfo['affiliations'])));
    unset($userinfo['affiliations']);
} else {
    $list[0]->addData(ucfirst(txt('bofh_info_affiliations')), txt('account_affs_empty'));
}

//expire
if(isset($userinfo['expire'])) {
    $list[0]->addData(ucfirst(txt('bofh_info_expire')).':', $userinfo['expire']);
    unset($userinfo['expire']);
}


if(isset($_GET['more'])) {
    $list[1] = View::createElement('a', txt('general_less_details'), 'account/');
} else {
    $list[1] = View::createElement('a', txt('general_more_details'), 'account/?more');
}


//extra info

if(isset($_GET['more'])) {
    $list[2] = View::createElement('dl', null, 'class="complicated"');
    //ksort($userinfo);
    foreach($userinfo as $k=>$v) {
        if(!$titl = @txt('bofh_info_'.$k)) { // @ prevents warnings, as data may change
            $titl = $k; // if no given translation, just output variable name
        }
        $list[2]->addData(ucfirst($titl).':', $v);
    }
}

$View->addElement('div', $list, 'class="primary"');


//other accounts
$accounts = $Bofh->getAccounts();
if (sizeof($accounts) > 1) {

    $View->addElement('h2', txt('account_other_title'));

    $table = View::createElement('table', null, 'class="mini"');
    $table->setHead(txt('account_other_table_name'), txt('account_other_table_expire'));

    foreach ($accounts as $aname => $acc) {
        if ($aname == $Bofh->getUsername()) {
            continue;
        }

        //checks for expired accounts:
        if($acc['expire']) {
            //older than today:
            if($acc['expire']->timestamp < time()) $aname = txt('account_name_deleted', array('username'=>$aname));
            $expire = date(txt('date_format'), $acc['expire']->timestamp);
        } else {
            $expire = txt('account_other_expire_not_set');
        }

        $table->addData(View::createElement('tr', array(
            $aname,
            $expire
        )));
    }

    $View->addElement($table);
    $View->addElement('p', txt('account_other_info'), 'class="ekstrainfo"');

}




/**
 * Gets the user_info from Bofhcom, and 
 * removes the unecessary info.
 */
function getUserinfo($username = null) {

    if(!$username) {
        $username = Init::get('User')->getUsername();
    }

    $Bofh = new Bofhcom();
    $info = $Bofh->getDataClean('user_info', $username);

    //removing
    unset($info['entity_id']);
    unset($info['owner_type']);
    unset($info['owner_id']);

    //removing null-elements:
    foreach($info as $k=>$v) {
        if(!$v) unset($info[$k]);
    }

    return $info;

}

/**
 * Adds a description onto spreads. Works with both a string and
 * array of strings.
 *
 * @param mixed     Array or string with the spreads to describe
 * @return          Returns the same as in the input, but with longer string(s)
 */
function addHelpSpread($spreads) {

    if(is_array($spreads)) {
        foreach($spreads as $k => $v) {
            $spreads[$k] = addHelpSpread($v);
        }
    } else {
        $spreads = trim($spreads);

        global $Bofh;
        $desc = $Bofh->getSpread($spreads);
        if($desc) $spreads = $desc;
    }

    return $spreads;
}

/**
 * Get a bofh-string with the persons affiliations and modify it
 * into a better presentation-form, and adds aff-definitions on it 
 * (by asking bofhcom for the descriptions).
 *
 * Todo: this function is not equal to the function in person/index.php, but
 *       they could be merged and handle different text-variations...
 *
 * TODO: should this, and all other help-functions, be in the same place somewhere?
 */
function addHelpAffiliations($string) {

    //recursive
    if(is_array($string)) {
        foreach($string as $k => $v) $string[$k] = addHelpAffiliations($v);
        return $string;
    }

    global $Bofh;
    $affs = $Bofh->getCache();
    $affs = $affs['affiliation_desc'];

    // example of a line:
    // ANSATT@150500 (Informatikk)
    // STUDENT@150000 (Mat.nat. fakultet)

    list($aff, $sted) = explode('@', trim($string), 2);
    list($stedkode, $stedkode_desc) = explode(' ', $sted, 2);

    return txt('bofh_info_account_affiliation_value', array(
        'aff'           => $aff,
        'aff_desc'      => $affs[strtoupper($aff)],
        'stedkode'      => $stedkode,
        'stedkode_desc' => $stedkode_desc
    ));

}


?>
