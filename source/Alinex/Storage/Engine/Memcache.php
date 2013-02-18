<?php
/**
 * @file
 * Storage keeping values in the Alternative PHP Cache.
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
 * Storage keeping values in the Alternative PHP Cache.
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
 */
class Memcache extends Storage\Engine
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
     * Time To Live
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
     * Constructor
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
     * Constructor
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
     * Method to set a storage variable
     *
     * @param string $key   name of the entry
     * @param string $value Value of storage key null to remove entry
     *
     * @return mixed value which was set
     * @throws \Alinex\Validator\Exception
     */
    function set($key, $value = null)
    {
        if (!isset($value)) {
            $this->remove($key);
            return null;
        }
        $this->checkKey($key);
        if (!isset($this->_memcache))
            $this->connect();
        return $this->_memcache->set($this->_context.$key, $value, $this->_ttl);
    }

    /**
     * Unset a storage variable
     *
     * @param string $key   name of the entry
     * @return bool    TRUE on success otherwise FALSE
     */
    public function remove($key)
    {
        if (isset($this->_memcache))
            return $this->_memcache->delete($mkey);
        return false;
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
        if (isset($this->_memcache))
            return $this->_memcache->get($this->_context.$key);
        return null;
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
        if (isset($this->_memcache))
            return (bool) $this->_memcache->get($this->_context.$key);
        return false;
    }

    /**
     * Get the list of keys
     *
     * @note This is done by searching through the APC cache and collecting all
     * registry keys because of the prefix.
     *
     * @return array   list of key names
     */
    public function keys()
    {
        if (isset($this->_memcache))
            return $this->_memcache->getAllKeys();
        return array();
    }

}