<?php
/**
 * @file
 * Methods to simplify the work with array structures.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Util;
use Exception;

/**
 * Methods to simplify the work with array structures.
 *
 * This class collects different static methods which will help simplifying
 * the work with array-like structures. This gives an easy way to access and
 * work with array of arrays of arrays...
 *
 * <b>Path access</b>
 * - set() - set a value
 * - get() - get a value
 * - has() - check if value is set
 * - remove() - remove the element
 *
 * With these methods you may set/get scalar values as also arrays which are
 * stored or extracted as substructures.
 *
 * @attention
 * It is not possible to store a value in a branch, this will lead to a
 * replacement of the branch or the value.
 */
class ArrayStructure
{
    /**
     * Set, get or unset a value in the array structure using path.
     *
     * @param array &$array array structure to use
     * @param array|string $path path under which to store it
     * @param string $delim delimiter used in path (if string)
     * @param mixed $value value to be set
     * @param bool $unset should the value be removed
     * @return mixed value which was set or NULL for problems
     * @throws Exception if $path cannot be converted to string
     */
    static private function path(
        array &$array, $path, $delim=null, $value=null, $unset=false
    )
    {
        // delimiter must be an string
        assert(!isset($delim) || is_string($delim));
        $ref = &$array;

        // convert $path to array
        if (! is_array($path) && strlen($delim))
            $path = explode($delim, $path);
        else if (is_scalar($path) )
            $path = array($path);
        if (! is_array($path))
            throw new Exception(
                "Path has to be array or string which will be split up using ".
                "\$delim as separator."
            );
        // step through array alongsite the path
        while (count($path)) {
            $key = array_shift($path);
            if (! $path && $unset) {
                // unset if neccessary
                unset($ref[$key]);
                unset($ref);
                $ref = NULL;
            } else if (!isset($ref[$key]) && !isset($value)) {
                return null;
            } else {
                if (!isset($ref[$key]))
                    $ref[$key] = array();
                $ref =& $ref[$key];
            }
        }
        // set the value
        if ( isset($value) && ! $unset )
            $ref = $value;

        return $ref;
    }

    /**
     * Set the value in the array structure using path.
     *
     * @param mixed $value value to be set
     * @param array &$array array structure to use
     * @param array|string $path path under which to store it
     * @param string $delimiter delimiter used in path (if string)
     * @return mixed value which was set or NULL if not
     */
    static function set($value, array &$array, $path, $delimiter = null)
    {
        return self::path($array, $path, $delimiter, $value);
    }

    /**
     * Get the value from the array structure using path.
     *
     * @param array $array array structure to use
     * @param array|string $path path under which to store it
     * @param string $delimiter delimiter used in path (if string)
     * @return mixed value which was found or NULL if not found
     */
    static function get(array &$array, $path, $delimiter = null)
    {
        return self::path($array, $path, $delimiter);
    }

    /**
     * Remove the element from the array structure using path.
     *
     * @param array $array array structure to use
     * @param array|string $path path under which to store it
     * @param string $delimiter delimiter used in path (if string)
     * @return mixed removed value which was set or NULL if not found
     */
    static function remove(array &$array, $path, $delimiter = null)
    {
        return self::path($array, $path, $delimiter, null, true);
    }

    /**
     * Check if a value is set in the array structure using path.
     *
     * @param array $array array structure to use
     * @param array|string $path path under which to store it
     * @param string $delimiter delimiter used in path (if string)
     * @return bool true if value is set
     */
    static function has(array $array, $path, $delimiter = null)
    {
        // delimiter have to be a string
        assert(!isset($delimiter) || is_string($delimiter));
        $ref = &$array;
        // convert $path to array
        if (! is_array($path) && strlen($delimiter))
            $path = explode($delimiter, $path);
        else if (is_scalar($path))
            $path = array($path);
        if (! is_array($path))
            throw new Exception(
                "Path has to be array or string which will be split up using ".
                "\$delim as separator."
            );

        foreach ($path as $key) {
            if (!array_key_exists($key, $ref))
                return false;
            $ref =& $ref[$key];
        }

        return true;
    }

    /**
     * Fast and efficient check für associative arrays.
     *
     * @param array $array to check
     * @return bool true if array is associative
     */
    static function isAssoc($array)
    {
        if (!is_array($array))
            return false;

        // method 1: fastest with a more memmory usage
        return (array_values($array) !== $array);

        // method 2: medium speed lower memory
        // compares the keys (which for a sequential array are always 0,1,2 etc)
        // to the keys of the keys (which will always be 0,1,2 etc)
        $array = array_keys($a);
        return ($array != array_keys($array));

        // method 3: low speed with less memory
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }


}