<?php
/**
 * @file
 * Platform initialization.
 *
 * This file is used for the initialization of the base plattform settings
 * like autoloading, translation system...
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref License.
 * @see       http://alinex.de
 */

use Alinex\Dictionary\Registry;
use Alinex\Code;

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
$loader->register();

// internationalization

I18n::init();

// error handler

Code\ErrorHandler::register();
// set to false for productive environment
Code\AssertHandler::enabled(true);

// registry
$registry = Registry::getInstance();
// @todo init registry load from file

// i18n

// @todo init and select locale from user
setlocale(LC_MESSAGES, 'de_DE');
setlocale(LC_ALL, 'de_DE');

date_default_timezone_set('Europe/Berlin');


return $loader;

