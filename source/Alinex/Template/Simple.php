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
 * complex documents. And it misses control structures.
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
 * Optional variables will be replaced with empty string if not existing. This
 * is done by adding a '?' at the end of the variable name:
 * @verbatim
 * Files {start?} till {end|printf %03d} are broken!
 * @endverbatim
 *
 * Therefore the following modifiers are possible:
 * - trim - strip whitespaces from start and end
 * - dump [&lt;depth&gt;] - dump the variable
 * - printf &lt;format&gt; - print a formated value see
 * http://www.php.net/manual/en/function.sprintf.php
 * - date &lt;format&gt; - output date using format specified under
 * http://www.php.net/manual/en/function.date.php the constants under
 * http://www.php.net/manual/en/class.datetime.php#datetime.constants.types
 * are also possible giving only the last part like 'RFC822'.
 * - upper - convert to upper case
 * - lower - convert to lower case
 * - timerange [&lt;shorten?&gt;] - output seconds human readable if short=true
 * not with the exact time but the most pregnant part
 * - unit &lt;unit&gt; [&lt;decimals&gt;] [&lt;long form?&gt;] - output numeric
 * value using given unit with specified decimals precsision and may be in long
 * form
 * - unitbinary &lt;unit&gt; [&lt;decimals&gt;] [&lt;long form?&gt;] - output numeric
 * value using given unit with specified decimals precsision and may be in long
 * form
 */
class Simple
{
    /**
     * Execute a simple template syntax with given values.
     * @param string $text simple template syntax string
     * @param array $values named array with values
     * @return string resulting text
     */
    public static function run($text, array $values = null)
    {
        assert(is_string($text));

        $simple = new self($values);
        return $simple->replace($text);
    }

    /**
     * Named array with values.
     * @var array
     */
    private $_values = array();

    /**
     * Create a new object and store values.
     * @param array $values named array with values
     */
    private function __construct(array $values = null)
    {
        if (isset($values))
            $this->_values = $values;
    }

    /**
     * Replace the simple template syntax within the text.
     * @param string $text to be replaced
     * @return string resulting text
     */
    private function replace($text)
    {
        assert(is_string($text));

        return preg_replace_callback(
            '/\{(\s?\w[^}]+?)\}/',
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
    private function replaceVariable(array $matches)
    {
        $parts = explode('|', $matches[1]);
        $variable = array_shift($parts);
        // check if value has to be present
        $optional = false;
        if (substr($variable, -1) == '?') {
            $variable = substr($variable, 0, -1);
            $optional = true;
        }
        $value = ArrayStructure::get($this->_values, $variable, '.');
        if (!$optional && !isset($value))
            return $matches[0]; // keep variable if not found
        foreach($parts as $modifier)
            $value = $this->modifier($value, $modifier);
        return $value;
    }

    /**
     * List of possible modifiers with callbacks and other options.
     * - call - contains the real callback
     * - reverse=true - will send the value as last (2nd) value
     * @var array
     */
    private $_modifier = array(
        'trim' => array('call' => 'trim'),
        'dump' => array('call' => array('\Alinex\Util\String', 'dump')),
        'printf' => array('call' => 'sprintf', 'reverse' => true),
        'upper' => array('call' => 'strtoupper'),
        'lower' => array('call' => 'strtolower'),
        'timerange' => array(
            'call' => array('\Alinex\Util\Number', 'toTimerange')
        ),
        'unit' => array(
            'call' => array('\Alinex\Util\Number', 'toUnit')
        ),
        'binaryunit' => array(
            'call' => array('\Alinex\Util\Number', 'toBinaryUnit')
        ),
    );

    /**
     * Run the modifier over the value
     * @param mixed $value value to be modified
     * @param string $modifier name of the modifier
     * @return mixed modified value
     */
    private function modifier($value, $modifier)
    {
        assert(is_string($modifier));

        $parts = explode(' ', $modifier, 2);
        $function = $parts[0];
        // by default each modifier will be called with value and modifier
        $param = array($value);
        if (isset($parts[1]))
            $param[] = $parts[1];
        // call method
        if (method_exists($this, 'call_'.$function)) {
            return call_user_func_array(
                array($this, 'call_'.$function), $param
            );
        }
        // use definitions
        if (isset($this->_modifier[$function])) {
            if (isset($this->_modifier[$function]['reverse'])
                && $this->_modifier[$function]['reverse'])
                $param = array_reverse($param);
            return call_user_func_array(
                $this->_modifier[$function]['call'], $param
            );
        }
        // no definition found
        throw new \BadMethodCallException(
            tr(
                __NAMESPACE__,
                'No modifier {modifier} defined in Simple Template.',
                array('name' => $modifier)
            )
        );
    }

    /**
     * @name Special modifier handling
     * @{
     */
    
    /**
     * Special handling for date modifier.
     *
     * The shortcut of the system constants are supported as parameters.
     * @param int $value timestamp to be formatted
     * @param string $param format string or constant shortcut
     * @return string formatted date string
     */
    private function call_date($value, $param = DATE_ISO8601)
    {
        // $value has to be a timestamp
        assert(is_int($value));
        assert(is_string($param));

        // allow use of DATE_XXX constants
        if (defined('DATE_'.$param))
            $param = constant('DATE_'.$param);
        // return formated
        return date($param, $value);
    }

    /**
     * @}
     */
}
