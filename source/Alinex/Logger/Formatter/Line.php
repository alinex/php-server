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
use Alinex\Util\ArrayStructure;

/**
 * Formatter writing message as single line.
 *
 * This formatter holds different templates in the $formatMap. Depending on the
 * defined variables in the message it will choose the first possible format
 * like the Text formatter.
 *
 * Any containing newlines within the message or values will be automatically
 * removed by spaces.
 */
class Line extends Formatter
{
    /**
     * Format mapping with neccessary variables.
     * @var array
     */
    public $formatMap = array(
        array(
            'vars' => array('file', 'line'),
            'format' => '{time.sec|date} {level.name|upper}: {message} in {file} on line {line}'
        ),
        array(
            'vars' => array('code.file', 'code.line'),
            'format' => '{time.sec|date} {level.name|upper}: {message} in {code.file} on line {code.line}'
        ),
        array(
            'vars' => array(),
            'format' => '{time.sec|date} {level.name|upper}: {message}'
        )
    );

    /**
     * Find the proper format depending on context variables.
     * @param  Message  $message Log message object
     * @return string format string to use
     */
    private function findFormat(Message $message)
    {
        foreach ($this->formatMap as $check) {
            $valid = true;
            foreach ($check['vars'] as $varname)
                if (!ArrayStructure::has($message->data, $varname, '.')) {
                    $valid = false;
                    break;
                }
            if ($valid)
                return $check['format'];
        }
    }

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
            $this->findFormat($message), $message->data
        );
        // replace all newlines with tab
        $message->formatted = preg_replace('/[\n\r]+/s', " ", $formatted);
        return true;
    }

}