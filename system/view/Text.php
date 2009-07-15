<?php
//   TEXT
// +------------------------------------------------------------------------+
// | PHP version 5                                                          |
// +------------------------------------------------------------------------+
// | Authors: Joakim S. Hovlandsvåg <joakim.hovlandsvag@gmail.com>          |
// +------------------------------------------------------------------------+
// |                                                                        |
// | The class which takes care of the text on the pages.                   |
// | Every institution has their own txt-files, and with different          |
// | languages.                                                             |
// |                                                                        |
// | INST has to be defined, so Text knows what text to output.             |
// |                                                                        |
// | The files are made in XML and is named on the form:                    |
// | txt.<inst>.<lang>.xml, e.g: txt.uio.en.xml                             |
// |                                                                        |
// +------------------------------------------------------------------------+

class Text {

    /** The language of the outputs */
    protected static $lang = DEFAULT_LANG;

    /** The institution to output text from */
    protected static $inst = INST;

    /** 
     * The location to the xml-files with the text, relative to LINK_DATA 
     * Must end with /
     * */
    const location = 'txt/';

    /** 
     * All the text on the given language is cached in this array.
     * The top key is the name of the language, so the cache can store
     * both active language and default language.
     */
    protected static $txts = array();

    /**
     * The values to be used in text-strings.
     *
     * If a text-string has something like '{{keyvalue}}',
     * it is replaced with what is defined in this array.
     */
    protected static $values = array();


    /** 
     * Ekstra values.
     * When some values are sent together with the call 
     * of one text, this is put here. This is to either
     * overwrite some values in the texts or just add some special values
     * that is not cached.
     */
    protected $extraValues = array();


    /**
     * An array with every defined language and its name on its own lanugage.
     * This array is only for giving the available languages names, it is not
     * defining what language is available.
     *
     * TODO: This array has to be expanded with all defined languages.
     */
    public static $lang_description = array(
        'no' => 'norsk',
        'en' => 'english',
        'no-nn' => 'nynorsk',
        'no-nb' => 'bokmål'
        //TODO!
    );


    /** 
     * Constructor
     *
     * @param String    $lang       The language to output, null makes the default
     */
    public function __construct($lang = null) {

        if($lang) self::setLang($lang);

    }

    /**
     * Returns the link to the file name with given inst and lang parameters
     * 
     * @param   String  $lang   The language to choose (if none given it uses the active lang)
     * @param   String  $inst   The institution to choose (if none given it uses the active inst)
     */
    protected static function getFile($lang = null, $inst = null) {

        $l = ($lang ? $lang : self::$lang);
        $i = ($inst ? $inst : self::$inst);
        return LINK_DATA . '/' . self::location . "txt.$i.$l.xml";

    }

    /**
     * The method which gets the text by its keyword.
     * Every languages and institutions has the same keys,
     * e.g. TXT_WELCOME, TXT_TITLE etc.
     *
     * Some values can be replaced in the text, on the form {{name}},
     * where 'name' refers to a cached value from getValue() (from init.php).
     *
     * TODO: make a list of all the keys? necessary?
     *
     * @param   String    $key      The key/id of the text to return
     * @param   mixed               When sprintf-values (%s, %d etc) are needed, these 
     *                              can be added as arguments. The first arg could be 
     *                              an array
     *
     * @return  String              The text, on the given language
     */
    public function get($key) {

        //only uppercased names in the xml-files
        $key = strtoupper($key);

        $lang = self::$lang;
        if(empty(self::$txts[$lang])) self::cache();

        if(!isset(self::$txts[$lang][$key])) {
            trigger_error(sprintf('Unknown text "%s" in language "%s", languagefile "%s"', $this->lang, $key, self::getFile()), E_USER_NOTICE);
            $lang = DEFAULT_LANG;
            if(empty(self::$txts[$lang])) self::cache($lang);

            if(!isset(self::$txts[$lang][$key])) {
                trigger_error(sprintf('Text "%s" undefined in standard language too, returning only key (file: "%s")', $key, self::getFile()), E_USER_WARNING);
                return $key;
            }
        }

        $txt = trim(self::$txts[$lang][$key]);

        
        //if extra values are sent with the call
        for($i = 1; $i < func_num_args(); $i++) {
            $a = func_get_arg($i);

            if(is_array($a)) {
                $this->extraValues = array_merge($this->extraValues, $a);
            } else {
                $this->extraValues[] = $a;
            }
        }

        //replacing text inside {{...}} with values
        $txt = preg_replace('/([^{]?){{(\w+)}}([^}]?)/e', '"$1".$this->getValue("$2", '.$key.')."$3"', $txt);
        //escaping is done with {{{text}}} -> {{text}}
        $txt = preg_replace('/{{(\w+)}}/', '{$1}', $txt);

        //resetting the extra values
        //TODO: should extra values be stored?
        $this->extraValues = array();

        return $txt;

    }

