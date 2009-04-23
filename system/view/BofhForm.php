<?php
//   BofhForm
// +------------------------------------------------------------------------+
// | PHP version 5                                                          |
// +------------------------------------------------------------------------+
// | Authors: Joakim S. Hovlandsvåg <joakim@usit.uio.no>                    |
// +------------------------------------------------------------------------+
// |                                                                        |
// | Just a subclass of HTML_QuickForm to make an easier design,            |
// |                                                                        |
// +------------------------------------------------------------------------+

class BofhForm extends HTML_QuickForm {

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

        $this->formRenderer = new HTML_QuickForm_Renderer_Default();
        $this->formRenderer->setFormTemplate($this->getFormTemplate());
        $this->formRenderer->setElementTemplate($this->getElementTemplate());
        $this->formRenderer->setHeaderTemplate($this->getHeaderTemplate());
        $this->formRenderer->setRequiredNoteTemplate($this->getRequiredNoteTemplate());

        //todo: add some id-check on the form, to make sure only one form has been sent?
        
        if(!$this->getAttribute('autocomplete')) {
            $this->setAttribute('autocomplete', 'off');
        }

    
    }

    //}}}

    /**
     * Returns the html-string to output the form (with the correct rendering)
     */
    public function __toString() {

        $this->accept($this->formRenderer);
        //return $this->toHtml();
        return $this->formRenderer->toHtml();

    }

    /**
     * The php4-ways of outputting the html. 
     * Are just using the __toString().
     */
    public function display() { echo $this; }
    public function toHtml() { return $this; }


    /**
     * Adding a new element to the form.
     * This calls the parent-method, and adds some 
     * functionality, like trimming all input.
     */
    public function addElement($type, $name, $message=null, $ekstra=null, $ekstra2=null) {

        $ele = parent::addElement($type, $name, $message, $ekstra, $ekstra2);

        //trim all elements
        $this->applyFilter($name, 'trim');

        // to make css work with ie, submits need class="submit"
        if($type=='submit' && !$ele->getAttribute('class')) {
            $ele->setAttribute('class', 'submit');
        }

        //autocompletion
        if($type=='password') $ele->setAttribute('autocomplete', 'off');
        if($type=='text' && !$ele->getAttribute('autocomplete')) {
            $ele->setAttribute('autocomplete', 'off');
        }

        //Since data from bofhd has been html-escaped, and HTML_QuickForm does it too,
        //data has to be unescaped here to prevent double-escaping...
        //
        //This might be a problem for data that is not from bofhd, but that doesn't happen here (yet)
        //Todo: add this to textareas as well
        if($type=='text' && $ele->getAttribute('value')) {
            $ele->setAttribute('value', htmlspecialchars_decode($ele->getAttribute('value')));
        }

        return $ele;

    }

    /**
     * Creating a new element.
     * This calls the parent-method, and adds some 
     * functionality, like trimming all input.
     */
    public function createElement($type, $name, $message, $ekstra=null, $ekstra2=null) {

        $ele = parent::createElement($type, $name, $message, $ekstra, $ekstra2);

        //trim all elements
        $this->applyFilter($name, 'trim');

        // to make css work with ie, submits need class="submit"
        if($type=='submit' && !$ele->getAttribute('class')) {
            $ele->setAttribute('class', 'submit');
        }

        //autocompletion
        if($type=='password') $ele->setAttribute('autocomplete', 'off');
        if($type=='text' && !$ele->getAttribute('autocomplete')) {
            $ele->setAttribute('autocomplete', 'off');
        }

        //Since data from bofhd has been html-escaped, and HTML_QuickForm does it too,
        //data has to be unescaped here to prevent double-escaping...
        //
        //This might be a problem for data that is not from bofhd, but that doesn't happen here (yet)
        //Todo: add this to textareas as well
        if($type=='text' && $ele->getAttribute('value')) {
            $ele->setAttribute('value', htmlspecialchars_decode($ele->getAttribute('value')));
        }

        return $ele;

    }

    /// Template texts
    
    /**
     * The template for the form
     */
    protected function getFormTemplate() {

        return "\n<form{attributes}>\n    {content}\n</form>\n";

    }

    /**
     * The template for each element
     */
    protected function getElementTemplate() {

        return <<<ELEMENT

        <div>
            <label class="header">{label}</label>
            <div class="element<!-- BEGIN error -->-error<!-- END error -->">
                {element}
            </div>
            <!-- BEGIN error --><span class="error">{error}</span><!-- END error -->
        </div>

ELEMENT;

        //old:
        /*return <<<ELEMENT

        <p>
            <label class="header">{label}<!-- BEGIN required --><span class="required">*</span><!-- END required --></label>
            <div class="element">
                {element}
            </div>
            <!-- BEGIN error --><span class="error">{error}</span><!-- END error -->
        </p>

ELEMENT;*/

    }
    /**
     * The template for the header elements.
     */
    protected function getHeaderTemplate() {

        return "\n    <h2>{header}</h2>\n";

    }

    /**
     * The template for the required notes.
     */
    protected function getRequiredNoteTemplate() {

        return null;
        //return "\n    <p class=\"requirednote\"><span class=\"required\">*</span> Denotes required field.</p>\n";

    }

    /// PREMADE FORMS
    /// Different standard-forms to be used around the pages
    



}
