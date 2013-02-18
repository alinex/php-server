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

namespace Alinex\Storage;

/**
 * System cache using multiple storages.
 *
 * @see Registry for storage with validation
 */
class Cache implements \Countable, \ArrayAccess
{
    /**
     * Singleton instance of cache class
	 * @var Cache
	 */
    protected static $_instance = NULL;

    /**
     * Get an instance of cache class
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
     * Context name for this instance.
     * @var string
     */
    protected $_context = '';

    /**
     * List of engines to possibly use.
     * @var array
     */
    private $_engines = array();

    /**
     * Add a new engine to the end of the list.
     * @param \Alinex\Storage\Engine $engine to be added
     * @return int the number of engines in the list
     */
    public function enginePush(Engine $engine)
    {
        return array_push($this->_engines, $engine);
    }

    /**
     * Add a new engine to the start of the list.
     * @param \Alinex\Storage\Engine $engine to be added
     * @return int the number of engines in the list
     */
    public function engineUnshift(Engine $engine)
    {
        return array_unshift($this->_engines, $engine);
    }

    /**
     * Remove the last engine of the list.
     * @return \Alinex\Storage\Engine $engine last engine of the list
     */
    public function enginePop()
    {
        return array_pop($this->_engines);
    }

    /**
     * Remove the first engine of the list.
     * @return \Alinex\Storage\Engine $engine first engine of the list
     */
    public function engineShift()
    {
        return array_shift($this->_engines);
    }

    /**
     * Search for engines with the given key.
     * @param string $key name of the entry
     * @param bool $all true to find all engines holding this key false for
     * first only
     * @return Engine|array one or all engines as list
     */
    protected function searchEngines($key, $all = false)
    {
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
     * @param int $scope identification of preferred scope
     *
     * @return bool    TRUE on success otherwise FALSE
     * @throws Validator\Exception
     */
    public final function set(
        $key, $value = null, $scope = Engine::SCOPE_GLOBAL
    )
    {
        // first remove old entries
        if (!isset($value))
            return $this->remove($key);
        $this->remove($key);
        // check possible stores
        $bestEngine = null;
        $bestScore = 0;
        foreach ($this->_engines as $engine) {
            $score = $engine->allowed($value, $scope);
            if ($score == 1)
                // use the optimal engine
                return $engine->set($key, $value);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestEngine = $engine;
            }
        }
        if ($bestScore == 0)
            throw new Exception(tr("No engine found to use for the value."));
        // set value using the best alternative
        return $bestEngine->set($key, $value);
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

}
