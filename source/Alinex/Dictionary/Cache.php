<?php
/**
 * @file
 * System cache using multiple storages.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Dictionary;

use Alinex\Logger;

/**
 * Using the cache.
 * @example Cache-default.php
 */
/**
 * System cache using multiple storages.
 *
 * The cache will hold a list of engines which may be used. Which engine to use
 * for a specific value will be decided automatically based on the engine's
 * scope, performance, persistence and its value size settings.
 *
 * Keep the list as short as possible to be performant.
 *
 * You may call the garbage collector manual on the cache class or it will
 * be done automatically before setting a value if min. gctime is set.
 *
 * **Automatic Configuration**
 *
 * This is possible using registry entries like in the following example:
 *   cache.gc_lastrun = [timestamp]
 *   cache.gc_time = 600
 *   cache.engine[0][type] = 'Redis'
 *   cache.engine[0][prefix] = 'ax:tmp:',
 *   cache.engine[0][server][0] = 'tcp://localhost:3456'
 *
 * @pattern{Singleton} One general cache, but individual additional caches
 * through normal constructor are also possible.
 * @pattern{Chaining} To add multiple engines in one chain.
 * @pattern{ArrayAccess} Used for all the values.
 * @see Registry for storage with validation
 * @see Session to easy integrate any engine as session storage
 * @see Dictionary for overview of use
 */
class Cache implements \Countable, \ArrayAccess
{
    /**
     * Dictionary engine definitions to use as storage.
     * This has to be a list of engine specifications.
     */
    const REGISTRY_ENGINE = 'cache.engine';

    /**
     * Information then the last garbage collector run was.
     */
    const REGISTRY_LASTRUN = 'cache.gc_lastrun';

    /**
     * Prefix for the data storage.
     */
    const DEFAULT_PREFIX = 'ax:tmp:';

    /**
     * Prefix for the min time before the next garbage collector run.
     * Set this to 0 to prevent garbage collection run while setting values. If
     * too much entries this may slow down the active process depending on the
     * engine's gc performance.
     */
    const DEFAULT_GCTIME = '600';

    /**
     * @copydoc DEFAULT_GCTIME
     */
    const REGISTRY_GCTIME = 'cache.gc_time';

    /**
     * Singleton instance of cache class
	 * @var Cache
	 */
    protected static $_instance = NULL;

    /**
     * Get an instance of cache class
     * @return Cache instance of Cache class
     */
    public final static function getInstance()
    {
        // create new instance
        if (! isset(self::$_instance))
            self::$_instance = new Cache();
        // return it
        return self::$_instance;
    }

    /**
     * Time intervall to run gc on cache.
     * @var int
     */
    private $_gctime = null;

    /**
     * Constructor
     *
     * This may be overwritten to implement some initialization of the storage
     * engine.
     */
    public function __construct()
    {
        // check for registry settings
        $registry = Registry::getInstance();
        if ($registry) {
            // add validators
            if ($registry->validatorCheck()) {
                if (!$registry->validatorHas(self::REGISTRY_ENGINE))
                    $registry->validatorSet(
                        self::REGISTRY_ENGINE, 'Type::arraylist',
                        array(
                            'keySpec' => array(
                                '' => array('Dictionary::engine')
                            ),
                            'description' => tr(
                                __NAMESPACE__,
                                'Storage engine used for caching.'
                            )
                        )
                    );
                if (!$registry->validatorHas(self::REGISTRY_GCTIME))
                    $registry->validatorSet(
                        self::REGISTRY_GCTIME, 'Type::integer',
                        array(
                            'unsigned' => true,
                            'description' => tr(
                                __NAMESPACE__,
                                'Time intervall to run garbage collector on cache.'
                            )
                        )
                    );
            }
            // set engine
            if ($registry->has(self::REGISTRY_ENGINE))
                foreach ($registry->get(self::REGISTRY_ENGINE) as $engine)
                    $this->enginePush(Engine::getInstance($engine));
            else
                $this->enginePush(Engine::getInstance(self::DEFAULT_PREFIX));
            if ($registry->has(self::REGISTRY_GCTIME))
                $this->_gctime = $registry->get(self::REGISTRY_GCTIME);
        } else {
            $this->enginePush(Engine::getInstance(self::DEFAULT_PREFIX));
        }
    }

