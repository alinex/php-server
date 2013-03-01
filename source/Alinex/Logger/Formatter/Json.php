<?php
/**
 * @file
 * Formatter storing messages as Json structure.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Logger\Formatter;

use Alinex\Logger\Formatter;
use Alinex\Logger\Message;

/**
 * Formatter storing messages as Json structure.
 *
 * Each log message will be added under the unix timestamp with milliseconds.
 */
class Json extends Formatter
{

    /**
     * Create the JSON code to log.
     *
     * @param  Message  $message Log message object
     * @return bool true on success
     */
    public function format(Message $message)
    {
        // set the final structure
        $message->formatted = json_encode($message->data);
        return true;
    }

}