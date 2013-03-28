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

namespace Alinex\Dictionary\Engine;

use Alinex\Dictionary\Engine;

/**
 * Storage keeping values in the Redis remote dictionary service.
 *
 * **Specification**
 * - Scope: global
 * - Performance: medium
 * - Persistence: medium
 * - Size: medium
 * - Objects: only flat lists, others will be serialized
 * - Manipulation: native support
 * - Garbage collection: ttl, self managed
 * - Requirements: Redis Server, Extension (optional)
 *
 * This will work using the credis library
 * (https://github.com/colinmollenhour/credis) and if available the native
 * extension phpredis (https://github.com/nicolasff/phpredis) is used.
 *
 * To use the garbage collector setTtl() have to be used. An implicit garbage
 * collector call is not neccessary because redis will do this on it's own.
 * The garbage collector will work in TTL mode there each entry will be
 * removed after the general defined time to live per key.
 *
 * **Garbage Collection**
 *
 * Set a timeout on key. After the timeout has expired, the key will
 * automatically be deleted. A key with an associated timeout is often said to
 * be volatile in Redis terminology.
 *
 * The timeout is cleared only when the key is removed using the remove()
 * command or overwritten using the set() command. This means that all the
 * operations that conceptually alter the value stored at the key without
 * replacing it with a new one will leave the timeout untouched. For instance,
 * incrementing the value of a key with inc(), pushing a new value into a list
 * with listPush(), or altering the field value of a hash with hashSet() are all
 * operations that will leave the timeout untouched.
 *
 * For redis it is not neccessary to call the garbage collector. This is done by
 * redis itself.
 *
 * @see Engine overview chart
 * @see Dictionary for usage examples
 */
class Redis extends Engine
{
    /**
     * @copydoc Engine::check()
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
     * List of redis server to connect
     * @var array
     */
    private $_server = array();

    /**
     * Add one or more redis servers.
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
     * @var \Credis_Client
     */
    private $_redis = NULL;

    /**
     * Connect to redis server
     */
    protected function connect()
    {
        // initialize redis
        $this->_redis = new \Credis_Cluster($this->_server);
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
        if (!isset($this->_redis))
            throw new \Exception(
                tr(__NAMESPACE__, 'No servers set to connect to redis')
            );
        if (\Alinex\Util\ArrayStructure::isAssoc($value))
            $this->_redis->hMSet($this->_context.$key, $value);
        else if (is_array($value))
            foreach($value as $entry)
                $this->_redis->rPush($this->_context.$key, $entry);
        else
            $this->_redis->set($this->_context.$key, $value);
        if (!isset($ttl)) $ttl = $this->_ttl;
        if (isset($ttl) && $ttl)
            $this->_redis->expire($this->_context.$key, $ttl);
        return $value;
    }

    /**
     * @copydoc Engine::remove()
     */
    public function remove($key)
    {
        if (isset($this->_redis))
            return (bool) $this->_redis->del($key);
        return false;
    }

    /**
     * @copydoc Engine::get()
     */
    public function get($key)
    {
        if (\Credis_Client::TYPE_HASH)
        $this->checkKey($key);
        if (isset($this->_redis)) {
            switch ($this->_redis->type($this->_context.$key)) {
                case \Credis_Client::TYPE_HASH:
                    $result = $this->_redis->hGetall($this->_context.$key);
                    break;
                case \Credis_Client::TYPE_LIST:
                    $result = $this->_redis->lRange($this->_context.$key, 0 -1);
                    break;
                default:
                    $result = $this->_redis->get($this->_context.$key);
            }
            return $result ?: null;
        }
        return null;
    }

    /**
     * @copydoc Engine::has()
     */
    public function has($key)
    {
        $this->checkKey($key);
        if (isset($this->_redis))
            return (bool) $this->_redis->exists($this->_context.$key);
        return false;
    }

    /**
     * @copydoc Engine::keys()
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
     * @copydoc Engine::inc()
     */
    public function inc($key, $num = 1)
    {
        assert(is_int($num));

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
            return $this->dec($key, -$num);
        else
            return $this->_redis->get($this->_context.$key);
    }

    /**
     * @copydoc Engine::dec()
     */
    public function dec($key, $num = 1)
    {
        assert(is_int($num));

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
            return $this->inc($key, -$num);
        else
            return $this->_redis->get($this->_context.$key);
    }

    /**
     * @copydoc Engine::append()
     */
    public function append($key, $text)
    {
        assert(is_string($text));

        $this->checkKey($key);
        if (!isset($this->_redis))
            throw new \Exception(
                tr(__NAMESPACE__, 'No servers set to connect to redis')
            );
        $this->_redis->append($this->_context.$key, $text);
        return $this->_redis->get($this->_context.$key);
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
        return $value;
    }

    /**
     * @copydoc Engine::hashGet()
     */
    public function hashGet($key, $name)
    {
        if (isset($this->_redis)) {
            $result = $this->_redis->hGet($this->_context.$key, $name);
            return $result ?: null;
        }
        return null;
    }

    /**
     * @copydoc Engine::hashHas()
     */
    public function hashHas($key, $name)
    {
        if (isset($this->_redis))
            return (bool) $this->_redis->hExists($this->_context.$key, $name);
        return false;
    }

    /**
     * @copydoc Engine::hashRemove()
     */
    public function hashRemove($key, $name)
    {
        if (isset($this->_redis))
            return (bool) $this->_redis->hDel($key, $name);
        return false;
    }

    /**
     * @copydoc Engine::hashCount()
     */
    public function hashCount($key)
    {
        if (isset($this->_redis)) {
            $result = $this->_redis->hLen($this->_context.$key);
            return $result ?: null;
        }
        return null;
    }

    /**
     * @copydoc Engine::listPush()
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
     * @copydoc Engine::listPop()
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
     * @copydoc Engine::listShift()
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

    /**@copydoc Engine::listUnshift()
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
     * @copydoc Engine::listGet()
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
     * @copydoc Engine::listSet()
     */
    public function listSet($key, $num, $value)
    {
        $this->checkKey($key);
        if (!isset($this->_redis))
            throw new \Exception(
                tr(__NAMESPACE__, 'No servers set to connect to redis')
            );
        if (isset($num))
            $this->_redis->lSet($this->_context.$key, $num, $value);
        else
            $this->_redis->rPush($this->_context.$key, $value);
        if ($this->_ttl)
            $this->_redis->expire($this->_context.$key, $this->_ttl);
        return $value;
    }

    /**
     * @copydoc Engine::listCount()
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