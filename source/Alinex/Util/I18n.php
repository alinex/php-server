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

use Alinex\Util\String;
use Alinex\Dictionary\Registry;
use Alinex\Template;

/**
 * Internationalization and translation helper methods.
 *
 * a complete overview can be found in the architecture description for
 * @ref codeI18n.
 *
 * Using emulateGettext(true) you may activate the gettext alternative
 * implementation also then  the extension is instaslled.
 *
 * The setLocal() method may be used to explicitly set a specific locale.
 *
 * The tr() and trn() methods can be used or their respective functions.
 */
class I18n
{
    /**
     * Name in the session to store selected locale
     * @session
     */
    const SESSION_LOCALE = 'alinex.i18n.locale';

    /**
     * Name in the registry to store possible locales.
     * If this entry is found only the locales listed here and included in the
     * system are possible to use.
     * @registry
     */
    const REGISTRY_LOCALES = 'i18n.locales';

    /**
     * List of languages with popular country codes.
     * This will be used for extending the locales to it's possible full setting
     * to a find a corresponding system local for installed languages.
     * @var array
     */
    private static $_languagesToCountry = array(
        'en' => array('US', 'GB', 'AU'),
        'de' => array('AT','CH')
    );

    /**
     * Flag defining if the system will emulate gettext.
     * If this is set to true the gettext functions will be emulated using
     * php methods which directly read from the *.mo files.
     * If set to NULL the translation system has not been initialized correctly.
     * @var bool
     */
    private static $_emulation = null;

    /**
     * Only for testing.
     * @return string
     */
    static function test()
    {
        return tr(__NAMESPACE__, "Test entry");
    }

    /**
     * Specify if gettext emulation should be used.
     *
     * If nothing specified gettext will be used if the extension is installed.
     * Only if the installed extension should not be used this method can switch
     * on the emulation.
     * @param bool $flag true to use emulation althouight gettext is installed
     * @return bool flag if emulation is active or not
     */
    static function emulateGettext($flag)
    {
        assert(is_bool($flag));

        return self::setEmulation(!function_exists('gettext') || $flag == true);
    }

    private static function setEmulation($flag)
    {
        self::$_emulation = $flag;
        if ($flag) {
            require_once __DIR__.'/../../vnd/php-gettext/streams.php';
            require_once __DIR__.'/../../vnd/php-gettext/gettext.php';
        }
        return $flag;
    }
    /**
     * Initialise the internationalization system.
     *
     * This will include the tr() and trn() functions to be used. If not called
     * explicitly it will be called before the first use. Althought it is better
     * behaviour to call this because it will also set the locale for some php
     * internal functions.
     */
    static function init()
    {
        require_once 'tr.php';
        // check for emulation of gettext
        if (!function_exists('gettext'))
            self::setEmulation(true);
        // set the locale
        self::setLocale();
    }

    /**
     * Set the system locale.
     *
     * If no locale given it will be autodetected.
     *
     * @param string|array $locales user provided locale
     * @return the local which is set
     */
    public static function setLocale($locales = null)
    {
        $set = false;
        if (is_string($locales))
            $locales = array($locales);
        // check if already selected in session
        if (!isset($locales) && isset($_SESSION[self::SESSION_LOCALE]))
            $set = setlocale(LC_ALL, $_SESSION[self::SESSION_LOCALE]);
        // if nothing given auto detect the local
        if (!$set && !isset($locales))
            return self::setLocale(self::localeDetect());
        // find optimal locale
        if (!$set) {
            // generalize and filter for alinex
            $locales = self::localeFilter(self::localeExtend($locales));
            // try extended list
            if ($locales)
                $set = setlocale(LC_ALL, $locales);
            // try to use earlier set version
            if (isset($_SESSION[self::SESSION_LOCALE]))
                $set = setlocale(LC_ALL, $_SESSION[self::SESSION_LOCALE]);
            // locale could not be set
            if (!$set)
                return false;
            // store in session
            $_SESSION[self::SESSION_LOCALE] = $set;
        }
        // set the locale in environment, too
        putenv('LC_ALL='.$set);
        putenv('LANG='.$set);
        putenv('LANGUAGE='.$set);
        // return used locale
        return $set;
    }

