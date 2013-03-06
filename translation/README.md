Translation files
=================

This directories contains the translation files in different versions:

- po - the files for translators
- mo - coompiled files for the system

The files within are separated into locales and domain files.

Workflow
--------

The whole magic behind, maintaining the translation files will be done
with the shell scripts of the development environment.

- The marked strings are exported into *.pot files.
- From which the language specific *.po files will be created or merged.
- Translators will insert their language
- The translated *.po files will be converted into *.mo files
- The *.mo files will be delivered with the application.

Translating
-----------

As a first thing a new *.po file is created by the developer or by copying an
existing one for the new locale if not done. The locale should be in
standardized POSIX format this means you may use only the two digit ISO language
code or with the additional country specification ll_CC like de_AT.

Since *.po files are so popular, there are several excellent tools for
translating them. The best known tool is a program called poedit
(http://www.poedit.net/). It is intended for translators and allows easy
translation and also merging between new texts in a *.po file and produces both
*.po and *.mo as output.

As a test you may also translate it with Google
(http://senko.net/services/googtext/), but the result is very bad.
Or you may try the open source translation database (http://littlesvr.ca/ostd/).


A.Schilling
