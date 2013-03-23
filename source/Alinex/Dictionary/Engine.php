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

namespace Alinex\Dictionary;

use Alinex\Util\String;
use Alinex\Validator;

/**
 * Base class for multiple implementations of key-value storages.
 *
 * This is an abstract base class to implement key-value storages with different
 * backend engines. This implies different type of scope like request, server,
 * session, global or permanent.
 *
 * On each engine the following operations are possible:
 * - <b>simple accessors</b>: set(), get(), has(), remove()
 * - <b>array access</b>: offsetSet(), offsetGet(), offsetExists(),
 * offsetUnset()
 * - <b>group access</b>: groupSet(), groupGet(), groupClear()
 * - <b>value editing</b>: incr(), decr(), append()
 * - <b>hash access</b>: hashSet(), hashGet(), hashHas(), hashRemove(),
 * hashCount()
 * - <b>list access</b>: listPush(), listPop(), listShift(), listUnshift(),
 * listGet(), listSet(), listCount()
 * - <b>overall control</b>: count(), keys(), clear()
 *
 * This engines are used as storage in Registry and Cache. Use one of this
 * class from the application level.
 *
 * For this to work some engine specifica are set by each enginelike $_scope,
 * $_performance, $_persistence and the $_limitSize. This may be requested
 * by the high level methods using the different allow() method.
 *
 * <b>Array Access</b>
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
 * @see Alinex\Dictionary for overview
 */
abstract class Engine implements \Countable, \ArrayAccess
{
    /**
     * Engine stores values in session.
     * The entries are only for the specific user on this machine.
     */
    const SCOPE_SESSION = 1;
    /**
     * Engine stores values on local machine.
     * The entries are only accessible for the users on this machine.
     */
    const SCOPE_LOCAL = 2;
    /**
     * Engine stores values global accessible.
     * The data can be accessed from all machines with the same cluster setup.
     */
    const SCOPE_GLOBAL = 4;

    /**
     * Engine keeps values only for short time.
     * This are some minutes to few hours. Mostly they will be removed in some
     * type of garbage collection.
     */
    const PERSISTENCE_SHORT = 8; // only minutes
    /**
     * Engine keeps values for some time.
     * This means some hours or days. Mostly this is stored till the next server
     * restart.
     */
    const PERSISTENCE_MEDIUM = 16; // some hours
    /**
     * Engine keeps values nearly for ever.
     * This means that the values won't be removed automatically and stay for
     * months or ever.
     */
    const PERSISTENCE_LONG = 32; // nearly for ever

