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
    View::forward('email/', 'Only for employees');
}

$addresses = getAddresses();

$form = formModName($addresses);
if ($form->validate()) {
    $form->process('formModNameProcess');
    # TODO: forward when done testing
    View::forward('email/');
}

$view = Init::get('View');
$view->start();
$view->addElement('h1', 'Change e-mail address');
$view->addElement('p', 'Choose your name variety to be used for generating 
    your primary e-mail address');
$primary = getPrimaryAddress();
$view->addElement('p', "Your current primary address is: <strong>$primary</strong>.");
$view->addElement($form);

/**
 * Return a form for specifying what names should go as input.
 */
function formModName($names)
{
    $data = array();
    foreach ($names as $key => $n) {
        $data[$key] = $key;
    }
    $form = new BofhFormUiO('email_mod_name');
    $form->addElement('select', 'address', 'Select address:', $data);
    $form->addElement('submit', null, 'Set name');

    $form->addRule('address', 'Wat', 'required');
    return $form;
}

/**
 * Process a HTML_QuickForm of email_mod_name.
 */
function formModNameProcess($input)
{
    global $addresses;

    if (empty($addresses[$input['address']])) {
        View::addMessage('Bogus data');
        return;
    }
    $names = $addresses[$input['address']];
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
    View::addMessage('Name and primary e-mail address changed');
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

    // get current name
    $pinfo = $bofh->getData('person_info', $user->getUsername());
    $current = explode(' ', $pinfo[0]['name']);
    array_pop($current); //       Cached]
    array_pop($current); // [from
    $last = end($current);

    $ret = array();
    foreach ($pinfo as $row) {
        if (empty($row['names'])) {
            continue;
        }
        $names = explode(' ', trim($row['names']));
        $ret[makeAddress($names)] = $names;
        array_pop($names);
        foreach ($names as $n) {
            $n = array($n, $last);
            $ret[makeAddress($n)] = $n;
        }
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

?>
