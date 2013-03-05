<?php
/**
 * @file
 * Validator helper functions.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de
 */

namespace Alinex;

use Alinex\Validator;

/**
 * Validator helper functions.
 *
 * This functions will help to easy use and call all the validation methods.
 */
class Validator
{
    /**
     * Call a validator method as specified in array.
     *
     * The document may be called with one of the following types:
     *
     * <b>As string:</b>
     * @code
     * Validator::check(
     *   'Alinex\Validator\Type::integer',
     *   $options, $value, $name
     * );
     * @endcode
     *
     * <b>As array:</b>
     * @code
     * Validator::check(
     *   array('Alinex\Validator\Type', 'integer'),
     *   $options, $value, $name
     * );
     * @endcode
     *
     * <b>Or to call other methods (not sublass of Validator)</b>
     *
     * Therefore you can use the above syntax or also call object methods.
     *
     * @param mixed $value value to check
     * @param string $name name of origin
     * @param string|array $callback specification of validator as callback
     * @param array $options additional options for the Validator
     *
     * @return mixed cleaned value
     * @throws Validator\Exception if not valid
     */
    public static function check($value, $name, $callback,
        array $options = null)
    {
        $callback = Validator\Code::callable(
            $callback, 'callback', array('relative' => __CLASS__)
        );

        return call_user_func_array($callback, array($value, $name, $options));
    }

    /**
     * Check the given value for conformance.
     *
     * This will do the same as check() but won't throw an exception or change
     * the value but return a boolean value.
     *
     * @param mixed $value value to check
     * @param string $name name of origin
     * @param string|array $callback specification of validator as callback
     * @param array $options additional options for the Validator
     *
     * @return true on validity
     */
    public static function is($value, $name, $callback,
        array $options = null)
    {
        try {
            self::check($value, $name, $callback, $options);
        } catch (Validator\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Get a human readable description of the expected type.
     *
     * @param string $name name of origin
     * @param string|array $callback specification of validator as callback
     * @param array $options additional options for the Validator
     *
     * @return string description of given type
     * @throws Validator\Exception if not valid
     */
    public static function describe($name, $callback, array $options = null)
    {
        $callback = Validator\Code::callable(
            $callback, 'callback', array('relative' => __CLASS__)
        );
        $message = '';
        // predefined help
        if (isset($name))
            $message .= tr(
                __NAMESPACE__,
                'Specification for \'{name}\': ',
                array('name' => $name)
            ).PHP_EOL;
        if (isset($options['description']))
            $message .= $options['description'].PHP_EOL;
        // specific help
        if (is_array($callback))
            $callback[count($callback)-1] = $callback[count($callback)-1].
                'Description';
        else
            $callback .= 'Description';
        if (!isset($options))
            $options = array();
        $message .= call_user_func($callback, $options);

        return $message;
    }

}
