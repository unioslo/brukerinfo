<?php
//   link element
// +------------------------------------------------------------------------+
// | PHP version 5                                                          |
// +------------------------------------------------------------------------+
// | Authors: Joakim S. Hovlandsvåg <joakim@usit.uio.no>                    |
// +------------------------------------------------------------------------+
// |                                                                        |
// | The base class for a link tag.                                         |
// |                                                                        |
// +------------------------------------------------------------------------+

class Html_A extends Html_Element {

    /**
     * The link in the <a href="$link">$data</a>
     */
    protected $link;

    /**
     * Constructor
     *
     * @param String    $type   What type of object this is supposed to be
     */
    public function __construct($content, $link, $attr=null, $tab=0) {

        //TODO: smarter way of adding data here... needs to accept classes...
        if($content) $this->content = $content;
        if($link) $this->link = $link;
        parent::__construct($attr, $tab);

    }

    public function __toString() {

        $attr = $this->getAttributes(true);

        return "<a href=\"{$this->link}\"$attr>$this->content</a>\n";

    }

}
