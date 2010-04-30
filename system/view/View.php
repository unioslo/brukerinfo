<?php
//   VIEW
// +------------------------------------------------------------------------+
// | PHP version 5                                                          |
// +------------------------------------------------------------------------+
// | Authors: Joakim S. Hovlandsvåg <joakim.hovlandsvag@gmail.com>          |
// +------------------------------------------------------------------------+
// |                                                                        |
// | The superclass for making pages in html                                |
// | You have to make a subclass of this one, so each institution get their |
// | own design.                                                            |
// |                                                                        |
// | View->start() sends out the first html (like <head>), and end() is     |
// | automatically called by __destruct() and sends the rest of the html.   |
// |                                                                        |
// | View is also making html-tags, by calling add- and createElement.      |
// |                                                                        |
// +------------------------------------------------------------------------+

abstract class View {

    /** 
     * If the first html is sent (if start() is called).
     * Must be true before __destruct() calls end().
     */
    protected $started = false;

    /**
     * If the last html is sent
     */
    protected $ended = false;

    /**
     * MSG_LEVELS
     * The user gets feedback if something's happened, 
     * like errors, warnings or just notices.
     * How it is shown is maintained in the css-files.
     */

    /** System errors */
    const MSG_ERROR     = 9;

    /** Warnings if something's wrong */
    const MSG_WARNING   = 6;

    /** Standard notices */
    const MSG_NOTICE    = 2;
    
    /** If no level is pointed out, this standard is sent */
    const MSG_STANDARD  = 2;

    /* USER data */

    /** The user-object (if logged on) */
    protected $user;

    /** If the user is logged on or not */
    protected $logged_in = false;


    /** The language to use */
    protected $language = DEFAULT_LANG;

    /** The protocol to be used - http or https */
    //TODO: smarter way of doing this?
    protected $protocol = 'https';

    /** 
     * If the messages will be deleted when sent out. 
     * If set to false, the message will be sent to the
     * next page as well.
     */
    protected $delMessages = true;

    /**
     * CSS-files to include
     * Double arrays, with the keys 'href', [media] and ['modern'],
     * where modern means using the @import-command (makes old browser
     * ignoring it)
     */
    protected $css = array(); 

    /** the id-name of form-element to set focus on at startup (javascript) */
    protected $focus;

    /** if the page should be cached or not */
    protected $cache = false;

    /** The array with the titles to use at the page.
     *  It is an array to easier change the direction of the
     *  titles, e.g:
     *   University of Oslo - Wofh - Account - Change password
     *  or:
     *   Change password - Account - Wofh - University of Oslo
     */
    protected $titles = array();

    /**
     * The content of the page, sorted in an array 
     * by element-classes which prints out html-tags
     */
    protected $content = array();

    /** The Text-class to where text are gotten */
    protected $text;

    /** If help-texts are to be automatic added on the content */
    protected $help = false;


    /** 
     * Constructor
     *
     * @param String $lang  The language of the site to output
     */
    protected function __construct($lang = null) {
    
        if(!isset($_SERVER['HTTPS'])) $this->protocol = 'http';

        global $User;
        if(!empty($User) && is_a($User, 'User') && $User->loggedIn()) {
            $this->user = $User;
            $this->logged_in = true;
        }

        // Getting the set language
        if ($lang !== null) {
            $this->setLang($lang);
        } elseif (!empty($_SESSION['chosenLang'])) {
            $this->setLang($_SESSION['chosenLang']);
        }

        // Getting the Text
        $this->text = new Text($this->getLang());

        //main title
        $this->addTitle(self::txt('PAGETITLE'));

    }

    /**
     * Calls the end-output at the end
     */
    protected function __destruct() {

        $this->end();

    }


    /// MODIFICATIONS

    /**
     * Sets the language to use at the page. The language is used by Text(), so
     * the real checking is in there.
     *
     * @param $newLang  String The new language to use, defaults to DEFAULT_LANG when false
     * @return          String       The language which are now being used
     */
    public function setLang($newLang = null) {
    
        if(!$newLang) {
            $this->language = DEFAULT_LANG;
        } else {
            $this->language = $newLang;
        }

        return $this->language;
    }

    /**
     * Returns the set language.
     */
    public function getLang() {
        return $this->language;
    }

