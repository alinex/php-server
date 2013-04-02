<?php
/**
 * @file
 * Additional number manipulation methods to simplify work.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Util;

/**
 * Additional number manipulation methods to simplify work.
 */
class Number
{
    /**
     * Prefixes used to convert numeric values to human readable notation.
     * @var array
     */
    private static $_prefixes = array(
        1e+24 => 'Y',
        1e+21 => 'Z',
        1e+18 => 'E',
        1e+15 => 'P',
        1e+12 => 'T',
        1e+9  => 'G',
        1e+6  => 'M',
        1e+3  => 'k',
        1e+2  => 'h',
        1e+1  => 'da',
        1e-1  => 'd',
        1e-2  => 'c',
        1e-3  => 'm',
        1e-6  => 'μ',
        1e-9  => 'n',
        1e-12 => 'p',
        1e-15 => 'f',
        1e-18 => 'a',
        1e-21 => 'z',
        1e-24 => 'y'
    );

    /**
     * Prefixes for biunary conversion based upon 1024.
     * @var array
     */
    private static $_binaryPrefixes = array(
        1208925819614629174706176 => 'Yi',
        1180591620717411303424 => 'Zi',
        1152921504606846976 => 'Ei',
        1125899906842624 => 'Pi',
        1099511627776 => 'Ti',
        1073741824 => 'Gi',
        1048576  => 'Mi',
        1024  => 'Ki',
    );

    /**
     * Get the full name of the prefix to get a long form.
     * @param string $prefix prefix to be used
     * @param numeric $value to decide for singular or plural forms
     * @return string long prefix name for this value
     */
    private static function getPrefixName($prefix, $value = 1)
    {
        assert(
            in_array($prefix, self::$_prefixes)
            || in_array($prefix, self::$_binaryPrefixes)
        );
        assert(is_numeric($value));
        switch ($prefix) {
            case 'Y':
                return trn(__NAMEPSACE__, 'septillion', 'septillions', $value);
            case 'Z':
                return trn(__NAMEPSACE__, 'sextillion', 'sextillions', $value);
            case 'E':
                return trn(__NAMEPSACE__, 'quintillion', 'quintillions', $value);
            case 'P':
                return trn(__NAMEPSACE__, 'quadrillion', 'quadrillions', $value);
            case 'T':
                return trn(__NAMEPSACE__, 'trillion', 'trillions', $value);
            case 'G':
                return trn(__NAMEPSACE__, 'billion', 'billions', $value);
            case 'M':
                return trn(__NAMEPSACE__, 'million', 'millions', $value);
            case 'k':
                return trn(__NAMEPSACE__, 'thousand', 'thousands', $value);
            case 'h':
                return trn(__NAMEPSACE__, 'hundred', 'hundreds', $value);
            case 'da':
                return trn(__NAMEPSACE__, 'ten', 'tens', $value);
            case 'd':
                return trn(__NAMEPSACE__, 'tenth', 'tenths', $value);
            case 'c':
                return trn(__NAMEPSACE__, 'hundredth', 'hundredths', $value);
            case 'm':
                return trn(__NAMEPSACE__, 'thousandth', 'thousandths', $value);
            case 'μ':
                return trn(__NAMEPSACE__, 'millionth', 'millionths', $value);
            case 'n':
                return trn(__NAMEPSACE__, 'billionth', 'billionths', $value);
            case 'p':
                return trn(__NAMEPSACE__, 'trillionth', 'trillionths', $value);
            case 'f':
                return trn(__NAMEPSACE__, 'quadrillionth', 'quadrillionths', $value);
            case 'a':
                return trn(__NAMEPSACE__, 'quintillionth', 'quintillionths', $value);
            case 'z':
                return trn(__NAMEPSACE__, 'sextillionth', 'sextillionths', $value);
            case 'y':
                return trn(__NAMEPSACE__, 'septillionth', 'septillionths', $value);
            case 'Ki':
                return tr(__NAMEPSACE__, 'kibi', $value);
            case 'Mi':
                return tr(__NAMEPSACE__, 'mibi', $value);
            case 'Gi':
                return tr(__NAMEPSACE__, 'gibi', $value);
            case 'Ti':
                return tr(__NAMEPSACE__, 'tibi', $value);
            case 'Pi':
                return tr(__NAMEPSACE__, 'pibi', $value);
            case 'Ei':
                return tr(__NAMEPSACE__, 'eibi', $value);
            case 'Zi':
                return tr(__NAMEPSACE__, 'zibi', $value);
            case 'Yi':
                return tr(__NAMEPSACE__, 'yibi', $value);
        }
    }

