<?php
// Copyright 2013 University of Oslo, Norway
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
        return false;
        //return (   $this->is_authenticated()
                //&& $this->bofh->hasTraits(array('guest_name', 'guest_owner')));
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
        return false;
        //return (   $this->is_authenticated()
                //&& $this->bofh->isEmployee());
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
