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

namespace Alinex\Dictionary\Engine;

use Alinex\Dictionary\Engine;
use Alinex\Util\String;

/**
 * Storage keeping values in the Alternative PHP Cache.
 *
 * **Specification**
 * - Scope: local
 * - Performance: high
 * - Persistence: small-medium
 * - Size: medium, configurable
 * - Objects: will be serialized
 * - Manipulation: only inc() dec(), others emulated
 * - Garbage collection: ttl, self managed
 * - Requirements: Extension
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
 *
 * **Size**
 *
 * The cache size can be specified through the apc.shm_size (default 32M)
 * PHP configuration setting.
 *
 * @see Engine overview chart
 * @see Dictionary for usage examples
 *
 * @codeCoverageIgnore because not testable while xcache installed
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
     * @name Normal value access
     * @{
     */
    
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
        $ttl = !isset($ttl) ? $this->_ttl : $ttl ? $ttl : null;
        return apc_store($this->_context.$key, $value, $ttl)
            ? $value : null;
    }

    /**
     * @copydoc Engine::remove()
     */
    public function remove($key)
    {
        return apc_delete($this->_context.$key);
    }

    /**
     * @copydoc Engine::get()
     */
    public function get($key)
    {
        return apc_exists($this->_context.$key) ?
                apc_fetch($this->_context.$key) : NULL;
    }

    /**
     * @copydoc Engine::has()
     */
    public function has($key)
    {
        return apc_exists($this->_context.$key);
    }

    /**
     * @copydoc Engine::keys()
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
     * @}
     */
    
    /**
     * @name Value modification methods
     * @{
     */
    
    /**
     * @copydoc Engine::inc()
     */
    public function inc($key, $num = 1)
    {
        assert(is_int($num));

        if ($num < 0)
            return $this->dec($key, -$num);
        $this->checkKey($key);
        if (!apc_exists($this->_context.$key))
            return apc_store($this->_context.$key, $num) ? $num : null;
        return apc_inc($this->_context.$key, $num);
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
        if (!apc_exists($this->_context.$key))
            return apc_store($this->_context.$key, -$num) ? -$num : null;
        return apc_dec($this->_context.$key, $num);
    }

    /**
     * @}
     */
    
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