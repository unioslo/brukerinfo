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

abstract class Html_Element extends HTML_Common {

    /** Number of spaces one tab makes */
    protected $tabWidth = 4;

    /** 
     * Where all the main data-content of the element is going
     */
    protected $content = array();



    /**
     * Constructor
     *
     * @param String    $type   What type of object this is supposed to be
     */
    public function __construct($attr=null, $tab=0) {

        parent::__construct($attr, $tab);

    }

    /**
     * The function for adding data to the element.
     * May be overridden by subclasses.
     */
    public function addData($data) {

        if($data) $this->content[] = $data;

    }
    /**
     * Retrieving the data (raw).
     */
    public function getData() {
        return $this->content;
    }

    /**
     * For echoing out the element
     */
    public function display() {
        echo $this;
    }

    /**
     * Returns number of spaces according to the tabOffset
     *
     * @param int   $ekstra     If you want to tab out $extra tabs
     */
    protected function tabOut($extra=0) {

        return str_repeat(' ', ($this->tabWidth * $this->getTabOffset()) + ($extra*$this->tabWidth));

    }

    /**
     * Every element must be echoed out!
     */
    abstract public function __toString();

}
