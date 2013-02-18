<?php
/**
 * @file
 * Additional string methods to simplify work.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Util;

/**
 * Additional string methods to simplify work.
 *
 * @note Most methods are only simplifications which use some of the PHP
 * internal string functions and combine them.
 */
class String
{
    /**
     * Check if string starts with given phrase.
     *
     * @param string $haystack The string to search in.
     * @param string $needle Note that the needle may be a string of one or
     * more characters.
     * @return bool TRUE if haystack starts with needle
     */
    public static function startsWith($haystack, $needle)
    {
        assert(is_string($haystack));
        assert(is_string($needle));
        return substr($haystack, 0, strlen($needle)) == $needle;
    }

    /**
     * Check if string ends with given phrase.
     *
     * @param string $haystack The string to search in.
     * @param string $needle Note that the needle may be a string of one or
     * more characters.
     * @return bool TRUE if haystack ends with needle
     */
    public static function endsWith($haystack, $needle)
    {
        assert(is_string($haystack));
        assert(is_string($needle));
        if (!isset($needle) || !$needle)
            return true;
        return substr($haystack, -strlen($needle)) == $needle;
    }

    /**
     * Dump variable or text human readable with quoting
     *
     * This method is used to output elements within an description in a clean
     * manner:
     * @verbatim
     * <null>       -> null
     * <number>     -> 328
     * <string>     -> "string" without masking of double quotes
     * <list array> -> ["value", "value", "value"]
     * <map array>  -> ["key" => 'value', "key" => "value"]
     * <object>     -> [class]
     * @endverbatim
     *
     * @param mixed $object which should be set in single quotes
     * @param integer $depth level of depth to run
     *
     * @return string describing the values content
     */
    public static function dump($object, $depth = 1)
    {
        if (is_null($object))
            return 'null';
        if (is_bool($object))
            return $object ? 'true' : 'false';
        if (is_string($object))
            return '"'.$object.'"';
        if (is_array($object)) {
            $islist = array_values($object) == $object;
            if ($depth == 0)
                return $islist ? '[list]' : '[map]';
            $buf = '';
            foreach ($object as $key => $entry)
                $buf .= ', '. ($islist
                    ? self::dump($entry, $depth-1)
                    : self::dump($key, $depth-1).' => '.
                        self::dump($entry, $depth-1));
            return '['.substr($buf, 2).']';
        }
        if (is_object($object))
            return '['.get_class($object).']';
        return (string) $object;
    }

    /**
     * Mask all regular expression characters to work as normal char in
     * the regexp functions.
     *
     * @param string $value string to be masked
     * @return string optimized string
     */
    static function pregMask($value)
    {
        assert(is_string($value));

        return \preg_replace(
            '/([\/()\[\]\^\$.*?{}-])/', '\\\\$1', $value
        );
    }

    /**
     * Break text into multiple lines using wordwrap.
     *
     * @param string $string
     * @param int $width
     * @param string $break
     * @param boolean $cut
     * @return string resulting text
     */
    static function wordbreak(
        $string, $width = 75, $break = PHP_EOL, $cut = false
    )
    {
        $array = explode("\n", $string);
        $string = "";
        foreach ($array as $value) {
            do {
                $line = wordwrap($value, $width, $break, $cut);
                $value = trim(substr($value, strlen($line)));
                $string .= $line.PHP_EOL;
            } while ($value);
        }
        return trim($string);
    }

    /**
     * Convert string to it's originating type.
     *
     * @param string $var string to be converted
     * @return mixed same information in matching type
     */
    static function convertType($var)
    {
        if (is_numeric($var))
            return (float) $var != (int) $var
                ? (float) $var
                : (int) $var;
        // check for boolean
        if ($var == 'true')
            return true;
        if ($var == 'false')
            return false;
        // else return unchanged
        return $var;
    }
}