    /**
     * Convert numeric value in human readable short or long notation.
     * @param numeric $value to be converted
     * @param string $unit to be added
     * @param int $digits number of floating point digits
     * @param bool $long should the long format be used
     * @param bool $binary for binary representation (1024 based)
     * @return string optimized value
     */
    public static function useUnit($value, $unit, $digits=0,
        $long = false, $binary = false)
    {
        if ($binary) {
            foreach (self::$_binaryPrefixes as $num => $prefix)
                if ($value > $num*10)
                    return round($value/$num, $digits) . ' ' . ($long ?
                        self::getPrefixName($prefix, $value).' ' : $prefix)
                        . $unit;
            return round($value, $digits) . ' ' . $unit;
        } else if ($value > 100) {
            foreach (self::$_prefixes as $num => $prefix)
                if ($value > $num*10)
                    return round($value/$num, $digits) . ' ' . ($long ?
                        self::getPrefixName($prefix, $value).' ' : $prefix)
                        . $unit;
        } else if ($value < 1) {
            foreach (self::$_prefixes as $num => $prefix)
                if ($num < 1 && $value > $num*10)
                    return round($value/$num, $digits) . ' ' . ($long ?
                        self::getPrefixName($prefix, $value).' ' : $prefix)
                        . $unit;
        }
        return round($value, $digits) . ' ' . $unit;
    }

    /**
     * Convert numeric value in human readable binary (1024 based) notation.
     * @param numeric $value to be converted
     * @param string $unit to be added
     * @param int $digits number of floating point digits
     * @param bool $long should the long format be used
     * @param bool $binary for binary representation (1024 based)
     * @return string optimized value
     */
    static function useBinaryUnit($value, $unit, $digits=0,
        $long = false)
    {
        assert($value > 1);
        return self::valueToHuman($value, $unit, $digits, $long, true);
    }

    /**
     * Get the full name of the prefix to get a long form.
     * @param string $unit time unit to be used
     * @param numeric $value to decide for singular or plural forms
     * @return string long prefix name for this value
     */
    private static function getTimeUnitName($unit, $value = 1)
    {
        assert(is_numeric($value));
        switch ($unit) {
            case 'year':
                return trn(__NAMEPSACE__, 'year', 'years', $value);
            case 'month':
                return trn(__NAMEPSACE__, 'month', 'months', $value);
            case 'day':
                return trn(__NAMEPSACE__, 'day', 'days', $value);
            case 'hour':
                return trn(__NAMEPSACE__, 'hour', 'hours', $value);
            case 'min':
                return trn(__NAMEPSACE__, 'minute', 'minutes', $value);
            case 'sec':
                return trn(__NAMEPSACE__, 'second', 'seconds', $value);
        }
    }

    /**
     * Convert number of seconds to human redable value.
     * @param int $secs number of seconds
     * @param bool $shorten shorten the number to only one unit or use exact
     * value
     * @return string representation of value
     */
    static function toTimerange($secs, $shorten = true)
    {
        $units = array(
                365*24*3600 => 'year',
                30*24*3600 => 'month',
                24*3600 => 'day',
                3600 => 'hour',
                60 => 'min',
                0 => 'sec'
        );
        // specifically handle zero
        if ( $secs == 0 ) return '0 '.self::getTimeUnitName('sec', 0);

        $s = "";

        if ($shorten) {
            foreach ($units as $num => $unit)
                if ($secs > $num*10)
                    return round($secs/$num) . ' ' .
                        self::getTimeUnitName($unit, $secs);
            return $secs . ' ' . self::getTimeUnitName($unit, $secs);
        }
        // else use exact form
        foreach ($units as $divisor => $unit) {
            $quot = intval($secs / $divisor);
            if ($quot) {
                $s .= $quot.self::getTimeUnitName($unit, $quot).", ";
                $secs -= $quot * $divisor;
            }
        }
        return substr($s, 0, -2);
    }

}
