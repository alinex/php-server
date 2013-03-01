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
 *
 * Any containing newlines within the message or values will be automatically
 * removed by spaces.
 */
class Line extends Formatter
{
    /**
     * Default format
     */
    const COMMON = '{time.sec|date} {level.name|upper}: {message}.';

    /**
     * Used format string to create message.
     * @var string
     */
    public $formatString = self::COMMON;

    /**
     * Format the log line.
     *
     * @param  Message  $message Log message object
     * @return bool true on success
     */
    public function format(Message $message)
    {
        // set the final structure
        $formatted = Template\Simple::run(
            $this->formatString, $message->data
        );
        // replace all newlines with spaces
        $message->formatted = preg_replace('/[\n\r]+/', ' ', $formatted);
        return true;
    }

}