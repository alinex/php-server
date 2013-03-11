<?php
/**
 * @file
 * Storage keeping values in the Redis remote dictionary service.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Storage\Engine;

use Alinex\Storage\Engine;

/**
 * Storage keeping values in the Redis remote dictionary service.
 *
 * This may use the phpredis extension from
 * https://github.com/nicolasff/phpredis or the included Predis lib from
 * https://github.com/nrk/predis + https://github.com/nicolasff/phpredis.
 */
class Redis extends Engine
{
    /**
     * Check if this storage is available.
     *
     * @return bool true if storage can be used
     * @throws \Exception if something is missing.
     */
    protected static function check()
    {
        if (!extension_loaded('phpredis')) {
            $loader = Code\Autoloader::getInstance();
            $loader->add('Predis', __DIR__.'/../../../vnd/predis/lib');
            if (!class_exists('Predis'))
                throw new \BadMethodCallException(
                    "PhpRedis extension or predis library should be available"
                );
        }
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
     * - scheme - like 'tcp'
     * - host - the server name or ip address
     * - port - the connection port (default is 6379)
     * - password
     * - database
     * 
     * @param array|string $server memcache server
     * @return array list of servers
     */
    public function addServer($server = 'tcp://localhost:6379')
    {
        assert(is_string($server) || (is_array($server) && count($server) > 0));

        if (is_string($server)) {
            $parts = explode(':', $server);
            if (count($parts) < 3)
                throw new \Exception(
                    'Redis server should contain of scheme, host and port'
                );
            $server = array(
                array(
                    'scheme' => $parts[0],
                    'host' => str_replace('//', '', $parts[1]),
                    'port' => $parts[2]
                )
            );
        }
        // converted string or array neccessary
        $this->_server = array_merge($this->_server, $server);
        // reconnect if already connected
        if (isset($this->_redis))
            $this->connect();
    }

    /**
     * Redis instance to use.
     * @var \Memcached | Memcache
     */
    private $_redis = NULL;

    /**
     * Connect to memcache
     *
     * The session handling will be started if not allready done and the
     * storage array will be added.
     */
    protected function connect()
    {
        // initialize memcache
        $this->_redis = new Predis\Client($this->_server);
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
            $this->del($key);
            return null;
        }
        $this->checkKey($key);
        if (!isset($this->_redis))
            throw new Exception(
                tr(__NAMESPACE__, 'No servers set to connect to redis')
            );
        $result = $this->_redis->set($this->_context.$key, $value);
        if ($this->ttl)
            $this->_redis->expire($this->_context.$key, $this->ttl);
        return $result;
    }

    /**
     * Unset a storage variable
     *
     * @param string $key   name of the entry
     * @return bool    TRUE on success otherwise FALSE
     */
    public function remove($key)
    {
        if (isset($this->_redis))
            return $this->_redis->delete($mkey);
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
        if (isset($this->_redis))
            return $this->_redis->get($this->_context.$key);
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
        if (isset($this->_redis))
            return $this->_redis->exists($this->_context.$key);
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
        if (isset($this->_redis))
            return $this->_redis->keys('*');
        return array();
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