<?php
//   div element
// +------------------------------------------------------------------------+
// | PHP version 5                                                          |
// +------------------------------------------------------------------------+
// | Authors: Joakim S. Hovlandsvåg <joakim@usit.uio.no>                    |
// +------------------------------------------------------------------------+
// |                                                                        |
// | The base class for a h1 tag.                                           |
// |                                                                        |
// +------------------------------------------------------------------------+

class Html_Div extends Html_Element {

    /**
     * Constructor
     *
     * @param String    $type   What type of object this is supposed to be
     */
    public function __construct($content, $attr=null, $tab=0) {

        parent::__construct($attr, $tab);

        if(is_array($content)) {
            $this->content = $content;
        } else if ($content) {
            $this->content[] = $content;
        }


    }

    public function __toString() {

        $attr = $this->getAttributes(true);

        //todo: make container-object for gathering several elements in one, like $this->data is now:
        $ret = "<div$attr>";
        foreach($this->content as $c) $ret .= "$c\n";

        return $ret . "</div>\n";

    }

}
