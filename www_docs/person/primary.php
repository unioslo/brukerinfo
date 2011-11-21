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

require_once '../init.php';
$Init = new Init();
$User = Init::get('User');
$View = Init::get('View');
$View->addTitle(txt('primary_person_title'));

$cache = Init::get('Bofh')->getCache();
$affs  = $cache['affiliations'];

$form = new BofhFormUiO('change_primary');
$form->setAttribute('class', 'app-form-big');

$radios = array();
foreach ($affs as $aff) {
    // the id to store in the trait
    $id = sprintf('%s/%s@%s', $aff['affiliation'], $aff['status'], $aff['stedkode']);

    // adding human descriptions
    $aff['source_system_desc'] = $cache['source_systems'][$aff['source_system']];
    $aff['affiliation_desc']   = $cache['affiliation_desc'][$aff['affiliation']];
    $aff['status_desc']        = $cache['affiliation_desc'][$aff['affiliation'].'/'.$aff['status']];

    $human_aff = txt('bofh_info_person_affiliation_value', $aff);
    $radios[] = $form->createElement('radio', 'aff', null, $human_aff, $id);
}
$form->addGroup($radios, 'primary', txt('primary_person_choice'), "<br />\n");
$form->addElement('submit', null, txt('primary_person_submit'));

$form->addRule('primary', txt('form_required'), 'required');
$form->setDefaults(array('primary' => array('aff' => get_chosen_primary())));

if ($form->validate()) {
    if ($form->process('process_set_primary')) {
        View::forward('person/primary.php', txt('primary_person_updated'));
    }
    View::forward('person/primary.php', txt('error_bofh_error'));
}

$View->start();
$View->addElement('h1', txt('primary_person_title'));
$View->addElement('p', txt('primary_person_intro'));
$View->addElement($form);

function process_set_primary($data)
{
    $primary = $data['primary']['aff'];
    $bofh = Init::get('Bofh');
    $user = Init::get('User');

    try {
        $ret = $bofh->run_command('trait_set', 'entity_id:'.get_person_id(),
            'primary_aff', 'strval='.$primary, 'date='.date('Y-m-d')
        );
    } catch (XML_RPC2_FaultException $e) { 
        trigger_error($e);
        return false;
    }
    return $ret;
}

// TODO: cache this?
function get_person_id()
{
    $bofh = Init::get('Bofh');
    $user = Init::get('User');
    $person_info = $bofh->getData('person_info', $user->getUsername());
    $person_id = null;
    foreach ($person_info as $p) {
        if (!empty($p['entity_id'])) {
            return $p['entity_id'];
        }
    }
}

/**
 * Return the value of a previously chosen primary affiliation, or null if it 
 * hasn't been set before.
 */
function get_chosen_primary()
{
    $bofh = Init::get('Bofh');
    $traits = $bofh->getData('trait_info', 'entity_id:'.get_person_id());
    if (!is_array($traits) || empty($traits['traits'])) {
        return null;
    }
    foreach ($traits['traits'] as $trait) {
        if ($trait['trait_name'] === 'primary_aff') {
            return $trait['strval'];
        }
    }
}

?>
