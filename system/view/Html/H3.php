<?php
//   h3 element
// +------------------------------------------------------------------------+
// | PHP version 5                                                          |
// +------------------------------------------------------------------------+
// | Authors: Joakim S. Hovlandsvåg <joakim@usit.uio.no>                    |
// +------------------------------------------------------------------------+
// |                                                                        |
// | The base class for a h3 tag.                                           |
// |                                                                        |
// +------------------------------------------------------------------------+

class Html_h3 extends HTML_Element {

    /** The title in the h1-tag */
    private $title;

    /**
     * Constructor
     *
     * @param String    $type   What type of object this is supposed to be
     */
    public function __construct($title, $attr=null, $tab=0) {

        $this->title = $title;
        parent::__construct($attr, $tab);

    }

    public function __toString() {

        $attr = $this->getAttributes(true);

        return "<h3$attr>$this->title</h3>\n";

    }

}
