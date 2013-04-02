<?php
/**
 * @file
 * Get information about the system.
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
 * Get information about the system.
 *
 * Get a description of the operating system PHP is running on:
 *
 * - system.info - Contains all modes in the sequence "os host release version
 * machine".
 * - system.os - Operating system name. eg. FreeBSD.
 * - system.host - Host name. eg. localhost.example.com.
 * - system.release -  Release name. eg. 5.1.2-RELEASE.
 * - system.version - Version information. Varies a lot between operating systems.
 * - system.machine - Machine type. eg. i386.
 *
 * @note On some older UNIX platforms, it may not be able to determine the
 * current OS information in which case it will revert to displaying the OS PHP
 * was built on. This will only happen if your uname() library call either
 * doesn't exist or doesn't work.
 */
class System extends Provider
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
            $base['info'] = php_uname('a');
            $base['os'] = php_uname('s');
            $base['host'] = php_uname('n');
            $base['release'] = php_uname('r');
            $base['version'] = php_uname('v');
            $base['machine'] = php_uname('m');
            self::$_data = $base;
        }
        $data = self::$_data;
        $message->data['system'] = $data;
        return true;
    }
}