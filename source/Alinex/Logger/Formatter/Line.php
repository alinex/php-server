<?php
/**
 * @file
 * Formatter writing message as single line.
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
use Alinex\Template;

/**
 * Formatter writing message as single line.
 */
class Line extends Formatter
{

    public $formatString = '{message}';

    /**
     * Format the log line.
     *
     * @param  Message  $message Log message object
     * @return bool true on success
     */
    public function format(Message $message)
    {
        // create the available data object
        $values = array_merge(
            $message->data,
            array(
                'message' => $message->message,
                'context' => $message->context,
            )
        );
        // set the final structure
        $message->formatted = Template\Simple::run(
            $this->formatString, $values
        );
        return true;
    }

}