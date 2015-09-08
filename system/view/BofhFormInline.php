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
 * A subclass of BofhForm to make an easier design for inline use in tables.
 */
class BofhFormInline extends BofhForm
{
    /** The HTML_QuickForm_Renderer_Default for modifying the html output */
    protected $formRenderer;

    // {{{ construct
    /** 
     * Constructor
     *
     * @param String    $name       The id-name of the form
     * @param String    $method     Type of method to send data, POST (standard) or GET
     * @param String    $action     The url to where to send the data (default is PHP_SELF)
     * @param String    $target     If target-attribute should be used (like target="_blank")
     * @param mixed     $attr       Attributes to the <form>-tag
     * @param boolean   $track      If a hidden element should be used to check if the form has been sent. Here this defaults to true.
     */
    public function __construct($name = null, $method = null, $action = null, $target = null, $attr = null, $track = true) {
        parent::__construct($name, $method, $action, $target, $attr, $track);
        $this->setAttribute('class', 'app-form');
    }

    //}}}


    /// Template texts
    

    /**
     * The template for each element
     */
    protected function getElementTemplate() {

        return <<<ELEMENT

        <span>
            <label class="header">{label}<!-- BEGIN required --><span class="required">*</span><!-- END required --></label>
            <span class="element">
                {element}
            </span>
            <!-- BEGIN error --><span class="error">{error}</span><!-- END error -->
        </span>

ELEMENT;

    }
    /**
     * The template for the header elements.
     */
    protected function getHeaderTemplate() {

        //TODO: other, more minimal tag... but what?
        return "\n    <h2>{header}</h2>\n";

    }

    /**
     * The template for the required notes.
     */
    protected function getRequiredNoteTemplate() {

        //return "\n    <p class=\"requirednote\"><span class=\"required\">*</span> Denotes required field.</p>\n";
        return '';

    }

    /// PREMADE FORMS
    /// Different standard-forms to be used around the pages
    



}
