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

require_once '../init.php';
$Init = new Init();
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
            'source_system'         => $personinfo['name_src'][$k],
                'source_system_desc'    => $source_system_descs[$personinfo['name_src'][$k]]
            ));
    }
}
if(!empty($fnr)) $dl->addData(txt('bofh_info_fnr'), View::createElement('ul', $fnr));

$View = Init::get('View');
$View->addTitle(txt('PERSON_TITLE'));
$View->addElement('h1', txt('PERSON_TITLE'));


$View->addElement($dl);
$View->start();

$changeinfo = View::createElement('ul', null, 'class="ekstrainfo"');
$changeinfo->addData(txt('person_howto_change_fs'));
$changeinfo->addData(txt('person_howto_change_sap'));
$View->addElement($changeinfo);


/**
 * Getting all the person_info, sorted
 *
 * This function is only returning the info you would
 * get from person_info in jbofh.
 */
function getPersonInfo() {

    global $Bofh;
    $p = $Bofh->getDataClean('person_info', Init::get('User')->getUsername());

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

    return $p;
}


?>

