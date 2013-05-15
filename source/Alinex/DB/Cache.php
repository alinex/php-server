<?php
/**
 * @file
 * Interface to use the Alinex caching system for doctrine, too.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de
 */

namespace Alinex\DB;

use Alinex\Dictionary\Cache;

/**
 * Interface to use the Alinex caching system for doctrine, too.
 */
class Cache extends \Doctrine\Common\Cache\CacheProvider
{
    /**
     * Flags for storing the entries.
     * @var int
     */
    private $_flags = 0;

    /**
     * Set the flags which should be used on all value settings.
     * @param type $flags scope, persistence and performance... flags
     */
    public function setFlags($flags)
    {
        assert(is_int($flags));
        $this->_flags = $flags;
    }

    /**
     * Fetches an entry from the cache.
     *
     * @param string $id cache id The id of the cache entry to fetch.
     * @return string The cached data or FALSE, if no cache entry exists for
     * the given id.
     */
    protected function doFetch($id)
    {
        return Cache::getInstance()->get($id);
    }

    /**
     * Test if an entry exists in the cache.
     *
     * @param string $id cache id The cache id of the entry to check for.
     * @return boolean TRUE if a cache entry exists for the given cache id,
     * FALSE otherwise.
     */
    protected function doContains($id)
    {
        return Cache::getInstance()->has($id);
    }

    /**
     * Puts data into the cache.
     *
     * @param string $id The cache id.
     * @param string $data The cache entry/data.
     * @param int $lifeTime The lifetime. If != false, sets a specific lifetime
     * for this cache entry (null => infinite lifeTime).
     *
     * @return boolean TRUE if the entry was successfully stored in the cache,
     * FALSE otherwise.
     */
    protected function doSave($id, $data, $lifeTime = false)
    {
        return Cache::getInstance()->set($id, $data, $this->_flags, $lifeTime);
    }

    /**
     * Deletes a cache entry.
     *
     * @param string $id cache id
     * @return boolean TRUE if the cache entry was successfully deleted,
     * FALSE otherwise.
     */
    protected function doDelete($id)
    {
        return Cache::getInstance()->remove($id);
    }

    /**
     * Deletes all cache entries.
     *
     * @return boolean TRUE if the cache entry was successfully deleted,
     * FALSE otherwise.
     */
    protected function doFlush()
    {
        return Cache::getInstance()->clear();
    }

    /**
     * Retrieves cached information from data store
     *
     * @return  array An associative array with server's statistics if
     * available, NULL otherwise.
     */
    protected function doGetStats()
    {
        return null;
    }

}