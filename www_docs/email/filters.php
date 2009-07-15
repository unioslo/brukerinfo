<?php
require_once '../init.php';
$Init = new Init();
$User = new User();
$Bofh = new Bofhcom();
$View = View::create();

$actives = getActiveFilters();


$form = new BofhForm('filters');

foreach(availableFilters() as $k=>$filter) {

    //title and intro
    $form->addElement('html', View::createElement('h2', ucfirst($filter['name'])));
    $form->addElement('html', View::createElement('p', $filter['desc']));

    $choose = array();
    $choose[] = $form->createElement('radio', $k, '', txt('email_filter_on', array('filter'=>$filter['name'])), 1);
    $choose[] = $form->createElement('radio', $k, '', txt('email_filter_off', array('filter'=>$filter['name'])), 0);

    $form->addGroup($choose, $k.'_group', ucfirst($filter['name']).':', "<br>\n");
    $form->addGroupRule($k.'_group', txt('email_filter_rule_required', array('filter'=>$filter['name'])), 'required');

    $status = (isset($actives[$k]) ? true : false);
    $form->setDefaults(array($k.'_group'=>array($k=>$status)));

}
$form->addElement('submit', null, txt('email_filter_submit'));



if($form->validate()) {

    foreach($form->exportValues() as $f) {
        $filter = key($f);
        $stat = current($f);
        $err = false;

        if($stat) { //add filter
            if(isset($actives[$filter])) continue;

            try {
                $res = $Bofh->run_command('email_add_filter', $filter, $User->getUsername());
            } catch(Exception $e) {
                Bofhcom::viewError($e);
                $err = true;
            }

        } else { //remove filter
            if(!isset($actives[$filter])) continue;

            try {
                $res = $Bofh->run_command('email_remove_filter', $filter, $User->getUsername());
            } catch(Exception $e) {
                Bofhcom::viewError($e);
                $err = true;
            }

        }

    }


    if(!$err) View::addMessage(txt('email_filter_update_success'));
    //if error occurs, this should already be handled and shown now
    View::forward('email/filters.php');
}


$View->addTitle('Email');
$View->addTitle(txt('EMAIL_FILTER_TITLE'));
$View->start();

$View->addElement('h1', txt('EMAIL_FILTER_TITLE'));
$View->addElement('p', txt('EMAIL_FILTER_intro'));
$View->addElement($form);

$View->addElement('p', txt('action_delay_email'), 'class="ekstrainfo"');






/**
 * This function gets all available filters from the constants EmailTargetFilter.
 */
function availableFilters() {

    global $Bofh;

    $filters_raw = $Bofh->getData('get_constant_description', 'EmailTargetFilter');

    //sorting the filters
    $filters = array();
    foreach($filters_raw as $f) {
        $id = $f['code_str'];
        $txtkey_name = 'email_filter_data_'.$id;
        $txtkey_desc = 'email_filter_data_'.$id.'_desc';

        $filters[$id]['name'] = $id;
        //looking for a better name
        if(Text::exists($txtkey_name)) {
            $filters[$id]['name'] = txt($txtkey_name);
        }

        $filters[$id]['desc'] = $f['description'];
        //looking for a better description
        if(Text::exists($txtkey_desc)) {
            $filters[$id]['desc'] = txt($txtkey_desc, array('bofh_desc'=>$f['description']));
        }
    }

    return $filters;

}

/**
 * Gets what filters the user has active.
 */
function getActiveFilters() {

    global $User;
    global $Bofh;

    $all = $Bofh->getDataClean('email_info', $User->getUsername());

    if(empty($all['filters']) || $all['filters'] == 'None') return null;

    //the filters comes in a comma-separated string
    $rawf = explode(', ', $all['filters'][0]);
    foreach($rawf as $v) $filters[$v] = true;
    return $filters;

}


?>
