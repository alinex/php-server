<?php
/**
 * @file
 * Abstract formatter to create log output.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Logger;

/**
 * Abstract formatter to create log output.
 */
abstract class Formatter
{
    /**
     * Format the log message.
     *
     * This class will create a formated message version for the handler to
     * directly output later. The formatted message is stored in the message
     * object.
     *
     * @param  Message  $message Log message object
     * @return bool true on success
     */
    abstract public function format(Message $message);
}