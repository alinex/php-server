<?php
/**
 * @file
 * Validator for network values.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de
 */

namespace Alinex\Validator;

use Alinex\Dictionary\Engine;

/**
 * Validator for network values.
 */
class Net
{
    /**
     * Check for port number.
     *
     * - \c disallowSystem - disallow the system ports 0-1023
     * - \c disallowUser - disallow the user ports 1024-49151
     * - \c disallowDynamic - disallow the dynamic ports 49152-65535
     * 
     * @param int $value    value to be checked
     * @param string  $name     readable variable identification
     * @param array   $options  options for allowed numbers
     * 
     * @return integer port number
     * @throws Exception if not valid
     */
    public static function port($value, $name, array $options = null)
    {
        // name of origin have to be a string
        assert(is_string($name));
        
        $options = self::pathOptions($options);
        try {
            // set the port ranges
            $intopt = array('type' => 16, 'unsigned' => true);
            if (isset($options['disallowSystem']) 
                && $options['disallowSystem'])
                $intopt['minRange'] = 1024;
            if (isset($options['disallowDynamic']) 
                && $options['disallowDynamic'])
                $intopt['maxRange'] = 49151;
            if (isset($options['disallowUser']) 
                && $options['disallowUser']) {
                if (isset($intopt['minRange']))
                    $intopt['minRange'] = 49152;
                else if (isset($intopt['maxRange']))
                    $intopt['maxRange'] = 1023;
            }
            return Type::integer(
                $value, $name, $intopt
            );
        } catch (Exception $ex) {
            throw $ex->createOuter(__METHOD__);
        }
    }

    /**
     * Optimize the options.
     *
     * @param array $options specific settings
     * @return array optimized options
     */
    private static function portOptions(array $options = null)
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
                        'disallowSystem',
                        'disallowUser',
                        'disallowDynamic'
                    )
                )
            ) == 0
        );
        // check options format
        assert(
            !isset($options['disallowSystem'])
            || is_bool($options['disallowSystem'])
        );
        assert(
            !isset($options['disallowUser'])
            || is_bool($options['disallowUser'])
        );
        assert(
            !isset($options['disallowDynamic'])
            || is_bool($options['disallowDynamic'])
        );
        // it's not possible to enable system and dynamic ports but disable user
        assert(
            (isset($options['disallowDynamic']) && $options['disallowDynamic'])
            || (!isset($options['disallowUser']) || !$options['disallowUser'])
            || (isset($options['disallowSystem']) && $options['disallowSystem'])
        );
        // it's not possible to disable all
        assert(
            isset($options['disallowDynamic']) && $options['disallowDynamic']
            && isset($options['disallowUser']) && $options['disallowUser']
            && isset($options['disallowSystem']) && $options['disallowSystem']
        );
            
        // return optimized array
        return $options;
    }

    /**
     * Get a human readable description for validity as port number.
     *
     * @param array   $options  options from check
     * @return string explaining message
     */
    public static function portDescription(array $options = null)
    {
        // set the port ranges
        $intopt = array('type' => 16, 'unsigned' => true);
        if (isset($options['disallowSystem']) 
            && $options['disallowSystem'])
            $intopt['minRange'] = 1024;
        if (isset($options['disallowDynamic']) 
            && $options['disallowDynamic'])
            $intopt['maxRange'] = 49151;
        if (isset($options['disallowUser']) 
            && $options['disallowUser']) {
            if (isset($intopt['minRange']))
                $intopt['minRange'] = 49152;
            else if (isset($intopt['maxRange']))
                $intopt['maxRange'] = 1023;
        }
        $desc = tr(__NAMESPACE__, 'The value has to be a port number.')
            .' '.Type::integerDescription($intopt);
        return $desc;
    }

    /**
     * Check for port number.
     *
     * @param mixed   $value    value to be checked
     * @param string  $name     readable variable identification
     * @param array   $options  for conformance only (not used)
     * 
     * @return string
     * @throws Exception if not valid
     */
    public static function host($value, $name, array $options = null)
    {
        try {
            return Type::string(
                $value, $name,
                array(
                    'minLength' => 3, 
                    'maxLength' => 255, 
                    'blacklist' => '/:@\n\r\ŧ\b\a'
                )
            );
        } catch (Exception $ex) {
            throw $ex->createOuter(__METHOD__);
        }
    }

    /**
     * Get a human readable description for validity as hostname.
     *
     * @param array   $options  options from check
     * @return string explaining message
     */
    public static function hostDescription(array $options = null)
    {
        $desc = tr(__NAMESPACE__, 'The value has to be a hostname.')
            .' '.Type::stringDescription(
                array(
                    'minLength' => 3, 
                    'maxLength' => 255, 
                    'blacklist' => '/:@\n\r\ŧ\b\a'
                )
            );
        return $desc;
    }

    /**
     * Check for IP addresses.
     *
     * <b>Options:</b>
     * - \c onlyIPV4 - only IPv4 addresses are valid
     * - \c onlyIPV6 - only IPv6 addresses are valid
     * - \c noPrivate - no private addresses allowed
     * - \c noReserved - no addresses in reserved range allowed
     *
     * @param mixed   $value    value to be checked
     * @param string  $name     readable variable identification
     * @param array   $options  specific settings
     * 
     * @return mixed  entry code or list of codes for option allowList
     * @throws Exception if not valid
     */
    private static function ip($value, $name, array $options = null)
    {
        if (isset($options['onlyIPV4']) && $options['onlyIPV4'] === true)
            $options['flags']  = \FILTER_FLAG_IPV4;
        if (isset($options['onlyIPV6']) && $options['onlyIPV6'] === true)
            $options['flags']  = \FILTER_FLAG_IPV6;
        if (isset($options['noPrivate']) && $options['noPrivate'] === true)
            $options['flags']  = \FILTER_FLAG_NO_PRIV_RANGE;
        if (isset($options['noReserved']) && $options['noReserved'] === true)
            $options['flags']  = \FILTER_FLAG_NO_RES_RANGE;
        // process single value
        $result = filter_var($value, \FILTER_VALIDATE_IP, $options);
        if ($result === null)
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'Value {value} is no valid ip address.',
                    array('value' => String::dump($value))
                ), $value, $name, __METHOD__, $options
            );
        // return result
        return $result;
    }

    /**
     * Get a human readable description for validity.
     *
     * @param array   $options  options from check
     * @return string explaining message
     */
    private static function ipDescription(array $options = null)
    {
        $desc = tr(__NAMESPACE__, "The value has to be a valid IP address.");
        if (!isset($options['onlyIPV4']) && $options['onlyIPV4'] === true)
            $desc .= ' '.tr(__NAMESPACE__, "Only IPv4 addresses are valid.");
        if (!isset($options['onlyIPV6']) && $options['onlyIPV6'] === true)
            $desc .= ' '.tr(__NAMESPACE__, "Only IPv6 addresses are valid.");
        if (!isset($options['noPrivate']) && $options['noPrivate'] === true)
            $desc .= ' '.tr(__NAMESPACE__, "No private addresses are allowed.");
        if (!isset($options['noReserved']) && $options['noReserved'] === true)
            $desc .= ' '.tr(
                __NAMESPACE__, "No addresses in the reserved range are allowed."
            );
        return $desc;
    }

}
