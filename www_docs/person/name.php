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

/*
 * This pages gives users the ability to set their own primary address as we 
 * allow. The family name is always stuck, but the given name(s) can vary.
 */
require_once '../init.php';
new Init();
$bofh = Init::get('Bofh');
if (!$bofh->isEmployee()) {
    View::forward('email/', txt('EMPLOYEES_ONLY'));
}

$addresses = getAddresses();
$name = getName();
$primary = getPrimaryAddress();

$form = formModName($primary, $name, $addresses);
if ($form->validate()) {
    $form->process('formModNameProcess');
    View::forward('person/');
}

$view = Init::get('View');
$view->start();
$view->addElement('h1', txt('person_name_title'));
$view->addElement('p', txt('person_name_intro')); 

$view->addElement('p', txt('person_name_current', array('name'=>$name, 'email'=>$primary))); 
$view->addElement($form);

/**
 * Return a form for specifying what names should go as input.
 */
function formModName($current_addr, $current_name, $names)
{
    $data = array();
    foreach ($names as $name => $row) {
        $data[$name] = sprintf('%s (%s)', $name, $row[1]);
    }
    $form = new BofhFormUiO('mod_name');
    $form->addElement('select', 'name', txt('person_name_form_select'), $data);
    $form->addElement('submit', null, txt('person_name_form_submit'));

    $form->addRule('name', txt('FORM_REQUIRED'), 'required');
    $form->setDefaults(array('name' => $current_name));
    return $form;
}

/**
 * Process a HTML_QuickForm of email_mod_name.
 */
function formModNameProcess($input)
{
    global $addresses;

    if (empty($addresses[$input['name']])) {
        View::addMessage('Bogus data');
        return;
    }
    $names = $addresses[$input['name']][0];
    $last = array_pop($names);
    $first = implode(' ', $names);

    $bofh = Init::get('Bofh');
    $user = Init::get('User');
    try {
        $ret = $bofh->run_command('email_mod_name', $user->getUsername(), $first, $last);
    } catch (XML_RPC2_FaultException $e) { 
        $bofh->viewError($e);
        return;
    }
    View::addMessage(txt('person_name_success'));
}

/**
 * Return the users current primary e-mail address.
 */
function getPrimaryAddress()
{
    $bofh = Init::get('Bofh');
    $user = Init::get('User');
    $info = $bofh->getData('email_info', $user->getUsername());
    foreach ($info as $i) {
        if (!empty($i['def_addr'])) {
            return $i['def_addr'];
        }
    }
    // TODO: how should no primary address be handled?
}

/**
 * Return an array with a suggestion of all e-mail addresses that can be set as 
 * primary.
 *
 * The given name(s) can be changed to any name that the person already has. The 
 * family name can not be changed by the person.
 *
 * @return  Array   The keys are the addresses, the values are arrays of names.
 */
function getAddresses()
{
    $bofh = Init::get('Bofh');
    $user = Init::get('User');

    $names = $bofh->getData('person_name_suggestions', 'id:'.$bofh->getCache('person_id'));
    // the raw format is:
    // array(
    //  array('first_name', 'second_name'),
    //  'email_address',
    // ),
    $ret = array();
    foreach ($names as $row) {
        $ret[implode(' ', $row[0])] = $row;
    }
    return $ret;
}

/**
 * Return how an e-mail address would look like in Cerebrum with the given 
 * names.
 *
 *  - John Doe -> john.doe
 *  - John Richard Doe -> j.r.doe
 *
 * @param  Array        Array of all given names. Family name must be last.
 * @return String       How the e-mail address would look like in Cerebrum, 
 *                      without the domain.
 */
function makeAddress($names)
{
    foreach ($names as $key => $name) {
        $names[$key] = strtolower($name);
    }
    if (sizeof($names) <= 2) {
        // if only one or two names, they are used directly
        return implode('.', $names);
    }
    // if more than two names, only initials are used together with family name
    $ret = array();
    $last = array_pop($names);
    foreach ($names as $name) {
        $ret[] = $name[0];
    }
    return implode('.', $ret) . '.' . $last;
}

/**
 * Get the primary full name for the current person.
 *
 * The name returned from $bofh->getName() is cached, so we should not use that 
 * in this case.
 */
function getName()
{
    $bofh = Init::get('Bofh');
    $raw = $bofh->getData('person_info', $bofh->getUsername());
    foreach ($raw as $row) {
        if (!empty($row['name'])) {
            return trim(substr($row['name'], 0, strpos($row['name'], '[')));
        }
    }
}

?>
