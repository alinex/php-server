<?php
/**
 * @file
 * Validating php code formats.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de
 */

namespace Alinex\Validator;

use Alinex\Util\String;

/**
 * Validating php code formats.
 */
class Code
{
    /**
     * Match for namespace check.
     * @verbatim
     * /^
     *  [a-zA-Z]            // start with letter
     *  [_a-zA-Z0-9]+       // can contain letters, digits and underscores
     *  (                   // subnamespaces:
     *      \\              // separator
     *      [a-zA-Z]        // start with letter
     *      [_a-zA-Z0-9]+   // can contain letters, digits and underscores
     *  )*                  // multiple times
     * $/';
     * @endverbatim
     *
     * @var string
     */
    private static $_namespaceMatch =
        '/^[a-zA-Z][_a-zA-Z0-9]+(\\\\[a-zA-Z][_a-zA-Z0-9]+)*$/';

    /**
     * Check for php namespace.
     *
     * A valid php namespace is enought it will not check if it exists. This is
     * only possible on classchecks.
     *
     * @param mixed   $value    value to be checked
     * @param string  $name     readable origin identification
     *
     * @return string
     * @throws Exception if not valid
     *
     * @see phpclass() checks the class name with additional namespace
     */
    static function phpNamespace($value, $name)
    {
        try {
            return Type::string(
                $value, $name,
                array(
                    'replace' => array('/^\\\\/' => ''),
                    'match' => self::$_namespaceMatch
                )
            );
        } catch (Exception $ex) {
            throw $ex->createOuter(__METHOD__);
        }
    }

    /**
     * Get a human readable description for validity.
     *
     * @return string explaining message
     */
    static function phpNamespaceDescription()
    {
        $desc = tr(__NAMESPACE__, 'The value has to be a php namespace.')
            .' '.tr(__NAMESPACE__, 'It can also contain the class name.')
            .' '.Type::stringDescription(
                array('match' => self::$_namespaceMatch)
            );
        return $desc;
    }

    /**
     * Match for namespace check with mandatory class.
     * @verbatim
     * /^
     *  (                   // namespace:
     *      [a-zA-Z]        // start with letter
     *      [_a-zA-Z0-9]+   // can contain letters, digits and underscores
     *      \\              // separator
     *  )?                  // is optional
     *  (                   // subnamespaces:
     *      [a-zA-Z]        // start with letter
     *      [_a-zA-Z0-9]+   // can contain letters, digits and underscores
     *      \\              // separator
     *  )*                  // multiple times
     *  [A-Z]               // class starting with uppercase
     *  [_a-zA-Z0-9]+       // can contain letters, digits and underscores
     * $/';
     * @endverbatim
     *
     * @var string
     */
    private static $_classMatch =
'/^([a-zA-Z][_a-zA-Z0-9]+\\\\)?([a-zA-Z][_a-zA-Z0-9]+\\\\)*[A-Z][_a-zA-Z0-9]+$/';

