<?php
/**
 * @file
 * Platform initialization for both http and cli calls.
 *
 * This file is used for the initialization of the base plattform settings
 * like autoloading, translation system...
 *
 * @attention Keep in mind that the correct order of the initialization is
 * neccessary to let the code work.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref License.
 * @see       http://alinex.de
 */

use Alinex\Code;
use Alinex\Util\I18n;
use Alinex\Dictionary\Registry;

/**
 * Flag to show if the base classes are all initilized.
 *
 * This is used to prevent some deadlocks because of incomplete code loadings.
 * So this is used to select between a simple local execution and the use of the
 * library for i.e. error handling (error_log() vs Logger).
 * @global string $GLOBALS['initialized'] is the base system initialized
 * @name $initialized
 */
$GLOBALS['initialized'] = false;

// general php configuration

// this is needed to show the parameters in backtrace log
ini_set('xdebug.collect_params', '4');
ini_set('display_errors', false);

// autoloader
// required to load the classes
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
Code\ExceptionHandler::register();
Code\AssertHandler::enabled(!PRODUCTIVE);

// initialize registry
Registry::getInstance(
    defined('CONFIGFILE') && file_exists(CONFIGFILE)
    ? 'file://'.CONFIGFILE
    : null
);

// setup logger

$logger = Alinex\Logger::getInstance();
$logger->attach(new Alinex\Logger\Handler\Stderr());

// configure internationalization

I18n::setLocale();
date_default_timezone_set('Europe/Berlin');


$GLOBALS['initialized'] = true;