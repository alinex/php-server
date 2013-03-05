<?php
/**
 * @file
 * Internationalization and translation helper methods.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Util;

/**
 * Internationalization and translation helper methods.
 */
class I18n
{

    static function init()
    {
        require_once 'tr.php';
    }

    static function test()
    {
        return tr(__NAMESPACE__, "Test entry");
    }

    /**
     * Set the domain from the calling class.
     *
     * The base namespace will be used as domain. It will be set if not
     * already done.
     *
     * @note This will be called from tr() and trn() automatically, so there
     * is no need to invoke this directly.
     *
     * @param string $namespace namespace of calling class
     * @throws \RuntimeException if given domain was not set
     */
    public static function setDomain($namespace)
    {
        if (!function_exists('gettext'))
            return;
        // get top namespace
        list($domain) = explode('\\', $namespace, 2);
        if (textdomain(null) == $domain)
            return;

        // closing slash for windows neccessary
        if (!bindtextdomain($domain, __DIR__.'/../../../tr/'))
            throw new \Exception('Could not bind translation directory under /tr');
        // set the dopmain
        if (!textdomain($domain) == $domain)
            throw new \Exception('Could not set translation domain to '.$domain.' under /tr');
        // set codeset to use UTF-8 (optional)
        bind_textdomain_codeset($domain, "UTF-8");
    }

    /**
     * Set the system local.
     *
     * @param string $locale user provided locale
     * @return the local which is set
     */
    public static function setLocale($locale)
    {
        // set system setting
        if ($locale)
            $locale = setlocale(LC_ALL, $locale);
        // try extended search for possible locale
        if (!$locale)
            $locale = setlocale(LC_ALL, self::getLocales());
        // locale could not be set
        if (!$locale)
            return false;
        // set the locale in environment, too
        putenv('LC_ALL='.$locale);
        putenv('LANG='.$locale);
        // return used locale
        return $locale;
    }

    /**
     * Get specific locals to be used.
     *
     * If an additional locale is given this will be added on top of the
     * wishlist for the local setting.
     *
     * @param string $locale user provided locale
     * @return array list of possible locales
     */
    private static function getLocales($locale = null)
    {
        // analyze browser and store settings in session
        $sname = self::PREFIX.'locales';
        if (!isset($_SESSION[$sname]))
            $_SESSION[$sname] = self::detectLocales();
        // add specified local topmost
        if (isset($locale)) {
            // remove old settings of same entry
            array_diff($_SESSION[$sname], array($locale));
#            $_SESSION[$sname] = \array_merge(self::extendLocales(array($locale)), $_SESSION[$sname]);
            $list = self::extendLocales(array($locale));
            foreach($_SESSION[$sname] as $l)
                if (!in_array($l, $list))
                    $list[] = $l;
            $_SESSION[$sname] = $list;
        }
        return $_SESSION[$sname];
    }

    /**
     * Detect the accepted locales from client information.
     *
     * First the browser settings from HTTP header will be used and
     * alternatively the client's first level domain.
     *
     * @return array list of possible locales
     */
    private static function detectLocales()
    {
        $locales = array();
        // find user accept locales
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $lang) {
                $l = null;
                $q = null;
                $lang = array_map('trim', explode(';', $lang));
                if (isset($lang[1])) {
                    $l = strtolower($lang[0]);
                    $q = (float) str_replace('q=', '', $lang[1]);
                } else {
                    $l = strtolower($lang[0]);
                }
                $lang = explode('-', $l);
                // correct format of locale
                if (count($lang) == 1) $l = strtolower($lang[0]);
                else $l = \strtolower($lang[0]).'_'.strtoupper($lang[1]);
                $locales[$l] = isset($q) ? $q : 1000 - count($locales);
            }
        }
        // sort locales by q setting
        if (count($locales)) {
            arsort($locales, SORT_NUMERIC);
            $locales = \array_keys($locales);
        }
        // find user locale by domain name
        if (isset($_SERVER['REMOTE_HOST'])) {
            $locales[] = strtolower(
                end($h = explode('.', $_SERVER['REMOTE_HOST']))
            );
        }

        return self::extendLocales($locales);
    }

    private static $_defaultLocales = array(
        'en_US', 'en_GB','de_DE'
    );

    /**
     * Extend the list of locales by adding alternatives.
     *
     * This is done in 4 steps:
     * <ol>
     * <li>add the locale with utf encoding
     * <li>add the normal locale
     * <li>add specializations (extended with country...)
     * <li>add generalization (only language code)
     * </ol>
     *
     * The available system locales will be stored statically and in APC through
     * direct call.
     *
     * The list of supported system locales will be stored in APC under key
     * 'i18n_systemLocales'.
     *
     * @param array $locales list of locales
     * @return extended list of locales
     */
    private static function extendLocales(array $locales)
    {
        $extended = array();
        $lastLang = false;
        foreach ($locales as $l) {
            // add generalization of previous
            if ($lastLang !== false) {
                $lang = substr($l, 0, 2);
                if ($lang != $lastLang)
                    // also add the specialization of generalization
                    $extended[] = self::extendLocales(array($lastLang));
                $lastLang = false;
            }
            if (!in_array($l.'.utf8', $extended))
                $extended[] = $l.'.utf8';   // add with utf encoding
            if (!in_array($l, $extended))
                $extended[] = $l;           // normal
            if (strlen($l) == 2) {
                // specialization
                // use static variable as request cache
                static $systemLocales = null;
                // check in apc cache if not defined
                $cacheName = "i18n_systemLocales";
                if (!isset($systemLocales) && function_exists('apc_fetch'))
                    $systemLocales = apc_fetch($cacheName);
                // analyze, if not in cache, too
                if ($systemLocales === false || !isset($systemLocales)) {
                    // get system locales
                    ob_start();
                    passthru('locale -a');
                    $str = ob_get_contents();
                    ob_end_clean();
                    // order default first
                    $systemLocales = preg_split("/\\n/", trim($str));
                    $list = array();
                    // add matched locales first
                    foreach (self::$_defaultLocales as $default) {
                        $len = \strlen($default);
                        foreach ($systemLocales as $test)
                            if ($default == substr($test, 0, $len)
                                && !in_array($test, $list))
                                $list[] = $test;
                    }
                    // add the not matched locales
                    foreach ($systemLocales as $test)
                        if (!in_array($test, $list))
                            $list[] = $test;
                    $systemLocales = $list;
                    // store in static variable and cache
                    if (function_exists('apc_store'))
                        apc_store($cacheName, $systemLocales, 0);
                }
                foreach ($systemLocales as $test) {
                    if ($l == substr($test, 0, 2))
                        if (!in_array($test, $extended))
                            $extended[] = $test;
                }
            } else {
            $lastLang = substr($l, 0, 2);
            }
        }
        if ($lastLang !== false)
            // add generalization and spezialization of generalization from last
            $extended[] = self::extendLocales(array($lastLang));
        return $extended;
    }

}
