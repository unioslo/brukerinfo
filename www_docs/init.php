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
