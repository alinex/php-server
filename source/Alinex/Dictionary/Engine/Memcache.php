<?php
/**
 * @file
 * Storage keeping values in the Memcache service.
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
 * Storage keeping values in the Memcache service.
 *
 * **Specification**
 * - Scope: global
 * - Performance: medium
 * - Persistence: medium
 * - Size: medium
 * - Objects: will be serialized
 * - Manipulation: only inc() dec(), others emulated
 * - Garbage collection: ttl, self managed
 * - Requirements: Extension
 *
 * This class will store the registry in the global memcache store. This makes
 * it less performant but globally accessible.
 *
 * @attention The Memcache package has to be installed on the server. This is
 * easy done using the PECL package. Read more at
 * http://pecl.php.net/package/memcache
 *
 * All values stored through this registry will be prefixed and stored in the
 * memcache. To use more than one instance of this registry you may use
 * different prefixes. Also use the prefix wisely to prevent collision with
 * other librarys and systems using the same memcache servers.
 *
 * The maximal key length is 250 characters and can't contain spaces. a single
 * value may contain up to 1MB of data. this is already taken care of in the
 * Registry base class.
 *
 * @see Engine overview chart
 * @see Dictionary for usage examples
 *
 * @codeCoverageIgnore because not testable while xcache installed
 */
class Memcache extends Engine
{
    /**
     * Check if this storage is available.
     *
     * @return bool true if storage can be used
     * @throws \Exception if something is missing.
     */
    protected static function check()
    {
        if (!extension_loaded('memcached'))
            throw new \BadMethodCallException("Memcached extension not loaded");
        if (!method_exists('Memcached', 'getAllKeys'))
            throw new \BadMethodCallException("Memcached 2.0 or higher needed");

        return true;
    }

    /**
     * List of memcache server to connect
     * @var array
     */
    private $_server = array();

    /**
     * Add one or more memcache servers.
     *
     * This can be done using a simple string with an hostname to connect to
     * this host on the default port:
     * @code
     * $storage->addServer('localhost');
     * @endcode
     *
     * Or to take full control over all configuration entries give a list of
     * hashes with the following fields:
     * - host - the server name or ip address
     * - port - the connection port (default is 11211)
     * - weight - the weighting to specify which one to use most
     *
     * @param array|string $server memcache server
     * @return array list of servers
     */
    public function addServer($server = 'localhost')
    {
        assert(is_string($server) || (is_array($server) && count($server) > 0));

        if (is_string($server))
            $server = array(array('host' => $server));
        // converted string or array neccessary
        $this->_server = array_merge($this->_server, $server);
        // reconnect if already connected
        if (isset($this->_memcache))
            $this->connect();
    }

    /**
     * Memcache instance to use.
     * @var \Memcached | Memcache
     */
    private $_memcache = NULL;

    /**
     * Connect to memcache
     *
     * The session handling will be started if not allready done and the
     * storage array will be added.
     */
    protected function connect()
    {
        // initialize memcache
        $this->_memcache = new \Memcached();
        foreach ($this->_server as $server) {
            $host = $server['host'];
            $weight = isset($server['weight']) ? $server['weight'] : null;
            $port = isset($server['port']) ? $server['port'] : 11211;
            $this->_memcache->addServer($host, $port, $weight);
        }
    }

    /**
     * @copydoc Engine::set()
     */
    function set($key, $value = null, $ttl = null)
    {
        if (!isset($value)) {
            $this->remove($key);
            return null;
        }
        $this->checkKey($key);
        if (!isset($this->_memcache))
            throw new Exception(
                tr(__NAMESPACE__, 'No servers set to connect to memcache')
            );
        $ttl = !isset($ttl) ? $this->_ttl : $ttl ? $ttl : null;
        return $this->_memcache->set($this->_context.$key, $value, $ttl);
    }

    /**
     * @copydoc Engine::remove()
     */
    public function remove($key)
    {
        if (isset($this->_memcache))
            return $this->_memcache->delete($mkey);
        return false;
    }

    /**
     * @copydoc Engine::get()
     */
    public function get($key)
    {
        $this->checkKey($key);
        if (isset($this->_memcache))
            return $this->_memcache->get($this->_context.$key);
        return null;
    }

    /**
     * @copydoc Engine::has()
     */
    public function has($key)
    {
        $this->checkKey($key);
        if (isset($this->_memcache))
            return (bool) $this->_memcache->get($this->_context.$key);
        return false;
    }

    /**
     * @copydoc Engine::keys()
     */
    public function keys()
    {
        if (isset($this->_memcache))
            return $this->_memcache->getAllKeys();
        return array();
    }

    /**
     * @copydoc Engine::inc()
     */
    public function inc($key, $num = 1)
    {
        assert(is_int($num));

        if ($num < 0)
            return $this->dec($key, -$num);
        $this->checkKey($key);
        if (!isset($this->_memcache))
            return null;
        if ($this->_memcache->get($this->_context.$key) !== false)
            return $this->_memcache->set($this->_context.$key, $num);
        return $this->_memcache->increment($this->_context.$key, $num);
    }

    /**
     * @copydoc Engine::dec()
     */
    public function dec($key, $num = 1)
    {
        assert(is_int($num));

        if ($num < 0)
            return $this->inc($key, -$num);
        $this->checkKey($key);
        if (!isset($this->_memcache))
            return null;
        if ($this->_memcache->get($this->_context.$key) !== false)
            return $this->_memcache->set($this->_context.$key, -$num);
        return $this->_memcache->decrement($this->_context.$key, $num);
    }

    /**
     * Scope of the engine.
     * @var int
     */
    protected $_scope = Engine::SCOPE_GLOBAL;

    /**
     * Persistence level of the engine.
     * @var int
     */
    protected $_persistence = Engine::PERSISTENCE_MEDIUM;

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
        10000000 => 0,
        1000000 => 0.2,
        100000 => 0.5,
        10000 => 0.8
    );

}