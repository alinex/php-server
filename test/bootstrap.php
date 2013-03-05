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

// internationalization

I18n::init();
I18n::setLocale("en_US");

return $loader;
