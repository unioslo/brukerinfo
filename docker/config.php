<?php
// System settings

/** If debugging output should be shown. */
define('DEBUG', false);

/**
 * The acronym of the institution, in lowercase. This constant should also be
 * used in the name of files, so let it be short, and use only regular
 * characters [a-z].
 */
define('INST', $_ENV['WOFH_INST']);

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
define('FEATURES', isset($_ENV['WOFH_FEATURES']) ? $_ENV['WOFH_FEATURES'] : null);

/**
 * Path to www_docs. This is the directory where this configuration file is located.
 */
define('WWW_DOCS_PATH', $_ENV['APACHE_DOCUMENT_ROOT']);


/**
 * Calculates the base path, which is one directory up from the location of this file.
 */
define('BASE_PATH', realpath(WWW_DOCS_PATH . '/..'));
define('LINK_SYSTEM', BASE_PATH . '/system');
define('LINK_DATA', BASE_PATH . '/data');
define('LOCK_FILE', LINK_DATA . '/lock');
define('MESSAGE_FILE', LINK_DATA . '/messages');

/**
 * The default language for the project. This is used for fallbacks, e.g. if
 * some text is missing from a chosen language, or when the user has no prefered
 * language the site can offer.
 *
 * A language file must exist for this language.
 */
define('DEFAULT_LANG', 'en');


/// Cerebrum settings

/**
 * The url to the bofhd server.
 *
 * protip: set to 'http://<docker-host>:8000/', and then
 *         ssh -L 0.0.0.0:8000:localhost:<bofhd-port> <bofhd-host>
 */
define('BOFH_URL', $_ENV['WOFH_BOFH_URL']);

/* Fetch and show MOTD from BOFH_URL? */
define('BOFH_MOTD', false);

/**
 * The url to the Cerebrum Integration Server (CIS).
 * TODO: Is this used? Remove from example config if not...
 */
define('CI_URL', null);

/* charset used in xmlrpc documents to and from bofhd */
define('BOFH_CHARSET', 'utf-8');


/// Web settings

/**
 * Minutes before an inactive user gets logged out from the site. Should be 
 * about the same as the timeout setting in bofhd, as the shortest timeout wins.
 */
define('TIME_OUT_MIN', 10);

/**
 * Number of logon attempts before a user gets temporary blocked. Set this high 
 * enough to avoid annoying real users, but low enough to make brute-force 
 * attacks a bit more difficult.
 */
define('ATTEMPTS', 20);

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
define('CHARSET', 'utf-8');

/**
 * If the page should only be shown in https mode. When set to true, the page 
 * will die with an error if the user comes in in http mode. This is used to 
 * double check that the (apache) server is redirecting users correctly from 
 * http to https.
 */
define('HTTPS_ONLY', false);

/**
 * The base url for where the site is located. It should contain the full 
 * url, including the preferred protocol, domain, and, if necessary, sub 
 * directories.
 *
 * This is e.g. used for redirecting correctly.
 */
define('BASE_URL_PREFIX', '');
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/');

/**
 * The prefix in the url (if the project is in a subdir of
 * htmlroot), like:
 *  http://example.com/HTML_PRE/index.php
 *
 * Must start with /, but not end with it.
 *
 * TODO: this is deprecated and will be removed. Use BASE_URL instead.
 */
define('HTML_PRE', '/cerebrum/wofh/www_docs');

/** Url to the logon page */
define('URL_LOGON', 'logon.php');

/** The default page to go after log on */
define('URL_LOGGED_IN', BASE_URL);

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
define('ACTION_DELAY', 4*60); // 4 hours

/** 
 * Delay for email
 * Number of minutes before email changes will be working.
 * This constant is not doing anything but just informing the end user about the delay.
 */
define('ACTION_DELAY_EMAIL', 30);


/** 
 * Max rows in long lists.
 * Elements over this number will not be shown at all.
 * This affects list of members in groups and is because of how the bofh
 * daemon works.
 */
define('MAX_LIST_ELEMENTS', 100);

/** 
 * Max rows in long lists.
 * Elements over this number will get on different pages.
 */
define('MAX_LIST_ELEMENTS_SPLIT', 25);


/**
 * Should new passwords be validated by sending POST requests to the
 * forgotten password service?
 */
define('REALTIME_PASSWORD_VALIDATION', true);

/**
 * If so, what's the relative URL to the forgotten password service?
 */
define('FORGOTTEN_PASSWORD_BASE_PATH', "/forgotten/");


/**
 * The relative URL to the password validation javascript-file?
 */
define('REALTIME_PASSWORD_JS', FORGOTTEN_PASSWORD_BASE_PATH . "shared_design/js/password_validator.js");

/**
 * Convert the username at the logon page to lowercase
 */
define('LOGON_USERNAME_TOLOWER', false);

/**
 * if set, use statsd.
 */
define('USE_STATSD', false);

/**
 * Host name for STATSD
 */
define('STATSD_HOST', 'localhost');

/**
 * Port for STATSD
 */
define('STATSD_PORT', 8125);

/**
 * Prefix for statsd
 */
define('STATSD_PREFIX', 'cerebrum.brukerinfo');

?>
