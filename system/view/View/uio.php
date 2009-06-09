<?php
//   VIEW.UIO
// +------------------------------------------------------------------------+
// | PHP version 5                                                          |
// +------------------------------------------------------------------------+
// | Authors: Joakim S. Hovlandsvåg <joakim.hovlandsvag@gmail.com>          |
// +------------------------------------------------------------------------+
// |                                                                        |
// | The UiO-specific design.                                               |
// |                                                                        |
// +------------------------------------------------------------------------+

class View_uio extends View {

    /** The name of the editor of the site */
    private $editor_name = 'Houston';

    /** The email to the editor of the site */
    private $editor_email = 'houston@usit.uio.no';

    /** The keywords to use as meta-data (comma separated) */
    private $meta_keywords = 'Brukerinfo, USIT, Cerebrum, brukeradministrering, egenadministrering';

    /** The template stored as a string in here */
    private $raw_template;

    /** 
     * The directory where the template files are.
     * The original template was found at 
     * http://www.usit.uio.no/it/ureg2000/.template_en.html
     *
     * The link is relative according to LINK_SYSTEM and %s
     * is replaced with the active language.
     */
    private $template_file = '/view/templates/template.uio.%s.txt';

    /**
     * CSS-files to include
     * Double arrays, with the keys 'href', [media] and ['modern'],
     * where modern means using the @import-command (makes old browser
     * not load it)
     */
    protected $css = array(
        array('href'=>'css/screen.css', 'media'=>'screen', 'modern'=>true),
        array('href'=>'css/print.css', 'media'=>'print', 'modern'=>true)
        );


    protected function __construct() {

        parent::__construct();

    }

    public function __destruct() {
        parent::__destruct();
    }


    /**
     * Returns the main menu, or a submenu by a given id
     *
     * @param String    $sub    What submenu shall be return, returns main menu if null
     * @return Array    The menu as a one dimensional array
     */
    protected function getMenu($sub = null) {
        //TODO: change this to work with different languages


        if(!$this->logged_in) return;

        //start
        $menu['home']['link']       = '';

        //person
        $menu['person']['link']     = 'person/';
        $menu['person']['sub']      = array(
        );

        //accounts
        $menu['account']['link']   = 'account/';
        $menu['account']['sub']    = array(
            '',
            'password.php',
            'primary.php'
        );

        //printer
        $menu['printing']['link']    = 'printing/';
        $menu['printing']['sub']     = array(
            '',
            'history.php'
        );

        //email
        $menu['email']['link']      = 'email/';
        $menu['email']['sub']       = array(
            '',
            'spam.php',
            'filters.php',
            'tripnote.php',
            'forward.php'
        );

        //groups
        $menu['groups']['link']     = 'groups/';
        $menu['groups']['sub']      = array(
        );
       

        //returning main menu
        if(!$sub) {
            $main = array();
            foreach($menu as $k=>$v) $main[$k] = $v['link'];

            return $main;

        } elseif(isset($menu[$sub])) {

            return $menu[$sub]['sub'];

        }

    }


    /**
     * The method which gets the template and modifies different values
     *
     * @param   boolean     $beginning  True returns the beginning html, false returns the ending
     * @return  String                  The template-html
     */
    protected function getTemplate($beginning) {

        if(empty($this->raw_template)) {

            $templlink = sprintf($this->template_file, $this->language);

            if(!is_readable(LINK_SYSTEM . $templlink)) {

                $templlink = sprintf($this->template_file, DEFAULT_LANG);

                if(!is_readable(LINK_SYSTEM . $templlink)) {
                    trigger_error('No template file for the HTML found - pages will be empty', 
                        E_USER_WARNING);
                }
            }
            $this->raw_template = file_get_contents(LINK_SYSTEM . $templlink);
        }

        $ret = null;

        if($beginning) {
            // getting the first part of the template
            $ret = substr($this->raw_template, 0, strpos($this->raw_template, '##BODY##'));
        } else {
            // getting the rest of the template
            $ret = substr($this->raw_template, strpos($this->raw_template, '##BODY##') + 8);
        }

        $ret = str_replace('##TITLE##', implode(' - ', array_reverse($this->titles)), $ret);

        $extrahead = '<base href="https://'.$_SERVER['SERVER_NAME'] . HTML_PRE."/\" />\n";
        $extrahead .= $this->htmlCss() . "\n";
        $extrahead .= '    <!--[if lt IE 8]><link rel="stylesheet" style="text/css" href="css/screen.ie.css"><![endif]-->'."\n";
        $extrahead .= '    <!-- Framebusting
        Avoiding that the page is in a frame, preventing clickjacking. 
        http://en.wikipedia.org/wiki/Framekiller -->
    <script type="text/javascript">
        if(top.location != location) { top.location.href = document.location.href; }
    </script>'."\n";
        $extrahead .= $this->htmlFocus() . "\n";
        $ret = str_replace('##HEADERS##', $extrahead, $ret);

        $ret = str_replace('##EDITOR.NAME##', $this->editor_name, $ret);
        $ret = str_replace('##EDITOR@EMAIL##', $this->editor_email, $ret);
        $ret = str_replace('##KEYWORDS##', $this->meta_keywords, $ret);

        //more edits above this line

        return $ret;

    }


