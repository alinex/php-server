<?php
/**
 * @file
 * Base class for multiple implementations of key-value storages.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Storage;

use Alinex\Util\String;
use Alinex\Validator;

/**
 * Base class for multiple implementations of key-value storages.
 *
 * This is an abstract base class to implement key-value storages with different
 * backend engines. This implies different type of scope like request, server,
 * session, global or permanent.
 *
 * This storages are used as storage in Registry and Cache. Use one of this
 * class from the application level.
 *
 * <b>Array Access<</b>
 *
 * The storage is also usable like normal Arrays with:
 * @code
 * count($storage);
 * isset($storage[$offset]);
 * $value = $storage[$offset];
 * $regis$storagetry[$offset] = $value;
 * unset($storage[$offset]);
 * @endcode
 *
 * <b>Group</b>
 *
 * A group is a subpart of the entries with the same group name as key start
 * in storage. This will be prepended on set and removed on get to use with
 * shorter array keys.
 *
 * @see Alinex\Storage for overview
 */
abstract class Engine implements \Countable, \ArrayAccess
{
    const SCOPE_GLOBAL = 1;
    const SCOPE_LOCAL = 2;
    const SCOPE_SESSION = 3;

    const TYPE_SCALAR = 1;
    const TYPE_LIST = 2;
    const TYPE_HASH = 4;
    const TYPE_STRUCTURE = 8;
    const TYPE_OBJECT = 16;

    /**
     * Check if this storage is available.
     *
     * If not overwritten, the storage will also be seen as available like
     * there are no dependencies.
     *
     * @return bool true if storage can be used
     * @throws \Exception if something is missing.
     */
    protected static function check()
    {
        return true;
    }

    /**
     * Check if this storage is available.
     *
     * If not overwritten, the storage will also be seen as available like
     * there are no dependencies.
     *
     * @return bool true if storage can be used
     */
    final public static function isAvailable()
    {
        try {
            static::check();
        } catch (\Exception $ex) {
            return false;
        }
        return true;
    }

    /**
     * Instances of key-value storages
     * @var array
     */
    protected static $_instances = null;

    /**
     * Get an instance of storage class
     *
     * Through different context names it is possible to use the same storage
     * type and location independently multiple times. This is also needed for
     * separation of different applications if global storages are used.
     *
     * The context is mostly used as prefix for the real key name in the engine.
     *
     * @param string $context special name of this instance
     * @return object Instance of storage class
     */
    public static function getInstance($context = '')
    {
        assert(
            Validator::is(
                $context, 'context',
                'Type::string',
                array(
                    'maxLength' => 10, // maximal 10 char. prefix is used
                    'match' => '/[A-Za-z_.:]*/'
                    // pipe makes problems in session keys
                    // - used as separator for array contents
                )
            )
        );

        static::check();
        $class = get_called_class();
        if (! isset(self::$_instances[$class.'#'.$context]))
            self::$_instances[$class.'#'.$context] = new $class($context);
        return self::$_instances[$class.'#'.$context];
    }

    /**
     * Context name for this instance.
     * @var string
     */
    protected $_context = '';

    /**
     * Constructor
     *
     * This may be overwritten to implement some initialization of the storage
     * engine.
     *
     * @param string $context special name of this instance
     */
    protected function __construct($context)
    {
        $this->_context = $context;
    }

    /**
     * Clone - prevent additional instances of the class.
     *
     * Better open a new instance with different context name.
     * @codeCoverageIgnore
     */
    private function __clone()
    {
        // not allowed
    }

