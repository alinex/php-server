<?php
/**
 * @file
 * Get information about the PHP process.
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
 * Get information about the PHP process.
 *
 * This will add information about the memory:
 * - process.pid - The process's id
 * - process.uid - The process's user id
 * - process.gid - The process's group id
 * - process.limit - The memory limit set in bytes
 * - process.usage - The current memory usage in bytes
 * - process.peak - The peak usage of memory in bytes
 */
class Process extends Provider
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
            $base['pid'] = getmypid();
            $base['uid'] = getmyuid();
            $base['gid'] = getmygid();
            $base['limit'] = ini_get('memory_limit');
            self::$_data = $base;
        }
        $data = self::$_data;
        $data['usage'] = memory_get_usage(true);
        $data['peak'] = memory_get_peak_usage(true);
        $message->data['process'] = $data;
        return true;
    }
}