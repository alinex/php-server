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
 *
 * @todo complete coding
 */
class Date
{
    /**
     * Check for boolean type.
     *
     * The value can be 1, "true", "on", "yes" for \c true and 0, "false",
     * "off", "no", "" for \c false.
     *
     * @param mixed   $value    value to be checked
     * @param string  $name     readable variable identification
     *
     * @return boolean
     * @throws Exception if not valid
     */
    public static function boolean($value, $name)
    {
        // name of origin have to be a string
        assert(is_string($name));

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
     * @return string explaining message
     */
    static function booleanDescription()
    {
        // TRANS: description for the possible values for type boolean
        return tr(
            __NAMESPACE__,
            'The value has to be a boolean. The value will be true for 1, "true", "on", "yes" and it will be considered as false for 0, "false", "off", "no", "". Other values are not allowed.'
        );
    }



}
