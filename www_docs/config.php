<?php
//   Constants
// +------------------------------------------------------------------------+
// | PHP version 5                                                          |
// +------------------------------------------------------------------------+
// |                                                                        |
// |                                                                        |
// +------------------------------------------------------------------------+


/// SYSTEM

/** If debugging output should be shown */
define('DEBUG',                 false);

/** 
 * The acronym of the institution, in lowercase. 
 * This constant will be used in the name of files, so let it be short,
 * and use only regular characters [a-z].
 */
define('INST',                  'uio');

/** The link to where the system files are located */
define('LINK_SYSTEM',           '/www/var/virtual/no.uio.w3utv-ws01_443/htdocs/cerebrum/wofh/system');

/** The link to where the system data are located */
define('LINK_DATA',             '/www/var/virtual/no.uio.w3utv-ws01_443/htdocs/cerebrum/wofh/data');

/** 
 * The location to the lock-file.
 * The lock-file works in the way, that if it contains text, this will be 
 * shown instead of the pages. All users will also be logged out.
 */
define('LOCK_FILE',             LINK_DATA . '/lock');

/** 
 * The default language to start with.
 * Before changing the language, check that you have the correct language file.
 */
define('DEFAULT_LANG',          'en');


/// BOFH


/** The url to the bofh-server */
define('BOFH_URL',              'https://cere-utv01.uio.no:8957/');

/** If bofhd's motd should be received and shown */
define('BOFH_MOTD',             false);

/** 
 * Bofh is (for now) not working in utf-8, which makes it buggy
 * sending utf8-data to it. Here you say in what encoding data
 * should be sent to bofh in.
 *
 * The charset has to be valid for php function unicode_enocde(),
 * (see http://php.net/unicode_encode)
 */
define('BOFH_CHARSET',          'ISO-8859-1');


/// WEB


/** 
 * Minutes before an inactive user gets logged out.
 * Should be the same as in bofhd, as the shortest timeout wins.
 */
define('TIME_OUT_MIN',          10);

/** Number of attempts before a user gets blocked */
define('ATTEMPTS',              10);

/** Minutes before an attempt-block wears out */
define('ATTEMPT_TIME_OUT_MIN',              15);

/** 
 * The charset which the pages are using.
 * Be sure that files are in the same charset as set here.
 */
define('CHARSET',               'utf-8');

/** 
 * The prefix in the url (if the project is in a subdir of
 * htmlroot), like:
 *  http://example.com/HTML_PRE/index.php
 *
 * Must start with /, but not end with it.
 */
define('HTML_PRE',          '/cerebrum/wofh/www_docs'); 
# which becomes http://example.com/cerebrum/wofh/www_docs/index.php

/** Url to the logon page */
define('URL_LOGON',         'logon.php');

/** The default page to go after log on */
define('URL_LOGGED_IN',     '');


/// OTHER


/** 
 * General delay
 * Number of minutes before changes will be working (for informing the user) 
 * This constant is not doing anything but just informing the end user about the delay.
 */
define('ACTION_DELAY',              4*60); // 4 hours

/** 
 * Delay for email
 * Number of minutes before email changes will be working.
 * This constant is not doing anything but just informing the end user about the delay.
 */
define('ACTION_DELAY_EMAIL',        30);

/** 
 * Max rows in long lists.
 * Elements over this number will not be shown at all.
 * This affects list of members in groups.
 */
define('MAX_LIST_ELEMENTS',         100);

/** 
 * Max rows in long lists.
 * Elements over this number will get on different pages.
 */
define('MAX_LIST_ELEMENTS_SPLIT',         25);

?>
