<?php
/**
 * @file
 * Get information about the PHP process memory.
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
 * Get information about the PHP process memory.
 *
 * This will add information about the memory:
 * - phpmemory.limit - The memory limit set in bytes
 * - phpmemory.usage - The current memory usage in bytes
 * - phpmemory.peak - The peak usage of memory in bytes
 */
class PhpMemory extends Provider
{
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
        $memory = array();
        $memory['limit'] = ini_get('memory_limit');
        $memory['usage'] = memory_get_usage(true);
        $memory['peak'] = memory_get_peak_usage(true);
        $message->data['phpmemory'] = $memory;
        return true;
    }
}