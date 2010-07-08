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


class Html_Td extends Html_Element {

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
        return $this->tabOut() . "<td$attr>$this->content</td>\n";

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
