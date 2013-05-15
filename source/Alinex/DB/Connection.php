<?php
/**
 * @file
 * Get a doctrine database connection.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de
 */

namespace Alinex\DB;

use Doctrine\DBAL\DriverManager;
use Alinex\Dictionary\Engine;

/**
 * Get a doctrine database connection.
 */
class Connection
{
    /**
     * Cache group to store SQL results.
     * This will hold the SQL codes which are generated out of DQL syntax.
     */
    const CACHE_GROUP = 'dbsql.';

    /**
     * Specific result cache to use.
     * @var \Doctrine\Common\Cache\Cache
     */
    private static $_resultCache = null;

    /**
     * Set specific result cache to use.
     *
     * If not set the \Alinex\DB\Cache will be used which is a wrapper for the
     * \Alinex\Dictionary\Cache. This is done with
     *
     * @param \Doctrine\Common\Cache\Cache $cache doctrine cache implementation
     * to use.
     */
    public static function setResultCacheImpl(\Doctrine\Common\Cache\Cache $cache)
    {
        self::$_resultCache = $cache;
    }

    /**
     * Creates a doctrine connection object.
     *
     * The configuration will be red from the registry.
     *
     * This method returns a Doctrine\DBAL\Connection which wraps the underlying
     * driver connection.
     *
     * @return \Doctrine\DBAL\Connection
     */
    public static function get()
    {
        // check for cache
        $cache = self::$_resultCache;
        if (!isset($cache)) {
            $cache = new Cache();
            $cache->setFlags(Engine::PERFORMANCE_HIGH & Engine::SCOPE_GLOBAL);
            $cache->setNamespace(self::CACHE_GROUP);
        }
        // setup config
        $config = new \Doctrine\DBAL\Configuration();
        $config->setResultCacheImpl($cache);
        if (!PRODUCTIVE)
            $config->setSQLLogger(new SQLLogger());
        //..
        $connectionParams = array(
            'dbname' => 'a3',
            'user' => 'alinex',
            'password' => 'test',
            'host' => 'localhost',
            'driver' => 'pdo_mysql',
        );

        return DriverManager::getConnection($connectionParams, $config);

    }
}