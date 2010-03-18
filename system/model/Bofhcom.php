<?php
//   Bofhcom
// +------------------------------------------------------------------------+
// | PHP version 5                                                          |
// +------------------------------------------------------------------------+
// |                                                                        |
// | The class taking care of the communication with bofh.                  |
// | Depends on XML_RPC2 from PEAR.                                         |
// |                                                                        |
// +------------------------------------------------------------------------+

class Bofhcom {

    /** The XML_RPC object to use for connecting with bofhd */
    protected $xmlrpc;

    /** The secret session-id to bofhd */
    private $secret;

    /** If the communication with bofhd is logged in or not */
    protected $loggedon = false;

    /** 
     * If it is possible to contact bofhd (connection ok etc).
     * Doesn't necessary be logged on for this to be true.
     */
    protected static $ok = true;



    /** Name of the active account */
    protected $account;

    /** 
     * Array with all the other accounts the person owns (if any).
     * Does not include the active account ($this->account).
     */
    protected $accounts = array();

    /** 
     * The Message Of The Day (cache) 
     * Static, aren't necessary to get in every object.
     * */
    protected static $motd;


    // CACHE-DATA
    
    /** All cached data that is gathered at logon from bofhd */
    protected $cache = array(
        //'commands' => null,         //all the given commands from bofhd the user can access
        'spreads' => null,          //descriptions of all the spreads available
        'source_systems' => null,   //description of the source systems (authoritative systems)
        'affiliation_desc' => null, //all affiliation descriptions (to use in help-texts)
        'affiliations' => null,     //the persons affiliations
        // in addition comes:
        // 'full_name'
    );    


    /** 
     * Constructor
     * Gets the xmlrpc ready for communication.
     */
    public function __construct() {

        if(self::$ok == false) return;

        //XML-RPC2:

        //sets what encoding is used, bofhd decodes utf-8 to its own (iso-8859-1),
        //but it does not encode it to utf-8 when sending data.
        $opt['encoding'] = strtolower(CHARSET);

        //debugging prints out everything sent and received
        //$opt['debug'] = true;

        //TODO: needs to verify the server (bofhd) (have pem-certificate)
        $opt['sslverify'] = false; //todo: set this to true if the check works...

        //TODO: test if the ca-file is checked and works...
        $default_opts = array(
              'ssl'=>array(
                  'cafile'=>'/www/var/virtual/no.uio.w3utv-ws01_443/htdocs/cerebrum/wofh/system/model/pem/w3cacert.pem'
              )
          );
        //$c = stream_context_set_default($default_opts);
        //var_dump($c);
        //$d = stream_context_get_default();
        //print_r(stream_context_get_options(reasource...));
        //stream_context_set_option  ( resource $stream_or_context  , string $wrapper  , string $option  , mixed $value  )
        //stream_context_set_option ( resource $stream_or_context , array $options )

        $this->xmlrpc = XML_RPC2_Client::create(BOFH_URL, $opt);

        if(!empty($_SESSION['bofhcom']['secret'])) {

            $this->secret           = $_SESSION['bofhcom']['secret'];
            $this->loggedon         = true;
            $this->account          = $_SESSION['bofhcom']['account'];
            $this->accounts         = $_SESSION['bofhcom']['accounts'];

            $this->cache            = $_SESSION['bofhcom']['cache'];

        }

        //Message Of The Day from bofhd
        //This is received to check the connection, but is not necessary 
        //stored and shown (depening on BOFH_MOTD's value).
        //TODO: does a return of motd at each click create too much payload for bofhd?
        try{
            $m = self::bofhd_to_native($this->xmlrpc->get_motd());
            if(BOFH_MOTD) self::$motd = $m; // stores and shows the motd
        } catch(Exception $e) {
            View::addMessage(txt('error_bofh_connection'), View::MSG_ERROR);
            trigger_error($e->getMessage(), E_USER_WARNING);
            self::$ok = false;
            $this->logOut();
        }



    }