    /**
     * Adds another title to the title-array
     */
    public function addTitle($newTitle) {

        $this->titles[] = trim($newTitle);

    }

    /**
     * Sets the writing-focus to an element. Done by javascript.
     *
     * @param String    $id     The id of the element to focus
     */
    public function setFocus($id) {

        //TODO: move this to Bofhform?
        $this->focus = $id;

    }

    /**
     * Adds a message to be given to the user.
     * This function is static to avoid objectmaking
     *
     * The levels are intvalues, but may change, so please use the constants, like this:
     *  View::addMessage('Warning, dont do that!', View::MSG_WARNING);
     *
     * @param   Mixed       $msg        The message(s) to show, in String or Array
     * @param   int         $level      The level of the message (warning, notice etc). None given makes it MSG_STANDARD.
     */
    public static function addMessage($msg, $level = self::MSG_STANDARD) {

        if(!$msg) { 
            trigger_error('Empty message given, with level='.intval($level), E_USER_WARNING); 
            return; 
        }

        if(!is_numeric($level)) { 
            trigger_error('Unknown level='.htmlspecialchars($level).'" upon the msg="'.htmlspecialchars($melding).'", using standard level', E_USER_NOTICE); 
            $level = self::MSG_STANDARD;
        }

        if(is_array($msg)) {
            foreach($msg as $m) self::addMessage($m, $level);
        } else {
            $_SESSION[md5('MELd]\G4Æ'.$_SERVER['SERVER_NAME'])][] = array($msg, $level);
        }

    }

    /**
     * Sets if the messages are to be deleted or not.
     *
     * @param boolean   $set        If true, the messages will be deleted when shown on the page, not if false
     */
    public function delMessages($set) {
        $this->delMessages = ($set ? true : false);
    }

    /**
     * Gets out all the messages in a double array.
     * [0] = text, [1] = type MSG
     *
     * @param bool $delete    If the messages should be deleted after output
     */
    public function getMessages($delete = false) {
        //TODO: make this static again

        //override deletion with the delMessages setting
        if(!$this->delMessages) $delete = false;

        $name = md5('MELd]\G4Æ'.$_SERVER['SERVER_NAME']);
        if(!isset($_SESSION[$name])) return null;

        if(!$delete) return $_SESSION[$name];

        $m = $_SESSION[$name];
        $_SESSION[$name] = array();
        return $m;

    }


    ///DESIGN

