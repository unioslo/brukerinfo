<?php
// Copyright 2012 University of Oslo, Norway
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
 * The hine specific design.
 */
class View_hine extends ViewTemplate
{
    /** The user-object (if logged on) */
    protected $user;

    /** If the user is logged on or not */
    protected $logged_in = false;

    public function __construct()
    {
        parent::__construct();
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()));

        // no caching
        header('Expires: ' . gmdate('D, d M Y H:i:s', time()-3600*7) . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', FALSE);
        header('Pragma: no-cache');

        // HTTP Strict Transport Security (HSTS)
        // Tells browsers to only use https at this domain, and block the user 
        // from access if a less trusted certificate is found - e.g.  
        // self-signed. Prevents some ssl-stripping man-in-the-middle attacks.
        //
        // http://tools.ietf.org/html/draft-ietf-websec-strict-transport-sec-03
        //
        // The max-age are defined in seconds, set to about one year.
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

        // Security tag, preventing site from being included in frames in 
        // browsers that supports this. Prevents some clickjacking attacks.
        // http://hackademix.net/2009/01/29/x-frame-options-in-firefox/
        header('X-FRAME-OPTIONS: DENY');

        $this->user = Init::get('User');
        $this->logged_in = $this->user->isLoggedOn();

        $language = Init::get('Text')->getLanguage();
        if ($language) {
            header('Content-Language: ' . strtolower($language));
        }

        // main title
        $this->addTitle(txt('PAGETITLE'));

        // add some template tags
        $this->addTag('##BASEURL##', BASE_URL);
        $this->addTag('##APPLIKASJONSNAVN##', txt('HEADER'));
        $this->addTag('##APPLIKASJONSLINK##', BASE_URL);
        $this->addTag('##TAGLINE##', txt('TAGLINE'));
        $this->addTag('##HEADERS##',     array('callback' => array($this, 'htmlHead')));
        $this->addTag('##MAINMENU##',    array('callback' => array($this, 'htmlMainmenu')));
        $this->addTag('##SUBMENU##',     array('callback' => array($this, 'htmlSubmenu')));
        $this->addTag('##LANGUAGES##',   array('callback' => array($this, 'htmlLanguages')));
        $this->addTag('##STATUSLOGON##', array('callback' => array($this, 'htmlStatusbox')));
        $this->addTag('##MESSAGES##',    array('callback' => array($this, 'htmlMessages')));
        $this->addTag('##MOTD##',        array('callback' => array($this, 'htmlMotd')));
    }

    /**
     * Returns the main menu, or a sub menu if a sub menu id is given.
     *
     * @param  String   $sub    If a sub menu should be returned.
     * @return Array            The menu as a one dimensional array, the keys 
     *                          are identifiers, and the values are links.
     */
    protected function getMenu($sub = null)
    {
        if (!$this->logged_in) {
            // unauthenticated users should only see the logon page
            return;
        }

        $bofh = Init::get('Bofh');
        $is_employee = $bofh->isEmployee();
        $is_personal = $bofh->isPersonal();

        //start
        $menu['home']['link']       = '';

        //person
        if ($is_personal) {
            $menu['person']['link']     = 'person/';
            $menu['person']['sub']      = array(
                '',
                'primary.php', 
            );
            if ($is_employee) {
                $menu['person']['sub'][] = 'name.php';
            }
        }

        //accounts
        $menu['account']['link']   = 'account/';
        $menu['account']['sub']    = array(
            '',
            'password.php',
        );

        if ($is_personal) {
            $menu['account']['sub'][] = 'primary.php';
        };

        //printer
        if ($is_personal) {
            $menu['printing']['link']    = 'printing/';
            $menu['printing']['sub']     = array(
                '',
                'history.php'
            );
        };

        //email
        $menu['email']['link']      = 'email/';
        $menu['email']['sub'][]     = '';
        $menu['email']['sub'][]     = 'spam.php';
        $menu['email']['sub'][]     = 'tripnote.php';
        $menu['email']['sub'][]     = 'forward.php';

        //groups
        $menu['groups']['link']     = 'groups/';
        $menu['groups']['sub']      = array(
            ''
        );
        if ($is_employee) $menu['groups']['sub'][] = 'new.php';

        // reservations
        if ($is_personal) {
            $menu['reservations']['link'] = 'reservations/';
            $menu['reservations']['sub'] = array(
            );
        }

        // TODO: not yet
        //if ($is_personal && $is_employee) {
        //    $menu['guests']['link'] = 'guests/';
        //    $menu['guests']['sub'] = array(
        //    );
        //}

        //returning main menu
        if($sub === null) {
            $main = array();
            foreach ($menu as $k => $v) {
                $main[$k] = $v['link'];
            }
            return $main;
        } elseif (isset($menu[$sub])) {
            return $menu[$sub]['sub'];
        }
    }

    /**
     * Returns a html formatted string of either the mainmenu or a submenu.
     */
    public function htmlMainmenu()
    {
        if (!$this->logged_in) {
            return '';
        }
        $base_path = parse_url(self::$base_url, PHP_URL_PATH);
        $current   = substr($_SERVER['PHP_SELF'], strlen($base_path));
        $current = preg_replace('/index\.php$/', '', $current);
        $current = substr($current, 0, strpos($current, '/') + 1);

        $menu = array(); 
        foreach($this->getMenu() as $id => $link) {
            $name = txt('MENU_' . strtoupper($id));
            $active = ($current == $link ? ' class="active"' : '');
            $menu[] = "<a href=\"$link\"$active>$name</a>";
        }
        return self::createElement('ul', $menu, 'id="app-mainmenu"');
    }

    /**
     * Returns the correct sub menu as a html formatted string.
     */
    public function htmlSubmenu()
    {
        if (!$this->logged_in) {
            return '';
        }
        $base_path = parse_url(self::$base_url, PHP_URL_PATH);
        $current   = substr($_SERVER['PHP_SELF'], strlen($base_path));
        $current   = preg_replace('/index\.php$/', '', $current);
        $maindir   = substr($current, 0, strpos($current, '/'));

        $rawmenu = $this->getMenu($maindir);
        if (!$rawmenu) {
            return '';
        }
        $menu = array(); 
        foreach($this->getMenu($maindir) as $link) {
            $name = txt(strtoupper('MENU_' . $maindir . '_' . basename($link, '.php')));
            $active = ($current == ("$maindir/$link") ? ' class="active"' : '');
            $menu[] = "<a href=\"$maindir/$link\"$active>$name</a>";
        }
        return self::createElement('ul', $menu, 'id="app-submenu"');
    }

    /**
     * Returns a html formatted string with the statusbox for logged on users.
     */
    public function htmlStatusbox()
    {
        if (!$this->logged_in) {
            return '';
        }
        return '<span id="head-login"><span id="head-login-user-fullname">'
            . $this->user->getUsername()
            . '</span><span id="head-login-logout"><a href="logon.php">'
            . txt('LOGOUT') . '</a></span></div>';
    }

    /**
     * Returns a html formatted string with a list of all available languages.
     */
    public function htmlLanguages()
    {
        $text = Init::get('Text');
        $query = $_GET;
        $languages = array();
        foreach ($text->getAvailableLanguages() as $l => $desc) {
            if ($l == $text->getLanguage()) {
                continue;
            }
            $query['chooseLang'] = $l;
            $languages[] = "<a href=\"{$_SERVER['PHP_SELF']}?" . http_build_query($query) 
                . "\">$desc</a>";
        }
        return self::createElement('ul', $languages, 'id="languages"');
    }

    // A mapping of the message type level to an html class
    protected $message_to_class = array(
        self::MSG_ERROR     => 'note error',
        self::MSG_WARNING   => 'note warning',
        self::MSG_NOTICE    => 'note',
    );

    /**
     * Returns messages in a html formatted string. Note that the messages get
     * flushed, so call it only once per page.
     */
    public function htmlMessages()
    {
        $messages = $this->getMessages(true);
        $ret = '';
        foreach ($messages as $message) {
            $class = $this->message_to_class[$message[1]];
            $ret .= "<div class=\"$class\">";
            if (!empty($message[2])) {
                $ret .= '<p class="admonition-title">' . $message[2] . '</p>';
            }
            $ret .= $message[0] . '</div>';
            $ret .= $msg . "\n";
        }
        return $ret;
    }

    /**
     * Returns extra <head> tags should be put on the page.
     */
    protected function htmlHead()
    {
        return implode("\n", $this->head_data);
    }

    /**
     * Get the message of the day.
     */
    protected static function getMotd()
    {
        throw new Exception('Not implemented');
    }

    /**
     * Returns the Message Of The Day in a html formatted string.
     */
    public function htmlMotd()
    {
        $motd = self::getMotd();
        if (!$motd) {
            return '';
        }
        return self::createElement('ul', $motd, 'id="motd"');
    }

    /**
     * Set the focus to an input field with the given id. Will be added to the 
     * extra headers, so it has to be called before start of output. Note that 
     * it makes use of the jQuery API, so that should be included in the 
     * template.
     *
     * @param String    $id     The identifier for the input field. The 
     *                          selector works as in css and jQuery, e.g.  
     *                          '#name' for getting input field with 
     *                          id="name", or '.name' for class="name".
     */
    public function setFocus($id)
    {
        if ($this->started) {
            throw new Exception('Output has already started');
        }
        $this->addHead("<script type=\"text/javascript\">\n"
            . "    $(document).ready(function(){ $('$id').focus(); });\n"
            . "  </script>");
    }
}
?>