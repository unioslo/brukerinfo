<?php
// Copyright 2009, 2010 University of Oslo, Norway
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
 * This is the file for setup, and where things get kick-started.
 *
 * It requires use of InitBase from the common library, which has to be 
 * included by either adding a require_once with an absolute path here, creating 
 * a symlink from InitBase.php to www_docs/InitBase.php, or update phps 
 * include_path with our phplib.
 *
 * All pages under www_docs should start with the following line:
 *
 *  $init = new Init();
 *
 * or 
 *
 *  $init = new Init(false);
 *
 * which disables the session for static pages.
 *
 */

// Get InitBase before Init, since autoload is not set up yet
require_once dirname(__FILE__) . '/config.php';
require_once LINK_LIB . '/controller/InitBase.php';


// the page can only work in https!
// this will hopefully not be seen, as the server is automatic resending users to https
if($_SERVER['HTTPS'] != 'on' && empty($_SERVER['argv'])) {
    trigger_error('Someone got to an unsecure page, died badly. Wrong setup in apache?', E_USER_WARNING);
    die('This page will only work in https mode, check your url.');
}

class Init extends InitBase
{
    /**
     * Constructor
     * Starts up, gathers config and make some action on the site.
     *
     * @param   bool    $session    If session_start() should be called or not.
     *                              Defaults to true, but set to false for
     *                              static pages.
     */
    public function __construct($session = true)
    {
        self::$autoload_dirs = array();
        foreach(array('controller', 'model', 'view') as $d) {
            self::$autoload_dirs[] = LINK_SYSTEM . "/$d";
            self::$autoload_dirs[] = LINK_LIB . "/$d";
        }

        parent::__construct();

        // TODO: move most of the rest to View (and other classes):
        
        View::setBaseUrl(BASE_URL);
        //BofhCom::setBofhdUrl(BOFH_URL);

        BofhForm_reCaptcha::setKeys(
            RECAPTCHA_PRIVATE_KEY, 
            RECAPTCHA_PUBLIC_KEY
        );

        // Headerdata (may be overriden by View.inc, but is nice for viewing errors)
        header('Content-Type: text/html; charset=' . strtolower(CHARSET) );
        // Security tag, preventing the site from popping up in iframes
        // Does for now only work in IE8 and Firefox with NoScript. 
        // http://hackademix.net/2009/01/29/x-frame-options-in-firefox/
        header('X-FRAME-OPTIONS: DENY');

        // Checking if the site has been locked
        if(file_exists(LOCK_FILE) && trim(file_get_contents(LOCK_FILE))) {
            define('LOCKED', true);
        } else {
            define('LOCKED', false);
        } // the locking is done by User and View

        if($session) {
            // if html_pre is '', session_set_cookie_params considers it to 
            // false, and defaults the path to be what directory you are in. 
            // This would create problems if you enter the site in a weird 
            // directory, e.g. //
            $html_pre = HTML_PRE;
            if (!$html_pre) $html_pre = '/';

            // sets the session cookie to only work in subpages of brukerinfo
            // (and not all in e.g. *.uio.no/*)
            session_set_cookie_params(0, $html_pre, $_SERVER['SERVER_NAME'], TRUE, TRUE);
            session_name('brukerinfoid');
            session_start();
        }

        // TODO: should be moved later
        TextBrukerinfo::setLocation(LINK_DATA . '/txt/' . INST);
        $this->language();

    }

    /**
     * Returns a object of the institution specific subclass of View. Only one 
     * object is constructed, the same is returned each time.
     */
    protected static function createView()
    {
        $view = new View_uio($lang, BASE_URL);
        // TODO: more settings from config are added here and not inside the 
        // class
        return $view;
    }

    /**
     * Creates a new User object with default settings for brukerinfo.
     */
    protected static function createUser($forward = true)
    {
        $user = new User(Init::get('Bofh'));

        // forward the user if not logged on
        if ($forward && !$user->isLoggedOn()) {
            View::forward(URL_LOGON);
        }

        //// logs out the user if tokens are corrupted:
        //BofhForm::addSecurityCallback(array(self::$user, 'logoff'));
        //// convenience: send users to logon page with error message:
        //BofhForm::addSecurityCallback('View::forward', array('logon.php',
        //    txt('LOGOUT_SECURITY')
        //));

        return $user;
    }

    /**
     * Creates the object for handling the proper Text in the right language.
     */
    protected function createText()
    {
        TextBrukerinfo::setLocation(LINK_DATA . '/txt/' . INST);
        TextBrukerinfo::setDefaultLanguage(DEFAULT_LANG);
        $text = new TextBrukerinfo($_SESSION['chosenLang']);
        return $text;
    }


    /**
     * Creates the default object for talking with bofhd, with the proper 
     * settings for the project.
     */
    protected static function createBofh()
    {
        BofhCom::setDefaultLocation(BOFH_URL);
        return new BofhCom();
    }



    /* 
     * Language
     *
     * This method is getting and setting the chosen
     * language for the session. Needs to be called
     * _after_ session_start().
     *
     * The language is gotten in this order:
     *  1. The session language - $_SESSION['chosenLang']
     *  2. The cookie language -  $_COOKIE['chosenLang']
     *  3. Bofhds chosen language for the person (not supported yet)
     *  4. The chosen languages by the browser (accept_language)
     *  5. The default DEFAULT_LANG if none of the above is present
     *
     * The language can be chosen by the user by sending $_GET[chooseLang],
     * which stores this in $_SESSION['chosenLang'] and $_COOKIE['chosenLang'].
     * If neither of those is set, the language is gotten from the http-parameter
     * ACCEPT_LANGUAGE. This is done by Text::parseAcceptLanguage()
     */
    protected function language()
    {
        $langs = array_keys(Text::getAvailableLanguages());

        if (!empty($_GET['chooseLang']) && in_array($_GET['chooseLang'], $langs)) {
            $chosen = $_GET['chooseLang'];
            $_SESSION['chosenLang'] = $chosen;
            setcookie('chosenLang', $chosen, time()+60*60*24*30, HTML_PRE);
        } elseif (!empty($_SESSION['chosenLang'])) {
        } elseif (!empty($_COOKIE['chosenLang']) && 
                                    in_array($_COOKIE['chosenLang'], $langs)) {
            $_SESSION['chosenLang'] = $_COOKIE['chosenLang'];
        } elseif (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $accept_langs = Text::parseAcceptLanguage($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach($accept_langs as $l) {
                if (in_array($l, $langs)) {
                    $_SESSION['chosenLang'] = $l;
                    break;
                }
            }
        } else {
            // at last, when nothing else is possible
            $_SESSION['chosenLang'] = DEFAULT_LANG;
        }
    }


}




/** 
 * A shortcut to $View->txt($key)
 * Todo: this could get removed, but clean up the references first.
 *
 * @param   String  $key    The key to what text to output
 * @param   mixed           More values to use in sprintf, the first may be an array
 */
function txt($key) {

    $v = Init::get('View');
    
    if(func_num_args() <= 1) return $v->txt($key);

    $i = 1;
    $args = array();

    if(is_array(func_get_arg($i))) {
        $args = func_get_arg($i++);
    } else {
        for(; $i < func_num_args(); $i++) {
            $args[] = func_get_arg($i);
        }
    }

    return $v->txt($key, $args);

}

/**
 * Wraps data into an array if it's not already an array.
 * Useful when returning data from bofhd, as it sometimes likes to
 * return a string instead of an array with one element.
 */
function to_array($data) {

    if(is_array($data)) return $data;
    return array($data);

}


?>
