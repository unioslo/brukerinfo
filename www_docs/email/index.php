<?php
require_once '../init.php';
$Init = new Init();
$User = new User();
$Bofh = new Bofhcom();
$View = View::create();

$primary = emailinfo($User->getUsername());
unset($primary['account']);


$View->addTitle(txt('EMAIL_INFO_TITLE'));
$View->start();

$View->addElement('h1', txt('EMAIL_INFO_TITLE'));
$View->addElement('h2', $User->getUsername());



$prilist = View::createElement('dl', null);


// default address
if(isset($primary['def_addr'])) {
    $prilist->addData(txt('email_info_primary_addr'), $primary['def_addr']);
    unset($primary['def_addr']);
}

// valid addresses
if(isset($primary['valid_addr'])) {
    $prilist->addData(txt('email_info_valid_addr'), $primary['valid_addr']);
    unset($primary['valid_addr']);
}

// quota
if(isset($primary['quota_used'])) {
    $prilist->addData(txt('email_info_quota'), txt('email_info_quota_info', array(
        'quota_used'    => $primary['quota_used'], 
        'quota_max'     => $primary['quota_hard'], 
        'quota_warn'    => $primary['quota_soft'])));
    unset($primary['quota_used']);
    unset($primary['quota_hard']);
    unset($primary['quota_soft']);
}

// forward
if(isset($primary['forward'])) {
    $prilist->addData(txt('email_info_forward'), $primary['forward']);
    unset($primary['forward']);
}

// spam level
if(isset($primary['spam_level'])) {
    //getting the translated description
    if(Text::exists('email_spam_level_'.$primary['spam_level'])) {
        $primary['spam_level_desc'] = txt('email_spam_level_'.$primary['spam_level']);
    }

    $prilist->addData(txt('email_info_spam_level'), 
        $primary['spam_level_desc'] . ' ('.ucfirst($primary['spam_level']).')');
    unset($primary['spam_level']);
    unset($primary['spam_level_desc']);
}
// spam action
if(isset($primary['spam_action'])) {
    //getting the translated description
    if(Text::exists('email_spam_action_'.$primary['spam_action'])) {
        $primary['spam_action_desc'] = txt('email_spam_action_'.$primary['spam_action']);
    }
    $prilist->addData(txt('email_info_spam_action'), 
        $primary['spam_action_desc'] . ' ('.ucfirst($primary['spam_action']).')');
    unset($primary['spam_action']);
    unset($primary['spam_action_desc']);
}

// filter
if(!empty($primary['filters']) && $primary['filters'] != 'None') {


    //getting the description of the filters
    $filter_desc = $Bofh->getData('get_constant_description', 'EmailTargetFilter');
    //sorting the filters
    foreach($filter_desc as $f) $filter_desc[$f['code_str']] = $f['description'];

    foreach($primary['filters'] as $f) {
        //if(Text::exists("email_filter_{$f}_desc")) {
        //    $filters[] = txt("email_filter_{$f}_desc", array('bofh_desc'=>$filter_desc[$f])) . " ($f)";
        //} elseif(isset($filter_desc[$f])) {
        if(isset($filter_desc[$f])) {
            $filters[] = $filter_desc[$f] . " ($f)";
        } else {
            $filters[] = $f;
            trigger_error("Unknown filter '$f' in constants EmailTargetFilter", E_USER_NOTICE);
        }
    }

    $prilist->addData(txt('email_info_filters'), $filters);
} else {
    $prilist->addData(txt('email_info_filters'), null);
}
unset($primary['filters']);

// server
if(isset($primary['server'])) {
    $prilist->addData(txt('email_info_server'), $primary['server'] . ' ('.$primary['server_type'].')');
    unset($primary['server']);
    unset($primary['server_type']);
}





//adds the rest (if any)
foreach($primary as $k => $pr) {
    $titl = @txt('email_info_'.$k);
    if(!$titl) $titl = $k;
    $titl = ucfirst($k).':';
    $prilist->addData($titl, $pr);

    trigger_error('Forgot to add the value '.$k.'="'.$pr.'" in email/index', E_USER_NOTICE);

}

$View->addElement('div', $prilist, 'class="primary"');



// other accounts:
$otheraccounts = $Bofh->getAccounts();
if(!empty($otheraccounts)) {
    $View->addElement('h2', txt('email_other_title'));
    $View->addElement('p', txt('email_other_intro'), 'class="ekstrainfo"');

    foreach($otheraccounts as $aname => $acc) {

        if(!$acc) continue;
        //todo: needs to know how expire is returned to remove it from the list:
        if($acc['expire'] && $acc['expire']->timestamp < time()) continue;

        $sec = emailinfo($aname);

        $View->addElement('h3', $sec['account']);

        $info = View::createElement('dl', null, 'class="secondary"');
        $info->addData(txt('email_info_primary_addr'), $sec['def_addr']);
        $info->addData(txt('email_info_valid_addr'), $sec['valid_addr']);
        $info->addData(txt('email_info_server'), $sec['server'] . ' ('.$sec['server_type'].')');

        $View->addElement($info);

    }
}



$View->addElement('ul', array(txt('email_info_more_info')), 'class="ekstrainfo"');


/**
 * Gets all the info from the users mail account,
 * and does some cleaning because of weird return
 * from bofhd.
 */
function emailinfo($username) {

    global $Bofh;
    $data = $Bofh->getDataClean('email_info', $username);

    //let valid_addr_1 be first in valid_addr list (if existing)
    if(empty($data['valid_addr'])) $data['valid_addr'] = array();
    if(!is_array($data['valid_addr'])) $data['valid_addr'] = array($data['valid_addr']);
    array_unshift($data['valid_addr'], $data['valid_addr_1']);
    unset($data['valid_addr_1']);

    if(!empty($data['forward_1'])) {
        if(empty($data['forward'])) {
            $data['forward'] = $data['forward_1'];
        } else {
            if(!is_array($data['forward'])) $data['forward'] = array($data['forward']);
            array_unshift($data['forward'], $data['forward_1']);
        }
    }
    unset($data['forward_1']);

    // the filters comes in a string in an array so need to split:
    if(!empty($data['filters']) && $data['filters'] != 'None') {
        if(count($data['filters'] == 1)) {
            $data['filters'] = explode(', ', $data['filters'][0]);
        } else {
            //assumes that it has been fixed?
            trigger_error('Email-filters changed - bug is fixed?', E_USER_NOTICE);
        }

    }


    return $data;

}

?>
