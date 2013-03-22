<?php
/**
 * @file
 * Dictionary keeping values in the Redis remote dictionary service.
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
 * Dictionary keeping values in the Redis remote dictionary service.
 *
 * This will work using the credis library
 * (https://github.com/colinmollenhour/credis) and if available the native
 * extension phpredis (https://github.com/nicolasff/phpredis) is used.
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
        if (file_exists(__DIR__.'/../../../vnd/credis')) {
            require_once __DIR__.'/../../../vnd/credis/Client.php';
            require_once __DIR__.'/../../../vnd/credis/Cluster.php';
        } else {
            throw new \BadMethodCallException(
                tr("Third party library 'credis' is not included")
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
     * $directory->addServer('localhost');
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
        // connect to new servers
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
        // initialize redis
        $this->_redis = new \Credis_Cluster($this->_server);
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
        if (!isset($this->_redis))
            throw new \Exception(
                tr(__NAMESPACE__, 'No servers set to connect to redis')
            );
        $result = $this->_redis->set($this->_context.$key, $value);
        if ($this->_ttl)
            $this->_redis->expire($this->_context.$key, $this->_ttl);
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
            return (bool) $this->_redis->del($key);
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
        if (\Credis_Client::TYPE_HASH)
        $this->checkKey($key);
        if (isset($this->_redis)) {
#            switch ($this->_redis->type($this->_context.$key)) {
#                case \Credis_Client::TYPE_HASH:
#                    $result = $this->_redis->hGetall($this->_context.$key);
#                    break;
#                default:
                    $result = $this->_redis->get($this->_context.$key);
#            }
            return $result ?: null;
        }
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
            return (bool) $this->_redis->exists($this->_context.$key);
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
        $this->checkKey($key);
        if (!isset($this->_redis))
            throw new \Exception(
                tr(__NAMESPACE__, 'No servers set to connect to redis')
            );
        if ($num == 1)
            return (bool) $this->_redis->incr($this->_context.$key);
        else if ($num > 1)
            return (bool) $this->_redis->incrBy($this->_context.$key, $num);
        else if ($num < 0)
            return $this->decr($key, $num);
        else
            return $this->_redis->get($this->_context.$key);
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
        $this->checkKey($key);
        if (!isset($this->_redis))
            throw new \Exception(
                tr(__NAMESPACE__, 'No servers set to connect to redis')
            );
        if ($num == 1)
            return (bool) $this->_redis->decr($this->_context.$key);
        else if ($num > 1)
            return (bool) $this->_redis->decrBy($this->_context.$key, $num);
        else if ($num < 0)
            return $this->incr($key, $num);
        else
            return $this->_redis->get($this->_context.$key);
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
        $this->checkKey($key);
        if (!isset($this->_redis))
            throw new \Exception(
                tr(__NAMESPACE__, 'No servers set to connect to redis')
            );
        return $this->_redis->append($this->_context.$key, $text);
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
        if (!isset($value)) {
            $this->hashRemove($key, $name);
            return null;
        }
        $this->checkKey($key);
        if (!isset($this->_redis))
            throw new \Exception(
                tr(__NAMESPACE__, 'No servers set to connect to redis')
            );
        $this->_redis->hSet($this->_context.$key, $name, $value);
        if ($this->_ttl)
            $this->_redis->expire($this->_context.$key, $this->_ttl);
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
        $this->checkKey($key);
        if (isset($this->_redis)) {
            $result = $this->_redis->hGet($this->_context.$key, $name);
            return $result ?: null;
        }
        return null;
    }

    /**
     * Check if the specified hash has the value
     * @param string $key name of storage entry
     * @param string $name key name within the hash
     * @return boll entry in hash found
     */
    public function hashHas($key, $name)
    {
        $this->checkKey($key);
        if (isset($this->_redis))
            return (bool) $this->_redis->hExists($this->_context.$key);
        return false;
    }

    /**
     * Remove some entry from within the specified hash.
     * @param string $key name of storage entry
     * @param string $name key name within the hash
     * @return bool true on success otherwise false
     */
    public function hashRemove($key, $name)
    {
        if (isset($this->_redis))
            return (bool) $this->_redis->hDel($key);
        return false;
    }

    /**
     * Count the number of entries within the specified hash.
     * @param string $key name of storage entry
     * @return int number of entries within the hash
     */
    public function hashCount($key)
    {
        $this->checkKey($key);
        if (isset($this->_redis)) {
            $result = $this->_redis->hLen($this->_context.$key);
            return $result ?: null;
        }
        return null;
    }

    /**
     * Add an element to the end of the list.
     * @param string $key name of storage entry
     * @param mixed $value data to be added
     * @return int new number of elements
     */
    public function listPush($key, $value)
    {
        if (!isset($value))
            return null;
        $this->checkKey($key);
        if (!isset($this->_redis))
            throw new \Exception(
                tr(__NAMESPACE__, 'No servers set to connect to redis')
            );
        $result = $this->_redis->rPush($this->_context.$key, $value);
        if ($this->_ttl)
            $this->_redis->expire($this->_context.$key, $this->_ttl);
        return $result;
    }

    /**
     * Get the last element out of the list.
     * @param string $key name of storage entry
     * @return mixed removed last element of list
     */
    public function listPop($key)
    {
        $this->checkKey($key);
        if (isset($this->_redis)) {
            $result = $this->_redis->rPop($this->_context.$key);
            return $result ?: null;
        }
        return null;
    }

    /**
     * Get the first element out of the list.
     * @param string $key name of storage entry
     * @return mixed removed first element
     */
    public function listShift($key)
    {
        $this->checkKey($key);
        if (isset($this->_redis)) {
            $result = $this->_redis->lPop($this->_context.$key);
            return $result ?: null;
        }
        return null;
    }

    /**
     * Add an element to the start of the list.
     * @param string $key name of storage entry
     * @param mixed $value data to be added
     * @return int new number of elements
     */
    public function listUnshift($key, $value)
    {
        if (!isset($value))
            return null;
        $this->checkKey($key);
        if (!isset($this->_redis))
            throw new \Exception(
                tr(__NAMESPACE__, 'No servers set to connect to redis')
            );
        $result = $this->_redis->lPush($this->_context.$key, $value);
        if ($this->_ttl)
            $this->_redis->expire($this->_context.$key, $this->_ttl);
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
        $this->checkKey($key);
        if (isset($this->_redis)) {
            $result = $this->_redis->lIndex($this->_context.$key, $num);
            return $result ?: null;
        }
        return null;
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
        $this->checkKey($key);
        if (!isset($this->_redis))
            throw new \Exception(
                tr(__NAMESPACE__, 'No servers set to connect to redis')
            );
        $this->_redis->lSet($this->_context.$key, $num, $value);
        if ($this->_ttl)
            $this->_redis->expire($this->_context.$key, $this->_ttl);
        return $value;
    }

    /**
     * Count the number of elements in list.
     * @param string $key name of storage entry
     * @return int number of list entries
     */
    public function listCount($key)
    {
        $this->checkKey($key);
        if (isset($this->_redis)) {
            $result = $this->_redis->lLen($this->_context.$key);
            return $result ?: null;
        }
        return null;
    }

}