    /**
     * Destructor
     */
    public function __destruct() {

        if(self::$ok && $this->loggedon) {
            //saves data in the session
            $_SESSION['bofhcom']['secret']              = $this->secret;
            $_SESSION['bofhcom']['account']             = $this->account;
            $_SESSION['bofhcom']['accounts']            = $this->accounts;

            //saves the cache
            $_SESSION['bofhcom']['cache']               = $this->cache;
        } else {

            $_SESSION['bofhcom'] = null;
            unset($_SESSION['bofhcom']);

        }

    }


    /**
     * Returns if the user is logged onto bofhd or not
     */
    public function loggedon() {

        if(self::$ok && $this->loggedon) return true;
        return false;

    }

    /**
     * Magic function for calling bofhd-methods.
     * This method calls directly and without exception handling,
     * so if you just want data out (and auto exception handling) call
     * $this->getData('command',...) instead.
     *
     * @param  String    $methodname    The name of the method that is called
     * @throws Exception                Throws exceptions right through
     * @return Array                    An array with the data from bofhd (or exception otherwise)
     */
    public function __call($methodname, $parameters_raw) {

        if(!self::$ok) return null;

        if(!$this->loggedon) {
            View::addMessage(txt('error_bofh_connection'));
            return null;
        }

        //converting the data
        $parameters = self::native_to_bofhd($parameters_raw);

        //the first argument has to be the session-id
        //this is standard behaviour, though some methods (e.g. get_motd) doesn't
        //have this, and has to be called directly through $this->xmlrpc
        array_unshift($parameters, $this->secret);
        $args = array($methodname, $parameters);

        try {
            $ret = call_user_func_array(array($this->xmlrpc, 'remoteCall___'), $args);
            return self::bofhd_to_native($ret);
        } catch(XML_RPC2_FaultException $e) { # error from bofhd

            if(DEBUG) View::addMessage(sprintf('Debugging: Got %s #%d: %s',
                get_class($e),
                $e->getCode(), 
                $e->getMessage()));

            // session expired
            if(is_numeric(strpos($e->getMessage(), 'SessionExpiredError'))) {

                $this->logOut();

                View::addMessage(txt('logon_timed_out'));

                //todo: this could probably be removed, but requires testing
                global $User;
                if(!empty($User)) {
                    //now the page will be remembered and forwarded to when logged on again
                    //todo: this is bad bad bad and should be done differently
                    $User->logOut(txt('logon_timed_out'));
                    $User->forwardIfNotLoggedIn(txt('logon_timed_out'));
                } else {
                    View::forward(URL_LOGON);
                }
                return;

            // server restarted
            } elseif(is_numeric(strpos($e->getMessage(), 'ServerRestartedError'))) {

                trigger_error('FYI: bofhd made a restart with "'.$this->account.'" still logged on', E_USER_NOTICE);

                try{
                    $ret = $this->__call($methodname, $parameters_raw);
                    return $ret;
                } catch(Exception $e) {
                    // still problems so user have to reauthenticate
                    View::addMessage(txt('error_bofh_restarted'), View::MSG_ERROR); 
                    trigger_error('The restart caused problems for '.$this->account.', logged him/her out', E_USER_WARNING);
                    $this->logOut();
                    return null;
                }

                return $ret;

            } else {

                //other exceptions from bofhd, has to be taken care of somewhere else
                //TBD: is it necessary to convert the exceptions? They're already in utf8,
                //     but can they start with :, or be :None?
                throw $e;

            }

        } catch(Exception $e) {

            if(DEBUG) View::addMessage(sprintf('Debugging: Got unknown exception %s #%d: %s',
                get_class($e),
                $e->getCode(), 
                $e->getMessage()));

            trigger_error('Unknown exception '.get_class($e).': '.$e, E_USER_WARNING);

        }



    }

