<?php
//   User
// +------------------------------------------------------------------------+
// | PHP version 5                                                          |
// +------------------------------------------------------------------------+
// |                                                                        |
// | Login                                                                  |
// | Different user values would be located in $_SESSION[$session][XXX]	    |
// |                                                                        |
// +------------------------------------------------------------------------+

class User {

    // CONTROL

    /** If the user's logged in **/
    protected $_loggedin = false;

    /** The name for user-array in $_SESSION **/
    private static $_session_name;
    /** The name for temporary-array in $_SESSION **/
    private static $_session_name_temp;

    // DATA
    
    /** Pointer to session-variables **/
    private $_session;

    /** Pointer to temp-variables **/
    private $_session_temp;

    // CONNECTION

    /** The pointer to the bofhcom-object */
    private $bofhd;


    /** 
     * Constructor
     *
     * @param bool      $forward_if_not     Forwards the user to the login page if not logged in
     */
    public function __construct($forward = true) {

        //gets the session data
        $this->_session_name      = md5($_SERVER['SERVER_NAME'] . 'UIo#&%&' . $_SERVER['SERVER_SIGNATURE']);
        $this->_session_name_temp = md5('ab@$' . $_SERVER['SERVER_NAME'] . 'UStI@' . $_SERVER['SERVER_SOFTWARE']);
        $this->_session           = (isset($_SESSION[$this->_session_name]) ? $_SESSION[$this->_session_name] : array());
        $this->_session_temp      = (isset($_SESSION[$this->_session_name_temp]) ? $_SESSION[$this->_session_name_temp] : array());

        //makes the connection
        global $Bofh;
        if(isset($Bofh)) {
            $this->bofhd = $Bofh;
        } else {
            $this->bofhd = new Bofhcom();
        }

        //if not loggeod onto bofhd you're not logged on
        if(!$this->bofhd->loggedon()) $this->logOut();

        //checks if the system has been locked
        if (LOCKED) {
            $this->logOut('The system has automatically logged you out');

            if($forward) $this->forwardIfNotLoggedIn();

        } else {
            //checks if everything is ok
            $this->_construct_control();

            if($forward) $this->forwardIfNotLoggedIn();
        }

    }

    /** 
     * Sends all data to the session
     */
    public function __destruct() {

        //om alt er tomt lagast heller ingen session-array
        //if(!$this->_session_name && !$this->_session_temp && !$_SESSION[$this->_session_name] && !$_SESSION[$this->_session_name_temp]) return;

        //sends user data to the session
        $_SESSION[ $this->_session_name ]      = (isset($this->_session) ? $this->_session : null);
        $_SESSION[ $this->_session_name_temp ] = (isset($this->_session_temp) ? $this->_session_temp : null);

    }


    /**
     * Safety check of the user.
     * This is normally called from __construct.
     *
     * TODO: trigger errors with more description?
     **/
    private function _construct_control() {

        $this->_loggedin = false;

        if(empty($this->_session['username'])) return false;

        //TODO: checking the timestamps if the system has been updated and the session needs a recaching


        //idle-time check
        if($this->_session['time'] < (time() - (TIME_OUT_MIN*60))) { //TIME_OUT_MIN = minutes
            View::addMessage(txt('logon_timed_out'));
            $this->_session_temp['forward_from'] = $_SERVER['REQUEST_URI'];
            $this->logOut('');
            return false;
        } 


        //browser check
        if($this->_session['control'] != $this->browserHash()) {
            trigger_error('Browser settings has changed, could be a snapped up session-id', E_USER_WARNING);
            View::addMessage(txt('logon_control_failed'), View::MSG_ERROR);
            $this->_session_temp['forward_from'] = $_SERVER['REQUEST_URI'];
            $this->logOut('');
            return false;
        }

        $this->_session['time'] = time();
        $this->_loggedin = true;
        return true;

    }

    




    /// KONTROLL:
    
    /**
     * Checks if the user is logged in or not
     *
     * @return  boolean  True if the user is logged in, false if not
     */
    public function loggedIn() {

        return $this->_loggedin;

    }

    /**
     * Forwards the user to logon if not logged in. If allready logged 
     * in: do nothing.
     *
     * @param   String      $message    The message the user gets (gets default if null)
     * @return  boolean  True if the user is logged in, false if not
     */
    public function forwardIfNotLoggedIn($message = null) {

        if($this->_loggedin) return;

        $this->_session_temp['forward_from'] = $_SERVER['REQUEST_URI'];

        if($message) View::addMessage($message);
        View::forward(URL_LOGON);

    }
    
