Alinex Server
=============

PHP Development Platform (see more at http://alinex.de/server)

About Alinex
------------

Alinex is a collection of stable, flexible and strong integrated components to
make a base system for any powerful web application. Web applications are used
more and more also with the new term 'working in the cloud' and the big market
share of tablet computers and smartphones. In contrast to traditional
applications there is no installation for each user neccessary and it can be
used anytime and anywhere. This applications can also be used from pocket
devices like mobile phones.

To reach this there are allready a lot of frameworks which help in development,
but often they are very complex and not flexible enought. The hardest part in
web applications are the client programming and just this is often not
supported well by most frameworks. Alinex aims this and will give better support
to make robust and powerful interfaces without to much effort. This is solved
not as one major system but as a base plattform with lots of intercorporating
modules.

But Just now the client application is not developed.

Motivation and Origin
---------------------

In this project all the experience from further projects of the author are put
together to make the best solution. The project started as a test for an optimal
PHP project.
It changed often in several years and a lot of other projects are successors of
this. But now this project itself should be finished to build a basis for my
next generation php projects.

Platform Features
-----------------

In the moment the following parts are developed:

- **Autloloading**

  Universal and standard conform autoloader for platform, vendor and system
  files.

- **Value Validation**

  This is a central point for stability, maintenance and security.

- **Multilingual Support**

  Using an gettext approach for easy use and translation with thread safe
  alternative and included template language use (see below).

- **Dictionary**

  An abstract layer for different key-value stores with lots of possibilities
  to use. A great list of engines will be supported.

  The easy use for different scenarios are provided through the Registry,
  Cache,... classes.

- **Logging**

  A framework for standard conform logging with lots of handlers, filter and
  the easy to use format specification through the simple template (see below).

- **Simple Template**

  This is an easy template language which will work mostly on short text and is
  integrated into the internationalization, logging and error management
  processes.

- **Automatic Configuration**

  Most parts are automatically configured to be used or may be specified using
  the configuration file.


API Documentation & More
---------------------

A complete API documentation is included in the code and is accessible
under the projects homepage http://alinex.de/server.
This will be updated with each version step only.

Installation
------------

Stable packages will be available on the projects website as soon as ready with
an easy install mechanism.

To install a development version you may download it from github using:

    git co git@github.com:alinex/php-server.git server

Then you have to add the requirements including third party libraries. This is
done using Composer:

    cd server
    curl -s https://getcomposer.org/installer | php
    php composer.phar install

The third party libraries are used through softlinks under source/vnd.

A.Schilling
