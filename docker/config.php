<?php

/* If debugging output should be shown. */
define('DEBUG', false);

/* preset (uio, tsd, hine) */
define('INST', $_ENV['WOFH_INST']);

/**
 * The available functions and pages for brukerinfo.
 *
 * Available features:
 *   account, email, groups, guests, person, printing, reservations,
 * and any class implementing the interface ModuleGroup.
 *
 * Set as a string containing the wanted features separated with space, e.g.
 * define('FEATURES', 'account guests printing');
 *
 * Set to null to get default set of features, based on INST (data/features.php)
 */
define('FEATURES', isset($_ENV['WOFH_FEATURES']) ? $_ENV['WOFH_FEATURES'] : null);

/**
 * The url to the bofhd server.
 *
 * protip: set to 'http://<docker-host>:8000/', and then
 *         ssh -L 0.0.0.0:8000:localhost:<bofhd-port> <bofhd-host>
 */
define('BOFH_URL', $_ENV['WOFH_BOFH_URL']);


/* ReCaptcha, if used */
define('RECAPTCHA_PUBLIC_KEY', '');
define('RECAPTCHA_PRIVATE_KEY', '');

/* statsd */
define('USE_STATSD', false);
define('STATSD_HOST', 'localhost');
define('STATSD_PORT', 8125);
define('STATSD_PREFIX', 'cerebrum.brukerinfo');

/* paths */
define('WWW_DOCS_PATH', $_ENV['APACHE_DOCUMENT_ROOT']);
define('BASE_PATH', realpath(WWW_DOCS_PATH . '/..'));
define('LINK_SYSTEM', BASE_PATH . '/system');
define('LINK_DATA', BASE_PATH . '/data');
define('LOCK_FILE', LINK_DATA . '/lock');
define('MESSAGE_FILE', LINK_DATA . '/messages');

/* urls */
define('BASE_URL_PREFIX', '');
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/');
define('HTML_PRE', '/cerebrum/wofh/www_docs');
define('URL_LOGON', 'logon.php');
define('URL_LOGGED_IN', BASE_URL);

/* wofh behaviour and ui settings */
define('ACTION_DELAY', 4 * 60); // 4 hours
define('ACTION_DELAY_EMAIL', 30);
define('ATTEMPTS', 20);
define('ATTEMPT_TIME_OUT_MIN',  5);
define('BOFH_CHARSET', 'utf-8');
define('BOFH_MOTD', false);
define('CHARSET', 'utf-8');
define('DEFAULT_LANG', 'en');
define('HTTPS_ONLY', false);
define('LOGON_USERNAME_TOLOWER', false);
define('MAX_LIST_ELEMENTS', 100);
define('MAX_LIST_ELEMENTS_SPLIT', 25);
define('TIME_OUT_MIN', 10);


/* Individuation stuff, not currently implemented in docker image */
define('CI_URL', null);
define('FORGOTTEN_PASSWORD_BASE_PATH', "/forgotten/");
define('REALTIME_PASSWORD_VALIDATION', false);
define('REALTIME_PASSWORD_JS', FORGOTTEN_PASSWORD_BASE_PATH . "shared_design/js/password_validator.js");

?>
