Brukerinfo
==========

Brukerinfo is an administration frontend for end users, for the identity
management system Cerebrum. The end users are able to look at some of their own
data from Cerebrum, and also to modify some of it. Brukerinfo is mainly a simple
PHP web application that presents the data retrieved from Cerebrum. Most of the
logic and business policies are handled by Cerebrum.

Some of the supported functionality in Brukerinfo:

- Change password
- Look at your own person data to see if something's incorrectly registered
- Set some of your own e-mail settings, like spam settings and forwarding
- Moderate your own groups

Brukerinfo is in use mainly at the University of Oslo (UiO) -
https://brukerinfo.uio.no - but it is also in various formats at other
universities and university colleges.

Note that Brukerinfo is *not* a tool for administrators. The preferred
administration tool for Cerebrum is still the command line tool `bofhd`, or by
using Cerebrum's webservice.

Some more documentation about Brukerinfo is, for now, located in UiO's internal
repository _cerebrum_config_, placed at
https://utv.uio.no/stash/projects/CRB/repos/cerebrum_config/browse/doc/intern/uio/utvikling/brukerinfo.
More information could be given if you contact Cerebrum.

Contact
-------

* https://usit.uio.no/om/tjenestegrupper/cerebrum/ - The main site for the
  people at UiO working with Cerebrum and Brukerinfo
* `cerebrum-kontakt@usit.uio.no` - Primary contact list for all Cerebrum related
* `cerebrum-commits@usit.uio.no` - all commit logs
* `cerebrum-developers@usit.uio.no` - the Cerebrum developers

License
-------

Cerebrum is licensed using GNU Public License version 2 and later.

Requirements
------------

* Apache, or some other web server
* PHP5, version 5.3 or later, with a few standard extensions.
* The Pear package `HTML_Common`
* The Pear package `HTML_QuickForm`
* The Pear package `XML_RPC2`

Also, you need to be able to talk with a *bofh daemon* to be able to make use of
the application.

Getting started
---------------

This section is for developers and contains guidelines for how to work with the
application.

Before you start, you should follow the instructions in `INSTALL.md` to have
something to work with.

### Institutions

Brukerinfo is designed to work for different *institutions*. The institution
switch is set in the config's `INST`. Changing this mainly affects what files
are read, e.g. for the application's text and design.

TODO: Some refactoring lately might have made the following text invalid.

New institutions have some modules and files they need to create and/or modify
before the project is ready for use. Look for the reference to `INST` further
down.

### View

The `View` class takes care of the html of the pages.

The `View` class itself is only meant to be abstract, so you have to create a
new subclass of it to manage the institutional design. The class has to be
named:

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