    /**
     * Sends correct headerdata to the browser
     */
    protected function sendHeaders() {

        header('Content-Type: text/html; charset=' . strtolower(CHARSET));
        header('Content-Language: ' . strtolower($this->language));

        // TODO: don't know what to put here as the correct time
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()));

        if(!$this->cache) {
            //different http tweaks to avoid caching
            header('Expires: ' . gmdate('D, d M Y H:i:s', time()-3600*7) . ' GMT');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Cache-Control: post-check=0, pre-check=0', FALSE);
            header('Pragma: no-cache');
        }
    }
    
    /**
     * Forwards the user to another page
     *
     * @param String    $to     The relative url to go to. If starting with /, it skipc the HTML_PRE.
     * @param String    $msg    If the forward also will output a message
     */
    static function forward($to, $msg=null, $msgType = self::MSG_STANDARD) {

        //TODO: make this smarter - settings in config or anything...
        $protocol = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://');

        if(strpos($to, '/') === 0) {
            $link = $to;
        } else {
            $link = HTML_PRE . '/' . $to;
        }

        if($msg) self::addMessage($msg, $msgType);

        header('Location: '.$protocol.$_SERVER['SERVER_NAME'].$link); 

        global $View;
        $v = null;

        if(isset($View)) {
            $v = $View;
        } else {
            $v = View::create();
        }
        $v->delMessages(false);
        $v->addTitle('Redirection in progress');
        $v->start();
        echo "<h1>Redirection in progress</h1>
            <p>You should now be redirected to the next page, but just <a href=\"$to\">click on this to go to the next page</a>.</p>";
        $v->end();
        die();

    }

    /**
     * Gets out text from the Text-class with given key
     *
     * @param String    $key        The key to what text that is outputted
     * @param mixed                 If more arguments, theese goes as args in a sprinf()-call
     */
    public function txt($key) {

        if(func_num_args() <= 1) return $this->text->get($key);

        $i = 1;
        $args = array();

        if(is_array(func_get_arg($i))) {
            $args = func_get_arg($i++);
        } else {
            for(; $i < func_num_args(); $i++) {
                $args[] = func_get_arg($i);
            }
        }

        return $this->text->get($key, $args);

    }


    /**
     * Brings out the Message of the day
     * TODO: move this to init or anywhere else?
     */
    static function getMotd() {

        $motd = array();

        //wofhs own motd
        $file = LINK_DATA . '/motd';
        $file_motd = (file_exists($file) ? trim(file_get_contents($file)) : null);
        if($file_motd) $motd[] = $file_motd;

        //global $Bofh;
        $bofh = new Bofhcom();
        $bofh_motd = trim($bofh->getMotd());
        if($bofh_motd) $motd[] = $bofh_motd;

        return $motd;

    }

    /// CONTENT handling
    /// Creating html-objects

    /**
     * Creates an element, and puts it in the View->content,
     * or, if the page is started, prints it out.
     *
     * @param mixed     $type       If an object, puts it directly in on the page, if String, it creates the object
     */
    public function addElement($type) {

        if(is_object($type)) {
            $ele = $type;
        } else {
            $args = func_get_args();
            $ele = call_user_func_array(array('self', 'createElement'), $args);
        }


        if($this->started) {
            if($this->help) echo self::addHelp($ele);
            else echo $ele;
        } else  {
            $this->content[] = $ele;
        }

        return $ele;

    }

    /**
     * Creates and returns an element.
     *
     * @param String    $type   What type of element you want created
     */
    static public function createElement($type) {

        $type = ucfirst(strtolower($type));
        $name = 'Html_'.$type;

        $args = func_get_args();
        for($i=1; $i<6; $i++) {
            if(!isset($args[$i])) $args[$i] = null;
        }

        //TODO: making an error or Exception here?
        $ele = new $name($args[1], $args[2], $args[3], $args[4], $args[5]);
        return $ele;

    }


    /** 
     * Returns the content of the page
     */
    public function getContent() {

        return $this->content;

    }

    /// HTML outputs
    /// These methods returns strings in html for output

    /**
     * Returns a html-string of the css-links, to be directly
     * outputted in the head.
     *
     * @return  String      The css-tags, in html
     */
    protected function htmlCss() {

        $css = '';
        foreach ($this->css as $c) {

            $media = (empty($c['media']) ? null : ' media="'.$c['media'].'"');
            $modern = (empty($c['modern']) ? false : true);

            if($modern) {
                $css .= '<style type="text/css"'.$media.'>@import url('.$c['href'].");</style>\n";
            } else {
                $css .= '<link rel="stylesheet" style="text/css" href="' . $c['href'] . '"' . $media . ">\n";
            }

        }

        return $css;

    }

    /**
     * Returns the javascript-lines for focusing an element
     */
    protected function htmlFocus() {

        if($this->focus) return '<script type="text/javascript">window.onload = function() {' .
            '    if (element = document.getElementById(\''.htmlspecialchars($this->focus).'\')) { element.focus(); }' .
            '}; </script>';

    }

    /**
     * Returns html of the messages
     * @param boolean   $delete     If the message-array should be wiped
     */
    protected function htmlMessages($delete) {

        $msgs = $this->getMessages($delete);
        if(!$msgs) return;

        $html =  "<ul id=\"messages\">\n";

        foreach($msgs as $m) {
            switch ($m[1]) {
            case self::MSG_WARNING:
                $msgtype = ' class="warning"';
                break;

            case self::MSG_ERROR:
                $msgtype = ' class="error"';
                break;

            case self::MSG_NOTICE:
                $msgtype = ' class="notice"';
                break;

            default:
                trigger_error('Unknown MSG_TYPE=('.htmlspecialchars($m[1]).')', E_USER_NOTICE);
            }

            $html .= "<li $msgtype>{$m[0]}</li>\n";
        }
        $html .= "</ul>\n";
        return $html;

    }




    /**
     * Creates the institution-specific View-object
     */
    static function create($lang = null) {

        $viewname = 'View_'. INST;
        return new $viewname($lang);


    }


    /**
     * Starting output of the html page.
     */
    abstract public function start();

    /**
     * The finishing design
     * Usually this is called automatic by __destruct(), 
     * so make, but don't use it
     */
    abstract public function end();

}

?>