    /*
     * Logs on the user with correct username and password.
     * Forwards the user to the last visited page (if any), or the 
     * default page (config: URL_LOGGEDIN)
     *
     * @param String    $usrn       The supposedly correct username
     * @param String    $pasw       The supposedly correct password
     */
    public function logOn($usrn, $pasw) {

        $this->logOut();

        //todo: check for ips blocked here!

        if(!$usrn || !$pasw) return;

        if($this->_session_temp['attempts'] > ATTEMPTS) {
            if($this->_session_temp['last_attempt'] > (time() - (ATTEMPT_TIME_OUT_MIN*60) )) {
                View::addMessage(txt('LOGON_BLOCKED'));
                return false;
            }
        }

        try {
            $res = $this->bofhd->logon($usrn, $pasw);

            if($res) {

                //renames the session cookie 
                session_regenerate_id(true);

                $this->_loggedin = true;
                $this->_session['username'] = htmlspecialchars($usrn);

                //creating control-session
                $this->_session['control'] = $this->browserHash();
                $this->_session['time'] = time();
                $this->_session['loggedon'] = time();
                $this->_session_temp['attempts'] = 0; 

                $url = (!empty($this->_session_temp['forward_from']) ? 
                    $this->_session_temp['forward_from'] : URL_LOGGED_IN);

                $this->_session_temp['forward_from'] = null;

                View::addMessage(txt('LOGON_SUCCESS', array(
                    'username' => $this->_session['username'],
                    'full_name' => $this->bofhd->getCache('full_name'))));

                View::forward($url);
                return true;

            }

        // connection exceptions etc are handled by Bofhcom
        } catch(XML_RPC2_FaultException $e) {
            //most likely: Cerebrum.modules.bofhd.errors.CerebrumError'>:CerebrumError: 
            //                                              Unknown username or password

            View::addMessage(txt('LOGON_BAD_NAME_OR_PASSWORD'));

        }

        //bad logon attempt
        trigger_error('Bad logon attempt. User="'.htmlspecialchars($usrn).'"', E_USER_WARNING);
        $this->_session_temp['attempts']++;
        $this->_session_temp['last_attempt'] = time();

        return false;

    }




    /**
     * Logs out the user
     *
     * @param string    $message    If a message should be sent to the user
     * @param bool      $warning    If true, the message is a warning, false means just a notice
     */
    public function logOut($message=null, $warning=false) {

        $this->_session = null;
        unset($this->_session);

        if($this->bofhd) $this->bofhd->logOut();


        if(!$this->_loggedin) return;

        //messaging
        $type = ($warning ? View::MSG_WARNING : View::MSG_NOTICE);

        if($message === null) $message = txt('logon_logged_out');
        if($message) View::addMessage($message, $type);


        //TODO: remove this line later?
        $_SESSION = array();

        $this->_loggedin = false;

    }
    

    /**
     * Makes a string out of the browser-settings.
     * This makes it more sure that the user is still
     * the same user. If the string from here changes,
     * the session_id could have been snapped by 
     * someone else.
     */
    private function browserHash() {

        // [HTTP_USER_AGENT]        - works
        // [HTTP_ACCEPT]
        // [HTTP_ACCEPT_LANGUAGE]   - works
        // [HTTP_ACCEPT_ENCODING]
        // [HTTP_ACCEPT_CHARSET]    - not in MSIE
        // [HTTP_KEEP_ALIVE]
        // [HTTP_CONNECTION]
        // [HTTP_CACHE_CONTROL]

        $setning = '}!a_+?4';
        if(isset($_SERVER['HTTP_USER_AGENT']))      $setning .= $_SERVER['HTTP_USER_AGENT'];
        $setning .= '!%ghjkl';
        if(isset($_SERVER['HTTP_ACCEPT_CHARSET']))  $setning .= $_SERVER['HTTP_ACCEPT_CHARSET'];
        $setning .= '251115';
        if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) $setning .= $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        return md5($setning);
        
    }



    public function getUsername() {
        if(!$this->_loggedin) return false;
        return $this->_session['username'];
    }


}

?>
