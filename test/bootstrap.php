<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alinex\Code;
use Alinex\Util\I18n;

error_reporting(E_ALL);

date_default_timezone_set('Europe/Berlin');

// autoloader

require_once __DIR__.DIRECTORY_SEPARATOR
        .'..'.DIRECTORY_SEPARATOR
        .'source'.DIRECTORY_SEPARATOR
        .'Alinex'.DIRECTORY_SEPARATOR
        .'Code'.DIRECTORY_SEPARATOR
        .'Autoloader.php';
$loader = new Code\Autoloader();
$loader->add('Alinex', __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'source');
$loader->register();

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

