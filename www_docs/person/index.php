<?php
require_once '../init.php';
$Init = new Init();
$User = new User();
$Bofh = new Bofhcom();
$View = View::create();

$personinfo = getPersonInfo();

$View->addTitle(txt('PERSON_TITLE'));
$View->addElement('h1', txt('PERSON_TITLE'));

$dl = View::createElement('dl', null, 'class="complicated"');
$dl->addData(ucfirst(txt('bofh_info_name')).':', $personinfo['name']);
$dl->addData(ucfirst(txt('bofh_info_birth')).':', $personinfo['birth']);


if(!empty($personinfo['affiliation'])) {
    if(is_array($personinfo['affiliation'])) {
        foreach($personinfo['affiliation'] as $k=>$a) {
            $affdef = '<dfn title="'.txt('TERM_'.$personinfo['source_system'][$k])."\">{$personinfo['source_system'][$k]}</dfn>";
            $affs[] = "Affiliation in $affdef: " . addHelpAffiliations($a);
        }
    } else {
        $affdef = '<dfn title="'.txt('TERM_'.$personinfo['source_system'])."\">{$personinfo['source_system']}</dfn>";
        $affs[] = "Affiliation in $affdef: " . addHelpAffiliations($p['affiliation']);
    }
}
if(!empty($personinfo['names'])) {
    if(is_array($personinfo['names'])) {
        foreach($personinfo['names'] as $k=>$n) {
            $namdef = '<dfn title="'.txt('TERM_'.$personinfo['name_src'][$k])."\">{$personinfo['name_src'][$k]}</dfn>";
            $names[] = "Name registered in {$namdef}: $n";
        }
    } else {
        $namdef = '<dfn title="'.txt('TERM_'.$personinfo['name_src'])."\">{$personinfo['name_src']}</dfn>";
        $names[] = "Name registered in {$namdef}: {$personinfo['names']}";
    }
}
if(!empty($personinfo['fnr'])) {
    if(is_array($personinfo['fnr'])) {
        foreach($personinfo['fnr'] as $k=>$f) {
            $fdef = '<dfn title="'.txt('TERM_'.$personinfo['fnr_src'][$k])."\">{$personinfo['fnr_src'][$k]}</dfn>";
            $fnr[] = "Number registered in {$fdef}: $f";
        }
    } else {
        $fdef = '<dfn title="'.txt('TERM_'.$personinfo['fnr_src'])."\">{$personinfo['fnr_src']}</dfn>";
        $fnr[] = "Number registered in {$fdef}: {$personinfo['fnr']}";
    }
}


$dl->addData(ucfirst(txt('bofh_info_person_affiliations')), View::createElement('ul', $affs));
if(!empty($names)) $dl->addData(ucfirst(txt('bofh_info_names').':'), View::createElement('ul', $names));
if(!empty($fnr)) $dl->addData(ucfirst(txt('bofh_info_fnr').':'), View::createElement('ul', $fnr));


$View->addElement($dl);
$View->start();

$changeinfo = View::createElement('ul', null, 'class="ekstrainfo"');
$changeinfo->addData(txt('person_howto_change_fs'));
$changeinfo->addData(txt('person_howto_change_sap'));
$View->addElement($changeinfo);


/**
 * Get a bofh-string with the persons affiliations and modify it
 * into a better presentation-form, and adds aff-definitions on it 
 * (by asking bofhcom for the descriptions).
 *
 * TODO: should this, and all other help-functions, be in the same place somewhere?
 */
function addHelpAffiliations($string) {

    global $Bofh;
    $affs = $Bofh->getAffiliations();

    $lines = explode("\n", $string);
    $return = array();
    foreach($lines as $l) {

        // example of a line:
        // ANSATT/vitenskapelig@150500 (Informatikk)

        //todo: this could be done better with some ereg
        $aff  = substr($l, 0, strpos($l, '/'));

        $type = substr($l, strlen($aff)+1);
        $type = substr($type, 0, strpos($type, '@'));

        $rest = substr($l, strlen($aff)+strlen($type)+2);

        //todo: is stedkode always 6 digits?
        $stedkode = substr($rest, 0, 6);

        $place = substr($rest, 6+2, -1);
        //$place = substr($place, 0, strpos($place, ')'));


        $return[] = "<dfn title=\"{$affs[$aff][0]}\">$aff</dfn> (<dfn title=\"{$affs[$aff][1][$type]}\">$type</dfn>) at <dfn title=\"".ucfirst(txt('bofh_info_stedkode')).": $stedkode\">$place</dfn>";

    }

    return implode('\n', $return);

}

function dfnAffiliation($aff) {

    global $Bofh;
    $affs = $Bofh->getAffiliations();

    if(!isset($affs[$aff])) return $aff;
    return "<dfn title=\"{$affs[$aff][0]}\">$aff</dfn>";

}
function dfnStatus($aff, $status) {
}


/**
 * Getting all the person_info, sorted
 */
function getPersonInfo() {

    global $User;
    global $Bofh;
    $p = $Bofh->getDataClean('person_info', $User->getUsername());

    //affiliation_1 should come first in affiliation
    if($p['affiliation']) {
        if(!is_array($p['affiliation'])) $p['affiliation'] = array($p['affiliation']);
        array_unshift($p['affiliation'], $p['affiliation_1']);
        unset($p['affiliation_1']);
    } else {
        $p['affiliation'] = array($p['affiliation_1']);
    }
    //source_system_1 should come first in source_system
    if($p['source_system']) {
        if(!is_array($p['source_system'])) $p['source_system'] = array($p['source_system']);
        array_unshift($p['source_system'], $p['source_system_1']);
        unset($p['source_system_1']);
    } else {
        $p['source_system'] = array($p['source_system_1']);
    }

    return $p;

}


?>

