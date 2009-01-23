<?php
//   dl element
// +------------------------------------------------------------------------+
// | PHP version 5                                                          |
// +------------------------------------------------------------------------+
// | Authors: Joakim S. Hovlandsvåg <joakim@usit.uio.no>                    |
// +------------------------------------------------------------------------+
// |                                                                        |
// | The base class for a h1 tag.                                           |
// |                                                                        |
// +------------------------------------------------------------------------+

class Html_Ul extends Html_Element {

    /**
     * Constructor
     *
     * @param String    $data    Array with the list-elements in this dl
     */
    public function __construct($data, $attr=null, $tab=0) {

        parent::__construct($attr, $tab);

        if(!$data) return;
        //TODO: maybe consider $data to be a string as well?
        if(is_array($data)) {
            $this->content = $data;
        } else {
            trigger_error('Unknown data sent to ul', E_USER_WARNING);
        }

    }

    public function __toString() {

        $attr = $this->getAttributes(true);

        $html = $this->tabOut() . "<ul$attr>\n";
        foreach($this->content as $l) {
            if($l == '') $l = '&nbsp;';
            $html .= $this->tabOut(1) . "<li>{$l}</li>\n";
        }

        $html .= "</ul>\n";
        return $html;

    }

}
