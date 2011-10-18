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

/**
 * Page for viewing and modifying reservations.
 */
require_once '../init.php';
$Init = new Init();
$User = Init::get('User');
$Bofh = Init::get('Bofh');

// Get the trait names for the relevant reservations
$reservations = getReservationTypes(array(
    'reserve_passw',
));

$view = Init::get('View');
$view->addTitle(txt('reservations_title'));

$flist = $view->createElement('table');
foreach ($reservations as $id => $reservation) {
    $status = ($reservation['value'] ? txt('reservations_action_unreserve')
                                     : txt('reservations_action_reserve'));
    $flist->addData(array(
        txt('reservation_type_' . $id),
        $reservation['description'],
        "<input type=\"submit\" name=\"$id\" class=\"submit\" value=\"$status\" />",
    ));
}
$resform = new BofhForm('reservations');
$resform->addElement('html', $flist);

if ($resform->validate()) {
    $resform->process('setReservations');
    View::forward('reservations/');
}

$view->start();
$view->addElement('h1', txt('reservations_title'));
$view->addElement('p', txt('reservations_intro'));
$view->addElement($resform);


/**
 * Get information about the reservations. The names of the traits that are 
 * defined as reservations has to be specified, as this is not handled by 
 * Cerebrum yet.
 */
function getReservationTypes($traitnames)
{
    $bofh = Init::get('Bofh');
    $traits = $bofh->getData('get_constant_description', 'EntityTrait');
    $pe_traits = getReservations($traitnames);
    $ret = array();
    foreach ($traits as $trait) {
        $id = $trait['code_str'];
        if (in_array($id, $traitnames)) {
            if (!empty($pe_traits[$id])) {
                $trait['value'] = $pe_traits[$id];
            }
            $ret[$id] = $trait;
        }
    }
    return $ret;
}

/**
 * Gets the logged on users's reservations.
 *
 * TODO: this function is not checked for more than one trait.
 */
function getReservations($traitnames)
{
    $bofh = Init::get('Bofh');
    $user = Init::get('User');
    $traits = $bofh->getData('trait_info', $user->getUsername());
    if (!is_array($traits)) {
        return array();
    }
    $sorted = array();
    $name = null;
    foreach ($traits as $trait) {
        foreach ($trait as $tag => $value) {
            if ($tag == 'trait_name') {
                $name = $value;
            } elseif ($tag == 'numval') {
                $sorted[$name] = $value;
            }
        }
    }
    $ret = array();
    // only return traits defined for reservations:
    foreach ($sorted as $id => $value) {
        if (in_array($id, $traitnames)) {
            $ret[$id] = (bool) $value;
        }
    }
    return $ret;
}

/**
 * Set given reservations. Input is gotten as an array formatted by 
 * Html_QuickForm.
 */
function setReservations($input)
{
    global $reservations;
    $bofh = Init::get('Bofh');
    $user = Init::get('User');
    foreach ($input as $id => $value) {
        if (!isset($reservations[$id])) {
            trigger_error("Unknown reservation '$id' sent from input");
            continue;
        }
        $value = intval($value == txt('reservations_action_reserve'));
        try {
            $res = $bofh->run_command('trait_set', $user->getUsername(), $id, "numval=$value");
            if ($res) {
                $msgtype = ($value ? 'reservation_update_success_set'
                                   : 'reservation_update_success_del');
                View::addMessage(txt($msgtype, array(
                    'type' => txt("reservation_type_$id"),
                )));
            }
        } catch (XML_RPC2_FaultException $e) {
            Bofhcom::viewError($e);
        }
    }
}

?>
