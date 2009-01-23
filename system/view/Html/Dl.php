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

class Html_dl extends Html_Element {

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
            trigger_error('Unknown data sent to dl', E_USER_WARNING);
        }

    }

    /**
     * Adding a line to the list
     *
     * @param String    $dt     The definition term
     * @param String    $dd     The definition data
     *
     * @return Array            The list-element, made to a String-array
     */
    public function addData($dt, $dd) {

        if(is_array($dd)) {
            $dd = View::createElement('ul', $dd);
        }
        $ele = array('dt'=>$dt, 'dd'=>$dd);
        $this->content[] = $ele;
        return $ele;

    }

    public function __toString() {

        $attr = $this->getAttributes(true);

        $html = "<dl$attr>\n";
        foreach($this->content as $l) {
            //to prevent empty boxes that could make unexpected floats in the design
            if(empty($l['dd'])) $l['dd'] = '&nbsp;';

            $html .= "<dt>{$l['dt']}</dt>\n<dd>{$l['dd']}</dd>\n";
        }

        $html .= "</dl>\n";
        return $html;

    }

}
