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

Contact
-------

* https://usit.uio.no/om/tjenestegrupper/cerebrum/ - The main site for the
  people at UiO working with Cerebrum and Brukerinfo
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
