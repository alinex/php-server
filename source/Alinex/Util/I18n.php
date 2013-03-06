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
    /**
     * Initialise the internationalization system.
     * 
     * This will include the tr() and trn() functions to be used.
     */
    static function init()
    {
        require_once 'tr.php';
    }

    /**
     * Only for testing.
     * @return string
     */
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
     * Current locale
     * @var string
     */
    private static $_locale = null;
    
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
            $locale = setlocale(LC_ALL, self::extendLocales($locale));
        // locale could not be set
        if (!$locale)
            return false;
        self::$_locale = $locale;
        // set the locale in environment, too
        putenv('LC_ALL='.$locale);
        putenv('LANG='.$locale);
        // return used locale
        return $locale;
    }

    /**
     * Extend the list of locales by getting alternatives.
     *
     * This is done in 3 steps:
     * <ol>
     * <li>add the locale with utf8 encoding
     * <li>add specializations (extended with country...)
     * <li>add generalization (only language code)
     * </ol>
     *
     * The modifier is not added on the possible tests.
     * 
     * @param string $locale locale to find alternatives
     * @param bool $onlyExtend this is used in continued calls to not 
     * generalize again
     * @return extended list of locales
     */
    private static function extendLocales($locale, $onlyExtend = false)
    {
        assert(is_string($locale));
        
        $extended = array();
        // analyse locale
        $part = array();
        if (!preg_match("/^(?P<lang>[a-z]{2,3})"              // language code
                       ."(?:_(?P<country>[A-Z]{2}))?"           // country code
                       ."(?:\.(?P<charset>[-A-Za-z0-9_]+))?"    // charset
                       ."(?:@(?P<modifier>[-A-Za-z0-9_]+))?$/",  // @ modifier
                       $locale, $part))
            return array(); // no POSIX style language
        // add alternatives
        if (isset($part['modifier'])) {
            if ($country) {
                if ($charset)
                    array_push($extended, "${lang}_$country.$charset@$modifier");
                array_push($extended, "${lang}_$country@$modifier");
            } elseif ($charset)
                array_push($extended, "${lang}.$charset@$modifier");
            array_push($extended, "$lang@$modifier");
        }
        if ($country) {
            if ($charset)
                array_push($extended, "${lang}_$country.$charset");
            array_push($extended, "${lang}_$country");
        } elseif ($charset)
            array_push($extended, "${lang}.$charset");
        array_push($extended, $lang);
        // return result
        return $extended;
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


}