    /**
     * Checks if a given key is defined or not.
     *
     * @param   $key    The key to search for
     * @param   $only_check_active_lang    If the key should be searched for in DEFAUL_LANG
     *                              if not existing in the given language
     * @return  bool    True if the text exists, false if not
     */
    public static function exists($key, $only_check_active_lang=false) {

        //only uppercased names in the xml-files
        $key = strtoupper($key);

        $lang = self::$lang;
        if(empty(self::$txts[$lang])) self::cache();

        if(isset(self::$txts[$lang][$key])) return true;

        if($only_check_active_lang) return false;

        //trying default language
        $lang = DEFAULT_LANG;
        if(empty(self::$txts[$lang])) self::cache($lang);

        if(isset(self::$txts[$lang][$key])) return true;

        return false;

    }


    /**
     * This function gathers together data strings to be used in the display.
     *
     * To see all the values available, see $_SESSION['values']
     *
     * @param   String  $valuekey   The key referring to the value
     * @param   String  $textkey    The key referring to the text (here used for debugging)
     */
    public function getValue($valuekey, $textkey) {

        if(empty(self::$values)) self::cache();

        if(isset($this->extraValues[$valuekey])) return $this->extraValues[$valuekey];
        if(isset(self::$values[$valuekey])) return self::$values[$valuekey];

        trigger_error("Unknown value {{{$valuekey}}} in the text '$textkey'", E_USER_NOTICE);
        return $key;

    }

    /**
     * This method gets all the text and caches in the object-array
     *
     * @param   String      $lang   The language to cache, if none given caches the default
     */
    protected static function cache($lang = null) {

        $l = ($lang ? $lang : self::$lang);

        if(!file_exists(self::getFile($l))) {
            trigger_error('The text-file for '.self::inst.' on the language "'.self::$lang.'" does not exist', E_USER_WARNING);
            return;
            //TODO: change to default lang here!
        }


        $txt = new Text();
        $xml_parser = xml_parser_create();
        xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 1);

        xml_set_object($xml_parser, $txt);
        xml_set_element_handler($xml_parser, 'xml_parse_open', 'xml_parse_close');
        xml_set_character_data_handler($xml_parser, 'xml_parse_data');

        //TODO: temp?
        $txt->xml_active = false;
        $txt->xml_lang = $l;

        if (!($fp = fopen(self::getFile($l), 'rb'))) {
            die("could not open XML input");
        }
        while ($data = fread($fp, 8192)) {
            if (!xml_parse($xml_parser, $data, feof($fp))) {
                // This is printed to screen, to see the error immediate
                // TODO: should it log a warning instead?
                die(sprintf("XML error: %s at line %d", 
                    xml_error_string(xml_get_error_code($xml_parser)), 
                    xml_get_current_line_number($xml_parser)));
            }
        }

        xml_parser_free($xml_parser);


        // Values
        self::$values = array();

        self::$values['delay_min'] = ACTION_DELAY;
        self::$values['delay_hour'] = ACTION_DELAY/60;
        self::$values['delay_email'] = ACTION_DELAY_EMAIL;


        //logged on information
        global $User;
        if(is_a($User, 'User') && $User->loggedIn()) {
            self::$values['username'] = $User->getUsername();
        }

        //information from bofhd
        global $Bofh;
        if(is_a($Bofh, 'Bofhcom') && $Bofh->loggedon()) {

            self::$values['primary'] = $Bofh->getPrimary();

        }

