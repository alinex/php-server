<?php
/**
 * @file
 * Get information about calling method.
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
 * Get information about calling method.
 *
 * This will add information about the calling method:
 * - code.function - The current function name.
 * - code.line - The current line number.
 * - code.file - The current file name.
 * - code.class - The current class name.
 * - code.object - The current object.
 * - code.type - The current call type. If a method call "->" is returned.
 * If a static method call "::" is returned. If a function call nothing is
 * returned.
 * - code.args - If inside a function, this lists the functions arguments. If inside
 * an included file, this lists the included file name(s).
 *
 * @codeCoverageIgnore because backtrace not possible through phpunit
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
    protected $_withTrace = false;

    /**
     * @opydoc Provider::addTo()
     */
    function addTo(Message $message)
    {
        // get information
        $trace = debug_backtrace();
        $offset = 0;
        foreach ($trace as $entry) {
            ++$offset;
            // step through
            if (strpos($entry['class'], 'Logger') !== false
                || strpos($entry['class'], 'ErrorHandler') !== false) 
                continue;
            $message->data['code'] = $entry;
#            error_log($entry['file'].':'.$entry['line']);
#            continue;
            if ($this->_withTrace)
                array_slice($trace, $offset);
            break;
        }
        return true;
    }
}