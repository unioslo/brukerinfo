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


/**
 * The base class for a <div> tag.
 */
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
