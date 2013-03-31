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

namespace Alinex\Dictionary\Engine;

use Alinex\Dictionary\Engine;

/**
 * Storage keeping values in local array.
 *
 * **Specification**
 * - Scope: request
 * - Performance: very high
 * - Persistence: none
 * - Size: limited by php max-memory setting
 * - Objects: native support
 * - Manipulation: native support
 * - Garbage collection: ttl, manual call
 * - Requirements: none
 *
 * This class will store the values locally in an array. This scope will stay
 * over multiple requests only if an opcode system stores them. A better
 * approach is to use the opcode user cache directly, if available.
 *
 * This engine can be used as a fallback with fast access but the whole
 * space is limited by the php max. memory setting.
 *
 * This is the simplest storage, it will last till the end of the php process
 * (mostly the request). It has no dependencies, the performance is high
 * but its usability is poor because of the restricted scope and size.
 *
 * **Garbage Collection**
 *
 * The ArrayList has a minimal implementation of an garbage collector. This is
 * only used in long running processes because the collection will be purged
 * after each request.
 *
 * To do so the ___gc key is holding a list of keys with the timeout date. The
 * timeout is set on each set() method and read on gc() call to check.
 *
 * @attention
 * To prevent out-of-memory crashes all values with a set time-to-live will be
 * removed automatically on set() if free php memory goes under defined limit.
 *
 * @see Engine overview chart
 * @see Dictionary for usage examples
 */
class ArrayList extends Engine
{
    /**
     * Stores the data key for gc().
     * Under this key the ttl for each key will be stored, if set.
     */
    const GC_KEY = '___gc';

    /**
     * Limit, if free php memory is under this value all ttl entries will be
     * deleted before set.
     *
     * This is used as emergency routine to not crash because of too much
     * caching.
     */
    const GC_MEMORY_MINFREE = 1048576; // 1MB

    /**
     * Dictionary for context with values
     * @var array
     */
    static protected $_data = array();

    /**
     * Reference to this context storage in $_data.
     * @var array
     */
    protected $_storage = null;

    /**
     * Memory limit to run the emergency cleanup.
     * @var int
     */
    private $_memorylimit = null;

    /**
     * Constructor
     *
     * This will create the context store and set the memory limit.
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
        // set memory limit
        $this->_memorylimit = ini_get('memory_limit') - self::GC_MEMORY_MINFREE;
    }

    /**
     * @copydoc Engine::set()
     */
    public function set($key, $value = null, $ttl = null)
    {
        if (!isset($value)) {
            $this->remove($key);
            return null;
        }
        // check memory
        if (isset($this->_storage[self::GC_KEY])
            && memory_get_usage(true) > $this->_memorylimit) {
            foreach (array_keys($this->_storage[self::GC_KEY]) as $key)
                unset($this->_storage[$key]);
            unset($this->_storage[self::GC_KEY]);
        }
        $this->checkKey($key);
        // store ttl if set
        if (!isset($ttl)) $ttl = $this->_ttl;
        if (isset($ttl) && $ttl) {
            if (!isset($this->_storage[self::GC_KEY]))
                $this->_storage[self::GC_KEY] = array();
            $this->_storage[self::GC_KEY][$key] = time() + $ttl;
        }
        // set value
        return $this->_storage[$key] = $value;
    }

    /**
     * @copydoc Engine::remove()
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
     * @copydoc Engine::get()
     */
    public function get($key)
    {
        $this->checkKey($key);
        return isset($this->_storage[$key])
            ? $this->_storage[$key]
            : NULL;
    }

    /**
     * @copydoc Engine::has()
     */
    public function has($key)
    {
        $this->checkKey($key);
        return isset($this->_storage[$key]);
    }

    /**
     * @copydoc Engine::keys()
     */
    public function keys()
    {
        return array_keys($this->_storage);
    }

    /**
     * @copydoc Engine::clear()
     */
    public function clear()
    {
        if (!$this->_storage)
            return false;
        $this->_storage = array();
        return true;
    }

    /**
     * @copydoc Engine::count()
     */
    public function count()
    {
        return count($this->_storage);
    }

    /**
     * @copydoc Engine::inc()
     */
    public function inc($key, $num = 1)
    {
        assert(is_int($num));

        $this->checkKey($key);
        if (!isset($this->_storage[$key]))
            $this->_storage[$key] = $num;
        else if (!is_integer($this->_storage[$key]))
            throw new \Exception(
                tr(
                    __NAMESPACE__,
                    'Incrementing or decrementing a dictionary value is only possible using integer values,'
                )
            );
        else
            $this->_storage[$key] += $num;
        return $this->_storage[$key];
    }