    /**
     * Check that the key is possible.
     *
     * This method will be used in assert calls to check the key for
     * conformance with all engines. In production code this method
     * won't be needed.
     *
     * @param string $key name of the entry
     * @return type
     * @throws Validator\Exception if not valid
     */
    static final protected function checkKey($key)
    {
        return Validator\Type::string(
            $key, 'key',
            array(
                'minLength' => 1,      // empty string is disallowed
                'maxLength' => 240,    // maximal 250 characters in memcache
                                       // max. 10 char. prefix is used
                'match' => '/[A-Za-z_.]*/'
                // pipe makes problems in session keys
                // - used as separator for array contents
                // special char not possible in ini export/import
           )
        );
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
    abstract public function set($key, $value = null);

    /**
     * Method to get a storage variable
     *
     * @param string $key   name of the entry
     * @return mixed value or NULL if entry is missing
     */
    abstract public function get($key);

    /**
     * Unset a storage variable
     *
     * @param string $key   name of the entry
     * @return bool    TRUE on success otherwise FALSE
     */
    abstract public function remove($key);

    /**
     * Check if storage variable is defined
     *
     * @param string $key   name of the entry
     * @return bool    TRUE on success otherwise FALSE
     */
    abstract public function has($key);

    /**
     * Get the list of keys from storage
     *
     * @return array list of keys
     */
    abstract public function keys();

    /**
     * Reset storage for this context
     *
     * This will be done in a common way by removing every single element.
     * For storage engines, which allow easier purging this may be overwritten.
     *
     * @return bool    TRUE on success otherwise FALSE
     */
    public function clear()
    {
        if (!$this->keys())
            return false;
        foreach ($this->keys() as $key)
            $this->remove($key);
        return true;
    }

    /**
     * Get all values which start with the given string.
     *
     * The key name will be shortened by cropping the group name from the start.
     *
     * @param string $group start phrase for selected values
     * @return array list of values
     */
    public function groupGet($group)
    {
        assert(is_string($group));

        $result = array();
        foreach ($this->keys() as $key) {
            if (strlen($group) == 0)
                $result[$key] = $this->get($key);
            else if (String::startsWith($key, $group))
                $result[substr($key, strlen($group))] = $this->get($key);
        }
        return $result;
    }

    /**
     * Set a list of values as group.
     *
     * The key names will be prepended with the group name given.
     *
     * @param string $group base name of the group
     * @param array $values
     */
    public function groupSet($group, array $values)
    {
        assert('is_string($group)');

        if (isset($values))
        foreach ($values as $key => $value) {
            $key = $group.$key;
            $this->set($key, $value);
        }
        return true;
    }

    /**
     * Reset group in storage
     *
     * @param string $group basename of the group
     * @return bool    TRUE on success otherwise FALSE
     */
    public function groupClear($group)
    {
        assert(is_string($group));

        $result = false;
        foreach ($this->keys() as $key) {
            if (!String::startsWith($key, $group))
                continue;
            $this->remove($key);
            $result = true;
        }
        return $result;
    }

    /**
     * Get the number of elements in the storage.
     *
     * This method will be called also with:
     * @code
     * count($storage).
     * @endcode
     *
     * @return integer number of values in the storage
     */
    public function count()
    {
        return count($this->keys());
    }

    /**
     * Check if key exists for ArrayAccess
     *
     * @code
     * isset($storage[$offset])
     * @endcode
     *
     * @param string $offset name of storage entry
     *
     * @return boolean true if key exists
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Get key for ArrayAccess
     *
     * @code
     * isset($storage[$offset])
     * @endcode
     *
     * @param string $offset name of storage entry
     * @return mixed storage entry
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Set key through ArrayAccess
     *
     * @code
     * isset($storage[$offset])
     * @endcode
     *
     * @param string $offset name of storage entry
     * @param mixed $value value to storage
     * @return mixed value which was just set
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Unset key using ArrayAccess
     *
     * @code
     * unset($storage[$offset])
     * @endcode
     *
     * @param string $offset name of storage entry
     */
    public function offsetUnset($offset)
    {
        $this->set($offset);
    }

    /**
     * Check how good the engine is for specified data.
     * 
     * @param mixed $value data to store
     * @param int $scope identification of preferred scope
     * @return float quality (0 impossible, 1 best)
     */
    public function allow($value, $scope)
    {
        return 1;
    }
}
