<?php
//   h2 element
// +------------------------------------------------------------------------+
// | PHP version 5                                                          |
// +------------------------------------------------------------------------+
// | Authors: Joakim S. Hovlandsvåg <joakim@usit.uio.no>                    |
// +------------------------------------------------------------------------+
// |                                                                        |
// | The base class for a h2 tag.                                           |
// |                                                                        |
// +------------------------------------------------------------------------+

class Html_h2 extends Html_Element {

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

        return "<h2$attr>$this->title</h2>\n";

    }

}
