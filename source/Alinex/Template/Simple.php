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
 * **Variables**
 *
 * The syntax is really simple, you may add variables in the text using curly
 * braces:
 * @verbatim
 * Found {num} errors!
 * @endverbatim
 *
 * Optional variables will be replaced with empty string if not existing. This
 * is done by adding a '?' at the end of the variable name:
 * @verbatim
 * Files {start?} till {end|printf %03d} are broken!
 * @endverbatim
 *
 * **Modifiers**
 *
 * Additional modifiers, added using pipe as separator, may help in formatting
 * the value. They may also contain some options:
 * @verbatim
 * Files {start|printf %03d} till {end|printf %03d} are broken!
 * @endverbatim
 *
 * Therefore the following modifiers are possible:
 * - default [&lt;value&gt;] - use the given default value or empty string for
 * undefined values
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
 * - unitbinary &lt;unit&gt; [&lt;decimals&gt;] [&lt;long form?&gt;] - output
 * numeric value using given unit with specified decimals precsision and may be
 * in long form
 *
 * **Control structure**
 *
 * More control is possible with the control flow operations. They will be
 * added using a % at the start:
 *   {%&lt;comand&gt; [&lt;params&gt;]}
 *
 * The following control comands are possible:
 * - comment / endcomment - the containing part will be removed
 *
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
     * Context of the active control element.
     * Elements:
     * - values: array of values
     * - out: output of context
     * - ctrl: command
     * - param: command params
     * @var array
     */
    private $_context = array(
        array(
            'ctrl' => '',
            'values' => array(),
            'out' => '',
            'param' => ''
        )
    );

    /**
     * Create a new object and store values.
     * @param array $values named array with values
     */
    private function __construct(array $values = null)
    {
        $context =& $this->_context[count($this->_context)-1];
        if (isset($values))
            $context['values'] = $values;
#        error_log(print_r($this->_context,1));
    }

    /**
     * Replace the simple template syntax within the text.
     * @param string $text to be replaced
     * @return string resulting text
     */
    private function replace($text)
    {
        assert(is_string($text));

        preg_replace_callback(
            '/\{(%?\s?\w[^}]+?)\}'    // variable or control
            .'|[^{]+?/',              // other content
            array($this, 'replacePart'),
            $text
        );
        // return the resulting main content
        return $this->_context[0]['out'];
    }

    /**
     * Replace the given simple template variable.
     *
     * @param array $matches matchers from the template search:
     * - 0 - complete syntax
     * - 1 - variable name with optional modifiers
     */
    private function replacePart(array $matches)
    {
        $context =& $this->_context[count($this->_context)-1];
        // normal part -> add to context out
        if ($matches[0][0] != '{') {
            $context['out'] .= $matches[0];
            return;
        }
        // control structure -> exec
        if ($matches[1][0] == '%') {
            $value = $this->control($matches[1]);
            return;
        }
        // variable -> replace by value (using mods)
        $parts = explode('|', $matches[1]);
        $variable = array_shift($parts);
        $value = ArrayStructure::get($context['values'], $variable, '.');
        // check if value has to be present
        if (substr($variable, -1) == '?') {
            $variable = substr($variable, 0, -1);
            if (!isset($value))
                return;
        }
        // run variable through modifier
        foreach($parts as $modifier)
            $value = $this->modifier($value, $modifier);
        // keep syntax if value not set
        if (!isset($value))
            $context['out'] .= $matches[0]; // keep variable if not found
        // set value
        else
            $context['out'] .= $value;
    }

    /**
     * List of possible modifiers with callbacks and other options.
     * - call - contains the real callback
     * - reverse=true - will send the value as last (2nd) value
     * Additionally the mod_... subroutines may be used.
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

        $parts = preg_split('/\s+/', $modifier, 2);
        $function = $parts[0];
        // by default each modifier will be called with value and modifier
        $param = array($value);
        if (isset($parts[1]))
            $param[] = $parts[1];
        // call method
        if (method_exists($this, 'mod_'.$function)) {
            return call_user_func_array(
                array($this, 'mod_'.$function), $param
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
    private function mod_date($value, $param = DATE_ISO8601)
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
     * Use default value if not set.
     *
     * If no param set the value is meant as optional and replaced by an empty
     * string.
     * @param int $value value to check
     * @param string $param default value to use if value not set
     * @return string replacement value
     */
    private function mod_default($value, $param = '')
    {
        return isset($value) ? $value : $param;
    }

    /**
     * @}
     */

    /**
     * Run the control code
     * @param string $control name of the modifier
     */
    private function control($control)
    {
        assert(is_string($control));

        // remove starting %
        $control = preg_replace('/^%\s*/', '', $control);
        // split command
        $param = preg_split('/\s+/', $control, 2);
        $function = array_shift($param);
        // call method
        if (method_exists($this, 'ctrl_'.$function)) {
            call_user_func(
                array($this, 'ctrl_'.$function),
                count($param) ? $param[0] : null
            );
        }
    }

    /**
     * @name Control operators
     * @{
     */

    /**
     * Create a new context for the comment.
     */
    private function ctrl_comment()
    {
        $context = array(
            'ctrl' => 'comment',
            'values' => array(),
            'out' => '',
            'param' => ''
        );
        $this->_context[] = $context;
    }

    /**
     * Remove the comment context without using the output.
     */
    private function ctrl_endcomment()
    {
        do {
            $context = array_pop($this->_context);
        } while ($context['ctrl'] != 'comment');
    }

    /**
     * Create a new context for the if statement.
     * @param string $param evaluation
     */
    private function ctrl_if($param = '')
    {
        $context =& $this->_context[count($this->_context)-1];
        $new = array(
            'ctrl' => 'if',
            'values' => $context['values'],
            'out' => '',
            'param' => $param
        );
        $this->_context[] = $new;
    }

    /**
     * Create a new context for the else if statement.
     * @param string $param evaluation
     */
    private function ctrl_elseif($param = '')
    {
        // find if and store else
        do {
            $if = array_pop($this->_context);
        } while ($if['ctrl'] != 'if');
        // if check ok change if param to true 
        if ($this->checkCondition($if['param'], $if)) {
            $if['param'] = 1;
            // readd to context
            array_push($this->_context, $if);
            // create comment tag to consume content
            $this->ctrl_comment();
        // if no check change if param
        } else {
            $if['out'] = '';
            $if['param'] = $param;
            // readd to context
            array_push($this->_context, $if);
        }
    }

    /**
     * Create a new context for the comment.
     */
    private function ctrl_else()
    {
        // same as calling elseif with param 1 (always true)
        $this->ctrl_elseif(1);
    }

    /**
     * Remove the comment context without using the output.
     */
    private function ctrl_endif()
    {
        // find if and store else
        $else = null;
        do {
            $if = array_pop($this->_context);
        } while ($if['ctrl'] != 'if');
        // append content of if to output
        $context =& $this->_context[count($this->_context)-1];
        $context['out'] .= $this->checkCondition($if['param'], $if)
            ? $if['out']
            : (isset($else) ? $else : '');
    }
    
    /**
     * Create a new context for the comment.
     * @param string $param variable to set
     */
    private function ctrl_set($param)
    {
        $context = array(
            'ctrl' => 'set',
            'values' => array(),
            'out' => '',
            'param' => $param
        );
        $this->_context[] = $context;
    }

    /**
     * Remove the comment context without using the output.
     */
    private function ctrl_endset()
    {
        do {
            $set = array_pop($this->_context);
        } while ($set['ctrl'] != 'set');
        $context =& $this->_context[count($this->_context)-1];
        $context['values'][$set['param']] = $set['out'];        
    }

    

    /**
     * @}
     */

    /**
     * Check condition in context.
     *
     * This is done in three different phases for different operators to get
     * a precedence.
     *
     * @param string $condition to check
     * @param array $context to read variables
     * @return bool
     */
    private function checkCondition($condition = '', $context = null)
    {
        // no condition given -> false
        if (!$condition)
            return false;
        // variable given -> check isset and true
        if (!isset($context))
            $context =& $this->_context[count($this->_context)-1];
        $parts = preg_split('/\s+/', trim($condition));
        if (count($parts) == 1) {
            if (is_numeric($parts[0])) 
                return true;
            $value = ArrayStructure::get($context['values'], $parts[0], '.');
            return isset($value) && $value;
        }
        // complex expression syntax
        // replace values
        $operators = array(
            '==', '!=', '<>', '>=', '=>', '<=', '=>', '>', '<',
            '!',
            'and', '&', 'or', '|', 'xor'
        );
        for ($i=0; $i<count($parts); $i++) {
            $value = ArrayStructure::get($context['values'], $parts[$i], '.');
            if (isset($value) && $value)
                $parts[$i] = $value;
            else if (!is_numeric($parts[$i])
                && !($parts[$i][0] == '"' 
                    && $parts[$i][strlen($parts[$i])-1] == '"')
                && !in_array($parts[$i], $operators))
                $parts[$i] = false; 
        }
        // step through expression: phase 1
        if (count($parts) > 2)
            for ($i=1; $i<count($parts)-1; $i++) {
                // check operator
                switch($parts[$i]) {
                    case '==':
                        $replace = $parts[$i-1] == $parts[$i+1] ? 1 : 0;
                        break;
                    case '!=':
                    case '<>':
                        $replace = $parts[$i-1] != $parts[$i+1] ? 1 : 0;
                        break;
                    case '>=':
                    case '=>';
                        $replace = $parts[$i-1] >= $parts[$i+1] ? 1 : 0;
                        break;
                    case '<=':
                    case '=<';
                        $replace = $parts[$i-1] <= $parts[$i+1] ? 1 : 0;
                        break;
                    case '<':
                        $replace = $parts[$i-1] < $parts[$i+1] ? 1 : 0;
                        break;
                    case '>':
                        $replace = $parts[$i-1] > $parts[$i+1] ? 1 : 0;
                        break;
                }
                // remove used values with result
                if (isset($replace))
                    array_splice($parts, $i-1, 3, $replace);
            }
        // step through expression: phase 2
        if (count($parts) > 1)
            for ($i=1; $i<count($parts)-1; $i++) {
                // check operator
                switch($parts[$i]) {
                    case '!':
                    case 'not':
                        $replace = ! $parts[$i+1] ? 1 : 0;
                        break;
                }
                // remove used values with result
                if (isset($replace))
                    array_splice($parts, $i, 2, $replace);
            }
        // step through expression: phase 3
        if (count($parts) > 2)
            for ($i=0; $i<count($parts)-1; $i++) {
                // check operator
                switch($parts[$i]) {
                    case 'and':
                    case '&':
                        $replace = $parts[$i-1] && $parts[$i+1] ? 1 : 0;
                        break;
                    case 'or':
                    case '|':
                        $replace = $parts[$i-1] || $parts[$i+1] ? 1 : 0;
                        break;
                    case 'xor';
                        $replace = $parts[$i-1] xor $parts[$i+1] ? 1 : 0;
                        break;
                }
                // remove used values with result
                if (isset($replace))
                    array_splice($parts, $i-1, 3, $replace);
            }
        return count($parts) == 1 ? (bool) $parts[0] : false;
    }


}
