<?php
# Copyright 2009, 2010 University of Oslo, Norway
# 
# This file is part of Cerebrum.
# 
# Cerebrum is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# Cerebrum is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with Cerebrum. If not, see <http://www.gnu.org/licenses/>.


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
     * @param mixed     $data   May be an array or several arguments. Each element or argument
     *                          becomes one th in the thead.
     */
    public function setHead($data) {

        if(is_array($data)) {
            $this->thead = $data;
        } else {
            $this->thead = func_get_args();
        }

    }

    /**
     * Adding data to the table.
     *
     * Each parameter is interpreted as a Tr-element.
     * If a parameter is an array it is handled as one Tr, and Tr takes care
     * of making Td of the array elements.
     *
     * Object can override this behaviour. Td- and Th-elements is wrapped inside
     * Tr-elements.
     *
     * @param   mixed           Each argument makes a Tr, and each element in arrays makes a Tr.
     *                          For adding Td, you need to send 2d-arrays.
     */
    public function addData() {

        $data = func_get_args();
        foreach($data as $d) {
            if (is_object($d) && is_a($d, 'Html_Tr')) {
                $this->content[] = $d;
            } elseif (is_array($d)) {
                $this->content[] = View::createElement('tr', $d);
            }
        }

    }

}