    /**
     * Check for php class.
     *
     * <b>Oprions:</b>
     * - \c exists - true if existence have to be checked
     * - \c autoload - true if class should be loaded
     * - \c relative - base namespace to allow relative
     * - \c instanceof - class have to be instance of one of the list
     *
     * @param mixed   $value    value to be checked
     * @param string  $name     readable origin identification
     * @param array   $options  specific options
     *
     * @return string
     * @throws Exception if not valid
     */
    static function phpClass($value, $name, array $options = null)
    {
        $options = self::phpClassOptions($options);
        $absolute = strlen($value) && $value[0] == '\\';
        try {
            $value = Type::string(
                $value, $name,
                array(
                    'replace' => array('/^\\\\/' => ''),
                    'match' => self::$_classMatch
                )
            );
        } catch (Exception $ex) {
            throw $ex->createOuter(__METHOD__, $options);
        }
        if (isset($options['exists']) && $options['exists'] === true) {
            $autoload = isset($options['autoload'])
                ? $options['autoload'] : false;
            if (!class_exists($value, $autoload)) {
                if (!$absolute && isset($options['relative'])) {
                    $nsbase = $options['relative'];
                    do {
                        $class = $nsbase . '\\' . $value;
                        if (class_exists($class, $autoload))
                            return $class;
                        $nsbase = strpos($nsbase, '\\') === FALSE
                            ? NULL
                            : substr($nsbase, 0, strrpos($nsbase, '\\'));
                    } while ($nsbase);
                }
                throw new Exception(
                    tr(
                        __NAMESPACE__,
                        'Class {name} didn\'t exist and can\'t be loaded',
                        array('name' => $value)
                    ), $value, $name, __METHOD__
                );
            }
        }
        if (isset($options['instanceof']) && $options['instanceof']) {
            $ok = false;
            foreach ($options['instanceof'] as $class)
                if ($value instanceof $class)
                    $ok = true;
            if (!$ok)
                throw new Exception(
                    tr(
                        __NAMESPACE__,
                        'Class {name} is none of the defined classes',
                        array('name' => $value)
                    ), $value, $name, __METHOD__
                );
        }
        return $value;
    }

    /**
     * Optimize the options.
     *
     * @param array $options specific settings
     *
     * @return array optimized options
     *
     * @see phpClass()
     */
    private static function phpClassOptions(array $options = null)
    {
        if (!isset($options))
            $options = array();

        // options have to be an array
        assert(isset($options) && is_array($options));
         // check for invalid options
        assert(
            count(
                array_diff(
                    array_keys($options),
                    array(
                        'description',
                        'exists',
                        'autoload',
                        'relative',
                        'instanceof'
                    )
                )
            ) == 0
        );
        assert(!isset($options['exists']) || is_bool($options['exists']));
        assert(!isset($options['autoload']) || is_bool($options['autoload']));
        assert(!isset($options['relative']) || is_string($options['relative']));
        if (isset($options['instanceof']) && is_string($options['instanceof']))
            $options['instanceof'] = array($options['instanceof']);
        assert(
            !isset($options['instanceof'])
            || is_array($options['instanceof'])
        );
        if (isset($options['relative']) && isset($options['relative']) === true)
            $options['autoload'] = true;
        if (isset($options['autoload']) && isset($options['autoload']) === true)
            $options['exists'] = true;

        return $options;
    }

