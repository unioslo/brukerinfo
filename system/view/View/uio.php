<?php
// Copyright 2009, 2010, 2011 University of Oslo, Norway
// 
// This file is part of Cerebrum.
// 
// Cerebrum is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// 
// Cerebrum is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with Cerebrum. If not, see <http://www.gnu.org/licenses/>.


/**
 * The UiO specific design.
 */
class View_uio extends View {

    /* USER data */

    /** The user-object (if logged on) */
    protected $user;

    /** If the user is logged on or not */
    protected $logged_in = false;



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
     * The link is relative according to LINK_DATA and %s
     * is replaced with the active language.
     */
    private $template_file = '/templates/template.uio.%s.txt';

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


    public function __construct($language, $base_url, Text $text = null)
    {
        parent::__construct($language, $base_url, $text);
        $this->user = Init::get('User');
        if ($this->user->isLoggedOn()) $this->logged_in = true;

        //main title
        $this->addTitle(self::txt('PAGETITLE'));
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
    protected function getMenu($sub = null)
    {
        if(!$this->logged_in) return;

        $bofh = Init::get('Bofh');
        $is_employee = $bofh->isEmployee();

        //start
        $menu['home']['link']       = '';

        //person
        $menu['person']['link']     = 'person/';
        $menu['person']['sub']      = array(
            '',
            'reservations.php',
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
            'tripnote.php',
            'forward.php'
        );

        //groups
        $menu['groups']['link']     = 'groups/';
        $menu['groups']['sub']      = array(
            ''
        );
        if($is_employee) $menu['groups']['sub'][] = 'new.php';

        //returning main menu
        if($sub === null) {
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

            $templlink = sprintf($this->template_file, $this->getLanguage());

            if(!is_readable(LINK_DATA . $templlink)) {

                $templlink = sprintf($this->template_file, DEFAULT_LANG);

                if(!is_readable(LINK_DATA . $templlink)) {
                    trigger_error('No template file for the HTML found - pages will be empty', 
                        E_USER_WARNING);
                }
            }
            $this->raw_template = file_get_contents(LINK_DATA . $templlink);
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

        $extrahead = '<base href="' . self::$base_url . "\" />\n";
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
        parent::start();

        // http-headers (not text)
        $this->sendHeaders();

        echo $this->getTemplate(true);

        echo '<div id="headtitle"><a href="">'.txt('header')."</a></div>\n";

        echo '<ul id="languages">';
        foreach (Text::getAvailableLanguages() as $l => $desc) {
            if ($l == $this->getLanguage()) {
                continue;
            }
            echo "<li><a href=\"{$_SERVER['PHP_SELF']}?chooseLang=$l\">$desc</a></li>\n";
        }
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


        echo $this->htmlMessages(true);

        //content
        foreach($this->content as $c) echo $c;

    }

    /**
     * Puts out the rest of the html at the end
     * of the page
     */
    public function end() {

        if(!$this->started) return;
        if($this->ended) return;
        parent::end();

        echo "\n\n</div>\n";
        echo '<div class="footer">'.txt('footer').' - <a href="'.txt('help_link').'">'.
            txt('help_title').'</a></div>';
        echo $this->getTemplate(false);

        $this->ended = true;

    }


}