    /**
     * This function calls bofhd->run_command($command, [parameters]) 
     * and retreives the answer. 
     * If an exception is thrown, this will return null, but an error is logged.
     *
     * @param String    $command     The name of the bofhd-command to use
     * @param mixed     $params      As many parameters you want, according to the given bofh-command
     */
    public function getData($command) {

        if(!self::$ok || !$this->loggedon) return null;

        $args = func_get_args();

        try {

            $ret =  $this->__call('run_command', $args);
            return $ret;

        } catch(Exception $e) {

            $argstr = implode(', ', $args);
            trigger_error("getData could not call method run_command($argstr) on ".BOFH_URL.", unknown exception: $e", E_USER_WARNING);
            return null;

        }

    }


    /**
     * This function does the same as getData, but also cleans up the returned data.
     * Most data from bofhd comes in an array with dicts, which has to be searched through.
     * This method searches through all the dicts and returns just one array with everything.
     * Normally this return is easier to use.
     *
     * @param String    $command    The name of the bofhd-command
     * @param mixed     $params     As many parameters you/bofhd wants.
     *
     * @return Array                An array with named keys
     */
    public function getDataClean() {

        if(!self::$ok || !$this->loggedon) return null;

        $args = func_get_args();

        $data = call_user_func_array(array(self, getData), $args);

        //can only sort arrays
        if(!is_array($data)) return $data;

        $ret = array();
        foreach($data as $n=>$dict) {
            if(!is_numeric($n)) {
                $ret[$n] = $dict;
                continue;
            }

            foreach($dict as $k=>$v) {
                //if there's more values with same keys: subarray
                if(isset($ret[$k])) {
                    if(is_array($ret[$k])) {
                        $ret[$k][] = $v;
                    } else {
                        $ret[$k] = array($ret[$k], $v);
                    }
                } else {

                    $ret[$k] = $v;

                }
            }
        }

        return $ret;
    }


    /**
     * Logs on bofhd.
     * 
     * @param String    $username   The users username to log on bofhd
     * @param String    $password   The password to the user
     */
    public function logon($username, $password) {

        if(!self::$ok) return;

        if(!$username || !$password) return false;
	
        try {

            $res = self::bofhd_to_native($this->xmlrpc->login(self::native_to_bofhd($username), self::native_to_bofhd($password)));

            $this->secret = $res;
            $this->loggedon = true;
            $this->account = $username;

            $this->cache();
            return true;

        } catch (XML_RPC2_FaultException $e) {
            // This is an error from bofhd, normally the "wrong username or password"
            // The User-object takse care of this.
            throw $e;

        //} catch (XML_RPC2_CurlException $e) {
            //throw new BofhException("Couldn't connect to bofhd");
        } catch (Exception $e) {
            //TODO: return error-message 'cannot connect to database'
            trigger_error("Unknown exception when logging on to bofhd: $e", E_USER_WARNING);

            //TODO: best to throw error here?
            throw new BofhException('Unknown exception, could not log on to bofhd');

        }
        //TODO: more error feedback here?
        
    }

    /**
     * Logs out from the bofh daemon
     */
    public function logOut() {

        try {
            $res = self::bofhd_to_native($this->xmlrpc->logout(self::native_to_bofhd($this->secret)));
            if($res != 'OK') trigger_error('Logout from bofhd made unsuspected return = "'.$res.'"');
        } catch (Exception $e) {
            trigger_error('Error from bofhd on logout of "'.$this->account.'" (connection dropped from here, but connection may still be open)', E_USER_WARNING);
        }

        $_SESSION['bofhcom'] = null;
        $this->secret = null;
        $this->loggedon = false;

    }

