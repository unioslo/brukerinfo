<?php
// Copyright 2013, 2014 University of Oslo, Norway
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
 * This is the UIO implementation of the authorization class
 */
class Authorization_uio extends Authorization
{
    /* Valid prefixes for authentication methods */
    protected $authz_prefixes = array('has', 'is', 'can');


    /**
     * Check if the user is logged in
     *
     * @return boolean
     */
    protected function is_authenticated()
    {
        return $this->logged_in;
    }


    /**
     * Check if the user a guest user
     *
     * @return boolean
     */
    protected function is_guest()
    {
        return (   $this->is_authenticated()
                && $this->bofh->hasTraits(array('guest_name', 'guest_owner')));
    }


    /**
     * If the user is owned by a person.
     *
     * @return bool
     */ 
    protected function is_personal()
    {
        return (   $this->is_authenticated()
                && $this->bofh->isPersonal());
    }

    /** 
     * If the user is member of a given group
     * NOTE: This call can be expensive.
     *
     * @param string $group Name of the group
     *
     * @return boolean
     */
    protected function is_member_of($group)
    {
        if (!$this->is_authenticated()) {
            return false;
        }
        try {
            // Sigh, this is a bit expensive
            $memberships = $this->bofh->run_command(
                'group_memberships', 'account', $this->user->getUsername()
            );
            if (empty($memberships)) {
                return false;  // Cop out if no memberships
            }
            foreach ($memberships as $membership) {
                if ($membership['group'] === $group) {
                    return true;
                }
            }
        } catch (XML_RPC2_FaultException $e) {
            // Maybe the group doesn't exist?
            trigger_error("Unable to check group memberships: $e", E_USER_WARNING);
        }
        return false;
    }


    /**
     * Check if the user has Office365.
     * This is done by checking the BofhCom-cache which is populated during user login.
     *
     * @return boolean
     */
    protected function has_office365()
    {
        $affs = $this->bofh->getCache('affiliations');
        foreach($affs as $aff) {
            if ($aff['affiliation'] == 'STUDENT' || $aff['affiliation'] == 'ANSATT')
                return true;
        }
        return false;
    }

    /**
     * Check if the user has IMAP spread
     *
     * @return boolean
     */
    protected function has_imap()
    {
        return (   $this->is_authenticated() 
                && $this->bofh->hasSpreads('IMAP@uio'));
    }


    /**
     * Check if the user has exchange spread
     *
     * @return boolean
     */
    protected function has_exchange()
    {
        return (   $this->is_authenticated() 
                && $this->bofh->hasSpreads('exchange_acc@uio'));
    }


    /**
     * Check if the user has one of the email spreads
     *
     * @return boolean
     */
    protected function has_email()
    {
        return $this->has_imap() || $this->has_exchange();
    }


    /**
     * Check if the user can create guest users.
     *
     * @return boolean
     */
    protected function can_create_guests()
    {
        /* This is the intended authorized group */
        return (   $this->is_authenticated()
            && $this->bofh->isEmployee());
    }


    /**
     * Check if the user can create groups.
     *
     * @return boolean
     */
    protected function can_create_groups()
    {
        return (   $this->is_authenticated()
                && $this->bofh->isEmployee());
    }


    /**
     * Check if the user can alter reservations
     *
     * @return boolean
     */
    protected function can_set_reservations()
    {
        return $this->is_personal();
    }


    /**
     * Check if the user can own other accounts.
     *
     * @return boolean
     */
    protected function can_own_multiple_accounts()
    {
        return $this->is_personal();
    }


    /**
     * Check if the user can print
     *
     * @return boolean
     */
    protected function can_print()
    {
        return $this->is_personal();
    }


    /**
     * Check if the user can set primary account
     *
     * @return boolean
     */
    protected function can_set_primary_account()
    {
        return $this->is_personal();
    }


    /**
     * Check if the user can select display name
     *
     * @return boolean
     */
    protected function can_set_display_name()
    {
        return (   $this->is_authenticated()
                && $this->bofh->isEmployee());
    }
}

?>