    /**
     * List of engines to possibly use.
     * @var array
     */
    private $_engines = array();

    /**
     * @name Chainable
     * @{
     */
    
    /**
     * Add a new engine to the end of the list.
     * @param \Alinex\Dictionary\Engine $engine to be added
     * @return Cache
     */
    public function engineAdd(Engine $engine)
    {        
        $this->_engines[spl_object_hash($engine)] = $engine;
        return $this;
    }

    /**
     * Add a new engine to the start of the list.
     * @param \Alinex\Dictionary\Engine $engine to be added
     * @return Cache
     */
    public function engineRemove(Engine $engine)
    {
        unset($this->_engines[spl_object_hash($engine)]);
        return $this;
    }

    /**
     * @}
     */
    
    /**
     * Search for engines with the given key.
     * @param string $key name of the entry
     * @param bool $all true to find all engines holding this key false for
     * first only
     * @return Engine|array one or all engines as list
     */
    protected function searchEngines($key, $all = false)
    {
        assert(is_bool($all));

        $list = array();
        foreach ($this->_engines as $test) {
            if ($test->has($key)) {
                $list[] = $test;
                if (!$all)
                    return $test;
            }
        }
        return $all ? $list : (bool)count($list);
    }

    /**
     * @name Working with the values
     * @{
     */
    