        return true;

    }

    /** 
     * Xml parsing method to handle the opening of each datablock
     * (automatically called by xml_parse() in $this->cache() )
     */
    protected function xml_parse_open($parser, $name, $attrs) {

        if($name == 'DATA') $this->xml_active = true;
        if(!$this->xml_active) return;

        $this->xml_active_key = $name;
    }

    /** 
     * XML parsing method to handle data values 
     * (automatically called by xml_parse() in $this->cache() )
     */
    protected function xml_parse_data($parser, $data) {

        if(!$this->xml_active) return;
        if(!$this->xml_active_key) return;

        if(!$data) return;

        //TODO: a better way of doing this without getting notice_error?
        if(empty(self::$txts[$this->xml_lang][$this->xml_active_key])) {
            self::$txts[$this->xml_lang][$this->xml_active_key] = stripslashes($data);
        } else {
            self::$txts[$this->xml_lang][$this->xml_active_key] .= stripslashes($data);
        }

    }

    /**
     * XML parsing method to handle closing of each datablock
     * (automatically called by xml_parse() in $this->cache() )
     */
    protected function xml_parse_close($parser, $name) {

        if($name == 'DATA') $this->xml_active = false;

    }

    /**
     * Sets the language, and returns true if the lang exists in given institution.
     * If no language is given, it just returns the current language set.
     *
     * @param String    $lang   The language to change to (e.g. 'en', 'no')
     */
    public function setLang($lang = null) {

        if($lang) {

            if(!is_file(self::getFile())) {
                trigger_error("Unknown text file where inst='{self::inst}' and lang='$lang'", E_USER_WARNING); 
                return false;
            }

            self::$lang = $lang;
            return true;
        }

        return self::$lang;
    }

    /**
     * Sets the institution to output the text from.
     * Returns the current institution.
     *
     * @param String    $inst   The given institution.
     */
    public function inst($inst = null) {

        if($inst) self::$inst = $inst;
        return self::$inst;

    }

    /**
     * Returns an array with all the available languages.
     * This goes through the directory with the language files.
     */
    public static function getAvailableLangs() {

        $dir = LINK_DATA . '/' . self::location;

        $inst = self::$inst;

        $d = opendir($dir);
        while ($f = readdir($d)) { // !== false is out on purpose, as we're grepping file names that is != '0'
            if(!is_file($dir . $f)) continue;

            $match = array();
            if(preg_match("/^txt\.$inst\.([A-Za-z-]+)\.xml$/", $f, $match)) {

                $langs[$match[1]] = self::$lang_description[$match[1]];
            }
        }

        return $langs;

    }


    /*
     * This function parses an AcceptLanguage-string, normally given by the browser, and returns 
     * the language code for the language which is available and has the highest priority.
     *
     * Please note that this function does not set the language, it only returns the best.
     *
     * Definition of Accept-Language:
     *  http://tools.ietf.org/html/rfc2616#section-14.4
     *
     * Info about the language code:
     *  http://tools.ietf.org/html/rfc4646
     *
     * Info about the language range:
     *  http://tools.ietf.org/html/rfc4647
     * 
     * Details of the AcceptLanguage-string:
     * The languages are splitted with ',', the first language has normally the highest priority,
     * but can the overridden by adding ';q=N', where N can be a number between 1 (highest) and 0.
     * To simplify, the function is ignoring the quality parameter; what comes first tends
     * to have the highest priority.
     *
     * In addition, a region-specific variant of the language code can be given, by adding '-' in
     * the language code. E.g. 'en-us' and 'en-gb' is regional variants of 'en'. If the browser
     * prefers e.g. 'en-us' but only 'en' is available, you have two ways of handling this:
     *
     * 1. The primary subtag (e.g. 'en') could have same priority as the region subtag. This gives
     *    'en' to a browser that prefers 'en-us' if 'en-us' is not available.
     * 2. The primary subtag in a regional specified code could have lower priority than the other
     *    codes that matches exactly. How low is not defined, but one way could be to add them after
     *    the rest of the languages, or you could set the quality down by e.g. 10%.
     *
     * This function goes with method 1; if a language code is regionally specified and is not 
     * available, the primary subtag is then searched for as the next.
     *  
     * '*' can be used as a wildcard, but this is ignored in this function, and so is the other
     * subtag variants used in language codes (script subtags, extension subtags, private use
     * subtags etc).
     *
     * Language codes are defined as case-insensitive.
     *
     * @param String    $http_accept_lang       The language string, normally given from 
     *                                          $_SERVER['HTTP_ACCEPT_LANGUAGE']
     *
     * @return String                           Returns the language code for the available language 
     *                                          which has the highest priority.
     */
    public static function parseAcceptLanguage($http_accept_lang) {

        if (empty($http_accept_lang)) return DEFAULT_LANG;

        $availableLangs = array_keys(self::getAvailableLangs());

        $preferred = explode(',', strtolower($http_accept_lang));
        foreach($preferred as $p) {
            list($lang, $pri) = explode(';', strtolower($p));
            //for now, doesn't check the quality ';q=N', where 0 <= N <= 1

            if(in_array($lang, $availableLangs)) return $lang;

            //also check the primary language if it is regionally specified
            if(strpos($lang, '-')) { // sic: the primary can't be empty, so '-' can't be at pos 0
                $lang = substr($lang, 0, strpos($lang, '-'));
                if(in_array($lang, $availableLangs)) return $lang;
            }
        }

        // none found
        return DEFAULT_LANG;

    }



}
