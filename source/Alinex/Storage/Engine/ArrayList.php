<?php
/**
 * @file
 * Storage keeping values in local static array.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Storage\Engine;

use Alinex\Storage;

/**
 * Storage keeping values in local array.
 *
 * This class will store the values locally in an array. This scope will stay
 * over multiple requests only if an opcode system stores them. A better
 * approach is to use the opcode user cache directly.
 *
 * This is the simplest storage, it will last till the end of the php process
 * (mostly the request). It has no dependencies but the performance is high
 * but its usability is poor because of the restricted scope.
 *
 * @see Storage
 */
class ArrayList extends Storage\Engine
{
    /**
     * Storage for context with values
     * @var array
     */
    static protected $_data = array();

    /**
     * Reference to this context storage in $_data.
     * @var array
     */
    private $_storage = null;

    /**
     * Constructor
     *
     * @param string $context special name of this instance
     */
    protected function __construct($context)
    {
        parent::__construct($context);
        // create context store
        if (!isset(self::$_data[$this->_context]))
            self::$_data[$this->_context] = array();
        $this->_storage =& self::$_data[$this->_context];
    }

    /**
     * Method to set a storage variable
     *
     * @param string $key   name of the entry
     * @param string $value Value of storage key null to remove entry
     *
     * @return mixed value which was set
     * @throws \Alinex\Validator\Exception
     */
    public function set($key, $value = null)
    {
        if (!isset($value)) {
            $this->remove($key);
            return null;
        }
        $this->checkKey($key);
        return $this->_storage[$key] = $value;
    }

    /**
     * Unset a storage variable
     *
     * @param string $key   name of the entry
     * @return bool    TRUE on success otherwise FALSE
     */
    public function remove($key)
    {
        if (isset($this->_storage[$key])) {
            unset($this->_storage[$key]);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Method to get a variable
     *
     * @param  string  $key   array key
     * @return mixed value on success otherwise NULL
     */
    public function get($key)
    {
        $this->checkKey($key);
        return isset($this->_storage[$key])
            ? $this->_storage[$key]
            : NULL;
    }

    /**
     * Check if storage variable is defined
     *
     * @param string $key   name of the entry
     * @return bool    TRUE on success otherwise FALSE
     */
    public function has($key)
    {
        $this->checkKey($key);
        return isset($this->_storage[$key]);
    }

    /**
     * Get the list of keys
     *
     * @return array   list of key names
     */
    public function keys()
    {
        return array_keys($this->_storage);
    }

    /**
     * Reset storage for this context
     *
     * @return bool    TRUE on success otherwise FALSE
     */
    public function clear()
    {
        if (!$this->_storage)
            return false;
        $this->_storage = array();
        return true;
    }

    /**
     * Get the number of elements.
     *
     * This method will called also with:
     * @code
     * count($storage).
     * @endcode
     *
     * @return integer number of values
     */
    public function count()
    {
        return count($this->_storage);
    }

}