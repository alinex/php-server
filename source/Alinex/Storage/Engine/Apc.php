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

use Alinex\Storage\Engine;
use Alinex\Util\String;

/**
 * Storage keeping values in the Alternative PHP Cache.
 *
 * This class will store the key-value pairs in the APC user storage. This scope
 * will be kept locally for the complete machine.
 *
 * The Alternative PHP Cache (APC) is a free and open opcode cache for PHP. Its
 * goal is to provide a free, open, and robust framework for caching and
 * optimizing PHP intermediate code. But it also offers an user-cache.
 * It can be easy installed as PECL Package.
 *
 * @attention The APC package has to be installed on the server. This is easy
 * done using the PECL package or through your systems packaging tool.
 * Read more at http://php.net/apc
 *
 * All values stored through this registry will be prefixed and stored in the
 * APC user-cache. To use more than one instance of this registry you may use
 * different prefixes. Also use the prefix wisely to prevent collision with
 * other librarys and php routines on the same machine.
 */
class Apc extends Engine
{
    /**
     * Check if this storage is available.
     *
     * @return bool true if storage can be used
     * @throws \Exception if something is missing.
     */
    protected static function check()
    {
        if(!extension_loaded('apc'))
            throw new \BadMethodCallException("APC extension not loaded");
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
        return apc_store($this->_context.$key, $value, $this->_ttl);
    }

    /**
     * Unset a storage variable
     *
     * @param string $key   name of the entry
     * @return bool    TRUE on success otherwise FALSE
     */
    public function remove($key)
    {
        return apc_delete($this->_context.$key);
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
        return apc_exists($this->_context.$key) ?
                apc_fetch($this->_context.$key) : NULL;
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
        return apc_exists($this->_context.$key);
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
        $keys = array();
        $info = apc_cache_info('user');
        foreach ($info['cache_list'] as $entry)
            if (String::startsWith($entry['info'], $this->_context))
                $keys[] = substr($entry['info'], strlen($this->_context));
        return $keys;
    }
    
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
        1000000 => 0,
        100000 => 0.2,
        10000 => 0.5,
        1000 => 0.8
    );
    
}