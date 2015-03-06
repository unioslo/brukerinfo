Installing Brukerinfo
=====================

This document is a how to for installing Brukerinfo. The document does not
include information about installing bofhd or the required software, and expects
the reader to be experienced with administration of web servers and bofhd.

## Requirements

Before you get Brukerinfo up and running you need to have:

* A running apache2 web server with PHP5 and some Pear packages.
* A running bofhd daemon with an updated configuration.
* Access between the web server and the bofhd daemon.

### Apache2

Apache2 is the recommended web server, as it is the server Brukerinfo was
developed on. The project could either be in a subdirectory of a site, or get
its own site (through `/etc/apache2/sites-available/`).

For configuring the site, put the following text in the proper configuration
file (most likely in one of the files in `/etc/apache2/sites-available/`):

``` 
Options -Indexes
php_flag session.cookie_secure on
``` 

If the project is in a sub directory of a site, you should wrap the config above
into a `<Location...>`-tag.

The first line, `Options -Indexes`, turns off the directory listing. The next,
`session.cookie_secure`, makes sure that session-cookies will not be sent
without a secure connection.

It is also recommended that the `magic_quotes` is off per default. If not, you
could add this to the config as well::

``` 
php_flag magic_quotes_gpc off
php_flag magic_quotes_runtime off
``` 

### PHP5

PHP5 needs to be installed. This project has been developed in php's safe-mode,
but may work without it (not tested though), as the safe mode is deprecated
after php 5.3.0.

The following extensions are normally enabled in php at startup, but make sure
they really are:

* cURL extension
* XMLRPCEXT
* OpenSSL 

### Pear packages

The project depends on some Pear packages. `XML_RPC2` does the communication
with bofhd, and `HTML_QuickForm` makes html forms. The Pear packages can be
installed by following the manual at http://pear.php.net/manual/en/installation.php
and should be automatically updated for security and bug fixes.

It is important that the location to the pear-packages is included in the
`include_dir` and `safe_mode_include_dir` in the php-config.

The project needs the pear-packages mentioned further on.

#### HTML_QuickForm

Version used: 3.2.10, or preferably later.

For automating use of html forms and to easily and securely validate them.

This package is superseded by *HTML_QuickForm2*, (only bugs and security fixes
remains), but this is still in its alpha-version. The new package may be used in
the future, as it is built for PHP5 while HTML_Quickform is for PHP4.

#### HTML_Common

Version used: 1.2.4, or preferably later.

This package is for making basic html-tags, and is also a requirement for
HTML_QuickForm.

This package is superseded by *HTML_Common2* (only bugs and security fixes
remains), but this is still in its beta-version. This may be used in the future, as
it is developed for PHP5 while HTML_Common is for PHP4.

#### XML_RPC2

Version used: 1.0.2, or preferably later.

This package handles the communication with bofhd over the xmlrpc protocol.

This package has some bugs today, and triggers some warnings on return data, so
a newer version would be preferred. It will only add some `PHP Notice` noise in
the logs, though, and the bugs are taken care of in the code.


## Bofh daemon

Obviously enough, you need a bofhd to communicate with. In the current version,
you can use the same bofhd as jbofh uses, but with a few additional settings in
the cereconf.py. You would need to make sure that the port to bofhd is open for
the web server address(es).

The bofhd version to use is svn rev 10415 (or preferably a later version), as
new functionality is added.

The bofh daemon has normally an idle-timeout up to a day, but this is far too
much for a web site. To make bofhd use shorter timeouts for connections from
Brukerinfo, you have to know the web servers ip-address(es) and include them in
`cereconf.py`:

``` 
BOFHD_SHORT_TIMEOUT = 60*10
BOFHD_SHORT_TIMEOUT_HOSTS = ("129.240.2.112/28", "129.240.2.119")
``` 

where `BOFHD_SHORT_TIMEOUT` sets the number of seconds before you have to
reauthenticate to bofhd, and `BOFHD_SHORT_TIMEOUT_HOSTS` is a list of all
IP-addresses from which the timeout has an effect. The consequence of such a
timeout is that the user has to reauthenticate, and the reason is to prevent
open and unused sessions between the web server and bofhd. Please note that
Brukerinfo also has a timeout function for sessions, and that the shortest
timeout will win.


