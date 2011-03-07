<?php
// Copyright 2009, 2010, 2011 University of Oslo, Norway
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
        foreach (array('controller', 'model', 'view') as $d) {
            self::$autoload_dirs[] = LINK_SYSTEM . "/$d";
            self::$autoload_dirs[] = LINK_LIB    . "/$d";
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
        header('Content-Type: text/html; charset=utf-8');
        // Security tag, preventing the site from popping up in iframes
        // Does for now only work in IE8 and Firefox with NoScript. 
        // http://hackademix.net/2009/01/29/x-frame-options-in-firefox/
        header('X-FRAME-OPTIONS: DENY');

        if($session) {
            // if the path is '', session_set_cookie_params considers it as
            // false, and sets it to the path the user is in. This would create 
            // problems if you enter the site in a sub path (e.g. email/), as 
            // the session then would only work for that sub path, while another 
            // path would give you another session.
            $path = parse_url(BASE_URL, PHP_URL_PATH);
            if (!$path) $path = '/';

            // sets the session cookie to only work in subpages of brukerinfo
            // (and not all in e.g. *.uio.no/*)
            session_set_cookie_params(0, $path, $_SERVER['SERVER_NAME'], TRUE, TRUE);
            session_name('brukerinfoid');
            session_start();
        }

        // Checking if the site has been locked
        if (file_exists(LOCK_FILE)) {
            $msg = trim(file_get_contents(LOCK_FILE));
            if (strlen($msg) > 0) {
                // log out user
                $user = Init::get('User', false);
                $username = $user->getUsername();
                if ($user->logoff()) {
                    trigger_error("$username got logged out due to locked page");
                }

                // view lock page
                $view = Init::get('View');
                $view->addTitle(txt('locked_title'));
                $view->start();
                $view->addElement('raw', nl2br($msg));
                die;
            }
        }
    }

    /**
     * Returns a object of the institution specific subclass of View. Only one 
     * object is constructed, the same is returned each time.
     */
    protected static function createView()
    {
        $view = new View_uio($lang, BASE_URL);
        // TODO: more settings from config should be added here and not inside 
        //       the class
        return $view;
    }

    /**
     * Creates a new User object with default settings for brukerinfo.
     */
    protected static function createUser($forward = true)
    {
        if (!User::setMaxAttempts(ATTEMPTS)) {
            trigger_error('User::setMaxAttempts('.ATTEMPTS.') didn\'t work');
        }
        if (!User::setMaxAttemptsTimeout(ATTEMPT_TIME_OUT_MIN * 60)) {
            trigger_error('User::setMaxAttemptsTimeout('. (TIME_OUT_MIN * 60)
                . ') didn\'t work'
            );
        }

        // TODO: check for exceptions from User::_construct_control, e.g. 
        // timeout and security issues.
        $user = new User(Init::get('Bofh'));
        // if timeout exception: store the present url to forward when logged on again

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
    protected static function createText()
    {
        TextBrukerinfo::setLocation(LINK_DATA . '/txt/' . INST);
        TextBrukerinfo::setDefaultLanguage(DEFAULT_LANG);
        $lang = self::chooseLanguage();
        $text = new TextBrukerinfo($lang);
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
     * Calculates the preferred language by different input values and stores it 
     * in the session and returns it.
     *
     * The language is searched for in this order:
     *  1. An active chosen language - $_GET['chooseLang']. Also sets a cookie.
     *  2. The session language - $_SESSION['chosenLang']
     *  3. Previously chosen language - $_COOKIE['chosenLang'].
     *  4. A preferred language - $_SERVER['HTTP_ACCEPT_LANGUAGE']
     *  5. The default DEFAULT_LANG if none of the above is present or matches 
     *     an available language.
     *
     *  @return String                  The chosen language.
     */
    protected function chooseLanguage()
    {
        $langs = array_keys(Text::getAvailableLanguages());
        $chosen = DEFAULT_LANG;

        if (!empty($_GET['chooseLang']) && in_array($_GET['chooseLang'], $langs)) {
            $chosen = $_GET['chooseLang'];
            $path = parse_url(BASE_URL, PHP_URL_PATH);
            if (!$path) $path = '/';
            setcookie('chosenLang', $chosen, time()+60*60*24*365, $path);
        } elseif (!empty($_SESSION['chosenLang'])) {
            $chosen = $_SESSION['chosenLang'];
        } elseif (!empty($_COOKIE['chosenLang']) && in_array($_COOKIE['chosenLang'], $langs)) {
            $chosen = $_COOKIE['chosenLang'];
        } elseif (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $accept_langs = Text::parseAcceptLanguage($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach($accept_langs as $l) {
                if (in_array($l, $langs)) {
                    $chosen = $l;
                    break;
                }
            }
        }
        $_SESSION['chosenLang'] = $chosen;
        return $chosen;
    }
}




/** 
 * A shortcut to $Text->get($key)
 *
 * @param   String  $key    The key to what text to output
 * @param   mixed           More values to use in sprintf, the first may be an array
 */
function txt($key)
{
    $txt = Init::get('Text');
    if (func_num_args() <= 1) {
        return $txt->get($key);
    }
    $i = 1;
    $args = array();

    if (is_array(func_get_arg($i))) {
        $args = func_get_arg($i++);
    } else {
        for (; $i < func_num_args(); $i++) {
            $args[] = func_get_arg($i);
        }
    }
    return $txt->get($key, $args);
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
