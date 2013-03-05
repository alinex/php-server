<?php
/**
 * @file
 * Abstract provider to get additional information for logging.
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
 * Abstract provider to get additional information for logging.
 */
class Code extends Provider
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
        $code = arra();
        // get information
        foreach (debug_backtrace() as $trace) {
            
        }
        // store results
        $message->data['code'] = $code;
        return true;
    }
}