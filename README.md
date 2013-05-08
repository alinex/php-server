Alinex Server
=============

PHP Development Platform (see more at http://alinex.de/server)

About Alinex
------------

Alinex is a collection of stable, flexible and strong integrated components to
make a base system for any powerful web application. Web applications are used
more and more also with the new term 'working in the cloud' and the big market
share of tablet computers and smartphones. In contrast to traditional
applications there is no installation for each user necessary and it can be
used anytime and anywhere. This applications can also be used from pocket
devices like mobile phones.

To reach this there are already a lot of frameworks which help in development,
but often they are very complex and not flexible enough. The hardest part in
web applications are the client programming and just this is often not
supported well by most frameworks. Alinex aims this and will give better support
to make robust and powerful interfaces without to much effort. This is solved
not as one major system but as a base platform with lots of intercorporating
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

All of the following basic features will cooperate strong together to get the
optimal base for the application. The main concept is on easy but powerful use.

- **Autoloader**

  Universal and standard conform autoloader for platform, vendor and system
  files. Using lazy loading.

- **Validation**

  This is a central point for stability, maintenance and security. Each and any
  value may be validated before use by a a deep analyzation based on different
  extensible validators. Problems will be reported in human understandable
  detailed description.

- **I18n support**

  Complete translation support over any and all base classes. The translator
  supports the use of the simple templates (described below) to easy include
  formatted values.

- **Multilingual Support**

  Using an gettext approach for easy use and translation with thread safe
  alternative and included template language use (see below). The complete
  base is setup multilingual.

- **Event Management**

  A general event management allows to listen and react to specific events
  on time. Any class can work as consumer or sender of events which may be send
  directly or through the event manager to all currently registered classes.

- **Dictionary**

  An abstract layer for different key-value stores with different supported
  storage engines like Redis, Memcache, APC, Xcache, Directory, ...\n
  Each engine can import/export in many formats supports value  manipulation
  methods and time limits through garbage collection.

  The easy use for different scenarios are provided through the accessors like
  Registry, Cache, Session and Queue... classes.

- **Logging**

  A framework for standard conform logging with lots of handlers, filter and
  the easy to use format specification through the simple template (see below).
  Logging to syslog, stdout, errorlog, streams, Dictionary, mail, files...

- **Simple Templates**

  This is an easy template language which will work mostly on short text and is
  integrated into the internationalization, logging and error management
  processes.\n
  It supports variable substitutions, different powerful modifiers and control
  operators.

- **Automatic Configuration**

  Classes may be configured directly using an initializer function you write
  or automatically using the registry configuration.\n
  Parts of the base system are already supporting this. If a configuration
  is found it will be used.

- **System execution**

  A Process execution and management package makes it easy to call external
  system routines on any Unix like System. With this PHP can also be used
  as replacement for bash scripts or use powerful comand line tools where they
  are better than the php solution like media conversion...


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
