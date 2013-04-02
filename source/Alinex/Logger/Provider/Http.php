<?php
/**
 * @file
 * Get information about the http access.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Logger\Provider;

use Alinex\Logger\Message;
use Alinex\Logger\Provider;

/**
 * Get information about the http access.
 *
 * This will add information about the http access if available:
 * - http.address - The IP address of the server under which the current script
 * is executing.
 * - http.vhost -The name of the server host under which the current script is
 * executing. If the script is running on a virtual host, this will be the value
 * defined for that virtual host.
 * - http.protocol - Name and revision of the information protocol via which the
 * page was requested; i.e. 'HTTP/1.0';
 * - http.method - Which request method was used to access the page; i.e. 'GET',
 * 'HEAD', 'POST', 'PUT'.
 * - http.time - The timestamp of the start of the request.
 * - http.query - The query string, if any, via which the page was accessed.
 * - http.root - The document root directory under which the current script is
 * executing, as defined in the server's configuration file.
 * - http.accept - Contents of the Accept: header from the current request, if
 * there is one.
 * - http.referer - The address of the page (if any) which referred the user
 * agent to the current page. This is set by the user agent. Not all user agents
 * will set this, and some provide the ability to modify HTTP_REFERER as a
 * feature. In short, it cannot really be trusted.
 * - http.agent - Contents of the User-Agent: header from the current request,
 * if there is one. This is a string denoting the user agent being which is
 * accessing the page. A typical example is: Mozilla/4.5 [en] (X11; U; Linux
 * 2.2.9 i586). Among other things, you can use this value with get_browser()
 * to tailor your page's output to the capabilities of the user agent.
 * - http.client - The client IP as good as it can be detected. This may be the
 * given ip address, the forwarded for address or the address of the proxy.
 * - http.script - Contains the current script's path. This is useful for pages
 * which need to point to themselves.
 * - http.uri - The URI which was given in order to access this page; for
 * instance, '/index.html'.
 * - http.path - Contains any client-provided pathname information trailing the
 * actual script filename but preceding the query string, if available. For
 * instance, if the current script was accessed via the URL
 * http://www.example.com/php/path_info.php/some/stuff?foo=bar, then
 * $_SERVER['PATH_INFO'] would contain /some/stuff.
 * - cookie.&lt;name&gt; - All cookies which are passed to the current script.
 */
class Http extends Provider
{
    /**
     * Cache for the static process information.
     * @var array
     */
    private static $_data = null;

    /**
     * Get additional information.
     *
     * This class will retrieve additional information to be added to the
     * Message object. They may be used later to generate the message in the
     * Formatter.
     *
     * @param  Message  $message Log message object
     * @return bool true on success
     */
    function addTo(Message $message)
    {
        if (!isset(self::$_data)) {
            $base = array();
            if (isset($_SERVER['SERVER_ADDR']))
                $base['address'] = $_SERVER['SERVER_NAME'];
            if (isset($_SERVER['DOCUMENT_ROOT']))
                $base['root'] = $_SERVER['DOCUMENT_ROOT'];
            self::$_data = $base;
        }
        $data = self::$_data;
        if (isset($_SERVER['SERVER_ADDR']))
            $data['vhost'] = $_SERVER['SERVER_NAME'];
        if (isset($_SERVER['SERVER_PROTOCOL']))
            $data['protocol'] = $_SERVER['SERVER_PROTOCOL'];
        if (isset($_SERVER['SERVER_METHOD']))
            $data['method'] = $_SERVER['SERVER_METHOD'];
        if (isset($_SERVER['REQUEST_TIME']))
            $data['time'] = $_SERVER['REQUEST_TIME'];
        if (isset($_SERVER['QUERY_STRING']))
            $data['query'] = $_SERVER['QUERY_STRING'];
        if (isset($_SERVER['HTTP_ACCEPT']))
            $data['accept'] = $_SERVER['HTTP_ACCEPT'];
        if (isset($_SERVER['HTTP_REFERER']))
            $data['referer'] = $_SERVER['HTTP_REFERER'];
        if (isset($_SERVER['HTTP_USER_AGENT']))
            $data['agent'] = $_SERVER['HTTP_USER_AGENT'];
        if (isset($_SERVER['SCRIPT_NAME']))
            $data['script'] = $_SERVER['SCRIPT_NAME'];
        if (isset($_SERVER['PATH_INFO']))
            $data['path'] = $_SERVER['PATH_INFO'];
        if (isset($_SERVER['REQUEST_URI']))
            $data['uri'] = $_SERVER['REQUEST_URI'];
        $data['client'] = \Alinex\Util\Http::determineIP();
        // add cookies
        if (isset($_COOKIE)) {
            $data['cookie'] = array();
            foreach ($_COOKIE as $name => $value)
                $data['cookie'][$name] = $value;
        }
        $message->data['http'] = $data;
        return true;
    }
}