    /**
     * Starts echoing out the html
     */
    public function start() {

        if($this->started) return;

        // http-headers (not text)
        $this->sendHeaders();

        echo $this->getTemplate(true);

        echo '<div id="headtitle"><a href="">'.txt('header')."</a></div>\n";

        echo '<ul id="languages">';
        foreach(Text::getLangs() as $l) echo "<li><a href=\"{$_SERVER['PHP_SELF']}?chooseLang=$l\">$l</a></li>\n";
        echo "</ul>\n";

        $motd = self::getMotd();

        if(!empty($motd)) {
            echo '<ul id="motd">';

            foreach($motd as $m) {
                if(!$m) continue;
                echo "<li>$m</li>\n";
            }

            echo "</ul>\n";
        }

        if($this->logged_in) echo '<div id="statusbox"><span>'.$this->user->getUsername().'</span>' .
            '<span><a href="logon.php">'.txt('LOGOUT').'</a></span></div>';


        $baselink = basename(dirname($_SERVER['PHP_SELF']));
        $realurl = substr($_SERVER['PHP_SELF'], strlen(HTML_PRE)+1);
        $realurl = str_replace('index.php', '', $realurl);

        //Main menu
        $firsturl = substr($realurl, 0, strpos($realurl, '/')+1);
        if($this->logged_in) {
            echo "<ul id=\"mainmenu\">\n";
            foreach($this->getMenu() as $k=>$v) {
                $subname = txt('MENU_' .strtoupper($k));
                $active = ($firsturl == $v ? ' class="active"' : '');
                echo "    <li><a href=\"$v\"$active>$subname</a></li>\n";
            }
            echo "</ul>\n";
        }

        echo "\n\n<div id=\"content\"><a name=\"snarvei\"></a>\n";

        if($this->logged_in) {

            //Sub menu
            $subentries = $this->getMenu($baselink);

            if($subentries) {

                $filename = basename($_SERVER['PHP_SELF']);
                if($filename == 'index.php') $filename = '';
                $name = basename($filename, '.php');


                $submenu = View::createElement('ul', null, 'id="submenu"');
                foreach($subentries as $e) {
                    $txtname = "MENU_{$baselink}_" . basename($e, '.php');
                    $submenu->addData(View::createElement('a', txt($txtname), "$baselink/$e", 
                        ($e == $filename ? 'class="active"' : '')));
                }

                echo $submenu;
            }
        }


        //messages
        if($this->delMessages) echo $this->htmlMessages(true);

        $this->started = true;

        //content
        foreach($this->content as $c) {
            if($this->help) { 
                echo self::addHelp($c);
            } else {
                echo $c;
            }
        }

    }

    /**
     * Puts out the rest of the html at the end
     * of the page
     */
    public function end() {

        if(!$this->started) return;
        if($this->ended) return;

        echo "\n\n</div>\n";
        echo '<div class="footer">'.txt('footer').' - <a href="'.txt('help_link').'">'.
            txt('help_title').'</a></div>';
        echo $this->getTemplate(false);

        $this->ended = true;

    }


}
