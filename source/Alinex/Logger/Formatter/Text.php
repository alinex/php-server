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
use Alinex\Util\ArrayStructure;

/**
 * Formatter writing message as simple text.
 *
 * This formatter holds different templates in the $formatMap. Depending on the
 * defined variables in the message it will choose the first possible format.
 *
 * Through this the formatter will automatically add information if it is
 * available. The formats may be changed here for an handler or a specific
 * format class may be written.
 */
class Text extends Formatter
{
    /**
     * Format mapping with neccessary variables.
     * @var array
     */
    public $formatMap = array(
        array(
            'vars' => array('file', 'line'),
            'format' => <<<'EOD'
{time.sec|date} {level.name|upper}: {message}.
In {file} on line {line} {%if trace}called through:
{trace}{%endif}
EOD
        ),
        array(
            'vars' => array('timing', 'params'),
            'format' => <<<'EOD'
{time.sec|date} {level.name|upper}: {message} (in {timing|printf %.3f}s).{%if paramas}
Using parameters: {params}{%endif}
EOD
        ),
        array(
            'vars' => array('timing'),
            'format' => <<<'EOD'
{time.sec|date} {level.name|upper}: {message} (in {timing}ms).
EOD
        ),
        array(
            'vars' => array('code.file', 'code.line'),
            'format' => <<<'EOD'
{time.sec|date} {level.name|upper}: {message}.
In {code.file} on line {code.line}
EOD
        ),
        array(
            'vars' => array(),
            'format' => <<<'EOD'
{time.sec|date} {level.name|upper}: {message}.
EOD
        )
    );

    /**
     * Use <br /> tags instead of newlines
     * @var bool
     */
    private $_br = false;

    /**
     * Use <br /> tags instead of newlines
     * @param bool $br Use <br /> tags instead of newlines
     */
    public function useBrTags($br)
    {
        assert(is_bool($br));

        $this->_br = $br;
    }

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
        // replace all newlines with spaces
        $message->formatted = $this->_br
            ? nl2br($formatted)
            : $formatted;
        return true;
    }

}