<?php
//   Tr element
// +------------------------------------------------------------------------+
// | PHP version 5                                                          |
// +------------------------------------------------------------------------+
// | Authors: Joakim S. Hovlandsvåg <joakim@usit.uio.no>                    |
// +------------------------------------------------------------------------+
// |                                                                        |
// | The base class for a table.                                            |
// |                                                                        |
// +------------------------------------------------------------------------+

class Html_Tr extends Html_Element {

    /**
     * Constructor
     *
     * @param String    $data       Array with the td-elements
     */
    public function __construct($data, $attr=null, $tab=0) {

        parent::__construct($attr, $tab);

        if(!$data) return;

        $this->addData($data);

    }

    /**
     * What comes out when object is echoed at
     */
    public function __toString() {

        $attr = $this->getAttributes(true);

        $html = $this->tabOut() . "<tr$attr>\n";
        foreach($this->content as $td) {
            $html .= $td;
        }
        $html .= $this->tabOut() . "</tr>\n";
        return $html;

    }


    /**
     * Adding more td to the tr.
     * Each adding (or each element in arrays) becomes a td in the table.
     * 
     * @param   mixed   Each argument makes a new td, or if first is a object, goes through that
     */
    public function addData() {

        if(is_array(func_get_arg(0))) {
            $f = func_get_arg(0);

            foreach($f as $g) {
                if(is_object($g) && (is_a($g, 'Html_Td') || is_a($g, 'Html_Th'))) {
                    $this->content[] = $g;
                } else {
                    $this->content[] = View::createElement('td', $g);
                }
            }

        } else {

            foreach(func_get_args() as $f) {
                if(is_object($f) && (is_a($f, 'Html_Td') || is_a($f, 'Html_Th'))) {
                    $this->content[] = $f;
                } else {
                    $this->content[] = View::createElement('td', $f);
                }
            }
        }

    }

    

}
