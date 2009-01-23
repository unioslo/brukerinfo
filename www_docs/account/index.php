<?php
require_once '../init.php';
$Init = new Init();
$User = new User();
$Bofh = new Bofhcom();

$userinfo = getUserinfo(); 
unset($userinfo['username']);


$View = View::create();
$View->addTitle(txt('ACCOUNT_TITLE'));
$View->start();

$View->addElement('h1', txt('ACCOUNT_TITLE'));
$View->addElement('h2', $User->getUsername(). ($Bofh->getPrimary() == $User->getUsername() ? ' (primary)' : ''));



$list[0] = View::createElement('dl', null, 'class="complicated"');


//standard info

$list[0]->addData(ucfirst(txt('bofh_info_spreads')), addHelpSpread(explode(',', $userinfo['spread'])));
unset($userinfo['spread']);

$list[0]->addData(ucfirst(txt('bofh_info_affiliations')), addHelpAffiliations(explode(',', $userinfo['affiliations'])));
unset($userinfo['affiliations']);

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
echo '<!-- ';
print_r($accounts);
echo '-->';
if($accounts) {

    $View->addElement('h2', txt('account_other_title'));

    $table = View::createElement('table', null, 'class="mini"');
    $table->setHead(txt('account_other_table_name'), txt('account_other_table_expire'));

    foreach($accounts as $aname => $acc) {

        //checks for expired accounts:
        if($acc['expire']) {
            //older than today:
            if($acc['expire']->timestamp < time()) $aname .= ' (deleted)';
            $expire = date('Y-m-d', $acc['expire']->timestamp);

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
        global $User;
        $username = $User->getUsername();
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
    $affs = $Bofh->getAffiliations();

    $string = trim($string);

    // example of a line:
    // ANSATT@150500 (Informatikk)
    // STUDENT@150000 (Mat.nat. fakultet)

    $aff = substr($string, 0, strpos($string, '@'));

    //todo: is stedkode always 6 digits?
    $stedkode   = substr($string, strlen($aff)+1, 6);

    $place = substr($string, strlen($aff)+strlen($stedkode)+3, -1);

    //todo: this could be done better with some ereg

    return "<dfn title=\"{$affs[$aff][0]}\">$aff</dfn> at <dfn title=\"".ucfirst(txt('bofh_info_stedkode')).": $stedkode\">$place</dfn>";
    //return "<dfn title=\"{$affs[$aff][0]}\">$aff</dfn>: <dfn title=\"{$affs[$aff][1][$type]}\">$type</dfn>,  (<dfn title=\"Stedkode: $stedkode\">$place</dfn>)";

}


?>
