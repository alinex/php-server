<?php
/**
 * @file
 * Helper methods to work with http accesses.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Util;

use Alinex\Dictionary\Registry;
use Alinex\Validator;

/**
 * Helper methods to work with http accesses.
 *
 * @codeCoverageIgnore complicated without browser
 */
class Http
{
    /**
     * Lifetime in seconds to keep content.
     *
     * The content can be kept without checking the server again through
     * this period. That means that updates won't be available for all
     * users till the end of this period.
     */
    const DEFAULT_EXPIRE = 604800; // in seconds (one week)

    /**
     * @copydoc DEFAULT_EXPIRE
     * @registry
     */
    const REGISTRY_EXPIRE = 'http.expire';

    /**
     * no caching allowed anywhere
     */
    const HTTPCACHE_NONE = 'none';

    /**
     * only cacheable in the browser
     */
    const HTTPCACHE_PRIVATE = 'private';

    /**
     * caching is everythere allowed
     */
    const HTTPCACHE_PUBLIC = 'public';

    /**
     * Add Validators to the Registry.
     *
     * This method is called from the registry instantiation to add the
     * validators for this class.
     *
     * This is neccessary for all static classes, other classes will do this on
     * her own in the constructor.
     *
     * @param Engine $registry to add the validators to
     */
    public static function addRegistryValidators(Registry $registry)
    {
        assert(isset($registry));

        if ($registry->validatorCheck()) {
            if (!$registry->validatorHas(self::REGISTRY_EXPIRE))
                $registry->validatorSet(
                    self::REGISTRY_EXPIRE, 'Type::integer',
                    array(
                        'unsigned' => true,
                        'description' => tr(
                            __NAMESPACE__,
                            'Default lifetime in seconds to keep content cached.'
                        )
                    )
                );
        }
    }

    /**
     * Send http header to client.
     *
     * The following headers will be sent:
     * - \c content-type with charset
     * - \c expires
     * - \c cache-control
     *
     * The cache can be set as:
     * - \c HTTPCACHE_NONE - no caching allowed anywhere
     * - \c HTTPCACHE_PRIVATE - only cacheable in the browser
     * - \c HTTPCACHE_PUBLIC - caching is everythere allowed
     *
     * If a file allows caching it should be checked, that it will anytime
     * return the same content to the cache. If the content depends on specific
     * settings they should change the url.
     * The best way is to add an additional path after the script file. Also set
     * parameters into the script path to be better cache supported.
     *
     * The default header will look like:
     * @code
     * Content-type: text/html; Charset=utf-8
     * Expires: Wed 23 Jan 1974 14:14:00 GMT
     * Cache-Control: no-store, no-cache, must-revalidate
     * Cache-Control: pre-check=0, post-check=0
     * @endcode
     * And with private caching allowed:
     * @code
     * Content-type: text/html; Charset=utf-8
     * Expires: Wed 17 May 2008 09:28:51 GMT
     * Cache-Control: private, max-age=604800
     * Cache-Control: pre-check=604800
     * @endcode
     *
     * In debug mode (Testserver) no caching will be allowed.
     *
     * @param string $contentType HTTP content-type string
     * @param string $cache one of 'none', 'private' or 'public'
     * @param int $expire Lifetime in seconds to keep content.
     * @return void
     */
    public static function header($contentType = 'text/html',
        $cache = self::HTTPCACHE_NONE, $expire = null)
    {
        assert(
            Validator::is(
                $contentType, null, 'Type::string',
                array('match' => '#\w+/\w+#')
            )
        );
        assert(
            $cache == self::HTTPCACHE_NONE
            || $cache == self::HTTPCACHE_PRIVATE
            || $cache == self::HTTPCACHE_PUBLIC
        );
        assert(
            Validator::is(
                $expire, null, 'Type::integer',
                array('unsigned' => true)
            )
        );

        // get default expiration
        if (!isset($expire)) {
            $registry = Registry::getInstance();
            $expire = isset($registry)
                && $registry->has(self::REGISTRY_EXPIRE)
                ? $registry->get(self::REGISTRY_EXPIRE)
                : self::DEFAULT_EXPIRE;
        }
        header('Content-type: ' . $contentType . '; Charset=utf-8');
        if ($cache == self::HTTPCACHE_PRIVATE
            || $cache == self::HTTPCACHE_PUBLIC ) {
            header('Expires: '
                .gmdate("D, d M Y H:i:s", time() + $expire).' GMT');
            header('Cache-Control: ' . $cache . ', max-age=' . $expire);
            header('Cache-Control: pre-check=' . $expire, false);
        } else if ($cache == self::HTTPCACHE_NONE) {
            // expired in the past
            header('Expires: Wed 23 Jan 1974 14:14:00 GMT');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Cache-Control: pre-check=0, post-check=0', false);
        } else {
            die("Wrong cache method: '$cache'");
        }
    }

