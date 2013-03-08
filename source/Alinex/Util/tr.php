<?php
/**
 * @file
 * Internationalization and translation shortcuts.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref License.
 * @see       http://alinex.de Alinex Project
 */

use Alinex\Util\I18n;

/**
 * Gettext translate function
 *
 * This function uses the gettext library which should be enabled in PHP. If not
 * installed, no translations will be available and the original text will be
 * shown.
 *
 * @param string $namespace namespace of caller (use __NAMESPACE__)
 * @param string $msgid text which should be translated
 * @param array $params params with vars which should be replaced
 * (enclosed with {})
 *
 * @return   string   translated text
 */
function tr($namespace, $msgid, array $params = array())
{
    return i18n::tr($namespace, $msgid, $params);
}

/**
 * Gettext translate function (with plural)
 *
 * This function uses the gettext library which should be enabled in PHP. If not
 * installed, no translations will be available and the original text will be
 * shown.
 *
 * @param string $namespace namespace of caller (use __NAMESPACE__)
 * @param string $msgSingular singular text which should be translated
 * @param string $msgPlural plural text which should be translated
 * @param integer $num number to decide between singular and plural
 * @param array $params params with vars which should be replaced
 * (enclosed with {})
 *
 * @return   string   translated text
 */
function trn(
    $namespace, $msgSingular, $msgPlural, $num, array $params = array()
)
{
    return I18n::trn($namespace, $msgSingular, $msgPlural, $num, $params);
}
