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
    protected $lang = DEFAULT_LANG;

    /** The institution to output text from */
    protected $inst = INST;

    /** The location to the xml-files with the text, relative to LINK_SYSTEM */
    protected static $location = 'txt/';

    /** 
     * All the text on the given language is cached in this array.
     * The top key is the name of the language, so the cache can store
     * both active language and default language.
     */
    protected static $txts = array();


    /** 
     * Constructor
     *
     * @param String    $lang       The language to output, null makes the default
     */
    public function __construct($lang = null) {

        if($lang) $this->lang($lang);
    
    }

    /**
     * Returns the link to the file name with given inst and lang parameters
     * 
     * @param   String  $lang   The language to choose (if none given it uses the active lang)
     * @param   String  $inst   The institution to choose (if none given it uses the active inst)
     */
    protected function getFile($lang = null, $inst = null) {

        $l = ($lang ? $lang : $this->lang);
        $i = ($inst ? $inst : $this->inst);
        return LINK_DATA . '/' . self::$location . "txt.$i.$l.xml";

    }

    /**
     * The method which gets the text by its keyword.
     * Every languages and institutions has the same keys,
     * e.g. TXT_WELCOME, TXT_TITLE etc
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

        if(empty(self::$txts[$this->lang])) $this->cache();

        if(!isset(self::$txts[$this->lang][$key])) {
            trigger_error(sprintf('Unknown text "%s" in languagefile "%s", using standard language', $key, $this->getFile()), E_USER_NOTICE);
            //TODO: something...
            return;
        }

        if(func_num_args() <= 1) return trim(self::$txts[$this->lang][$key]);

        $i = 1;
        $args = array();

        if(is_array(func_get_arg($i))) {
            $args = func_get_arg($i++);
        }

        for(; $i < func_num_args(); $i++) {
            $args[] = func_get_arg($i);
        }

        return trim(vsprintf(self::$txts[$this->lang][$key], $args));
    }


    /**
     * This method gets all the text and caches in the object-array
     *
     * @param   String      $lang   The language to cache, if none given caches the default
     */
    protected function cache($lang = null) {

        $l = ($lang ? $lang : $this->lang);

        if(!file_exists($this->getFile($l))) {
            trigger_error('The text-file for '.$this->inst.' on the language "'.$this->lang.'" does not exist', E_USER_WARNING);
            return;
            //TODO: change to default lang?
        }

        $xml_parser = xml_parser_create();
        xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 1);

        xml_set_object($xml_parser, $this);
        xml_set_element_handler($xml_parser, 'xml_parse_open', 'xml_parse_close');
        xml_set_character_data_handler($xml_parser, 'xml_parse_data');

        //TODO: temp?
        $this->xml_active = false;
        $this->xml_lang = $l;

        if (!($fp = fopen($this->getFile($l), 'rb'))) {
            die("could not open XML input");
        }
        while ($data = fread($fp, 8192)) {
            if (!xml_parse($xml_parser, $data, feof($fp))) {
                //TODO: trigger an error in stead, and go to default language?
                die(sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($xml_parser)), xml_get_current_line_number($xml_parser)));
            }
        }

        xml_parser_free($xml_parser);
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
        if(empty(self::$txts[$this->xml_lang][$this->xml_active_key])) self::$txts[$this->xml_lang][$this->xml_active_key] = $data;
        else self::$txts[$this->xml_lang][$this->xml_active_key] .= $data;

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
    public function lang($lang = null) {

        if($lang) {

            if(!is_file($this->getFile())) {
                trigger_error("Unknown text file where inst='{$this->inst}' and lang='$lang'", E_USER_WARNING); 
                return false;
            }

            $this->lang = $lang;
            return true;
        }

        return $this->lang;
    }

    /**
     * Sets the institution to output the text from.
     * Returns the current institution.
     *
     * @param String    $inst   The given institution.
     */
    public function inst($inst = null) {

        if($inst) $this->inst = $inst;
        return $this->inst;

    }
}
