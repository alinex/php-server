<?php
/**
 * @file
 * Platform initialization for both http and cli calls.
 *
 * This file is used for the initialization of the base plattform settings
 * like autoloading, translation system...
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref License.
 * @see       http://alinex.de
 */

use Alinex\Code;
use Alinex\Util\I18n;
use Alinex\Dictionary\Registry;

// general php configuration

// this is needed to show the parameters in backtrace log
ini_set('xdebug.collect_params', '4');
ini_set('display_errors', false);

// autoloader

require_once __DIR__.DIRECTORY_SEPARATOR
    .'Alinex'.DIRECTORY_SEPARATOR
    .'Code'.DIRECTORY_SEPARATOR
    .'Autoloader.php';
$loader = Code\Autoloader::getInstance();
$loader->add('Alinex', __DIR__);
$loader->addBackports(__DIR__.DIRECTORY_SEPARATOR.'backport');
$loader->add(
    'Doctrine',
    __DIR__.DIRECTORY_SEPARATOR
    .'vnd'.DIRECTORY_SEPARATOR
    .'doctrine'
);
$loader->register();

// internationalization

I18n::init();

// error handler

Code\ErrorHandler::register();
// set to false for productive environment
Code\AssertHandler::enabled(true);

// initialize registry

$dir = __DIR__.'/';
Registry::getInstance(
    file_exists($dir.'config.ini')
    ? 'file://'.$dir.'config.ini'
    : null
);

// configure internationalization

I18n::setLocale();
date_default_timezone_set('Europe/Berlin');