    /** 
     * Caches different data from bofhd.
     * Groups are not cached before necessary, see $this->cacheGroup()
     */
    protected function cache() {

        if(!self::$ok) return;
        if(!$this->loggedon) return null;

        //todo: commands may be used in the future, but not for now...
        //$this->cache['commands'] = $this->get_commands();
       
        //getting the other accounts (without the active)
        $acc = $this->getData('person_accounts', $this->account);
        foreach($acc as $a) {
            //skipping the active account
            if($a['name'] == $this->account) continue;
            $this->accounts[$a['name']] = $a;
        }

        //gets descriptions of affiliations, for use in help-texts
        $affs = $this->getData('misc_affiliations');
        $aktivaff = null;

        foreach($affs as $aff) {
            if(!empty($aff['aff'])) {
                $aktivaff = $aff['aff'];
                $this->cache['affiliation_desc'][$aktivaff] = $aff['desc'];
            }

            if(!empty($aff['status'])) {
                $this->cache['affiliation_desc'][$aktivaff.'/'.$aff['status']] = $aff['desc'];
            }
        }

        //gets the list of source_systems to use in help-texts
        $sources = $this->getData('get_constant_description', 'AuthoritativeSystem');
        foreach($sources as $source) {
            $this->cache['source_systems'][$source['code_str']] = $source['description'];
        }

        //getting the full name of the person
        $persinfo = $this->getDataClean('person_info', $this->account);
        // using the cached name, but that comes in the format "First Last [from Cached]"
        $this->cache['full_name'] = substr($persinfo['name'], 0, trim(strrpos($persinfo['name'], '[')));

        //caching the persons affiliations
        $affs = array(); 
        if(!empty($persinfo['affiliation'])) {
            $affs = to_array($persinfo['affiliation']);
            $persinfo['source_system'] = to_array($persinfo['source_system']);
        }
        if(!empty($persinfo['affiliation_1'])) {
            //source_system_1 _should_ follow affiliation_1
            array_unshift($affs, $persinfo['affiliation_1']);
            array_unshift($persinfo['source_system'], $persinfo['source_system_1']);
        }
        foreach($affs as $k=>$f) {
            list($afs, $ou) = explode('@', $f);
            $stedkode = substr($ou, 0, 6);
            $stedkode_desc = substr($ou, 8, -1);
            list($aff, $status) = explode('/', $afs);

            $this->cache['affiliations'][] = array(
                'affiliation'=>$aff,
                'status'=>$status,
                'stedkode'=>$stedkode,
                'stedkode_desc'=>$stedkode_desc,
                'source_system'=>$persinfo['source_system'][$k]);
        }

    }


    /**
     * This function converts data to bofhds xml-rpc-format.
     *
     * Also, native_to_bofhd escapes values beginning with : with an extra colon:
     * :foo -> ::foo
     * This is expected by bofhd, as :None -> None in bofhd. That is why:
     * null -> :None
     *
     * The charset is set to utf8, which works correctly with data sent from
     * bofhd, but not the other way. That is why this method converts the data
     * to iso-8859-1.
     *
     * @param   mixed       $text   The text which will be sent to bofh. Can be strings and arrays of strings.
     * @return  mixed               Returns the encoded text, on the same form as the input.
     */
    static protected function native_to_bofhd($text) {

        if (is_array($text)) {
            foreach ($text as $k=>$v) {
                $text[$k] = self::native_to_bofhd($v);
            }

        } else {
            if ($text === NULL) {
                return ':None';
            } elseif (substr($text, 0, 1) == ':') {
                return ':' . utf8_decode($text);
            } else {
                return utf8_decode($text);
            }
        }

        return $text;

    }