    /**
     * @copydoc Engine::append()
     */
    public function append($key, $text)
    {
        assert(is_string($text));

        $this->checkKey($key);
        if (!isset($this->_storage[$key]))
            $this->_storage[$key] = $text;
        else if (!is_string($this->_storage[$key]))
            throw new \Exception(
                tr(
                    __NAMESPACE__,
                    'Appending to a dictionary value is only possible using string values,'
                )
            );
        else
            $this->_storage[$key] .= $text;
        // return result
        return $this->_storage[$key];
    }

    /**
     * @copydoc Engine::hashSet()
     */
    public function hashSet($key, $name, $value)
    {
        assert(is_string($name));

        if (!isset($value)) {
            $this->hashRemove($key, $name);
            return null;
        }
        $this->checkKey($key);
        if (!isset($this->_storage[$key]))
            $this->_storage[$key] = array($name => $value);
        else if (!is_array($this->_storage[$key]))
            throw new \Exception(
                tr(
                    __NAMESPACE__,
                    'The key isn\'t holding a hash'
                )
            );
        else
            $this->_storage[$key][$name] = $value;
        return $value;
    }

    /**
     * @copydoc Engine::hashGet()
     */
    public function hashGet($key, $name)
    {
        return isset($this->_storage[$key][$name])
            ? $this->_storage[$key][$name]
            : null;
    }

    /**
     * @copydoc Engine::hashHas()
     */
    public function hashHas($key, $name)
    {
        return isset($this->_storage[$key][$name]);
    }

    /**
     * @copydoc Engine::hashRemove()
     */
    public function hashRemove($key, $name)
    {
        if (isset($this->_storage[$key][$name])) {
            unset($this->_storage[$key][$name]);
            return true;
        }
        return false;
    }

    /**
     * @copydoc Engine::hashCount()
     */
    public function hashCount($key)
    {
        return isset($this->_storage[$key])
            ? $this->count($this->_storage[$key])
            : 0;
    }

    /**
     * @copydoc Engine::listPush()
     */
    public function listPush($key, $value)
    {
        $this->checkKey($key);
        if (!isset($this->_storage[$key])) {
            $this->_storage[$key] = array($value);
            return 1;
        }
        return array_push($this->_storage[$key], $value);
    }

    /**
     * @copydoc Engine::listPop()
     */
    public function listPop($key)
    {
        return isset($this->_storage[$key])
            ? array_pop($this->_storage[$key])
            : null;
    }

    /**
     * @copydoc Engine::listShift()
     */
    public function listShift($key)
    {
        return isset($this->_storage[$key])
            ? array_shift($this->_storage[$key])
            : null;
    }

    /**
     * @copydoc Engine::listUnshift()
     */
    public function listUnshift($key, $value)
    {
        $this->checkKey($key);
        if (!isset($this->_storage[$key])) {
            $this->_storage[$key] = array($value);
            return 1;
        }
        return array_unshift($this->_storage[$key], $value);
    }

    /**
     * @copydoc Engine::listGet()
     */
    public function listGet($key, $num)
    {
        return isset($this->_storage[$key][$num])
            ? $this->_storage[$key][$num]
            : null;
    }

    /**
     * @copydoc Engine::listSet()
     */
    public function listSet($key, $num, $value)
    {
        $this->checkKey($key);
        if (!isset($this->_storage[$key]))
            $this->_storage[$key] = array($value);
        else if (isset($this->_storage[$key][$num]))
            $this->_storage[$key][$num] = $value;
        else
            $this->_storage[$key][] = $value;
        return count($this->_storage[$key]);
    }

    /**
     * @copydoc Engine::listCount()
     */
    public function listCount($key)
    {
        return isset($this->_storage[$key]) ? count($this->_storage[$key]) : 0;
    }

    /**
     * Scope of the engine.
     * @var int
     */
    protected $_scope = Engine::SCOPE_SESSION;

    /**
     * Performance level of the engine.
     * @var int
     */
    protected $_performance = Engine::PERFORMANCE_HIGH;

    /**
     * Size quotes to select best Cache engine.
     * @var array
     */
    protected $_limitSize = array(
        1000000 => 0 // not more than 1Mio characters per entry
    );

    /**
     * Garbage collector run.
     *
     * This implementation will look after the key ___gc and read the list
     * there. While the value is out of time it will be removed.
     *
     * @return bool true on success
     */
    public function gc()
    {
        if (!isset($this->_storage[self::GC_KEY]))
            return false;
        $now = time();
        foreach ($this->_storage[self::GC_KEY] as $key => $time)
            if ($time < $now)
                unset($this->_storage[self::GC_KEY][$key]);
    }

}