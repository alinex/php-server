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
use Alinex\Template;

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
    // domain has to be set
    assert(is_string($namespace));
    // only strings may be translated
    assert(is_string($msgid));

    I18n::setDomain($namespace);
    // get the message
    if (function_exists('gettext'))
        // call native php function
        $trans = gettext($msgid); // call native php function
    else
        // fallback keep english
        $trans = $msgid;
    // replace variables and return
    return Template\Simple::run($trans, $params);
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
) {
    // domain has to be set
    assert(is_string($namespace));
    // only strings may be translated
    assert(is_string($msgSingular));
    // only strings may be translated
    assert(is_string($msgPlural));
    // have to be a positive integer
    assert(is_int($num) && $num >= 0);

    I18n::setDomain($namespace);
    if (function_exists('gettext'))
        // call native php function
        $trans = ngettext($msgSingular, $msgSingular, $num);
    else
        // fallback keep english
        $trans = $num != 1 ? $msgSingular : $msgPlural;
    // replace variables and return
    return Template\Simple::run($trans, $params);
}
