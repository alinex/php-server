<?php
/**
 * @file
 * Formatter storing messages as Array structure.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Logger\Formatter;

use Alinex\Logger\Message;
use Alinex\Logger\Formatter;

/**
 * Formatter storing messages as Array structure.
 *
 * Each log message will be added under the unix timestamp with milliseconds.
 */
class ArrayStructure extends Formatter
{

    /**
     * Create the log object structure.
     *
     * @param  Message  $message Log message object
     * @return bool true on success
     */
    public function format(Message $message)
    {
        // set the final structure
        $message->formatted = $message->data;
        return true;
    }

}