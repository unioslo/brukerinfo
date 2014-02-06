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
 * This is a simple class to control access to certain pages.
 * 
 * It is a simple class, used to check whether a user has access to certain 
 * pages. We should look more into the Authentication/Authorization procedure in 
 * regards to a re-design of brukerinfo.
 *
 * This class is just a place to gather authorization checks that might be used 
 * in multiple locations.
 *
 * Usage example:
 *   $authz = new Authorization;
 *   if $authz(has_something, 'given some arg') {
 *      echo "Do authorized action";
 *   } else {
 *      echo "Handle authz error";
 *   }
 */
class Authorization
{
    protected $init;
    protected $bofh;
    protected $user;

    protected $authz_prefixes = array('_example_');

    /**
     * All our communication is at the moment with bofhd through the BofhCom 
     * client.
     *
     * @param BofhCom $bofh The bofh communication client. This object is used 
     *                      to check user attributes in Cererbum.
     * @param User    $user The user object. Most authorizations will require a 
     *                      User to be logged in. 
     */
    public function __construct(BofhCom $bofh, User $user)
    {
        $this->bofh = $bofh;
        $this->user = $user;
        $this->logged_in = $this->user->isLoggedOn();
    }

    /** 
     * All authorization is done in a similar way to the Init factory.
     *
     * NOTE: Every method that begins with an $auth_prefix in this class should 
     *       be an authorization method, and should return true/false.
     *
     * We attempt to call a function, '(has|is)_<some_authz_name>'. 
     * If a protected method with that name exists, we try to call it, and 
     * return its value. If not, we simply return 'false' (no authz method => no 
     * authz)...
     *
     * @param string $name The function that was called
     * @param array  $args The arguments given to the function $name.
     *
     * @return boolean If the action of name $name is authorized for the 
     *                 currently logged in user.
     *
     * @throws BadMethodCallException If a $name without a valid prefix is 
     *                                called.
     */
    public function __call($name, $args)
    {
        foreach ($this->authz_prefixes as $valid_prefix) {
            if (strpos($name, $valid_prefix) === 0) {
                if (method_exists($this, $name)) {
                    $is_authz = call_user_func_array(array($this, $name), $args);
                    return (is_bool($is_authz) && $is_authz);
                }
                trigger_error("Authorization method is not implemented '$name'");
                return false;
            }
        }
        throw new BadMethodCallException("Invalid authorization method '$name'");
    }

    /**
     * Example, true for everybody
     *
     * @return boolean true
     */
    private function _example_true()
    {
        return true;
    }

    /**
     * Example, false for everybody
     *
     * @return boolean false
     */
    private function _example_false()
    {
        return false;
    }
}