    /**
     * Method to set a cache variable.
     *
     * This will search the engine stack for the optimal engine based on
     * value type, size and preferred scope. If no perfect match found the
     * best alternative will be used.
     *
     * The rules may be adjusted using the limit... methods on the Engine
     * instances.
     *
     * @param string $key   Registry array key
     * @param string $value Value of cache key
     * @param int $flags scope, persistence and performance... flags
     * @param int $ttl set time to live for this entry
     *
     * @return bool    TRUE on success otherwise FALSE
     * @throws Validator\Exception
     */
    public final function set($key, $value = null, $flags = 0, $ttl = null)
    {
        if (!isset($this->_engines)) {
            Logger::getInstance()->warn(
                'No engines defined for cache.'
            );
            return false;
        }
        // maybe cleanup cache
        if (isset($this->_gctime) && $this->_gctime) {
            $registry = Registry::getInstance();
            $lastrun = $registry->get(self::REGISTRY_LASTRUN);
            if (!isset($lastrun) || $lastrun + $this->_gctime < time()) {
                foreach ($this->_engines as $engine)
                    $engine->gc();
                $registry->set(self::REGISTRY_LASTRUN, time());
            }
        }
        // first remove old entries
        if (!isset($value))
            return $this->remove($key);
        $this->remove($key);
        // check possible stores
        $bestEngine = null;
        $bestScore = 0;
        foreach ($this->_engines as $engine) {
            $score = $engine->allow($value, $flags);
            if ($score == 1)
                // use the optimal engine
                return $engine->set($key, $value);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestEngine = $engine;
            }
        }
        if ($bestScore == 0)
            Logger::getInstance()->warn(
                'No engine found to use for the value.',
                array('value' => $value, 'flags' => $flags)
            );
        // set value using the best alternative
        return $bestEngine->set($key, $value, $ttl);
    }

    /**
     * Method to get a cache variable
     *
     * @param string $key Registry array key
     *
     * @return bool    TRUE on success otherwise NULL
     */
    public final function get($key)
    {
        return $this->searchEngines($key)->get($key);
    }

    /**
     * Check if cache variable is defined
     *
     * @param string $key Registry array key
     *
     * @return bool    TRUE on success otherwise FALSE
     */
    public final function has($key)
    {
        return $this->searchEngines($key);
    }

    /**
     * Unset a cache variable
     *
     * @param  string $key Registry array key
     *
     * @return bool    TRUE
     */
    public final function remove($key)
    {
        $result = false;
        foreach ($this->searchEngines($key, true) as $engine)
            $result |= $engine->remove($key);
        return $result;
    }

    /**
     * Get the list of keys from cache
     *
     * @return boolean   FALSE
     */
    public function keys()
    {
        $list = array();
        foreach ($this->_engines as $engine)
            $list = array_merge($list, $engine->keys());
        return array_unique($list);
    }

    /**
     * Reset cache
     *
     * This will be done in a common way by removing every single element.
     * For storage engines, which allow easier purging this may be overwritten.
     *
     * @return bool    TRUE on success otherwise FALSE
     */
    public function clear()
    {
        foreach ($this->_engines as $engine)
            return $engine->clear();
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
        $list = array();
        foreach ($this->_engines as $engine)
            $list = array_merge($list, $engine->groupGet($key));
        return $list;
    }

    /**
     * Set a list of values as group.
     *
     * The key names will be pretended with the group name given.
     *
     * @param string $group
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
     * Clear a group of values from the cache.
     * @param string $group name of the group
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
     * Get the number of elements in the cache.
     *
     * This method will called also with:
     * @code
     * count($cache).
     * @endcode
     *
     * @return integer number of values in the cache
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
     * isset($cache[$offset])
     * @endcode
     *
     * @param string $offset name of cache entry
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
     * isset($cache[$offset])
     * @endcode
     *
     * @param string $offset name of cache entry
     *
     * @return mixed cache entry
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Set key through ArrayAccess
     *
     * @code
     * isset($cache[$offset])
     * @endcode
     *
     * @param string $offset name of cache entry
     * @param mixed $value value to store
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Unset key using ArrayAccess
     *
     * @code
     * unset($cache[$offset])
     * @endcode
     *
     * @param string $offset name of cache entry
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * @}
     */
    
    /**
     * @name Value manipulation
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

        if (!isset($this->_engines)) {
            Logger::getInstance()->warn(
                'No engines defined for cache.'
            );
            return false;
        }
        return $this->searchEngines($key)->inc($key, $num);
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

        if (!isset($this->_engines)) {
            Logger::getInstance()->warn(
                'No engines defined for cache.'
            );
            return false;
        }
        return $this->searchEngines($key)->append($key, $text);
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

        if (!isset($this->_engines)) {
            Logger::getInstance()->warn(
                'No engines defined for cache.'
            );
            return false;
        }
        return $this->searchEngines($key)->hashSet($key, $name, $value);
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
        return $this->searchEngines($key)->hashGet($key, $name);
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
        return $this->searchEngines($key)->hashHas($key, $name);
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
        return $this->searchEngines($key)->hashRemove($key, $name);
    }

    /**
     * Count the number of entries within the specified hash.
     *
     * @param string $key name of storage entry
     * @return int number of entries within the hash
     */
    public function hashCount($key)
    {
        return $this->searchEngines($key)->hashCount($key);
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
        return $this->searchEngines($key)->listPush($key, $value);
    }

    /**
     * Get the last element out of the list.
     *
     * @param string $key name of storage entry
     * @return mixed removed last element of list
     */
    public function listPop($key)
    {
        return $this->searchEngines($key)->listPop($key);
    }

    /**
     * Get the first element out of the list.
     *
     * @param string $key name of storage entry
     * @return mixed removed first element
     */
    public function listShift($key)
    {
        return $this->searchEngines($key)->listShift($key);
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
        return $this->searchEngines($key)->listUnshift($key, $value);
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
        return $this->searchEngines($key)->listGet($key, $num);
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
        return $this->searchEngines($key)->listSet($key, $num);
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
        return $this->searchEngines($key)->listCount($key);
    }

    /**
     * @}
     */
    
    /**
     * Run garbage collector on each engine now.
     *
     * If this is called with configured automatic garbage collection the time
     * will be stored for the next automatic run.
     */
    public function gc()
    {
        if (isset($this->_engines))
            foreach ($this->_engines as $engine)
                $engine->gc();
        $registry = Registry::getInstance();
        // set lastrun if already there
        if ($registry->has(self::REGISTRY_LASTRUN))
            $registry->set(self::REGISTRY_LASTRUN, time());
    }
}
