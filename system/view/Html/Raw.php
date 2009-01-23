<?php
//   raw html
// +------------------------------------------------------------------------+
// | PHP version 5                                                          |
// +------------------------------------------------------------------------+
// | Authors: Joakim S. Hovlandsvåg <joakim@usit.uio.no>                    |
// +------------------------------------------------------------------------+
// |                                                                        |
// | A class for outputting html directly (depreciated, but quick).         |
// |                                                                        |
// +------------------------------------------------------------------------+

class Html_Raw extends Html_Element {

    /**
     * Constructor
     *
     * @param String    $content   The raw html, as a string
     */
    public function __construct($content) {

        $this->content = $content;

    }

    public function __toString() {

        if(is_array($this->content)) {

            $ret = '';
            foreach($this->content as $c) $ret .= "$c\n";

            return $ret;

        }

        return $this->content;

    }

}
