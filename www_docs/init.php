<?php
//   Init
// +------------------------------------------------------------------------+
// | PHP version 5                                                          |
// +------------------------------------------------------------------------+
// |                                                                        |
// | This file loads the config, defines some basic functions, and gets     |
// | things going.                                                          |
// |                                                                        |
// +------------------------------------------------------------------------+

/// Action
//  Pages should include the following line(s):
//
//  $init = new Init();
//  or 
//  $init = new Init(false);
//  which disables the session (for logged out, static pages)
//

// the page can only work in https!
// this will hopefully not be seen, as the server is automatic resending users to https
if($_SERVER['HTTPS'] != 'on') {
    trigger_error('Someone got to an unsecure page, died badly. Wrong setup in apache?', E_USER_WARNING);
    die('This page will only work in https mode, check your url.');
}

class Init {

    /**
     * __construct
     * Starts up, gathers config and make some action on the site
     *
     * @param   boolean     $session   If session_start() should be called or not
     */
    public function __construct( $session = true ) {

        // Getting the configs (has to in the same dir as this file)
        require_once(dirname(__FILE__) . '/config.php');

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
            // sets the session cookie to only work in subpages of brukerinfo (and not all *.uio.no/*)
            // TODO: this can be removed when apaches config is updated with the same
            session_set_cookie_params(0, HTML_PRE, $_SERVER['SERVER_NAME'], TRUE, TRUE);
            session_name('brkrnfid');
            session_start();
        }

        $this->language();

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
    private function language() {

        $langs = array_keys(Text::getAvailableLangs());

        if(!empty($_GET['chooseLang'])) {

            $chosen = trim($_GET['chooseLang']);

            if(in_array($chosen, $langs)) {
                $_SESSION['chosenLang'] = $chosen;
                setcookie('chosenLang', $chosen, time()+60*60*24*30, HTML_PRE);
            }

            //todo: check if this works... or if it is necessary at all
            //View::forward($_SERVER['HTTP_REFERER']); //todo: create internal history-array instead?
            return true;
        }

        if(!empty($_SESSION['chosenLang'])) return true;

        //the cookie can not be trusted as valid
        if(!empty($_COOKIE['chosenLang']) && in_array($_COOKIE['chosenLang'], $langs)) {
            $_SESSION['chosenLang'] = $_COOKIE['chosenLang'];

            //todo: forward here? or is the session value stored now?
            return true;
        }


        //if neither the session nor the cookie has some logic value
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $_SESSION['chosenLang'] = Text::parseAcceptLanguage($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            return true;
        }

        // at last, when nothing else is possible
        $_SESSION['chosenLang'] = DEFAULT_LANG;
    }


}



/**
 * __autoload
 * Autoloads classes, normally with $object = new Classname()
 *
 * First is the include_path tried (with include_once), before
 * a local search.
 *
 * The local classes are located different places. There are two 
 * main directories: /system/view and /system/model, and class names
 * has to be unique (though the model dir has priority). If the 
 * classname has a '_', it is replaced to a subdir '/'.
 *
 * @param string $classname 	The class-name
 */
function __autoload($class) {

    $class = str_replace('_', '/', $class);

    //first, try using include_path (Pear)
    if(@include_once($class.'.php')) return true;

    //local classes
    $link = LINK_SYSTEM . '/model/' . $class . '.php';
    if(!is_file($link)) $link = LINK_SYSTEM . '/view/' . $class . '.php';
    if(!is_file($link)) return false;


    ob_start(); //prevents output
    require_once($link);
    if($tmp = ob_get_contents()) trigger_error('The class "'.$link.'"), made unsuspected output: "' . $tmp . '"', E_USER_WARNING);
    ob_end_clean();

    return true;

}


/** 
 * A shortcut to $View->txt($key)
 * Todo: this could get removed, but clean up the references first.
 *
 * @param   String  $key    The key to what text to output
 * @param   mixed           More values to use in sprintf, the first may be an array
 */
function txt($key) {

    global $View;
    if(!isset($View)) $v = View::create();
    else $v = $View;
    
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





?>
