<?php
/**
 * @file
 * Validation Exception
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de
 */

namespace Alinex\Validator;

use Alinex\Validator;

/**
 * Validation Exception
 *
 * This Exception is used for failed data checks in a validator. In addition
 * to the short messaage getDetail() gives a more detailed overview of how the
 * value have to be set.
 */
class Exception extends \Exception
{
    /**
     * original message
     * @var array
     */
    private $_message = null;

    /**
     * value which was checked
     * @var mixed
     */
    private $_value = null;

    /**
     * readable origin identification
     * @var string
     */
    private $_name = null;

    /**
     * user specific name of Validator
     * @var string
     */
    private $_func = null;

    /**
     * called Validator method with class
     * @var string
     */
    private $_method = null;

    /**
     * filter options
     * @var array
     */
    private $_options = null;

    /**
     * Create a new exception
     *
     * @param string $message Failure description
     * @param mixed   $value    value which was checked
     * @param string  $name     readable origin identification
     * @param string  $method   called Validator method with class
     * @param array   $options  filter options
     * @param object $previous previous Exception
     */
    function __construct($message, $value, $name, $method = null,
        array $options = null, \Exception $previous = null)
    {
        // TRANS: default name of variables if not given in options
        if (!isset($name)) $name = tr(__NAMESPACE__, 'variable');
        $func = preg_replace('#^.*?(\w+(::|->)?\w+)$#', '$1', $method);
        $location = tr(
            __NAMESPACE__,
            ' detected by check \'{method}\' for variable \'{name}\'.',
            array('method' => $func, 'name' => $name)
        ) . PHP_EOL;
        parent::__construct($message.$location, null, $previous);
        $this->_message = $message;
        $this->_value = $value;
        $this->_name = $name;
        $this->_func = $func;
        $this->_method = $method;
        $this->_options = $options;
    }

    /**
     * Rewrite exception into new one with outer methos and options.
     *
     * @param string  $method   called Validator method with class
     * @param array   $options  filter options
     * @return \Alinex\Validator\Exception
     */
    function createOuter($method, array $options = null)
    {
        return new Exception(
            $this->_message, $this->_value, $this->_name,
            $method, $options,
            $this
        );
    }

#    public function __toString()
#    {
#        return $this->message . ' ' . $this->getDetail();
#    }

    /**
     * Get an additional and detailed documentation about the needed data
     * format.
     *
     * @return string Detailed data format description
     */
    function getDetail()
    {
        return Validator::describe($this->_name, $this->_method, $this->_options);
    }
}
