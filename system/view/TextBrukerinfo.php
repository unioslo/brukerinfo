<?php
// Copyright 2010 University of Oslo, Norway
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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Cerebrum. If not, see <http://www.gnu.org/licenses/>.


/**
 * The class which takes care of the text for Brukerinfo.
 */
class TextBrukerinfo extends Text
{
    public function __construct($lang, $location = null, $default_lang = null)
    {
        parent::__construct($lang, $location, $default_lang);

        // Cache some values
        self::$values['delay_min']   = ACTION_DELAY;
        self::$values['delay_hour']  = ACTION_DELAY/60;
        self::$values['delay_email'] = ACTION_DELAY_EMAIL;

        //logged on information
        $user = Init::get('User');
        if ($user->isLoggedOn()) {
            self::$values['username'] = $user->getUsername();
        }
        $bofh = Init::get('Bofh');
        if ($bofh->isLoggedOn()) {
            self::$values['primary'] = $bofh->getPrimary();
            self::$values['full_name'] = $bofh->getName();
        }
    }

    /**
     * Reads the right language file and caches it.
     *
     * @param  String    $lang   Specific language, NULL caches default.
     * @param  bool      $force  Forces recaching.
     *
     * @return bool              If the data is cached or not.
     */
    protected static function cache($lang, $force = false)
    {
        return parent::cache($lang, $force);
    }


}
