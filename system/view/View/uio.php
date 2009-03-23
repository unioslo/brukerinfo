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

    /** The template stored as a string in here */
    private $raw_template;

    /** The link to the template */
    //todo: this is not working...
    private $template_link = 'http://www.usit.uio.no/it/ureg2000/.template_%s.html';

    /** 
     * The backup template to use if the original is not found.
     * The link is relativ according to LINK_SYSTEM.
     */
    private $template_link_backup = '/view/templates/template.uio.en.txt';

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


        //TODO: change this, now the name has to be equal of the link
        //try to name basic keys ($menu['these']) to be the link, and 
        //add a $menu[link]['name'] in stead

        //start
        $menu['home']['link']       = '';

        //person
        $menu['person']['link']     = 'person/';
        $menu['person']['sub']      = array(
        );

        //accounts
        $menu['account']['link']   = 'account/';
        $menu['account']['sub']    = array(
            'your account' =>   '',
            'change password' =>   'password.php',
            'make account primary' =>    'primary.php'
        );

        //printer
        $menu['printing']['link']    = 'printing/';
        $menu['printing']['sub']     = array(
            'printing overview' =>       '',
            'print quota history' =>    'history.php'
        );

        //email
        $menu['email']['link']      = 'email/';
        $menu['email']['sub']       = array(
            'e-mail overview' =>   '',
            'spam settings' =>       'spam.php',
            'additional spam settings' =>    'filters.php',
            'Out of office messages' =>   'tripnote.php',
            'forwarding' =>    'forward.php'
        );

        //groups
        $menu['groups']['link']     = 'groups/';
        $menu['groups']['sub']      = array(
        );
       
        //help
        //$menu['help']['link']       = 'help/';

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

        if(!$this->raw_template) {

            $templlink = sprintf($this->template_link, $this->language);

            if(is_readable($templlink)) {
                //todo: this will never work as long fopen is blocked from http-content
                $this->raw_template = file_get_contents($templlink);
            } else {
                $this->raw_template = file_get_contents(LINK_SYSTEM . $this->template_link_backup);
            }

        }

        $ret = null;

        if($beginning) {

            $ret = substr($this->raw_template, 0, strpos($this->raw_template, '##BODY##'));

            $ret = str_replace('<HTML lang="en">', '<HTML lang="'.$this->getLang().'">', $ret);
            $ret = str_replace('##TITLE##', implode(' - ', array_reverse($this->titles)), $ret);
            $ret = str_replace('<A NAME="snarvei"></A>', '', $ret);
            //TODO: more edits here

        } else {

            $ret = substr($this->raw_template, strpos($this->raw_template, '##BODY##') + 8);

            //TODO: more edits here

        }

        //edits for both start and end:
        $ret = str_replace('##EDITOR.NAME##', $this->editor_name, $ret);
        $ret = str_replace('##EDITOR@EMAIL##', $this->editor_email, $ret);

        return $ret;

    }


    /**
     * Starts echoing out the html
     */
    public function start() {

        if($this->started) return;

        $this->sendHeaders();


        //adding ekstra header-data to the template

        $base = '<base href="' . (isset($_SERVER['HTTPS']) ? 'https://' : 'http://')  . $_SERVER['SERVER_NAME'] . HTML_PRE."/\">\n";

        $head = str_replace('</HEAD>', $base . $this->htmlCss() . "\n" . $this->htmlFocus() . "\n</HEAD>", $this->getTemplate(true));


        //adding ie-specific css
        //a bit dirty, this could be placed somewhere more logic (the best would be to remove it - blame IE)
        $head = str_replace('</HEAD>', '<!--[if IE]><link rel="stylesheet" style="text/css" href="css/screen.ie.css"><![endif]-->', $head);

        //Temporary hack before putting pages on uio.no:
        $head = str_replace('/visuell-profil', 'https://www.uio.no/visuell-profil', $head);

        //TODO: more edits here!

        echo $head;


        echo '<div id="headtitle"><a href="">'.txt('header')."</a></div>\n";

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
            '<span><a href="logon.php">Log out</a></span></div>';


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
            $submenu = $this->getMenu($baselink);
            //TODO: clean this up and make more elegant
            $basename = basename($_SERVER['PHP_SELF']);
            if($basename == 'index.php') $basename = '';
            $sublink = $baselink . $basename;
            if($submenu) {
                echo "<ul id=\"submenu\">\n";
                foreach($submenu as $k=>$v) {
                    $active = ($v == $basename ? ' class="active"' : '');
                    if($v == '' && basename($_SERVER['PHP_SELF']) == 'index.php') $active = ' class="active"';
                    $subname = ucfirst($k);
                    echo "    <li><a href=\"$baselink/$v\"$active>$subname</a></li>\n";
                }
                echo "</ul>\n";
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
