Installing Brukerinfo
=====================

Prerequisites
-------------

Before installing Brukerinfo, you need:

**Apache**: Brukerinfo has been developed running Apache, so other servers are
not supported. Both setting it up as a site or in a sub directory works. The
application needs the config:

``` 
Options -Indexes
php_flag session.cookie_secure on
php_flag magic_quotes_gpc off
php_flag magic_quotes_runtime off
``` 

**PHP5**: The application makes use of some extensions, that are normally
installed by default:

* cURL extension
* XMLRPCEXT
* OpenSSL 

**Pear packages**: See http://pear.php.net/manual/en/installation.php for how to
install the Pear packages. It is important that these are automatically updated
for security and bug fixes! Also, the location of the pear-packages must be
included in the `include_dir` and `safe_mode_include_dir` in the php-config.

* `HTML_QuickForm` - to create and validate HTML forms securely
* `HTML_Common` - to create basic HTML, required by `HTML_QuickForm`
* `XML_RPC2` - to communicate with bofhd through XMLRPC

**Bofh daemon**: Obviously enough, you need a bofh daemon (bofhd) to communicate
with Cerebrum.

Normally, the idle timeout for bofhd is up to one day. This should be set lower
for the web application. It is recommended to update bofhd's configuration with
a shorter timeout for sessions coming from the web site's IP addresses, for
example:

``` 
BOFHD_SHORT_TIMEOUT = 60*10
BOFHD_SHORT_TIMEOUT_HOSTS = ("129.240.2.112/28", "129.240.2.119")
``` 

Brukerinfo has its own timeout function for sessions as well, but this makes
sure that the sessions are removed from bofhd too. The shortest timeout of
Brukerinfo or bofhd will be the one that the end user experiences.

Installing
----------

### Getting the project

The code could be fetched from https://utv.uio.no/stash/projects/CRB/, normally
by checking it out:

``` 
git clone ssh://git@utv.uio.no:7999/crb/brukerinfo.git
git clone ssh://git@utv.uio.no:7999/crb/phplib.git
git clone ssh://git@utv.uio.no:7999/crb/phplib2.git
``` 

The `phplib` and `phplib2` are PHP code that is also used by other web
applications.

### Set up application directories

Place all the repos in the same directory.

Brukerinfo's root directory looks like:

``` 
www_docs/
    ...
data/
    ...
system/
    model/
    view/
``` 

The three main directories are to be placed at different locations, and need
different access settings::

``` 
# public dir is readable only 
chown --recursive cerebrum:wwwgroup     www_docs/
chmod --recursive ug=rX,a=              www_docs/

# system code is readable only 
chown --recursive cerebrum:wwwgroup     system/
chmod --recursive ug=rX,a=              system/

# data dir has to be writable
chown --recursive cerebrum:wwwgroup     data/
chmod --recursive ug=rwX,a=             data/

# Make sure that www_docs are publicly reachable:
mv www_docs/ <DocumentRoot>/
``` 

where `cerebrum` is the user that should be updating the code, and `wwwgroup` is
the administration group that both the administrators and the _httpd user_
should be in. If no such common group is available, the different files has to
be readable by everyone instead. Note, however, that the `data/` directory might
contain passwords and certicates and should be protected.

### Configure

Set up Brukerinfo's configuration:

    cp www_docs/config.sample.php www_docs/config.php

and start modifying `www_docs/config.php`. Most settings are documented, but
some notes:

- You could either set the location of the `phplib` directory through the
  config, in `LINK_LIB`, or you could create a symlink to it at `system/phplib`.

- If the `www_docs` needed to be moved to comply with Apache's `DocumentRoot`,
  you also need to set `LINK_SYSTEM` and `LINK_DATA` to the system and data
  directories' absolute paths, respectively.
