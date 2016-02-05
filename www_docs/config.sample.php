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
 * This is an example config file for Brukerinfo, used by the Init and InitBase 
 * classes. An adapted version of this file must be placed in the www_docs 
 * directory, normally as www_docs/config.php, and be imported by the Init 
 * class, normally placed in www_docs/init.php.
 *
 * The config variables can differ from institution to institution, depending on 
 * what is put in the local Init class and made use of in the www_docs 
 * directory.
 *
 * TODO: Some variables from this config file are still being used in different 
 * classes, which is wrong. Classes should not depend on manually created 
 * constants. Note that this will change, as the classes are slowly migrated.
 */

/// System settings

/** If debugging output should be shown. */
define('DEBUG',                 false);

/** 
 * The acronym of the institution, in lowercase. This constant should also be 
 * used in the name of files, so let it be short, and use only regular 
 * characters [a-z].
 */
define('INST',                  'uio');

/**
 * The available functions and pages for brukerinfo.
 *
 * Available features:
 * account, email, groups, guests, person, printing, reservations,
 * and any class implementing the interface ModuleGroup.
 *
 * Set as a string containing the wanted features separated with space, e.g.
 * define('FEATURES', 'account guests printing');
 *
 * Set to null to get default set of features, based on INST.
 */
define('FEATURES',              null);

/**
 * Path to www_docs. This is the directory where this configuration file is located.
 */
define('WWW_DOCS_PATH',         realpath(dirname(__FILE__)));

/**
 * Calculates the base path, which is one directory up from the location of this file.
 */
define('BASE_PATH',             realpath(WWW_DOCS_PATH . '/..'));

/**
 * The location of the system files, that is, the projects' "system/" directory.
 * It is preferred that this directory is not reachable through a web browser, 
 * so place it somewhere else than in www_docs.
 */
define('LINK_SYSTEM',           BASE_PATH . '/system');


/**
 * The location of the shared 'phplib/' directory. The phplib directory contains 
 * common classes that are used in different Cerebrum projects, and several 
 * classes depends on this to exist. 
 *
 * https://utv.uio.no/stash/projects/CRB/repos/phplib/browse
 *
 */
define('LINK_LIB',              BASE_PATH . '/system/phplib');

/** 
 * The location of the "data/" directory. It is preferred that this directory is 
 * not reachable through a web browser, so place it somewhere else than in www_docs.
 */
define('LINK_DATA',             BASE_PATH . '/data');

/** 
 * The location of the lock-file. If this file contains data (preferrably text), 
 * all users will be logged out and the site will only be showing this text.
 * This can be used to inform the users when upgrading, or to block the page 
 * while fixing security issues.
 */
define('LOCK_FILE',             LINK_DATA . '/lock');

/**
 * The location of a message file.
 * If the file exists and contains data, it will be added to all pages as a 
 * message, like View::addMessage(file_contents).
 */
define('MESSAGE_FILE',          LINK_DATA . '/messages');

/** 
 * The default language for the project. This is used for fallbacks, e.g. if 
 * some text is missing from a chosen language, or when the user has no prefered 
 * language the site can offer.
 *
 * A language file must exist for this language.
 */
define('DEFAULT_LANG',          'en');

/**
 * The default timezone to be used when working with datetimes.
 * PHP complains by throwing warnings if it has to fall back to
 * to using the system settings.
 */
date_default_timezone_set('Europe/Oslo');


/// Cerebrum settings

/**
 * The url to the bofhd server. The communication is done through xml-rpc and is 
 * handled by the class BofhCom.
 */
define('BOFH_URL',              'https://cere-test.uio.no:8080/');

/**
 * If bofhd's motd should be received and shown on the site.
 */
define('BOFH_MOTD',             false);

/**
 * The url to the Cerebrum Integration Server (CIS). The communication is done 
 * through soap and is handled by the class CICom.
 */
define('CI_URL',                'https://cere-test.uio.no:8081/');

