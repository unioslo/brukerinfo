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

    /** The token to be used in the forms **/
    private $secretToken;

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

        // add random token to forms, to prevent CSRF
        $this->updateToken();

        // remove autocompletion from our forms
        if(!$this->getAttribute('autocomplete')) {
            $this->setAttribute('autocomplete', 'off');
        }

        //trim all elements
        $this->applyFilter('__ALL__', 'trim');
    
    }

    //}}}

    /**
     * Produces a semirandom secret token, to be used in forms to prevent cross-site 
     * request forgery (CSRF).
     * http://www.owasp.org/index.php/Cross-Site_Request_Forgery_%28CSRF%29
     *
     * This could be replaced by Owasps ESAPI for php when that is done.
     * http://code.google.com/p/owasp-esapi-php/
     *
     * Please note that this method only creates a random token, it's not stored 
     * anywhere.
     *
     * @return string   A string containing a random GUID
     */
    protected function createToken() {

        // Function gotten from Owasps ESAPI, defaultRandomizer.php, which 
        // again was gotten from comments found on http://php.net/uniqid
        // This is an implementation of GUID, but we use it for tokens 
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 65535), mt_rand(0, 65535), // 32 bits for "time_low"
                mt_rand(0, 65535), // 16 bits for "time_mid"
                mt_rand(0, 4095),  // 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
                bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
                // 8 bits, the last two of which (positions 6 and 7) are 01, for "clk_seq_hi_res"
                // (hence, the 2nd hex digit after the 3rd hyphen can only be 1, 5, 9 or d)
                // 8 bits for "clk_seq_low"
                mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535) // 48 bits for "node"
                );  
    }

    /**
     * Updates the secret token if it doesn't exist, and adds it to the form.
     * Please note that the $User object needs to be created before a form is 
     * created, otherwise the form will not contain CSRF tokens.
     */
    protected function updateToken() {

        if (empty($_SESSION)) return;

        // remove old token if not logged on, so the user gets a new token e.g. 
        // after logon, to prevent people getting hackers sessions
        global $User;
        if (empty($User) || !is_a($User, User) || !$User->loggedIn()) {
            // TODO: this could create problems if a form is created before the 
            // $User-object is created
            $_SESSION['bofhform']['secretToken'] = null;
            unset($_SESSION['bofhform']['secretToken']);
            return;
        } 
        
        if (empty($_SESSION['bofhform']['secretToken'])) {
            $_SESSION['bofhform']['secretToken'] = $this->createToken();
        }
        $this->secretToken = $_SESSION['bofhform']['secretToken'];

        // add the secret token to the form
        $eletoken = $this->addElement('hidden', 'token', $this->secretToken);
    
    }

    /**
     * Checks if a given token is correct and returns true or false.
     *
     * @param   string  $given_token  The token which is supposed to be correct
     * @return  boolean true if given token is correct, otherwise false
     */
    protected function checkToken($given_token) {
        return ($given_token === $this->secretToken);
    }

    /**
     * Adds some validations to the form, e.g. checking the secret token, before 
     * passing it to HTML_QuickForms validate method.
     *
     * @return  boolean     true if no error found
     * @throws  HTML_QuickForm_Error
     */
    public function validate() {

        if (!$this->isSubmitted()) return false;

        // checks the csrf token before anything else
        if ($this->secretToken && !$this->checkToken($this->getSubmitValue('token'))) {
            trigger_error("Possible CSRF attack, secret form token doesn't match!", 
                                                               E_USER_WARNING);

            // TODO: should $User be called from this object?
            global $User;
            $User->logOut();
            View::forward(URL_LOGON);
            return false;
        }

        return parent::validate();

    }

    /**
     * Returns the html-string to output the form (with the correct rendering)
     *
     * @return string   A string with the html representation of the form
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

        //This might be a problem for data that is not from bofhd, but that doesn't happen here (yet)
        //TODO: add this to textareas as well (a bit more complicated, as textareas get their values
        //      throug $textarea->setValue().
        if ($type=='text' && $ele->getAttribute('value')) {
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
