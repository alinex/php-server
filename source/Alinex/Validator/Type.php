<?php
/**
 * @file
 * Validating core system types.
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
 * Validating core system types.
 *
 * Each of this methods will check one type with different optional settings.
 * If possible it will sanitize the value, if the value fails an exception
 * with end user description will be created.
 *
 * Each of the options may have an \c description element which will be used as
 * first line description, before the concret format rules.
 *
 * @see Alinex\Validator for general info
 */
class Type
{
    /**
     * Check for boolean type.
     *
     * The value can be 1, "true", "on", "yes" for \c true and 0, "false",
     * "off", "no", "" for \c false.
     *
     * @param mixed   $value    value to be checked
     * @param string  $name     readable variable identification
     * @param array   $options  description only
     *
     * @return boolean
     * @throws Exception if not valid
     */
    public static function boolean($value, $name, array $options = null)
    {
        if (!isset($options))
            $options = array();
        // name of origin have to be a string
        assert(is_string($name));
        // check for invalid options
        assert(
            count(
                array_diff(
                    array_keys($options),
                    array('description')
                )
            ) == 0
        );

        if ($value === false || $value === '')
            $value = 0; // to also accept false
        $res = filter_var(
            $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE
        );
        // check the result and throw exception
        if ($res === null)
            // TRANS: Title for the error message if value is not an boolean
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'Value unknown for boolean'
                ), $value, $name, __METHOD__
            );
        // return result
        return $res;
    }

    /**
     * Get a human readable description for validity as boolean.
     *
     * @param array   $options  description only
     *
     * @return string explaining message
     */
    static function booleanDescription(array $options = null)
    {
        if (!isset($options))
            $options = array();
        // check for invalid options
        assert(
            count(
                array_diff(
                    array_keys($options),
                    array('description')
                )
            ) == 0
        );

        // TRANS: description for the possible values for type boolean
        return tr(
            __NAMESPACE__,
            'The value has to be a boolean. The value will be true for 1, "true", "on", "yes" and it will be considered as false for 0, "false", "off", "no", "". Other values are not allowed.'
        );
    }

    /**
     * Map with max signed number for integer types.
     *
     * This values are used to translate named types to number ranges.
     *
     * @var array
     */
    private static $_integerTypeMax = array(
        4       => 7,
        8       => 127,
        'byte'  => 127,
        16      => 32767,
        'short' => 32767,
        32      => 2147483647,
        'long'  => 2147483647,
        64      => 9223372036854775807,
        'quad'  => 9223372036854775807
    );


    /**
     * Check for integer number.
     *
     * Only integers till the maximum supported on the server's platform is
     * possible. PHP only supports signed integers, so the maximum is 32bit or
     * 64bit signed mostly (check with \c PHP_INT_MAX).
     *
     * <b>Options:</b>
     * - \c sanitize - (bool) remove invalid characters
     * - \c type - (integer|string) the integer is of given type
     * (4, 8, 16, 32, 64, 'byte', 'short','long','quad')
     * - \c unsigned - (bool) the integer has to be positive
     * - \c minRange - (integer) the smalles allowed number
     * - \c maxRange - (integer) the biggest allowed number
     * - \c allowFloat - (bool) integer in float notation allowed
     * - \c round - (bool) arithmetic rounding of float
     * - \c allowOctal - (bool) true to accept also octal numbers starting with
     * with '0'
     * - \c allowHex - (bool) true to accept also hexadecimal numbers starting
     * with '0x' or '0X'
     *
     * @param mixed   $value    value to be checked
     * @param string  $name     readable origin identification
     * @param array   $options  specific settings
     *
     * @return integer cleaned integer value
     * @throws Exception if not valid
     */
    static function integer($value, $name, array $options = null)
    {
        // name of origin have to be a string
        assert(is_string($name));

        $options = self::integerOptions($options);
        if (isset($options['allowFloat']))
            $value = self::float(
                $value, $name, array(
                    'round' => isset($options['round'])
                        ? 0 : null,
                    'sanitize' => isset($options['sanitize'])
                        ? $options['sanitize'] : null
                )
            );
        // sanitize
        if (isset($options['sanitize']) && $options['sanitize'] === true)
            $value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
        // create filter options
        $filterSpec = array();
        $filterSpec['options'] = array();
        if (isset($options['minRange']))
            $filterSpec['options']['min_range'] = $options['minRange'];
        if (isset($options['maxRange']))
            $filterSpec['options']['max_range'] = $options['maxRange'];
        // add flags for filter
        $filterSpec['flags'] = 0;
        if (isset($options['allowOctal']) && $options['allowOctal'] === true)
            $filterSpec['flags'] |= FILTER_FLAG_ALLOW_OCTAL;
        if (isset($options['allowHex']) && $options['allowHex'] === true)
            $filterSpec['flags'] |= FILTER_FLAG_ALLOW_HEX;
        // run filter
        $res = filter_var($value, FILTER_VALIDATE_INT, $filterSpec);
        if ($res === false)
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'Value {value} is not an valid integer',
                    array('value' => String::dump($value))
                ), $value, $name, __METHOD__, $options
            );
        // return result
        return $res;
    }

    /**
     * Optimize the options.
     *
     * @param array $options specific settings
     *
     * @return array optimized options
     *
     * @see integer()
     */
    private static function integerOptions(array $options = null)
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
                        'sanitize',
                        'type',
                        'unsigned',
                        'minRange',
                        'maxRange',
                        'allowFloat',
                        'round',
                        'allowOctal',
                        'allowHex'
                    )
                )
            ) == 0
        );
        // check options format
        assert(
            !isset($options['sanitize'])
            || is_bool($options['sanitize'])
        );
        // type has to be defined in $_integerTypeMax
        assert(
            !isset($options['type']) ||
            isset(self::$_integerTypeMax[$options['type']])
        );
        assert(
            !isset($options['unsigned'])
            || is_bool($options['unsigned'])
        );
        // minRange have to be an integer value
        assert(!isset($options['minRange']) || is_int($options['minRange']));
        // maxRange have to be an integer value
        assert(!isset($options['maxRange']) || is_int($options['maxRange']));
        assert(
            !isset($options['allowFloat'])
            || is_bool($options['allowFloat'])
        );
        assert(
            !isset($options['round'])
            || is_bool($options['round'])
        );
        assert(
            !isset($options['allowOctal'])
            || is_bool($options['allowOctal'])
        );
        assert(
            !isset($options['allowHex'])
            || is_bool($options['allowHex'])
        );

        // convert options
        $min = null;
        $max = null;
        if (isset($options['type'])) {
            $max = self::$_integerTypeMax[$options['type']];
            $min = -$max-1;
        }
        if (isset($options['unsigned'])) {
            // move range to positive
            if ($max) $max += $max + 1;
            $min = isset($min) ? max(0, $min) : 0;
        }

        if (isset($min))
            $options['minRange'] = isset($options['minRange'])
                ? max($options['minRange'], $min)
                : $min;
        if (isset($max))
            $options['maxRange'] = isset($options['maxRange'])
                ? min($options['maxRange'], $max)
                : $max;
        // range should be valid with min<max
        assert(
            !isset($options['minRange'])
            || !isset($options['maxRange'])
            || $options['minRange'] <= $options['maxRange']
        );

        // float
        if ((isset($options['round']) && $options['round'] === true)
            || (isset($options['floor']) && $options['floor'] === true)
            || (isset($options['ceil']) && $options['ceil'] === true)
            ) {
            $options['allowFloat'] = true;
        } else {
            unset($options['round']);
        }
        // return optimized array
        return $options;
    }

    /**
     * Get a human readable description for validity as integer.
     *
     * @param array   $options  options from check
     *
     * @return string explaining message
     */
    static function integerDescription(array $options = null)
    {
        $options = self::integerOptions($options);
        // TRANS: description for the possible values for type integer
        $desc = tr(
            __NAMESPACE__,
            'The value has to be an integer.'
        );
        // sanitize
        if (isset($options['sanitize']) && $options['sanitize'] === true)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'All characters except digits, plus and minus sign are removed.'
            );
        // add range description
        if (isset($options['minRange'])
                && isset($options['maxRange']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The value has to be between {min} and {max}.',
                array('min' => $options['minRange'],
                      'max' => $options['maxRange'])
            );
        elseif (isset($options['minRange']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The value has to be equal or larger than {min}.',
                array('min' => $options['minRange'])
            );
        elseif (isset($options['maxRange']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The value has to be equal or smaller than {max}.',
                array('max' => $options['maxRange'])
            );
        // optional number systems
        if (isset($options['allowOctal']) && $options['allowOctal'] === true)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'Optionally the number can be given in octal notation starting with \'0...\'.'
            );
        if (isset($options['allowHex']) && $options['allowHex'] === true)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'Optionally the number can be given in hexa decimal notation starting with \'0x...\' or \'0X...\'.'
            );
        if (isset($options['allowFloat']) && $options['allowFloat'] === true) {
            $desc .= ' '.tr(
                __NAMESPACE__,
                'Floating point notation is also allowed.'
            );
            if (isset($options['round']) && $options['round'] === true) {
                $desc .= ' '.tr(
                    __NAMESPACE__,
                    'If the value evaluates not to a whole number it is rounded.'
                );
            }
        }
        // return description text
        return $desc;
    }

    /**
     * Check for foating point number.
     *
     * <b>Options:</b>
     * - \c decimal - sign used as decimal separator
     * - \c sanitize - remove invalid characters
     * - \c round - (int) number of decimal digits to round to
     * - \c unsigned - (bool) the integer has to be positive
     * - \c minRange - (numeric) the smalles allowed number
     * - \c maxRange - (numeric) the biggest allowed number
     *
     * @param mixed   $value    value to be checked
     * @param string  $name     readable origin identification
     * @param array   $options  specific settings
     *
     * @return integer
     * @throws Exception if not valid
     */
    static function float($value, $name, array $options = null)
    {
        // name of origin have to be a string
        assert(is_string($name));

        $options = self::floatOptions($options);
        // create filter options
        $filterSpec = array();
        $filterSpec['options'] = array();
        if (isset($options['minRange']))
            $filterSpec['options']['min_range'] = $options['minRange'];
        if (isset($options['maxRange']))
            $filterSpec['options']['max_range'] = $options['maxRange'];
        if (isset($options['decimal']))
            $filterSpec['options']['decimal'] = $options['decimal'];
        // add filter flags
        $filterSpec['flags'] = FILTER_FLAG_ALLOW_FRACTION;
        // sanitize
        if (isset($options['sanitize']) && $options['sanitize'] === true)
            $value = filter_var(
                $value, FILTER_SANITIZE_NUMBER_FLOAT, $filterSpec
            );
        // run filter
        $res = filter_var($value, FILTER_VALIDATE_FLOAT, $filterSpec);
        // check the result and throw exception
        if ($res === false)
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'Value {value} is not an valid float',
                    array('value' => String::dump($value))
                ), $value, $name, __METHOD__, $options
            );
        // round value
        if (isset($options['round']))
            $res = round($res, $options['round']);
        // cech range
        if ((isset($options['minRange']) && $options['minRange'] > $res)
            || (isset($options['maxRange']) && $options['maxRange'] < $res))
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'Value {value} is out of range',
                    array('value' => String::dump($value))
                ), $value, $name, __METHOD__, $options
            );
        // return result
        return $res;
    }

    /**
     * Optimize the options.
     *
     * @param array $options specific settings
     *
     * @return array optimized options
     *
     * @see float()
     */
    private static function floatOptions(array $options = null)
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
                        'decimal',
                        'sanitize',
                        'round',
                        'unsigned',
                        'minRange',
                        'maxRange'
                    )
                )
            ) == 0
        );
        // minRange have to be an integer value
        assert(
            !isset($options['minRange']) ||
            is_numeric($options['minRange'])
        );
        // maxRange have to be an integer value
        assert(
            !isset($options['maxRange']) ||
            is_numeric($options['maxRange'])
        );
        // flag unsigned has to be boolean
        assert(!isset($options['unsigned']) || is_bool($options['unsigned']));
        // decimal has to be string
        assert(!isset($options['decimal']) || is_string($options['decimal']));
        // round has to be integer
        assert(!isset($options['round']) || is_int($options['round']));
        // round has to be a positive number
        assert(!isset($options['round']) || $options['round'] >= 0);
        // flag sanitize has to be boolean
        assert(!isset($options['sanitize']) || is_bool($options['sanitize']));
        // range should be valid with min<max
        assert(
            !isset($options['minRange']) || !isset($options['maxRange']) ||
            $options['minRange'] <= $options['maxRange']
        );

        // ranges
        if (isset($options['unsigned']))
            $options['minRange'] =  isset($options['minRange'])
            ? $options['minRange'] = max(0, $options['minRange'])
            : 0;
        // return optimized array
        return $options;
    }

    /**
     * Get a human readable description for validity as integer.
     *
     * @param array   $options  options from check
     *
     * @return string explaining message
     */
    static function floatDescription(array $options = null)
    {
        $options = self::floatOptions($options);
        $desc = tr(
            __NAMESPACE__,
            'The value has to be a floating point number.'
        );
        // optionas
        if (isset($options['decimal']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The decimal separator has to be {separator}.',
                array('separator' => String::dump($options['decimal']))
            );
        // sanitize
        if (isset($options['sanitize']) && $options['sanitize'] === true)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'All characters except digits, plus, minus and {separator} are removed.',
                array('separator' => isset($options['decimal'])
                    ? $options['decimal']
                    : '.')
            );
        // round
        if (isset($options['round']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The value will be rounded to {number} digits after decimal point.',
                array('number' => $options['round'])
            );
        // add range description
        if (isset($options['minRange'])
                && isset($options['maxRange']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The value has to be between {min} and {max}.',
                array('min' => $options['minRange'],
                      'max' => $options['maxRange'])
            );
        elseif (isset($options['minRange']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The value has to be equal or larger than {min}.',
                array('min' => $options['minRange'])
            );
        elseif (isset($options['maxRange']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The value has to be equal or smaller than {max}.',
                array('max' => $options['maxRange'])
            );
        // return description text
        return $desc;
    }

    /**
     * Check for text type.
     *
     * <b>Sanitize Options:</b>
     * - \c allowControls - keep control characters in string instead of
     * stripping them (but keep \\r\\n)
     * - \c replace - replace given string or regular expressions (keys) to its
     * replacements (values)
     * - \c stripTags - remove all html tags
     * - \c trim - true to strip whitespace from the beginning and end of a
     * string
     * - \c crop - crop text after number of characters
     *
     * <b>Check Options:</b>
     * - \c minLength - minimum text length in characters
     * - \c maxLength - maximum text length in characters
     * - \c whitelist - characters which are allowed (as one text string)
     * - \c blacklist - characters which are disallowed (as one text string)
     * - \c values - array of possible values (complete text)
     * - \c startsWith - start of text
     * - \c endsWith - end of text
     * - \c match - string or regular expression which have to be matched
     * (or list of expressions)
     * - \c matchNot - string or regular expression which is not allowed to
     * match (or list of expressions)
     *
     * @param mixed   $value    value to be checked
     * @param string  $name     readable origin identification
     * @param array   $options  specific settings
     * @return string of cleaned up value
     *
     * @throws ValidationException if not valid
     */
    static function string($value, $name, array $options = null)
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
                        'allowControls',
                        'replace',
                        'stripTags',
                        'trim',
                        'crop',
                        'minLength',
                        'maxLength',
                        'whitelist',
                        'blacklist',
                        'values',
                        'startsWith',
                        'endsWith',
                        'match',
                        'matchNot'
                    )
                )
            ) == 0
        );
        assert(
            !isset($options['allowControls'])
            || is_bool($options['allowControls'])
        );
        assert(
            !isset($options['stripTags'])
            || is_bool($options['stripTags'])
        );
        assert(!isset($options['trim']) || is_bool($options['trim']));
        assert(!isset($options['replace']) || is_array($options['replace']));
        // Option 'values' has to be array
        assert(
            !isset($options['values'])
            || is_array($options['values'])
        );
        assert(
            !isset($options['minLength'])
            || (is_int($options['minLength']) && $options['minLength'] >= 0)
        );
        assert(
            !isset($options['maxLength'])
            || (is_int($options['maxLength']) && $options['maxLength'] > 0)
        );
        assert(
            !isset($options['minLength'])
            || !isset($options['maxLength'])
            || $options['minLength'] <= $options['maxLength']
        );
        assert(
            !isset($options['whitelist'])
            || is_string($options['whitelist'])
        );
        assert(
            !isset($options['blacklist'])
            || is_string($options['blacklist'])
        );
        assert(
            !isset($options['match'])
            || is_string($options['match'])
        );
        assert(
            !isset($options['matchNot'])
            || is_string($options['matchNot'])
        );
        assert(
            !isset($options['crop'])
            || (is_int($options['crop']) && $options['crop'] > 0)
        );
        assert(!isset($options['trim']) || is_bool($options['trim']));
        assert(
            !isset($options['startsWith'])
            || is_string($options['startsWith'])
        );
        assert(
            !isset($options['endsWith'])
            || is_string($options['endsWith'])
        );

        // check for string
        if (!is_string($value))
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'Value has to be of type string but {type} given',
                    array('type' => gettype($value))
                ), $value, $name, __METHOD__, $options
            );
        // sanitize
        if (!isset($options['allowControls'])
            || $options['allowControls'] === false)
            $value = preg_replace(
                '/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $value
            );
        if (isset($options['replace']))
            foreach ($options['replace'] as $match => $replace)
                $value = @preg_match($match, '') === false
                    ? str_replace($match, $replace, $value)
                    : preg_replace($match, $replace, $value);
        if (isset($options['stripTags']) && $options['stripTags'] === true)
            $value = filter_var($value, \FILTER_SANITIZE_STRING);
        if (isset($options['trim']) && $options['trim'] === true)
            $value = trim($value);
        if (isset($options['crop']))
            if (strlen($value) > $options['crop'])
                $value = substr($value, 0, $options['crop']);
        // check value
        if (isset($options['values'])) {
            if (!in_array($value, $options['values'], true))
                throw new Exception(
                    tr(
                        __NAMESPACE__,
                        'Value {value} is not allowed',
                        array('value' => String::dump($value))
                    ), $value, $name, __METHOD__, $options
                );
            return $value;
        }
        if (isset($options['minLength'])
            && strlen($value) < $options['minLength'])
            throw new Exception(
                tr(__NAMESPACE__, 'Text is to short'),
                $value, $name, __METHOD__, $options
            );
        if (isset($options['maxLength'])
            && strlen($value) > $options['maxLength'])
            throw new Exception(
                tr(__NAMESPACE__, 'Text is to long'),
                $value, $name, __METHOD__, $options
            );
        if (isset($options['whitelist'])) {
            $reg = String::pregMask($options['whitelist']);
            $matches = null;
            if (\preg_match('/([^'.$reg.'])/', $value, $matches))
                throw new Exception(
                    tr(
                        __NAMESPACE__,
                        'Invalid character {char} found',
                        array('char' => String::dump($matches[0]))
                    ), $value, $name, __METHOD__, $options
                );
        }
        if (isset($options['blacklist'])) {
            $reg = String::pregMask($options['blacklist']);
            $matches = null;
            if (\preg_match('/(['.$reg.'])/', $value, $matches))
                throw new Exception(
                    tr(
                        __NAMESPACE__,
                        'Invalid character found',
                        array('char' => $matches[0])
                    ), $value, $name, __METHOD__, $options
                );
        }
        if (isset($options['match'])) {
            if (is_string($options['match']))
               $options['match'] = array($options['match']);
            foreach($options['match'] as $test)
                if (@preg_match($test, '') === false
                    ? strpos($value, $test) === false
                    : !preg_match($test, $value))
                    throw new Exception(
                        tr(
                            __NAMESPACE__,
                            'Mandatory match not reached'
                        ), $value, $name, __METHOD__, $options
                    );
        }
        if (isset($options['matchNot'])) {
            if (is_string($options['matchNot']))
                $options['matchNot'] = array($options['matchNot']);
            foreach($options['matchNot'] as $test)
                if (@preg_match($test, '') === false
                    ? strpos($value, $test) !== false
                    : preg_match($test, $value))
                    throw new Exception(
                        tr(
                            __NAMESPACE__,
                            'Forbidden match found'
                        ), $value, $name, __METHOD__, $options
                    );
        }
        if (isset($options['startsWith'])
            && !String::startsWith($value, $options['startsWith']))
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'String don\'t start with {start}',
                    array('start' => String::dump($options['startsWith']))
                ), $value, $name, __METHOD__, $options
            );
        if (isset($options['endsWith'])
            && !String::endsWith($value, $options['endsWith']))
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'String don\'t start with {end}',
                    array('end' => String::dump($options['endsWith']))
                ), $value, $name, __METHOD__, $options
            );

        // return result
        return $value;
    }

    /**
     * Get a human readable description for validity.
     *
     * @param array   $options  options from check
     * @return string explaining message
     */
    static function stringDescription(array $options = null)
    {
        if (!isset($options))
            $options = array();

        // TRANS: description for the possible values for type boolean
        $desc = tr(__NAMESPACE__, 'The value has to be a text.');
        if (isset($options['values'])) {
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The value can only be one of {values}.',
                array('values' => String::dump($options['values']))
            );
            return $desc;
        }
        // sanitize
        if (!isset($options['allowControls'])
            || $options['allowControls'] === false)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'Control characters excluding newline and carriage return will be removed.'
            );
        if (isset($options['stripTags']) && $options['stripTags'] === true)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'HTML tags will be removed.'
            );
        if (isset($options['trim']) && $options['trim'] === true)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'Whitespaces will be striped from the start and end of text.'
            );
        if (isset($options['replace'])) {
            $desc .= ' '.trn(
                __NAMESPACE__,
                'The following replacement will be done: ',
                'The following replacements will be done: ',
                count($options['replace'])
            );
            $desc .= String::dump($options['replace']) . '.';
        }
        // add range description
        if (isset($options['minLength']) && isset($options['maxLength']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The value has to be between {min} and {max} characters long.',
                array('min' => $options['minLength'],
                      'max' => $options['maxLength'])
            );
        elseif (isset($options['minLength']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The value has to be equal or longer than {min} characters.',
                array('min' => $options['minLength'])
            );
        elseif (isset($options['maxLength']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The value has to be equal or shorter than {max} characters.',
                array('max' => $options['maxLength'])
            );
        // matching
        if (isset($options['whitelist']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'Only the characters {list} are allowed.',
                array('list' => String::dump($options['whitelist']))
            );
        if (isset($options['blacklist']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The characters {list} are not allowed.',
                array('list' => String::dump($options['blacklist']))
            );
        if (isset($options['match']))
            $desc .= ' '.trn(
                __NAMESPACE__,
                'The expression {match} have to be matched.',
                'The expressions {match} have to be matched.',
                count($options['match']), array(
                    'match' => count($options['match']) == 1
                    ? String::dump($options['match'][0])
                    : String::dump($options['match'])
                )
            );
        if (isset($options['matchNot']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The expressions {match} is forbidden to match.',
                array('match' => String::dump($options['matchNot']))
            );
        if (isset($options['startsWith'])) {
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The value have to start with {text}.',
                array('text' => String::dump($options['startsWith']))
            );
        }
        if (isset($options['endsWith'])) {
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The value have to end with {text}.',
                array('text' => String::dump($options['endsWith']))
            );
        }
        return $desc;
    }

    /**
     * Check for array.
     *
     * <b>Allowed options:</b>
     * - \c delimiter - allow value text with specified list separator
     * (it can also be an regular expression)
     * - \c notEmpty - set to true if an empty array is not valid
     * - \c mandatoryKeys - the list of elements which are mandatory
     * - \c allowedKeys - gives a list of elements which are also allowed
     * - \c minLength - minimum number of entries
     * - \c maxLength - maximum number of entries
     *
     * All mandatory keys are automatically allowed keys if given, too.
     *
     * @param mixed   $value    value to be checked
     * @param string  $name     readable variable identification
     * @param array   $options  specific settings
     *
     * @return boolean
     * @throws Exception if not valid
     */
    static function arraylist($value, $name, array $options = null)
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
                        'delimiter',
                        'notEmpty',
                        'mandatoryKeys',
                        'allowedKeys',
                        'minLength',
                        'maxLength'
                    )
                )
            ) == 0
        );
        assert(
            !isset($options['delimiter'])
            || is_string($options['delimiter'])
        );
        assert(!isset($options['notEmpty']) || is_bool($options['notEmpty']));
        assert(
            !isset($options['mandatoryKeys'])
            || (is_array($options['mandatoryKeys'])
                && count($options['mandatoryKeys']))
        );
        assert(
            !isset($options['allowedKeys'])
            || (is_array($options['allowedKeys'])
                && count($options['allowedKeys']))
        );
        assert(
            !isset($options['minLength'])
            || (is_int($options['minLength']) && $options['minLength'] >= 0)
        );
        assert(
            !isset($options['maxLength'])
            || (is_int($options['maxLength']) && $options['maxLength'] > 0)
        );
        assert(
            !isset($options['minLength'])
            || !isset($options['maxLength'])
            || $options['minLength'] <= $options['maxLength']
        );

        if (!is_array($value) && isset($options['delimiter'])) {
            if ($options['delimiter'][0] == '/')
                $value = preg_split($options['delimiter'], (string) $value);
            else
                $value = \explode($options['delimiter'], (string) $value);
        }
        if (!is_array($value))
            // TRANS: Title for the error message if value is not an array
            throw new Exception(
                tr(__NAMESPACE__, 'Value is not an array'),
                $value, $name, __METHOD__, $options
            );
        if (isset($options['notEmpty']) && $options['notEmpty'] === true
            && empty($value))
            throw new Exception(
                tr(__NAMESPACE__, 'Value is an empty array'),
                $value, $name, __METHOD__, $options
            );
        if (isset($options['mandatoryKeys'])) {
            // check mandatory elements
            foreach ($options['mandatoryKeys'] as $key) {
                if (!isset($value[$key]))
                    throw new Exception(
                        tr(
                            __NAMESPACE__,
                            'Array is missing the mandatory {key} key',
                            array('key' => $key)
                        ), $value, $name, __METHOD__, $options
                    );
            }
        }
        if (isset($options['allowedKeys'])) {
            // check allowed elements
            foreach (\array_keys($value) as $key) {
                if (!\in_array($key, $options['allowedKeys'])
                    && (!isset($options['mandatoryKeys'])
                        || !\in_array($key, $options['mandatoryKeys'])))
                    throw new Exception(
                        tr(
                            __NAMESPACE__,
                            'The key {key} is not allowed',
                            array('key' => $key)
                        ), $value, $name, __METHOD__, $options
                    );
            }
        }
        if (isset($options['minLength'])
            && \count($value) < $options['minLength'])
            throw new Exception(
                tr(__NAMESPACE__, 'Array has to few elements'),
                $value, $name, __METHOD__, $options
            );
        if (isset($options['maxLength'])
            && \count($value) > $options['maxLength'])
            throw new Exception(
                tr(__NAMESPACE__, 'Array has to much elements'),
                $value, $name, __METHOD__, $options
            );
        // return it
        return $value;
    }

    /**
     * Get a human readable description for validity as boolean.
     *
     * @param array $options for conformance only (not used)
     *
     * @return string explaining message
     */
    static function arraylistDescription(array $options)
    {
        if (isset($options['delimiter']))
            $desc = tr(
                __NAMESPACE__,
                'Multiple values can be added as array or with {delimiter} as delimiter.',
                array('delimiter' => $options['delimiter'])
            );
        else
            $desc = tr(
                __NAMESPACE__,
                'The value has to be an array.'
            );
        if (isset($options['notEmpty']) && $options['notEmpty'] === true)
            $desc .= ' '.tr(
                __NAMESPACE__,
                'An empty array is not allowed.'
            );
        if (isset($options['mandatoryKeys']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The keys {list} have to be present.',
                array('list' => String::dump($options['mandatoryKeys']))
            );
        if (isset($options['mandatoryKeys']) && isset($options['allowedKeys']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'Additionally the keys {list} are allowed.',
                array('list' => String::dump($options['allowedKeys']))
            );
        else if (isset($options['allowedKeys']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'Only the keys {list} are allowed.',
                array('list' => String::dump($options['allowedKeys']))
            );
        // range description
        if (isset($options['minLength']) && isset($options['maxLength']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The array has to consist of {min} to {max} elements.',
                array('min' => $options['minLength'],
                      'max' => $options['maxLength'])
            );
        else if (isset($options['minLength']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The array has to have {min} or more elements.',
                array('min' => $options['minLength'])
            );
        else if (isset($options['maxLength']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'The array has to have {max} or less elements.',
                array('max' => $options['maxLength'])
            );
        return $desc;
    }

    /**
     * Check for enumerated values.
     *
     * A readable name is given and converted mostly in numeric values.
     *
     * <b>Options:</b>
     * - \c values - list of possible values as hash with codes
     * - \c allowList - true if multiple entries can be selected
     * - \c delimiter - select the list separator for given value
     * (it can also be an regular expression)
     *
     * @param mixed   $value    value to be checked
     * @param string  $name     readable variable identification
     * @param array   $options  specific settings
     *
     * @return mixed  entry code or list of codes for option allowList
     * @throws Exception if not valid
     */
    static function enum($value, $name, array $options)
    {
        // name of origin have to be a string
        assert(is_string($name));
        assert(
            !isset($options['values'])
            || (is_array($options['values']) && count($options['values']) > 0)
        );
        assert(
            !isset($options['allowList'])
            || is_bool($options['allowList'])
        );
        assert(
            !isset($options['delimiter'])
            || is_string($options['delimiter'])
        );

        // process arrays
        if (isset($options['allowList']) && $options['allowList'] === true) {
            if (empty($value))
                throw new Exception(
                    tr(__NAMESPACE__, 'Value is an empty array'),
                    $value, $name, __METHOD__, $options
                );
            if (!isset($options['delimiter'])) $options['delimiter'] = ',';
            $value = self::arraylist(
                $value, $name, array('delimiter' => $options['delimiter'])
            );
            $result = array();
            foreach ($value as $entry) {
                if (!isset($options['values'][$entry]))
                    throw new Exception(
                        tr(
                            __NAMESPACE__,
                            'Value {value} is not allowed',
                            array('value' => $entry)
                        ), $value, $name, __METHOD__, $options
                    );
                $result[] = $options['values'][$entry];
            }
            return $result;
        }
        // process single value
        if (!isset($options['values'][$value]))
            throw new Exception(
                tr(__NAMESPACE__, 'Value is not allowed'),
                $value, $name, __METHOD__, $options
            );
        return $options['values'][$value];
    }

    /**
     * Get a human readable description for validity.
     *
     * @param array   $options  options from check
     *
     * @return string explaining message
     */
    static function enumDescription(array $options)
    {
        $desc = tr(__NAMESPACE__, 'The value has to be a text.');
        $desc .= ' '.tr(
            __NAMESPACE__,
            'The value can only be one of {list}.',
            array('list' => String::dump($options['values']))
        );
        if (isset($options['allowList']) && $options['allowList'] === true) {
            if (!isset($options['delimiter'])) $options['delimiter'] = ',';
            $desc .= ' '.self::arraylistDescription(
                array('delimiter' => $options['delimiter'])
            );
        }
        return $desc;
    }

}
