<?php
//   Th element
// +------------------------------------------------------------------------+
// | PHP version 5                                                          |
// +------------------------------------------------------------------------+
// | Authors: Joakim S. Hovlandsvåg <joakim@usit.uio.no>                    |
// +------------------------------------------------------------------------+
// |                                                                        |
// | The base class for a td element in a table.                            |
// |                                                                        |
// +------------------------------------------------------------------------+

class Html_Th extends Html_Element {

    /**
     * Constructor
     *
     * @param String    $data       Array with the td-elements
     */
    public function __construct($data, $attr=null, $tab=0) {

        parent::__construct($attr, $tab);

        $this->content = $data;

    }

    /**
     * What comes out when object is echoed at
     */
    public function __toString() {

        $attr = $this->getAttributes(true);
        return $this->tabOut() . "<th$attr>$this->content</th>\n";

    }


    /*
     * Adding more td to the tr.
     * Each adding (or each element in arrays) becomes a td in the table.
     * 
     * @param   mixed   Each argument makes a new td
     */
    public function addData() {

        $this->content[] = func_get_args();

    }

    

}
