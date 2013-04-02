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
     * @param int $value    value to be checked
     * @param string  $name     readable variable identification
     * @param array   $options  for conformance only (not used)
     * 
     * @return integer port number
     * @throws Exception if not valid
     */
    public static function port($value, $name, array $options = null)
    {
        try {
            return Type::integer(
                $value, $name,
                array('type' => 16, 'unsigned' => true)
            );
        } catch (Exception $ex) {
            throw $ex->createOuter(__METHOD__);
        }
    }

    /**
     * Get a human readable description for validity as port number.
     *
     * @param array   $options  options from check
     * @return string explaining message
     */
    public static function portDescription(array $options = null)
    {
        $desc = tr(__NAMESPACE__, 'The value has to be a port number.')
            .' '.Type::integerDescription(
                array('type' => 16, 'unsigned' => true)
            );
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
