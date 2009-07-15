<?php
require_once '../init.php';
$Init = new Init();
$User = new User();
$Bofh = new Bofhcom();
$View = View::create();

$personinfo = getPersonInfo();

$cache = $Bofh->getCache();
$aff_descs = $cache['affiliations'];
$source_system_descs = $cache['source_systems'];

$View->addTitle(txt('PERSON_TITLE'));
$View->addElement('h1', txt('PERSON_TITLE'));

$dl = View::createElement('dl', null, 'class="complicated"');
$dl->addData(txt('bofh_info_name'), $personinfo['name']);
$dl->addData(txt('bofh_info_birth'), $personinfo['birth']);


//affiliations
if(!empty($personinfo['affiliation'])) {
    foreach($personinfo['affiliation'] as $key=>$aff) {
        $data = splitAffiliation($aff);
        $s_sys = $personinfo['source_system'][$key];

        $data['source_system'] = $s_sys;

        if(isset($source_system_descs[$s_sys])) {
            $data['source_system_desc'] = $source_system_descs[$s_sys];
        } else {
        trigger_error(printf('Unknown source_system "%s" could not describe', 
                             $s_sys), E_USER_NOTICE);
        //TODO: what should be written when description is not found?
        $data['source_system_desc'] = $s_sys;
    }

    $data['aff_desc'] = $aff_descs[$data['aff']];
    $data['aff_sub_desc'] = $aff_descs[substr($aff,0,strpos($aff,'@'))];

    $affs[] = txt('bofh_info_person_affiliation_value', $data);
}
}
$dl->addData(txt('bofh_info_person_affiliations'), View::createElement('ul', $affs));


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


$View->addElement($dl);
$View->start();

$changeinfo = View::createElement('ul', null, 'class="ekstrainfo"');
$changeinfo->addData(txt('person_howto_change_fs'));
$changeinfo->addData(txt('person_howto_change_sap'));
$View->addElement($changeinfo);


/**
 * Inputs a string of preformatted affiliation, e.g:
 *
 * STUDENT/aktiv@150000 (Mat.Nat fakultet)
 *
 * and outputs an array with the different bits.
 */
function splitAffiliation($affstring) {

    $ret = array();

    list($affs, $sted) = explode('@', $affstring);

    list($aff, $subaff) = explode('/', $affs);
    $ret['aff'] = trim($aff);
    $ret['aff_sub'] = trim($subaff);

    list($stedkode, $stedkode_desc) = explode(' ', $sted, 2);
    $ret['stedkode']        = trim($stedkode);
    $ret['stedkode_desc']   = substr(trim($stedkode_desc), 1, -1);

    return $ret;

}

/**
 * Getting all the person_info, sorted
 *
 * This function is only returning the info you would
 * get from person_info in jbofh.
 */
function getPersonInfo() {

    global $User;
    global $Bofh;
    $p = $Bofh->getDataClean('person_info', $User->getUsername());

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

