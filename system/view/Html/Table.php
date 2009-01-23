<?php
//   Table element
// +------------------------------------------------------------------------+
// | PHP version 5                                                          |
// +------------------------------------------------------------------------+
// | Authors: Joakim S. Hovlandsvåg <joakim@usit.uio.no>                    |
// +------------------------------------------------------------------------+
// |                                                                        |
// | The base class for a table.                                            |
// |                                                                        |
// +------------------------------------------------------------------------+

class Html_Table extends Html_Element {

    /** The place for the <thead>s content */
    protected $thead = array();

    /** The content */
    protected $content = array();



    /**
     * Constructor
     *
     * @param String    $data       Array with the list-elements in this dl
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

        $html = $this->tabOut() . "<table$attr>\n";

        if($this->thead) {
            $html .= $this->tabOut(1) . "<thead>\n<tr>\n";
            foreach($this->thead as $th) {
                $html .= $this->tabOut(2) . "<th>$th</th>\n";
            }
            $html .= $this->tabOut(1) . "</tr>\n</thead>\n";
            $html .= $this->tabOut(1) . "<tbody>\n";
        }

        $i = 1;
        foreach($this->content as $tr) {
            $par = $i++ % 2 ? 'odd' : 'even';
            $tr->setAttribute('class', $par);

            $html .= $this->tabOut(1) . $tr;

        }

        if($this->thead) $html .= $this->tabOut(1) . "</tbody>\n";
        $html .= $this->tabOut() . "</table>\n";

        return $html;

    }


    /**
     * For setting the <thead> with <th> values.
     * 
     * @param mixed     $data   If array, overwrites, if string it adds each arg as a new th in the thead
     */
    public function setHead($data) {

        if(is_array($data)) {
            $this->thead = $data;
        } else {
            $this->thead = func_get_args();
        }

    }

    /**
     * Adding more data to the table.
     * Each adding (or each element in arrays) becomes a tr in the table.
     * To send attributes to the tr, create it yourself
     * 
     * @param   mixed           Each argument makes a tr, and each element in arrays makes a tr
     */
    public function addData() {

        $data = func_get_args();
        foreach($data as $d) {
            if(is_object($d) && is_a($d, 'Html_Tr')) {
                $this->content[] = $d;
            } elseif(is_array($d)) {
                foreach($d as $e) {
                    if(is_object($e) && is_a($e, 'Html_Tr')) {
                        $this->content[] = $e;
                    } else {
                        $this->content[] = View::createElement('tr', $e);
                    }
                }
            } else {
                $this->content[] = View::createElement('tr', $d); 
            }
        }

    }

    

}
