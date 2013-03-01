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

use Alinex\Storage\Registry;
use Alinex\Code;
use Alinex\Util\I18n;

// general php configuration

// this is needed to show the parameters in backtrace log
ini_set('xdebug.collect_params', '4');
ini_set('display_errors', false);

date_default_timezone_set('Europe/Berlin');

// autoloader

require_once __DIR__.DIRECTORY_SEPARATOR
        .'Alinex'.DIRECTORY_SEPARATOR
        .'Code'.DIRECTORY_SEPARATOR
        .'Autoloader.php';
$loader = new Code\Autoloader();
$loader->add('Alinex', __DIR__);
$loader->register();

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

// @todo set timezone


return $loader;


/**
 * Gettext translate function
 *
 * This function uses the gettext library which should be enabled in PHP. If not
 * installed, no translations will be available and the original text will be
 * shown.
 *
 * @param string $msgid text which should be translated
 * @param array $params params with vars which should be replaced
 * (enclosed with {})
 *
 * @return   string   translated text
 */
function tr($msgid, array $params = array())
{
    return I18n::tr($msgid, $params);
}

/**
 * Gettext translate function (with plural)
 *
 * This function uses the gettext library which should be enabled in PHP. If not
 * installed, no translations will be available and the original text will be
 * shown.
 *
 * @param string $msgSingular singular text which should be translated
 * @param string $msgPlural plural text which should be translated
 * @param integer $num number to decide between singular and plural
 * @param array $params params with vars which should be replaced
 * (enclosed with {})
 *
 * @return   string   translated text
 */
function trn($msgSingular, $msgPlural, $num, array $params = array())
{
    return I18n::trn($msgSingular, $msgPlural, $num, $params);
}