    /**
     * Get a human readable description for validity.
     *
     * @param array   $options  options from check
     *
     * @return string explaining message
     */
    static function phpClassDescription(array $options = null)
    {
        $options = self::phpClassOptions($options);
        $desc = tr(__NAMESPACE__, 'The value has to be a php class name.')
            .' '.tr(__NAMESPACE__, 'The namespace can be prepended.');
        if (isset($options['exists']) && $options['exists'] === true) {
            if (isset($options['autoload']) && $options['autoload'] === true)
                $desc .= ' '.tr(
                    __NAMESPACE__,
                    'The class have to exists and may be loaded.'
                );
            else
                $desc .= ' '.tr(
                    __NAMESPACE__,
                    'The class have to exists and be already loaded.'
                );
            if (isset($options['relative']) && $options['relative'])
                $desc .= ' '.tr(
                    __NAMESPACE__,
                    'Full class name will be calculated from relative {namespace}',
                    array('namespace' => String::dump($options['relative']))
                );
        }
        if (isset($options['instanceof']) && $options['instanceof'])
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The class have to be of {list} or any subclass.',
                array('list' => $options['instanceof'])
            );
        return $desc;
    }

    /**
     * Check for php callable
     *
     * The possibilities are:
     * - string function - name of function to call (if allowed)
     * - string class::method - static method call
     * - array(class, method) - static method call
     * - array(object, method) - method of given object
     *
     * <b>Oprions:</b>
     * - \c relative - base namespace to allow relative class names
     * - \c allowFunction - functions are also allowed
     *
     * @param mixed   $value    value to be checked
     * @param string  $name     readable origin identification
     * @param array   $options  specific options
     *
     * @return mixed callable information
     * @throws Exception if not valid
     */
    static function callable($value, $name, array $options = null)
    {
        if (!isset($options))
            $options = array();

        // options have to be an array
        assert(is_array($options));
        // check for invalid options
        assert(
            count(
                array_diff(
                    array_keys($options),
                    array(
                        'description',
                        'relative',
                        'allowFunction'
                    )
                )
            ) == 0
        );
        // string class::method -> array(class, method)
        if (is_string($value) && strpos($value, '::') !== false)
            $value = explode('::', $value, 2);

        if (is_array($value)) {
            if (is_string($value[0])) {
            // test array(class, method)
                try {
                    $value[0] = self::phpClass(
                        $value[0], $name,
                        array(
                            'exists' => true,
                            'autoload' => true,
                            'relative' => isset($options['relative'])
                                ? $options['relative'] : null
                        )
                    );
                } catch (Exception $ex) {
                    throw $ex->createOuter(__METHOD__, $options);
                }
            }
            // test array(object||class, method)
            if (!method_exists($value[0], $value[1]))
                throw new Exception(
                    tr(
                        __NAMESPACE__,
                        'Method {method} didn\'t exist in class {class}',
                        array('class' => $value[0], 'method' => $value[1])
                    ), $value, $name, __METHOD__, $options
                );
        } else if (isset($options['allowFunction'])
            && $options['allowFunction'] == true) {
            // test string function (+namespace)
            if (!function_exists($value))
                throw new Exception(
                    tr(
                        __NAMESPACE__,
                        'Function {name} didn\'t exist',
                        array('name' => $value.'()')
                    ), $value, $name, __METHOD__, $options
                );
        } else {
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'Method {name} is not callable',
                    array('name' => is_array($value)
                        ? implode('::', $value) : $value.'()')
                ), $value, $name, __METHOD__, $options
            );
        }
        return $value;
    }

    static function callableDescription(array $options = null)
    {
        $desc = tr(
            __NAMESPACE__,
            'The value has to be a callable php method or function.'
        ).' '.tr(
            __NAMESPACE__,
            'A callback method can be specified using static method call as a string or an array with class name or object and method name'
        );
        if (isset($options['relative']) && $options['relative'])
            $desc .= ' '.tr(
                __NAMESPACE__,
                'For static methods the class name may be relative to {base}.',
                array('base' => $options['relative'])
            );
        if (isset($options['allowFunction'])
            && $options['allowFunction'] === true)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'Function names are also possible.'
            );
        return $desc;
    }

    /**
     * Match for sprintf parameters
     * @verbatim
     * %1$06.2f
     * @endverbatim
     * This is checked with the following regexp:
     * @verbatim
     * /%                   // starting percent sign
     *  (?:\d+\$)?          // numbered parameters
     *  [+-]?               // sign specifier
     *  (?:[ 0]|\'.)?       // padding specifier
     *  -?                  // alignment specifier
     *  \d*                 // width specifier
     *  (?:\.\d+)?          // precision specifier
     *  [bcdeEufFgGosxX]    // type specifier
     *  /
     * @endverbatim
     *
     * @var string
     */
    private static $_printfMatch =
        '/%(?:\d+\$)?[+-]?(?:[ 0]|\'.{1})?-?\d*(?:\.\d+)?[bcdeEufFgGosxX]/';

    /**
     * Check for sprintf format string.
     *
     * <b>Oprions:</b>
     * - \c minParameter minimal numbers of variables
     * - \c maxParameter maximal numbers of variables
     * - \c replace list of replacements
     * - \c parameter titles for replacements in description
     *
     * @param mixed   $value    value to be checked
     * @param string  $name     readable origin identification
     * @param array   $options  for conformance only (not used)
     *
     * @return string
     * @throws Exception if not valid
     */
    static function printf($value, $name, array $options = null)
    {
        if (!isset($options))
            $options = array();

        // name of origin have to be a string
        assert(is_string($name));
        // options have to be an array
        assert(is_array($options));
        // check for invalid options
        assert(
            count(
                array_diff(
                    array_keys($options),
                    array(
                        'description',
                        'minParameter',
                        'maxParameter',
                        'replace',
                        'parameter'
                    )
                )
            ) == 0
        );
        assert(
            !isset($options['minParameter'])
            || is_int($options['minParameter'])
        );
        assert(
            !isset($options['maxParameter'])
            || is_int($options['maxParameter'])
        );
        assert(
            !isset($options['replace'])
            || is_array($options['replace'])
        );
        assert(
            !isset($options['parameter'])
            || is_array($options['parameter'])
        );
        // min should be equal or less than max
        assert(
            !isset($options['minParameter'])
            || !isset($options['maxParameter'])
            || $options['minParameter'] <= $options['maxParameter']
        );
        // if both given number of values in replace and param equal
        assert(
            !isset($options['replace'])
            || !isset($options['parameter'])
            || count($options['replace']) == count($options['parameter'])
        );

        // run the checks
        try {
            $value = Type::string($value, $name);
        } catch (Exception $ex) {
            throw $ex->createOuter(__METHOD__, $options);
        }

        // check number of parameters
        $dummy = array();
        $count = \preg_match_all(self::$_printfMatch, $value, $dummy);
        if (isset($options['maxParameter'])
            && $count > $options['maxParameter'])
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    '{num} replacement parameter are too much.',
                    array('num' => $count)
                ), $value, $name, __METHOD__, $options
            );
        if (isset($options['minParameter'])
            && $count < $options['minParameter'])
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    '{num} replacement parameter are too less.',
                    array('num' => $count)
                ), $value, $name, __METHOD__, $options
            );
        if (isset($options['replace']))
            $value = vsprintf($value, $options['replace']);
        return $value;
    }

    /**
     * Get a human readable description for validity.
     *
     * @param array   $options  options from check
     *
     * @return string explaining message
     */
    static function printfDescription(array $options = null)
    {
        if (isset($options['maxParamter']) && !isset($options['minParamter']))
            $options['minParamter'] = $options['maxParamter'];
        if (isset($options['replace']) && !isset($options['paramter']))
            $options['paramter'] = $options['replace'];
        // create output text
        $desc = tr(
            __NAMESPACE__, 'The value has to be a php sprintf format string.'
        ).tr(
            __NAMESPACE__,
            'Parameters are maked in the text like described under {url}.',
            array('url' => 'http://php.net/manual/en/function.sprintf.php')
        );
        $desc .= ' '.Type::stringDescription();
        if (isset($options['maxParameter']) && isset($options['minParameter'])
            && $options['minParameter'] == $options['maxParameter']) {
            $desc .= ' '.tr(
                __NAMESPACE__,
                'Exactly {num} replacement parameter are needed.',
                array('num' => $options['maxParameter'])
            );
        } else {
            if (isset($options['minParameter']))
                $desc .= ' '.tr(
                    __NAMESPACE__,
                    'At least {num} replacement parameter are needed.',
                    array('num' => $options['minParameter'])
                );
            if (isset($options['maxParameter']))
                $desc .= ' '.tr(
                    __NAMESPACE__,
                    'Not more than {num} replacement parameter are allowed.',
                    array('num' => $options['maxParameter'])
                );
        }
        if (!isset($options['paramter']) && isset($options['replace']))
            $options['paramter'] = $options['replace'];
        if (isset($options['paramter'])) {
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The replacement parameters are: '
            );
            foreach ($options['paramter'] as $n => $v)
                $desc .= ($n+1)." => '".$v."'; ";
        }
        return $desc;
    }

}