    /**
     * Engine has a low performance.
     * Accessing the values is not as fast, used for seldom accessed data.
     */
    const PERFORMANCE_LOW = 64;
    /**
     * Engine has a good performance.
     */
    const PERFORMANCE_MEDIUM = 128;
    /**
     * Engine has a very high performance.
     * This engine should be used for heavily accessed data.
     */
    const PERFORMANCE_HIGH = 256;

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
     * @param string|array $config
     * - array with complete engine configuration (callen on Engine directly)
     * - special name of this instance (called on subclass)
     * @return Engine Instance of storage class
     */
    public static function getInstance($config = null)
    {
        $class = get_called_class();
        if (get_called_class() == __CLASS__) {
            assert(
                Validator::is(
                    $config,
                    isset($config['name']) ? $config['name'] : 'engine',
                    'Dictionary::engine'
                )
            );

            # create engine
            $engine = call_user_func(
                $config['type'].'::getInstance',
                $config['prefix']
            );
            if ($config['server'] && method_exists($engine, 'addServer'))
                call_user_func(
                    array($engine, 'addServer'),
                    $config['server']
                );
            // analyze validator
            return $engine;

        } else {
            if (!isset($config))
                $config = '';
            assert(
                Validator::is(
                    $config, 'storage-context',
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
            if (! isset(self::$_instances[$class.'#'.$config]))
                self::$_instances[$class.'#'.$config] = new $class($config);
            return self::$_instances[$class.'#'.$config];
        }
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
     * @return string key name given
     * @throws Validator\Exception if not valid
     */
    static final protected function checkKey($key)
    {
        if (is_numeric($key))
            $key = (string) $key;
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
            else if (String::startsWith($key, $group)
                && strlen($key) > strlen($group))
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
     * Increment value of given key.
     *
     * @param string $key name of storage entry
     * @param numeric $num increment value
     * @return numeric new value of storage entry
     * @throws \Exception if storage entry is not numeric
     */
    public function incr($key, $num = 1)
    {
        $value = $this->get($key);
        if (!is_numeric($value))
            throw new \Exception(
                tr(
                    __NAMESPACE__,
                    'Incrementing a dictionary value is only possible using numeric values, {num} was given',
                    array('num' => $num)
                )
            );
        return $this->set($key, $value + $num);
    }

    /**
     * Decrement value of given key.
     *
     * @param string $key name of storage entry
     * @param numeric $num decrement value
     * @return numeric new value of storage entry
     * @throws \Exception if storage entry is not numeric
     */
    public function decr($key, $num = 1)
    {
        return $this->incr($key, -$num);
    }

    /**
     * Append string to storage value.
     *
     * @param string $key name of storage entry
     * @param string $text text to be appended
     * @return string new complete text entry
     * @throws \Exception if storage entry is not a string
     */
    public function append($key, $text)
    {
        $value = $this->get($key);
        if (!is_string($value))
            throw new \Exception(
                tr(
                    __NAMESPACE__,
                    'Appending to a dictionary value is only possible using string values, {text} was given',
                    array('text' => $text)
                )
            );
        return $this->set($key, $value . $text);
    }

    /**
     * Set an value in the hash specified by key.
     * @param string $key name of storage entry
     * @param string $name key name within the hash
     * @param mixed $value data to be stored
     * @return mixed value set in hash
     */
    public function hashSet($key, $name, $value)
    {
        $hash = $this->get($key);
        if (!isset($hash))
            $hash = array();
        $hash[$name] = $value;
        $this->set($key, $hash);
        return $value;
    }

    /**
     * Get an value from  the hash specified by key.
     * @param string $key name of storage entry
     * @param string $name key name within the hash
     * @return mixed value from hash
     */
    public function hashGet($key, $name)
    {
        $hash = $this->get($key);
        return isset($hash[$name]) ? $hash[$name] : null;
    }

    /**
     * Check if the specified hash has the value
     * @param string $key name of storage entry
     * @param string $name key name within the hash
     * @return boll entry in hash found
     */
    public function hashHas($key, $name)
    {
        $hash = $this->get($key);
        return isset($hash[$name]);
    }

    /**
     * Remove some entry from within the specified hash.
     * @param string $key name of storage entry
     * @param string $name key name within the hash
     * @return bool true on success otherwise false
     */
    public function hashRemove($key, $name)
    {
        $hash = $this->get($key);
        if (isset($hash[$name])) {
            unset($hash[$name]);
            $this->set($key, $hash);
            return true;
        }
        return false;
    }

    /**
     * Count the number of entries within the specified hash.
     * @param string $key name of storage entry
     * @return int number of entries within the hash
     */
    public function hashCount($key)
    {
        $hash = $this->get($key);
        return isset($hash) ? $this->count($hash) : 0;
    }

    /**
     * Add an element to the end of the list.
     * @param string $key name of storage entry
     * @param mixed $value data to be added
     * @return int new number of elements
     */
    public function listPush($key, $value)
    {
        $list = $this->get($key);
        if (!isset($list))
            $list = array();
        $result = array_push($list, $value);
        $this->set($key, $list);
        return $result;
    }

    /**
     * Get the last element out of the list.
     * @param string $key name of storage entry
     * @return mixed removed last element of list
     */
    public function listPop($key)
    {
        $list = $this->get($key);
        if (!isset($list))
            $list = array();
        $result = array_pop($list);
        $this->set($key, $list);
        return $result;
    }

    /**
     * Get the first element out of the list.
     * @param string $key name of storage entry
     * @return mixed removed first element
     */
    public function listShift($key)
    {
        $list = $this->get($key);
        if (!isset($list))
            $list = array();
        $result = array_shift($list);
        $this->set($key, $list);
        return $result;
    }

    /**
     * Add an element to the start of the list.
     * @param string $key name of storage entry
     * @param mixed $value data to be added
     * @return int new number of elements
     */
    public function listUnshift($key, $value)
    {
        $list = $this->get($key);
        if (!isset($list))
            $list = array();
        $result = array_unshift($list, $value);
        $this->set($key, $list);
        return $result;
    }

    /**
     * Get a specified element from the list.
     * @param string $key name of storage entry
     * @param int $num number of element
     * @return mixed value at the defined position
     */
    public function listGet($key, $num)
    {
        $list = $this->get($key);
        return isset($list[$num]) ? $list[$num] : null;
    }

    /**
     * Set the value of a specific list entry.
     * @param string $key name of storage entry
     * @param int $num number of element
     * @param mixed $value data to be set
     * @return mixed data which were set
     */
    public function listSet($key, $num, $value)
    {
        $list = $this->get($key);
        if (!isset($list))
            $list = array();
        if (!isset($num))
            $list[] = $value;
        else
            $list[$num] = $value;
        $this->set($key, $list);
        return $value;
    }

    /**
     * Count the number of elements in list.
     * @param string $key name of storage entry
     * @return int number of list entries
     */
    public function listCount($key)
    {
        $list = $this->get($key);
        return isset($list) ? count($list) : 0;
    }

    /**
     * Garbage collector run.
     * 
     * A default garbage collector didn't exist. Each engine have to implement
     * their own.
     * 
     * It will also return false if the engine itself will automatically do the
     * garbage collector.
     * 
     * @return bool true on success
     */
    public function gc()
    {
        return false;
    }
    
    /**
     * Estimate the size of the value.
     *
     * This may vary depending on the engine. This general method can only
     * estimate. For more accurate values this has to be overriden in the engine
     * itself.
     *
     * @param mixed $value value to calculate
     * @return int size in characters of serialized message
     */
    protected static function size($value)
    {
        return strlen(serialize($value));
    }

    /**
     * Scope of the engine.
     * @var int
     */
    protected $_scope = Engine::SCOPE_LOCAL;

    /**
     * Persistence level of the engine.
     * @var int
     */
    protected $_persistence = Engine::PERSISTENCE_SHORT;

    /**
     * Performance level of the engine.
     * @var int
     */
    protected $_performance = Engine::PERFORMANCE_LOW;

    /**
     * Size quotes to select best Cache engine.
     * @var array
     */
    protected $_limitSize = array();

    /**
     * Limit the value size for Cache selection.
     *
     * The defined limit will be used from the Cache class to find the best
     * caching solution for specific values.
     *
     * To clean the limits call it with a size of 0 and no limit (percent = 1):
     * @code
     * $engine->limitSize(0, 1);
     * @endcode
     *
     * To only get the defined limits call it without any parameters:
     * @code
     * $limits = $engine->limitSize();
     * @endcode
     *
     * @param int $size number of characters
     * @param float $percent 0 (not possible)...(not perfect)...1 (no limit)
     * @return array list of limits set
     */
    public function limitSize($size = null, $percent = 0)
    {
        if (!isset($size))
            return $this->_limitSize;

        assert(is_int($size));
        assert(is_numeric($percent) && $percent >= 0 && $percent <= 1);

        // delete limits
        if ($percent == 1) {
            foreach (array_keys($this->_limitSize) as $limit) {
                if ($limit < $size)
                    break;
                unset($this->_limitSize[$limit]);
            }
        }

        if ($size != 0) {
            $this->_limitSize[$size] = $percent;
            krsort($this->_limitSize);
        }
        return $this->_limitSize;
    }

    /**
     * Check how good the engine is for specified data.
     *
     * The returning quote will give a percentage level (0...1) estimating
     * how good the engine is for this purpose. The best quality is an exact
     * match in all categories.
     *
     * The following will be checked:
     * - engine scope
     * - engine persistence
     * - engine performance
     * - value size
     *
     * The results can be affected by the engine's settings.
     *
     * @param mixed $value data to store later
     * @param int $flags scope, persistence and performance... flags
     * @return float quality (0 impossible, 1 best)
     */
    public function allow($value, $flags = 0)
    {
        assert(is_int($flags) && $flags >= 0);

        $quote = 1;
        // check flags
        if ($flags) {
            $quote = $this->allowScope($flags);
            $quote *= $this->allowPersistence($flags);
            $quote *= $this->allowPerformance($flags);
        }
        // check value
        if (isset($value))
            $quote *= $this->allowSize($value);
        // return result
        return $quote;
    }

    /**
     * Check the scope.
     *
     * The quality of each engine will be as shown in the following decision
     * table:
     * @verbatim
     * in \ engine   session    local    global
     *   session       1.0        0         0
     *    local        0.8       1.0        0
     *    global       0.5       0.8       1.0
     *   undefined     1.0       1.0       1.0
     * @endverbatim
     *
     * @param int $flags scope, persistence and performance... flags
     * @return float quality (0 impossible, 1 best)
     */
    private function allowScope($flags)
    {
        if ($flags & $this->_scope)
            return 1; // identical
        if ($flags & ($this->_scope * 2) && $this->_scope < self::SCOPE_GLOBAL)
            return 0.8; // one step too low
        if ($flags & self::SCOPE_GLOBAL && $this->_scope = self::SCOPE_SESSION)
            return 0.5; // two steps too low
        if ($flags & self::SCOPE_SESSION || $flags & self::SCOPE_LOCAL)
            return 0; // too high
        return 1;
    }

    /**
     * Check the persistence level.
     *
     * The quality of each engine will be as shown in the following decision
     * table:
     * @verbatim
     * in \ engine    short    medium     long
     *    short        1.0       0.8       0.5
     *    medium       0.5       1.0       0.8
     *     long        0.2       0.5       1.0
     *   undefined     1.0       1.0       1.0
     * @endverbatim
     *
     * @param int $flags scope, persistence and performance... flags
     * @return float quality (0 impossible, 1 best)
     */
    private function allowPersistence($flags)
    {
        if ($flags & $this->_persistence)
            return 1; // identical
        if ($flags & ($this->_persistence * 2)
            && $this->_persistence < self::PERSISTENCE_LONG)
            return 0.5; // one step too low
        if ($flags & self::PERSISTENCE_LONG
            && $this->_persistence = self::PERSISTENCE_SHORT)
            return 0.2; // two steps too low
        if ($this->_persistence > self::PERSISTENCE_SHORT
            && $flags & ($this->_persistence / 2))
            return 0.8; // one step too high
        if ($flags & self::PERSISTENCE_SHORT
            && $this->_persistence = self::PERSISTENCE_LONG)
            return 0.5; // two steps too high
        return 1;
    }

    /**
     * Check the performance level.
     *
     * The quality of each engine will be as shown in the following decision
     * table:
     * @verbatim
     * in \ engine     low     medium     high
     *     low         1.0       0.8       0.5
     *    medium       0.5       1.0       0.8
     *     high        0.2       0.5       1.0
     *   undefined     1.0       1.0       1.0
     * @endverbatim
     *
     * @param int $flags scope, persistence and performance... flags
     * @return float quality (0 impossible, 1 best)
     */
    private function allowPerformance($flags)
    {
        if ($flags & $this->_performance)
            return 1; // identical
        if ($flags & ($this->_performance * 2)
            && $this->_performance < self::PERFORMANCE_HIGH)
            return 0.5; // one step too low
        if ($flags & self::PERFORMANCE_HIGH
            && $this->_performance = self::PERFORMANCE_LOW)
            return 0.2; // two steps too low
        if ($this->_performance > self::PERFORMANCE_LOW
            && $flags & ($this->_performance / 2))
            return 0.8; // one step too high
        if ($flags & self::PERFORMANCE_LOW
            && $this->_performance = self::PERFORMANCE_HIGH)
            return 0.5; // two steps too high
        return 1;
    }

    /**
     * Check how good the engine is for specified data.
     *
     * The returning quote will give a percentage level (0...1) estimating
     * how good the engine is for this purpose. The best quality is an exact
     * match in all categories.
     *
     * The following will be checked:
     * - engine scope
     * - engine persistence
     * - engine performance
     * - value size
     *
     * The results can be affected by the engine's settings.
     *
     * @param mixed $value data to store later
     * @return float quality (0 impossible, 1 best)
     */
    private function allowSize($value)
    {
        $quote = 1;
        // check size if set
        if (count($this->_limitSize)) {
            $size = static::size($value);
            foreach ($this->_limitSize as $limit => $percent) {
                if ($limit > $size)
                    continue;
                $quote *= $percent;
                break;
            }
        }
        return $quote;
    }


}
