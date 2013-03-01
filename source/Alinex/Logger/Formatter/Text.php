<?php
/**
 * @file
 * Formatter writing message as simple text.
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
 * Formatter writing message as simple text.
 */
class Text extends Formatter
{
    /**
     * Default format
     */
    const COMMON = <<<'EOD'
{time.sec|date} {level.name|upper}: {message}.
At {code.file} at line {code.line}
EOD;
    
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
        $message->formatted = $formatted;
        return true;
    }

}