/** 
 * The charset for data being sent to bofhd.
 *
 * TODO: this might not be necessary anymore.
 *
 * The charset has to be valid for the php function unicode_enocde(),
 * see http://php.net/unicode_encode.
 */
define('BOFH_CHARSET',          'ISO-8859-1');


/// Web settings


// TODO: fix all time values to be in seconds, not minutes, to make it easier.

/** 
 * Minutes before an inactive user gets logged out from the site. Should be 
 * about the same as the timeout setting in bofhd, as the shortest timeout wins.
 */
define('TIME_OUT_MIN',          10);

/**
 * Number of logon attempts before a user gets temporary blocked. Set this high 
 * enough to avoid annoying real users, but low enough to make brute-force 
 * attacks a bit more difficult.
 */
define('ATTEMPTS',              20);

/** 
 * Number of minutes a user gets blocked from the site, e.g. by too many logon 
 * attempts.
 */
define('ATTEMPT_TIME_OUT_MIN',  5);

/** 
 * The charset which the pages is telling the browser that it's using. Be sure 
 * that files are in the same charset as set here.
 *
 * TODO: this should be removed, as it is of no use.
 */
define('CHARSET',               'utf-8');

/**
 * If the page should only be shown in https mode. When set to true, the page 
 * will die with an error if the user comes in in http mode. This is used to 
 * double check that the (apache) server is redirecting users correctly from 
 * http to https.
 */
define('HTTPS_ONLY',            true);

/**
 * The base url for where the site is located. It should contain the full 
 * url, including the preferred protocol, domain, and, if necessary, sub 
 * directories.
 *
 * This is e.g. used for redirecting correctly.
 */
$base_url_prefix = substr(WWW_DOCS_PATH, strlen($_SERVER['DOCUMENT_ROOT']));
define('BASE_URL_PREFIX',       ($base_url_prefix) ? $base_url_prefix : '');
define('BASE_URL',              'https://' . $_SERVER['HTTP_HOST'] . BASE_URL_PREFIX . '/');
unset($base_url_prefix);

/** 
 * The prefix in the url (if the project is in a subdir of
 * htmlroot), like:
 *  http://example.com/HTML_PRE/index.php
 *
 * Must start with /, but not end with it.
 *
 * TODO: this is deprecated and will be removed. Use BASE_URL instead.
 */
define('HTML_PRE',          '/cerebrum/wofh/www_docs'); 

/** Url to the logon page */
define('URL_LOGON',         'logon.php');

/** The default page to go after log on */
define('URL_LOGGED_IN',     '');

/// Other settings

// reCaptcha

/**
 * The public key used in the reCaptcha forms. To get a key pair for your 
 * domain, see https://www.google.com/recaptcha/admin/create
 *
 * This is used by HTML_QuickForm for adding captchas to your forms.
 */
define('RECAPTCHA_PUBLIC_KEY', '');

/**
 * The private key used in the reCaptcha forms. To get a key pair for your 
 * domain, see https://www.google.com/recaptcha/admin/create
 *
 * This is used by HTML_QuickForm for adding captchas to your forms.
 */
define('RECAPTCHA_PRIVATE_KEY', '');


/** 
 * General delay
 * Number of minutes before changes will be working (for informing the user).
 * This constant is not doing anything but just informing the end user about the 
 * delay, e.g. how long it will take for a password change to work in all the 
 * systems.
 *
 * All delays are in minutes.
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
 * This affects list of members in groups and is because of how the bofh
 * daemon works.
 */
define('MAX_LIST_ELEMENTS',         100);

/** 
 * Max rows in long lists.
 * Elements over this number will get on different pages.
 */
define('MAX_LIST_ELEMENTS_SPLIT',         25);


/**
 * Should new passwords be validated by sending POST requests to the
 * forgotten password service?
 */
define('REALTIME_PASSWORD_VALIDATION', true);

/**
 * If so, what's the relative URL to the validation endpoint?
 */
define('REALTIME_PASSWORD_VALIDATION_ENDPOINT', "forgotten/password/validator.php");


// Test

?>
