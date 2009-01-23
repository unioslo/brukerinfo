<?php
//   Html_Element
// +------------------------------------------------------------------------+
// | PHP version 5                                                          |
// +------------------------------------------------------------------------+
// | Authors: Joakim S. Hovlandsvåg <joakim@usit.uio.no>                    |
// +------------------------------------------------------------------------+
// |                                                                        |
// | The base class for different html-elements.                            |
// | TODO: maybe make subclasses later.                                     |
// |                                                                        |
// +------------------------------------------------------------------------+

abstract class Html_Element extends HTML_Common {

    /** Number of spaces one tab makes */
    protected $tabWidth = 4;

    /** 
     * Where all the main data-content of the element is going
     */
    protected $content = array();



    /**
     * Constructor
     *
     * @param String    $type   What type of object this is supposed to be
     */
    public function __construct($attr=null, $tab=0) {

        parent::__construct($attr, $tab);

    }

    /**
     * The function for adding data to the element.
     * May be overridden by subclasses.
     */
    public function addData($data) {

        if($data) $this->content[] = $data;

    }
    /**
     * Retrieving the data (raw).
     */
    public function getData() {
        return $this->content;
    }

    /**
     * For echoing out the element
     */
    public function display() {
        echo $this;
    }

    /**
     * Returns number of spaces according to the tabOffset
     *
     * @param int   $ekstra     If you want to tab out $extra tabs
     */
    protected function tabOut($extra=0) {

        return str_repeat(' ', ($this->tabWidth * $this->getTabOffset()) + ($extra*$this->tabWidth));

    }

    /**
     * Every element must be echoed out!
     */
    abstract public function __toString();

}
