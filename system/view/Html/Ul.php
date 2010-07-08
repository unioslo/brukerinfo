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