## Installing Brukerinfo

Now that you have a working web server which is able to connect to a running
bofhd, you need to install the project.

### Getting the project


If you do not already have the code, the project files are located at
https://utv.uio.no/stash/projects/CRB/. To fetch the code:

``` 
git clone ssh://git@utv.uio.no:7999/crb/brukerinfo.git
git clone ssh://git@utv.uio.no:7999/crb/phplib.git
git clone ssh://git@utv.uio.no:7999/crb/phplib2.git
``` 

Some more documentation about Brukerinfo is, for now, located in UiO's internal
repository *cerebrum_sites*, placed under::

    https://utv.uio.no/stash/projects/CRB/repos/cerebrum_config/browse/doc/intern/uio/utvikling/brukerinfo

More information could be given if you contact *cerebrum-kontakt@usit.uio.no*.

## Placing the project

If you already have the code, it may come tarred together, and can be opened with::

``` 
tar -xzf brukerinfo.tar.gz
tar -xzf phplib.tar.gz
``` 

You should now have Brukerinfo's "root directory"::

``` 
./
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
``` 

where `cerebrum` is the user that should be updating the code, and `wwwgroup` is
the administration group that both the administrators and the apache user should
be in.

If no such common group is available, the different files has to be readable by
everyone instead. Note, however, that the `data/` directory might be used for
passwords files in the future, and should therefore not be readable by anyone
other than the apache user.

### `www_docs`

`www_docs` is the web directory, available to the public, so make sure that the
location of this directory equals apaches *DocumentRoot* for this project. For
instance, if *DocumentRoot* is `/var/www/brukerinfo`, move `www_docs` to be this
directory. The code is usually::

``` 
mv www_docs/ <DocumentRoot>/
mv www_docs /var/www/brukerinfo
``` 

Projects are free to make their own `www_docs` and only use the original as an
example.

### `system`

The `system` directory contains most of the code and classes used in the web
directory, and should not be put in a public directory.

### `data`

The `data` directory contains the files and settings for the different
institutions, and some data that is changed by the web site, which is why it has
to be writable. This directory should also *not* be put in a public directory.

### `phplib`

in addition you have the phplib directory, which contains more generic code that
can be used in different projects. This directory can be put anywhere but in a
public directory, as long as the constant `LINK_LIB` points to it. It can for
instance be put or symlinked to in under `system/`.

## Configure the project

Next step is to configure Brukerinfo to be used at a given institution.  Go to
`www_docs` and modify the `config.php`, which contains the settings. First, you
need to modify the paths `LINK_SYSTEM`, `LINK_DATA` and `LINK_LIB` to the
correct locations of where you put `data`, `system` and `phplib`, respectively.
The rest of the settings has to be updated as well, and every setting is
explained in the example file `config.example.php`.

TODO: The text that follows is about to be changed, as the code is reorganized
to be easier to use in different projects without having to duplicate as much
code.

New institutions have some modules and files they need to create and/or modify
before the project is ready for their use. Please note that the string you put 
in `INST` in `config.php` is later used for reference to your institutions files
and classes.

### View

The `View` class takes care of the html of the pages.

The `View` class itself is only meant to be abstract, so you have to create a
new subclass of it to manage the institutional design. The class has to be
named::

    View_<INST>

e.g. `View_uio`, and must be placed as `system/view/View/<INST>.php`. An easy
way to create this class is to copy the `View_uio`-class and change what is
necessary.

Another file that is necessary for the layout is the html-template file. These
files are named::

    data/templates/template.<INST>.<LANG>.txt

As you can see it is one template for each language. Note that the use of
templates is not obligatory, as this is only controlled and may be used by your
subclass of View.

### Text

The `Text` class handles most of the text on the web page, and all text is
defined in xml files placed as::

    data/txt/<INST>/<LANG>.xml

If you want to use the original `www_docs` you can just use the original xml
files and modify what is necessary for your institution. Please note that it is
the text-files that defines what languages that are supported on the web page.
If you create a new text-file you also have to create a new template-file for
that language.

