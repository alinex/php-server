<?php

/**
 * @file
 * Simple template class.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Template;

use Alinex\Util\ArrayStructure;

/**
 * Simple template class.
 *
 * This may be used for short text phrases including some variables.
 *
 * @attention
 * It isn't a full fledged template engine to create page templates or other
 * complex documents.
 *
 * The syntax is really simple, you may add variables in the text using curly
 * braces:
 * @verbatim
 * Found {num} errors!
 * @endverbatim
 *
 * Additional modifiers, added using pipe as separator, may help in formatting
 * the value. They may also contain some options:
 * @verbatim
 * Files {start|printf %03d} till {end|printf %03d} are broken!
 * @endverbatim
 *
 * Therefore the following modifiers are possible:
 * - trim - strip whitespaces from start and end
 * - dump &lt;depth&gt; - dump the variable
 * - printf &lt;format&gt; - print a formated value see
 * http://www.php.net/manual/en/function.sprintf.php
 */
class Simple
{
    /**
     * Execute a simple template syntax with given values.
     * @param string $text simple template syntax string
     * @param array $values named array with values
     * @return string resulting text
     */
    public static function run($text, array $values)
    {
        assert(is_string($text));

        $simple = new self($values);
        return $simple->replace($text);
    }

    /**
     * Named array with values.
     * @var array
     */
    private $_values = null;

    /**
     * Create a new object and store values.
     * @param array $values named array with values
     */
    private function __construct(array $values)
    {
        $this->_values = $values;
    }

    /**
     * Replace the simple template syntax within the text.
     * @param string $text to be replaced
     * @return string resulting text
     */
    private function replace($text)
    {
        return preg_replace_callback(
            '/\{(\s?\w[^}]+)\}/',
            array($this, 'replaceVariable'),
            $text
        );
    }

    /**
     * Replace the given simple template variable.
     *
     * @param array $matches matchers from the template search:
     * - 0 - complete syntax
     * - 1 - variable name with optional modifiers
     * @return string replacement for this part
     */
    private function replaceVariable($matches)
    {
        $parts = explode('|', $matches[1]);
        $variable = array_shift($parts);
        $value = ArrayStructure::get($this->_values, $variable);
        foreach($parts as $modifier)
            $value = $this->modifier($value, $modifier);
        return $value;
    }

    /**
     * List of possible modifiers with callbacks and other options.
     * @var array
     */
    private $_modifier = array(
        'trim' => array('call' => 'trim'),
        'dump' => array('call' => array('\Alinex\Util\String', 'dump')),
        'printf' => array('call' => 'sprintf', 'reverse' => true),
    );

    /**
     * Run the modifier over the value
     * @param mixed $value value to be modified
     * @param string $modifier name of the modifier
     * @return mixed modified value
     */
    private function modifier($value, $modifier)
    {
        $parts = explode(' ', $modifier, 2);
        $function = $parts[0];
        // by default each modifier will be called with value and modifier
        $param = array($value);
        if (isset($parts[1]))
            $param[] = $parts[1];
        if (isset($this->_modifier[$function]['reverse'])
            && $this->_modifier[$function]['reverse'])
            $param = array_reverse($param);
        return call_user_func_array($this->_modifier[$function]['call'], $param);
    }
}
