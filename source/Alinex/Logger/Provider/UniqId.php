<?php
/**
 * @file
 * Get an unique id.
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
 * Get an unique id.
 *
 * This will add information about the memory:
 * - uniq.id - A globally unique id
 */
class UniqId extends Provider
{
    /**
     * Cache for the static process information.
     * @var array
     */
    private static $_system = null;

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
        if (!isset(self::$_system))
            self::$_system = php_uname('n').':'.getmypid().':';
        $message->data['uniq.id'] = uniqid(self::$_system, true);
        return true;
    }
}