    /*
     * This function converts data from bofhd to native php-format.
     *
     * Pear's xml_rpc2 takes care of standard xmlrpc-objects, but bofhd
     * has its own solution for null/None:
     * :None -> null
     * Strings starting with : is escaped with another ':':
     * ::foo -> :foo
     *
     * The charset is set to utf8, which works correctly with data sent from
     * bofhd, but not the other way. This method doesn't need to convert the data.
     *
     * @param  mixed    $text      The string or array from bofhd (lists are already converted to arrays by xmlrpc2)
     * @return mixed               Php-ready data.
     */
    static protected function bofhd_to_native($text) {

        if (is_array($text)) {
            foreach ($text as $k=>$v) {
                $text[$k] = self::bofhd_to_native($v);
            }

        } else {
            if (is_bool($text)) {
                return (bool) $text;
            // TODO: add more type checks here, to avoid getting it as strings
            } elseif ($text === ':None') {
                return null;
            } elseif (is_object($text)) {
                return $text;
            } elseif (substr($text, 0, 1) == ':') {
                //bofhd sends data in utf-8, so no conversion is needed here. 
                return htmlspecialchars(substr($text, 1));
            } else {
                //htmlspecialchars escapes html-tags, as data from bofhd should not include html
                return htmlspecialchars($text);
            }
        }

        return $text;

    }

    /**
     * Sends user-errors from bofhd to the user, after some clean-up
     * 
     * @param   Exception   $err        The exception thrown by xmlrpc
     */
    static public function viewError(Exception $err) {

        $msg = $err->getMessage();

        if(is_numeric(strpos($msg, 'CerebrumError:'))) {

            $msg = substr($msg, strrpos($msg, 'CerebrumError: ')+15);
            View::addMessage(htmlspecialchars($msg), View::MSG_WARNING);

        } else {

            //system errors
            //TODO: should all errors be shown to the user?
            View::addMessage(txt('error_bofh_error'), View::MSG_ERROR);
            View::addMessage(htmlspecialchars($err->getMessage()), View::MSG_ERROR);

            trigger_error('Unexpected error: '.$e, E_USER_WARNING);

        }

    }




    /// CACHED COMMANDS
    /// These commands is just returning cached data.

    /**
     * Returns the users accounts
     */
    public function getAccounts() {

        if(!$this->loggedon) return null;
        return $this->accounts;

    }

    /**
     * Returns persons primary account.
     * If owner is not a person, the logged on account is returned.
     * Returns NULL if the person has no user affiliations.
     */
    public function getPrimary() {

        if(!self::$ok) return;
        if(!$this->loggedon) return null;

        try {
            $acclist = $this->run_command('person_list_user_priorities', $this->account);
        } catch(Exception $e) {
            // if owner isn't a person
            return $this->account;
        }

        $primary = null;
        foreach($acclist as $acc) {
            if(!empty($acc['status']) && $acc['status'] == "Expired") continue;
            if(!$primary) $primary = $acc;
            if($acc['priority'] < $primary['priority']) $primary = $acc;
        }

        if(!$primary) return null;
        return $primary['uname'];

    }

    /**
     * Returns the array of all the cached data, or one part of it if a key is given.
     *
     * @param String    $key    If not null, it returns the element of the cache array
     *                          with the given key.
     */
    public function getCache($key = null) {

        if($key !== null) return $this->cache[$key];
        return $this->cache;

    }

    /**
     * Returns the Message Of The Day.
     */
    public function getMotd() {
        return self::$motd;
    }

    /**
     * Returns the description of a given spread.
     * This function also caches the spreads (this is not done in 
     * cache, as the user may not need it).
     *
     * @param String    The name of the spread, if null given, the function returns all available spreads
     */
    public function getSpread($spread = null) {

        //cache if not done before
        if(empty($this->cache['spreads'])) {
            $raw = $this->getData('spread_list');
            foreach($raw as $spr) {
                $this->cache['spreads'][$spr['name']] = $spr['desc'];
            }
        }

        if(!$spread) return $this->cache['spreads'];

        if(!isset($this->cache['spreads'][$spread])) return null;
        return $this->cache['spreads'][$spread];

    }

    /**
     * If the person is defined as employee or not.
     *
     * @return boolean     True if person is registered as an employee
     */
    public function is_employee() {

        if(!$this->loggedon()) return;

        foreach($this->cache['affiliations'] as $aff) {
            // TODO: this is not the correct definition, should have a more 
            //       generic definition (in config most likely)
            if($aff['source_system'] == 'SAP') return true;
        }
        return false;
    }

      
}
