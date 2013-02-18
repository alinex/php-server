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
use Alinex\Util\String;

/**
 * Storage keeping values in the Alternative PHP Cache.
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
 * @codeCoverageIgnore because not testable while apc installed
 */
class XCache extends Storage\Engine
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
        return xcache_set($this->_context.$key, serialize($value), $this->ttl);
    }

    /**
     * Unset a storage variable
     *
     * @param string $key   name of the entry
     * @return bool    TRUE on success otherwise FALSE
     */
    public function remove($key)
    {
        return xcache_unset($this->_context.$key);
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
        return xcache_isset($this->_context.$key)
                ? unserialize(xcache_get($this->_context.$key))
                : NULL;
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
        return xcache_isset($this->_context.$key);
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

}