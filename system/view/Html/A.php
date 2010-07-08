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
 * The base class for a link tag.
 */
class Html_A extends Html_Element {

    /**
     * The link in the <a href="$link">$data</a>
     */
    protected $link;

    /**
     * Constructor
     *
     * @param String    $type   What type of object this is supposed to be
     */
    public function __construct($content, $link, $attr=null, $tab=0) {

        //TODO: smarter way of adding data here... needs to accept classes...
        if($content) $this->content = $content;
        if($link) $this->link = $link;
        parent::__construct($attr, $tab);

    }

    public function __toString() {

        $attr = $this->getAttributes(true);

        return "<a href=\"{$this->link}\"$attr>$this->content</a>\n";

    }

}
