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
     * Registry entry group for db connection settings.
     * This group will hold named connections with their configuration. The
     * standard connection is called 'default'.
     */
    const REGISTRY_BASE = 'dbconn.';
    
    /**
     * Result cache to use.
     * This will hold the SQL codes which are generated out of DQL syntax. By
     * default each connection uses a subgroup with the connection name.
     */
    const CACHE_GROUP = 'db.';

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
     * @param string $name name of the configuration
     * @return \Doctrine\DBAL\Connection
     */
    public static function get($name = 'default')
    {
        // get configuration
        $registry = \Alinex\Dictionary\Registry::getInstance();
        if (!isset($registry))
            throw new \Exception(tr(__NAMESPACE__, 'No registry set up to read configuration from'));
        // add validators
        if ($registry->validatorCheck()
            && !$registry->validatorHas(self::REGISTRY_BASE.$name))
            $registry->validatorSet(
                self::REGISTRY_BASE.$name, 
                __NAMESPACE__.'\Validator::connection',
                array(
                    'exclude' => 'Session',
                    'description' => tr(
                        __NAMESPACE__,
                        'Database connection configuration for {name}.',
                        array('name' => $name)
                    )
                )
            );
        $connectionConfig = $registry->get(self::REGISTRY_BASE.$name);
        if (!isset($connectionConfig))
            throw new \Exception(
                tr(__NAMESPACE__, 'Database is not configured')
            );
        // check for cache
        $cache = self::$_resultCache;
        if (!isset($cache)) {
            $cache = new Cache();
            $cache->setFlags(Engine::PERFORMANCE_MEDIUM & Engine::SCOPE_GLOBAL);
            $cache->setNamespace(self::CACHE_GROUP.$name.'.');
        }
        // setup config
        $config = new \Doctrine\DBAL\Configuration();
        $config->setResultCacheImpl($cache);
        if (!defined('PRODUCTIVE') || !PRODUCTIVE)
            $config->setSQLLogger(new SQLLogger());

        return DriverManager::getConnection($connectionConfig, $config);
    }
}