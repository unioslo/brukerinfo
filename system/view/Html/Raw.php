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


class Html_Raw extends Html_Element {

    /**
     * Constructor
     *
     * @param String    $content   The raw html, as a string
     */
    public function __construct($content) {

        $this->content = $content;

    }

    public function __toString() {

        if(is_array($this->content)) {

            $ret = '';
            foreach($this->content as $c) $ret .= "$c\n";

            return $ret;

        }

        return $this->content;

    }

}
