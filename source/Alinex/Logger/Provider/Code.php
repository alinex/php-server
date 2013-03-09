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
 * 
 * This will add information about the calling method:
 * - function - The current function name.
 * - line - The current line number.
 * - file - The current file name.
 * - class - The current class name.
 * - object - The current object.
 * - type - The current call type. If a method call "->" is returned. 
 * If a static method call "::" is returned. If a function call nothing is 
 * returned.
 * - args - If inside a function, this lists the functions arguments. If inside
 * an included file, this lists the included file name(s). 
 * - trace - same information of the calling methods if required
 */
class Code extends Provider
{
    /**
     * Should the trace be included.
     * 
     * If not set only the last call before Logger will be added. If set to
     * true, also the back trace will be added as \c code.trace array.
     * @var bool
     */
    public $_withTrace = false;
    
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
        // get information
        $trace = debug_backtrace();
        $offset = 0;
        foreach ($trace as $entry) {
            ++$offset;
            // step through
            if (strpos($entry['class'], 'Logger') !== false) // within Logger
                continue;
            $message->data['code'] = $entry;
            if ($this->_withTrace)
                array_slice($trace, $offset);
            break;
        }
        return true;
    }
}