<?php
/**
 * @file
 * Storage keeping values in the XCache.
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
 * Storage keeping values in the XCache.
 *
 * **Specification**
 * - Scope: local
 * - Performance: high
 * - Persistence: medium
 * - Size: small-medium, configureable
 * - Objects: will be serialized
 * - Manipulation: only inc() dec(), others emulated
 * - Garbage collection: ttl, self managed
 * - Requirements: Extension
 *
 * http://xcache.lighttpd.net/ XCache is a fast, stable PHP opcode cacher
 * which was first developed for the lighttpd web server but is also available
 * for apache and nginx. Like APC it has also the abality to use a shared
 * memory user cache in which this this registry will be stored.
 *
 * @attention The XCache package has to be installed on the server. This can be
 * done with the prebuild packages like on debian:
 * <pre>apt-get install php5-xcache</pre>
 *
 * All values stored through this registry will be prefixed and stored in the
 * XCache user-cache.
 *
 * **Size**
 *
 * The cache size can be specified through the xcache.var_size (default 64M)
 * PHP configuration setting.
 *
 * @see Engine overview chart
 * @see Dictionary for usage examples
 *
 * @codeCoverageIgnore because not testable while apc installed
 */
class XCache extends Engine
{
    /**
     * Check if this storage is available.
     *
     * @return bool true if storage can be used
     * @throws \Exception if something is missing.
     */
    protected static function check()
    {
        if(!extension_loaded('xcache'))
            throw new \BadMethodCallException("XCache extension not loaded");
        return true;
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
        $ttl = !isset($ttl) ? $this->_ttl : $ttl ? $ttl : null;
        return xcache_set($this->_context.$key, serialize($value), $ttl);
    }

    /**
     * @copydoc Engine::remove()
     */
    public function remove($key)
    {
        return xcache_unset($this->_context.$key);
    }

    /**
     * @copydoc Engine::get()
     */
    public function get($key)
    {
        return xcache_isset($this->_context.$key)
                ? unserialize(xcache_get($this->_context.$key))
                : NULL;
    }

    /**
     * @copydoc Engine::has()
     */
    public function has($key)
    {
        return xcache_isset($this->_context.$key);
    }

    /**
     * @copydoc Engine::keys()
     */
    public function keys()
    {
        for ($i = 0, $count = xcache_count(XC_TYPE_VAR); $i < $count; $i++) {
            $entries = xcache_list(XC_TYPE_VAR, $i);
            if (is_array($entries['cache_list'])) {
                foreach ($entries['cache_list'] as $entry)
                    if (String::startsWith($entry['name'], $this->_context))
                        $keys[] = substr(
                            $entry['name'], strlen($this->_context)
                        );
            }
        }
        return $keys;
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
        if (!xcache_isset($this->_context.$key))
            return xcache_set($this->_context.$key, $num) ? $num : null;
        return xcache_inc($this->_context.$key, $num);
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
        if (!xcache_isset($this->_context.$key))
            return xcache_set($this->_context.$key, -$num) ? -$num : null;
        return xcache_dec($this->_context.$key, $num);
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