    /**
     * Check if 304 response can be send.
     *
     * This depends on the last modification of the data requested and the etag parameter.
     * The headers \c Last-Modified and \c ETag will be set and
     * if the response contains the \c if-modified-since or
     * \c if-none-match headers it will check whether a '304 not modified' response
     * can be send or the content has to be delivered.
     *
     * On 304 answers the processing will stop here.
     *
     * @code
     * // add last-modified and etag header with locale
     * Http::check304(filemtime(__FILE__), __FILE__ . implode('', Core::locale()));
     * @endcode
     *
     * @param time $lastModified Timestamp
     * @param string $etagParam Additional parameters used for etag calculation.
     * This should contain all parameters which would lead to different output
     * like locale setting.
     * @return void
     */
    public static function check304($lastModified, $etagParam = '')
    {
        assert(
            Validator::is(
                $lastModified, null, 'Type::integer',
                array('unsigned' => true)
            )
        );
        assert(is_string($etagParam));

        $eTag = 'ax-'.dechex(crc32($etagParam.$lastModified));
        header('Last-Modified: '
            .gmstrftime("%a, %d %b %Y %T %Z",$lastModified));
        header('ETag: "'.$eTag.'"');
        if ((isset($_SERVER['IF_MODIFIED_SINCE'])
                && strtotime($_SERVER['IF_MODIFIED_SINCE']) == $lastModified)
            || (isset($_SERVER['HTTP_IF_NONE_MATCH'])
                && $_SERVER['HTTP_IF_NONE_MATCH'] == $eTag)) {
            header('HTTP/1.0 304 Not Modified');
            exit();
        }
    }

    /**
     * Get the client ip address if possible.
     *
     * @return string ipv4 client address
     */
    static function determineIP()
    {
        if (checkIP($_SERVER["HTTP_CLIENT_IP"])) {
            return $_SERVER["HTTP_CLIENT_IP"];
        }
        foreach (explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]) as $ip) {
            if (self::checkIP(trim($ip))) {
                return $ip;
            }
        }
        if (checkIP($_SERVER["HTTP_X_FORWARDED"])) {
            return $_SERVER["HTTP_X_FORWARDED"];
        } elseif (self::checkIP($_SERVER["HTTP_X_CLUSTER_CLIENT_IP"])) {
            return $_SERVER["HTTP_X_CLUSTER_CLIENT_IP"];
        } elseif (self::checkIP($_SERVER["HTTP_FORWARDED_FOR"])) {
            return $_SERVER["HTTP_FORWARDED_FOR"];
        } elseif (self::checkIP($_SERVER["HTTP_FORWARDED"])) {
            return $_SERVER["HTTP_FORWARDED"];
        } else {
            return $_SERVER["REMOTE_ADDR"];
        }
    }

    /**
     * List of ip addresses of private ranges.
     * @var array
     */
    private static $_privateips = array(
        array('0.0.0.0','2.255.255.255'),
        array('10.0.0.0','10.255.255.255'),
        array('127.0.0.0','127.255.255.255'),
        array('169.254.0.0','169.254.255.255'),
        array('172.16.0.0','172.31.255.255'),
        array('192.0.2.0','192.0.2.255'),
        array('192.168.0.0','192.168.255.255'),
        array('255.255.255.0','255.255.255.255')
    );

    /**
     * Check if given ip is a possibly correct public ip.
     *
     * @param string $ip address to check
     * @return boolean true if address is possible
     */
    private static function checkIP($ip)
    {
        if (!empty($ip) && ip2long($ip)!=-1 && ip2long($ip)!=false) {
            foreach (self::$_privateips as $r) {
                $min = ip2long($r[0]);
                $max = ip2long($r[1]);
                if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max))
                    return false;
            }
            return true;
        } else {
            return false;
        }
    }

}
