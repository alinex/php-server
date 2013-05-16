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
 * Each engine maybe best used for the one or other task. Below is a quick
 * overview:
 *
 * | engine    | scope        | perf.  | persistence | registry | cache | session | comment             |
 * | :-------- | :----------: | :----: | :---------: | :------: | :---: | :-----: | :------------------ |
 * | Apc       | local        | high   | medium      | +        | ++    | +++     | needs extension     |
 * | ArrayList | request      | best   | none        | --       | -     | ---     | fallback            |
 * | Directory | local/global | low    | long        | -        | +++   | -       | only for big data   |
 * | GloblList | request      | best   | none        | --       | -     | ---     | fallback accessible |
 * | Memcache  | global       | medium | medium      | +        | ++    | +++     | needs extension     |
 * | Redis     | global       | medium | medium      | +        | +++   | +++     | opt. extension      |
 * | Session   | session      | high   | medium      | -        | +     | default | in standard config  |
 * | XCache    | local        | high   | medium      | +        | ++    | +++     | needs extension     |
 *
 * But read more about the specific engines in their class description. A
 * special criteria may also be the size and garbage collection.
 *
 * **Methods**
 *
 * On each engine the following operations are possible:
 * - <b>simple accessors</b>: set(), get(), has(), remove()
 * - <b>array access</b>: offsetSet(), offsetGet(), offsetExists(),
 * offsetUnset()
 * - <b>group access</b>: groupSet(), groupGet(), groupClear()
 * - <b>value editing</b>: inc(), dec(), append()
 * - <b>hash access</b>: hashSet(), hashGet(), hashHas(), hashRemove(),
 * hashCount()
 * - <b>list access</b>: listPush(), listPop(), listShift(), listUnshift(),
 * listGet(), listSet(), listCount()
 * - <b>overall control</b>: count(), keys(), clear(), gc()
 *
 * This engines are used as storage in Registry, Cache and Session or from the
 * application level.
 *
 * **Instanciation**
 *
 * A new engine can be generated:
 * - directly from each class
 * - autodetected using Engine::getInstance()
 * - by specification using Engine::getInstance($spec)
 *
 * The engine specification may also be checked using the
 * Alinex\Dictionary\Validator::engine Validator.
 *
 * For this to work some engine specifica are set by each engine like $_scope,
 * $_performance, $_persistence and the $_limitSize. This may be requested
 * by the high level methods using the different allow() method.
 *
 * **Array Access**
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
 * **Group**
 *
 * A group is a subpart of the entries with the same group name as key start
 * in storage. This will be prepended on set and removed on get to use with
 * shorter array keys.
 *
 * **Modifiing Methods**
 *
 * This are methods which change cchanges the value:
 * - <b>value editing</b>: inc(), dec(), append()
 * - <b>hash access</b>: hashSet(), hashGet(), hashHas(), hashRemove(),
 * hashCount()
 * - <b>list access</b>: listPush(), listPop(), listShift(), listUnshift(),
 * listGet(), listSet(), listCount()
 *
 * Some engines support this natively for all others it will be emulated.
 *
 * **Garbage Collection**
 *
 * Most engines support garbage collection. To switch this on it have to be
 * configured by setting the default time-to-live using setTtl() or giving the
 * time-to-live with the set() method.
 *
 * The timeout is cleared only when the key is removed using the remove()
 * command or overwritten using the next set() command. This means that all the
 * operations that conceptually alter the value stored at the key without
 * replacing it with a new one will leave the timeout untouched. For instance,
 * incrementing the value of a key with inc(), pushing a new value into a list
 * with listPush(), or altering the field value of a hash with hashSet() are all
 * operations that will leave the timeout untouched.
 *
 * Each engine works with it's own garbage collection algorithm. On some engines
 * you have to trigger this by calling gc(). Read more in the description of
 * each engine.
 *
 * @pattern{Multiton} Create single instance for each context name. But this
 * getInstance() method is also used to get a specific Engine by configuration.
 * @pattern{ArrayAccess} For easy acessing values.
 * @see Dictionary for overview of use
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
     * This is done in three different ways (see the config parameter).
     *
     * Through different context names it is possible to use the same storage
     * type and location independently multiple times. This is also needed for
     * separation of different applications if global storages are used.
     *
     * The context is mostly used as prefix for the real key name in the engine.
     *
     * @param string|array $config
     * - array with complete engine configuration (callen on Engine directly)
     * - context to use for autodetect engine (called on Engine directly)
     * - special name of this instance (called on subclass)
     * @return Engine Instance of storage class
     */
    public static function getInstance($config = null)
    {
        $class = get_called_class();
        if (get_called_class() == __CLASS__) {
            assert(
                is_string($config)
                || Validator::is(
                    $config,
                    isset($config['name']) ? $config['name'] : 'engine',
                    'Dictionary::engine'
                )
            );

            if (is_string($config)) {
                if (php_sapi_name() == 'cli')
                    return Engine\ArrayList::getInstance($config);
                else if (Engine\Apc::isAvailable())
                    return Engine\Apc::getInstance($config);
                else if (Engine\XCache::isAvailable())
                    return Engine\Apc::getInstance($config);
                else
                    return Engine\ArrayList::getInstance($config);
            } else {
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
                if ($config['ttl'])
                    call_user_func(
                        array($engine, 'setTtl'),
                        $config['ttl']
                    );
                if ($config['directory'])
                    call_user_func(
                        array($engine, 'setDirectory'),
                        $config['directory']
                    );
                // analyze validator
                return $engine;
            }

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
     * Default Time To Live
     *
     * Store var in the cache for ttl seconds. After the ttl has passed, the
     * stored variable will be expunged from the cache (on the next request).
     * If no ttl is supplied (or if the ttl is 0), the value will persist until
     * it is removed from the cache manually, or otherwise fails to exist in
     * the cache (clear, remove).
     *
     * @var integer
     */
    protected $_ttl = null;

    /**
     * Set the default time to live.
     *
     * @param integer   $ttl time to live for each individual value
     * @return int value set
     */
    protected function setTtl($ttl)
    {
        assert(is_int($ttl));
        $this->_ttl = $ttl;
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
     * @name Normal access routines
     * @{
     */

    /**
     * Method to set a storage variable
     *
     * If key already holds a value, it is overwritten, regardless of its type.
     * Lists and Hashes will be stored in its proper type if the engine supports
     * this.
     *
     * If $ttl is given this value is used, if not the default setting applies.
     * To prevent  setting a time-to-live even if defined as default 0 have to
     * be given here.
     *
     * @param string $key   name of the entry
     * @param string $value Value of storage key null to remove entry
     * @param int $ttl time to live for entry or 0 for endless
     *
     * @return mixed value which was set
     * @throws \Alinex\Validator\Exception
     */
    abstract public function set($key, $value = null, $ttl = null);

    /**
     * Method to get a storage variable
     *
     * Lists and hashes will be returned as arrays.
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
     * @}
     */

    /**
     * @name Group access
     * @{
     */

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
     * @}
     */

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
     * @name Array access
     * @{
     */

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
     * @}
     */

    /**
     * @name Value modification
     * @{
     */

    /**
     * Increment value of given key.
     *
     * Increments the number stored at key by one. If the key does not exist,
     * it is set to 0 before performing the operation. An error is returned if
     * the key contains a value of the wrong type or contains a string that can
     * not be represented as integer.
     *
     * @param string $key name of storage entry
     * @param numeric $num increment value
     * @return numeric new value of storage entry
     * @throws \Exception if storage entry is not numeric
     */
    public function inc($key, $num = 1)
    {
        assert(is_int($num));

        $this->checkKey($key);
        $value = $this->get($key);
        if (!isset($value)) $value = 0;
        if (!is_integer($value))
            throw new \Exception(
                tr(
                    __NAMESPACE__,
                    'Incrementing or decrementing a dictionary value is only possible using integer values.'
                )
            );
        return $this->set($key, $value + $num);
    }

    /**
     * Decrement value of given key.
     *
     * Decrements the number stored at key by one. If the key does not exist,
     * it is set to 0 before performing the operation. An error is returned if
     * the key contains a value of the wrong type or contains a string that can
     * not be represented as integer.
     *
     * @param string $key name of storage entry
     * @param numeric $num decrement value
     * @return numeric new value of storage entry
     * @throws \Exception if storage entry is not numeric
     */
    public function dec($key, $num = 1)
    {
        return $this->inc($key, -$num);
    }

    /**
     * Append string to storage value.
     *
     * If key already exists and is a string, this command appends the value at
     * the end of the string. If key does not exist it is created and set as an
     * empty string, so APPEND will be similar to SET in this special case.
     *
     * @param string $key name of storage entry
     * @param string $text text to be appended
     * @return string new complete text entry
     * @throws \Exception if storage entry is not a string
     */
    public function append($key, $text)
    {
        assert(is_string($text));

        $value = $this->get($key);
        if (!isset($value)) $value = '';
        if (!is_string($value))
            throw new \Exception(
                tr(
                    __NAMESPACE__,
                    'Appending to a dictionary value is only possible using string values,'
                )
            );
        return $this->set($key, $value . $text);
    }

    /**
     * @}
     */

    /**
     * @name Hash value access
     * @{
     */

    /**
     * Set an value in the hash specified by key.
     *
     * Sets field in the hash stored at key to value. If key does not exist, a
     * new key holding a hash is created. If field already exists in the hash,
     * it is overwritten.
     *
     * @param string $key name of storage entry
     * @param string $name key name within the hash
     * @param mixed $value data to be stored
     * @return mixed value set in hash
     */
    public function hashSet($key, $name, $value)
    {
        assert(is_string($name));

        $hash = $this->get($key);
        if (!isset($hash))
            $hash = array();
        if (!is_array($hash))
            throw new \Exception(
                tr(
                    __NAMESPACE__,
                    'The key isn\'t holding a hash'
                )
            );
        $hash[$name] = $value;
        $this->set($key, $hash);
        return $value;
    }

    /**
     * Get an value from  the hash specified by key.
     *
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
     *
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
     *
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
     *
     * @param string $key name of storage entry
     * @return int number of entries within the hash
     */
    public function hashCount($key)
    {
        $hash = $this->get($key);
        return isset($hash) ? $this->count($hash) : 0;
    }

    /**
     * @}
     */

    /**
     * @name List value access
     * @{
     */

    /**
     * Add an element to the end of the list.
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
     * Returns the length of the list stored at key. If key does not exist, it
     * is interpreted as an empty list and 0 is returned. An error is returned
     * when the value stored at key is not a list.
     *
     * @param string $key name of storage entry
     * @return int number of list entries
     */
    public function listCount($key)
    {
        $list = $this->get($key);
        return isset($list) ? count($list) : 0;
    }

    /**
     * @}
     */

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
