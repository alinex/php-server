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
        '1e+24' => 'Y',
        '1e+21' => 'Z',
        '1e+18' => 'E',
        '1e+15' => 'P',
        '1e+12' => 'T',
        '1e+9'  => 'G',
        '1e+6'  => 'M',
        '1e+3'  => 'k',
// seldom used        
//        '1e+2'  => 'h',
//        '1e+1'  => 'da',
//        '1e-1'  => 'd',
//        '1e-2'  => 'c',
        '1e-3'  => 'm',
        '1e-6'  => 'μ',
        '1e-9'  => 'n',
        '1e-12' => 'p',
        '1e-15' => 'f',
        '1e-18' => 'a',
        '1e-21' => 'z',
        '1e-24' => 'y'
    );

    /**
     * Prefixes for biunary conversion based upon 1024.
     * @var array
     */
    private static $_binaryPrefixes = array(
        '1208925819614629174706176' => 'Yi',
        '1180591620717411303424' => 'Zi',
        '1152921504606846976' => 'Ei',
        '1125899906842624' => 'Pi',
        '1099511627776' => 'Ti',
        '1073741824' => 'Gi',
        '1048576'  => 'Mi',
        '1024'  => 'Ki',
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
     * Prefixes which use other units.
     * @var array
     */
    private static $_fixUnit = array(
        'Mg' => array('','t'),
        'Gg' => array('K','t'),
        'Tg' => array('M','t'),
        'Pg' => array('T','t'),
        'Eg' => array('P','t'),
        'Zg' => array('E','t'),
        'Yg' => array('Z','t'),
        'kt' => array('K','t'),
        'mt' => array('k','g'),
        'μt' => array('','g'),
        'nt' => array('m','g'),
        'pt' => array('μ','g'),
        'ft' => array('n','g'),
        'at' => array('p','g'),
        'zt' => array('f','g'),
        'yt' => array('a','g')
    );
    
    /**
     * Fix the value and unit output.
     * 
     * Some things are not possible between unit and prefix like Mg will be
     * called t.
     * 
     * @param numeric $value value to be used
     * @param string $prefix prefix name
     * @param string $unit prefix with unit entry
     * @param int $digits number of floating point digits
     * @param bool $long should the long format be used
     * @return string combined final value
     */
    private static function formatValue($value, $prefix, $unit, $digits = 0,
            $long = false)
    {
        if (isset(self::$_fixUnit[$prefix.$unit]))
            list($prefix, $unit) = self::$_fixUnit[$prefix.$unit];
        return sprintf('%1.'.$digits.'f', $value)
            . ($long ? self::getPrefixName($prefix, $value) : $prefix)
            . $unit;
    }
    
    /**
     * Convert numeric value in human readable short or long notation.
     * 
     * @param numeric $value to be converted
     * @param string $unit to be added
     * @param int $digits number of floating point digits
     * @param bool $long should the long format be used
     * @param bool $binary for binary representation (1024 based)
     * 
     * @return string optimized value
     */
    public static function toUnit($value, $unit, $digits=0,
        $long = false, $binary = false)
    {
        $sign = $value < 0 ? '-' : '';
        $value = abs($value);
        if ($binary) {
            foreach (self::$_binaryPrefixes as $num => $prefix)
                if ($value > $num*2)
                    return self::formatValue(
                        $sign.round($value/$num, $digits), 
                        $prefix, $unit, $digits, $long
                    );
            return self::formatValue($sign.round($value, $digits), '', $unit, $digits);
        } else if ($value > 100) {
            foreach (self::$_prefixes as $num => $prefix) {
                if ($num < 1) break;
                if ($value > $num*2)
                    return self::formatValue(
                        $sign.round($value/$num, $digits),
                        $prefix, $unit, $digits, $long
                    );
            }
        } else if ($value < 1) {
            foreach (self::$_prefixes as $num => $prefix)
                if ($num < 1 && $value > $num*10)
                    return self::formatValue(
                        $sign.round($value/$num, $digits),
                        $prefix, $unit, $digits, $long
                    );
        }
        return self::formatValue($sign.round($value, $digits), '', $unit, $digits);
    }

    /**
     * Convert numeric value in human readable binary (1024 based) notation.
     * 
     * @param numeric $value to be converted
     * @param string $unit to be added
     * @param int $digits number of floating point digits
     * @param bool $long should the long format be used
     * 
     * @return string optimized value
     */
    static function toBinaryUnit($value, $unit, $digits=0,
        $long = false)
    {
        assert($value > 1);
        return self::toUnit($value, $unit, $digits, $long, true);
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
                return trn(__NAMESPACE__, 'year', 'years', $value);
            case 'month':
                return trn(__NAMESPACE__, 'month', 'months', $value);
            case 'day':
                return trn(__NAMESPACE__, 'day', 'days', $value);
            case 'hour':
                return trn(__NAMESPACE__, 'hour', 'hours', $value);
            case 'min':
                return trn(__NAMESPACE__, 'minute', 'minutes', $value);
            case 'sec':
                return trn(__NAMESPACE__, 'second', 'seconds', $value);
        }
    }

    /**
     * Convert number of seconds to human redable value.
     * @param int $secs number of seconds
     * @param bool $shorten shorten the number to only one unit or use exact
     * value
     * @return string representation of value
     */
    static function toTimerange($secs, $shorten = false)
    {
        $units = array(
            // 365 x 4 + 1 = 1.461 Tage in 4 Jahren
            // 1461 x 25 - 1 = 36.524 Tage in 100 Jahren
            // 36.524 x 4 + 1 = 146.097 Tage in 400 Jahren
            // 146.097 / 400 = 365,2425 Tage pro Jahr
            '31556952' => 'year',
            // 365,2425 / 12 = 30,436875 Tage im Monat
            '2629746' => 'month',
            // 24 * 3600
            '86400' => 'day',
            '3600' => 'hour',
            '60' => 'min',
            '0' => 'sec'
        );
        // specifically handle zero
        if ( $secs == 0 ) return '0 '.self::getTimeUnitName('sec', 0);

        if ($shorten) {
            foreach ($units as $num => $unit) {
                if (!$num)
                    return $secs . ' ' . self::getTimeUnitName($unit, $secs);
                if ($secs > $num*2) {
                    $secs = round($secs/$num);
                    return $secs . ' ' . self::getTimeUnitName($unit, $secs);
                }
            }
        }
        if ($shorten || $secs == 0) 
            return $secs . ' ' . self::getTimeUnitName($unit, $secs);
        // else use exact form
        $res = "";
        foreach ($units as $divisor => $unit) {
            if ($divisor == 0) {
                if ($secs != 0)
                    $res .= $secs . ' ' . self::getTimeUnitName($unit, $secs).", ";
                break;;
            }
            $quot = intval($secs / $divisor);
            if ($quot) {
                $res .= $quot . ' ' . self::getTimeUnitName($unit, $quot).", ";
                $secs -= $quot * $divisor;
            }
        }
        error_log($res);
        return substr($res, 0, -2);
    }

}