    /**
     * Detect the accepted locales from client information.
     *
     * First the browser settings from HTTP header will be used and
     * alternatively the client's top level domain.
     *
     * @return array list of possible locales
     */
    private static function localeDetect()
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
            $locales[] = strtolower(end(explode('.',$_SERVER['REMOTE_HOST'])));
        }
        return self::localeExtend($locales);
    }

    /**
     * Extend the given locale names.
     *
     * This is done in two different steps:
     * - generalization list upper locales till only language code
     * - specialization to country code and charset
     *
     * @param array $locales user provided locales
     * @return array list of possible locales
     */
    private static function localeExtend($locales)
    {
        $lang = array(); // simplest entries using ll
        $cont = array(); // advanced entries using ll_CC
        $cset = array(); // full entries using ll_CC.charset
        foreach ($locales as $check) {
            $part = null;
            if (!preg_match(
                "/^(?P<lang>[a-z]{2,3})"                 // language code
                ."(?:_(?P<country>[A-Z]{2}))?"           // country code
                ."(?:\.(?P<charset>[-A-Za-z0-9_]+))?"    // charset
                ."(?:@(?P<modifier>[-A-Za-z0-9_]+))?$/", // @ modifier
                $check, $part
            )) continue; // no POSIX style language
            if (isset($part['country'])) {
                if (isset($part['charset'])) {
                    $cset[] = $check;
                    // specialize
                    if ($part['charset'] != 'utf8')
                        $cset[] = $part['lang'].'_'.$part['country'].'.utf8';
                    // generalize
                    $cont[] = $part['lang'].'_'.$part['country'];
                    $lang[] = $part['lang'];
                } else {
                    $cont[] = $check;
                    // specialize
                    $cset[] = $part['lang'].'_'.$part['country'].'.utf8';
                    // generalize
                    $lang[] = $part['lang'];
                }
            } else {
                $lang[] = $check;
                // specialize
                $cset[] = $part['lang'].'_'.strtoupper($part['lang']).'.utf8';
                $cont[] = $part['lang'].'_'.strtoupper($part['lang']);
                // extend by well known country codes
                if (isset(self::$_languagesToCountry[$part['lang']])) {
                    foreach (self::$_languagesToCountry[$part['lang']] as $c) {
                        $cset[] = $part['lang'].'_'.$c.'.utf8';
                        $cont[] = $part['lang'].'_'.$c;
                    }
                }
            }
        }
        return array_merge($cset, $cont, $lang);
    }

    /**
     * Filter out all locales not supported by alinex.
     *
     * @param array $locales user provided locales
     * @return array list of possible locales
     */
    private static function localeFilter($locales)
    {
        $ok = array();
        if (!$locales)
            return false;
        foreach ($locales as $locale)
            foreach (self::localesAlinex() as $check)
                if (String::startsWith($locale, $check))
                    $ok[] = $locale;
        return array_unique($ok);
    }

    /**
     * Get a list of all locales supported by alinex.
     *
     * This is done checking the translation files and the registry. Therefore
     * it is possible to prevent installed locales from usage.
     *
     * @return array list of locales
     */
    private static function localesAlinex()
    {
        // get from files
        $locales = scandir(__DIR__.'/../../tr');
        $registry = Registry::getInstance();
        if ($registry->has(self::REGISTRY_LOCALES)) {
            // filter with registry setting
            $locales = array_intersect(
                $locales,
                $registry->get(self::REGISTRY_LOCALES)
            );
        }
        usort(
            $locales, function($a, $b)
            {
                return strlen($b) - strlen($a);
            }
        );
        return $locales;
    }

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
    static function tr($namespace, $msgid, array $params = array())
    {
        // domain has to be set
        assert(is_string($namespace));
        // only strings may be translated
        assert(is_string($msgid));

        self::setDomain($namespace);
        // get the message
        if (self::$_emulation)
            $trans = isset(self::$_reader)
                // emulated gettext
                ? self::$_reader->translate($msgid)
                // fallback keep english
                : $msgid;
        else
            // call native php function
            $trans = gettext($msgid); // call native php function
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
    static function trn(
        $namespace, $msgSingular, $msgPlural, $num, array $params = array()
    )
    {
        // domain has to be set
        assert(is_string($namespace));
        // only strings may be translated
        assert(is_string($msgSingular));
        // only strings may be translated
        assert(is_string($msgPlural));
        // have to be a positive integer
        assert(is_numeric($num) && $num >= 0);

        self::setDomain($namespace);
        if (self::$_emulation)
            $trans = isset(self::$_reader)
                // emulated gettext
                ? self::$_reader->ngettext($msgSingular, $msgPlural, $num)
                // fallback keep english
                : $num==1 ? $msgSingular : $msgPlural;
        else
            // call native php function
            $trans = ngettext($msgSingular, $msgPlural, $num);
        // replace variables and return
        return Template\Simple::run($trans, $params);
    }

    /**
     * Domain name which is currently active.
     * @var string
     */
    private $_domain = null;
    
    /**
     * Set to active reader if emulation is active.
     * @var \FileReader
     */
    private $_reader = null;

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
     * @return string domain name
     * @throws \Exception if given domain was not set
     */
    private static function setDomain($namespace)
    {
        // check for initialization
        if (!isset(self::$_emulation))
            self::init();
        // get top namespace
        list($domain) = explode('\\', $namespace, 2);
        // check if gettext will be used
        if (self::$_emulation) {
            if ($domain == self::$_domain)
                return;
            $locale = setlocale(0);
            $file = __DIR__.'/../../../tr/'.$locale.'/'.$domain.'.mo';
            if (!file_exists($file)) {
                list($locale) = explode('.', $locale, 2);
                $file = __DIR__.'/../../../tr/'.$locale.'/'.$domain.'.mo';
                if (!file_exists($file)) {
                    list($locale) = explode('_', $locale, 2);
                    $file = __DIR__.'/../../../tr/'.$locale.'/'.$domain.'.mo';
                    if (!file_exists($file))
                        throw new \Exception('Could not bind translation directory under /tr');
                }
            }
            self::$_reader = new \FileReader($file);
            return $domain;
        }
        if (textdomain(null) == $domain)
            return;
        // closing slash for windows neccessary
        if (!bindtextdomain($domain, __DIR__.'/../../tr/'))
            throw new \Exception('Could not bind translation directory under '.__DIR__.'/../../../tr/');
        // set the dopmain
        if (!textdomain($domain) == $domain)
            throw new \Exception('Could not set translation domain to '.$domain.' under /tr');
        // set codeset to use UTF-8 (optional)
        bind_textdomain_codeset($domain, "UTF-8");
        return $domain;
    }
}
