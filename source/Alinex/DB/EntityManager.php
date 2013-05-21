<?php

/**
 * @file
 * Access to doctrine entity manager.
 *
 * @copyright \ref Copyright 2009 - 2011, Alexander Schilling
 * @license All Alinex code is released under the GNU General Public \ref License.
 * @author Alexander Schilling <info@alinex.de>
 * @see http://alinex.de Alinex Project
 */

namespace Alinex\DB;

use Alinex\Dictionary\Engine;
use Doctrine\ORM\Configuration;

/**
 * Access to doctrine entity manager.
 *
 * This class is an singleton wrapper to the Doctrine::ORM::EntityManager. It
 * will initialize it and configure it for use in the alinex system.
 */
class EntityManager
{
    /**
     * Cache group to store table metadata.
     * Caching metadata information, that is, all the information you supply 
     * via annotations or yaml, so that they do not need to be parsed and loaded
     * from scratch on every single request.
     */
    const CACHEMETADATA_GROUP = 'dbmeta.';

    /**
     * Cache group to store SQL results.
     * This will hold the SQL codes which are generated out of DQL syntax.
     */
    const CACHEQUERY_GROUP = 'dbsql.';

    /**
     * The multiton instance of the Doctrine::ORM::EntityManager.
     * @var array of \Doctrine\ORM\EntityManager
     */
    private static $instance = array();

    /**
     * Retrieve the singleton instance.
     *
     * Get the doctrine entity manager or instantiate it to be used.
     * 
     * @param string $name name of the configuration
     * @return \Doctrine\ORM\EntityManager
     */
    public static function getInstance($name = 'default')
    {
        if (!isset(self::$instance[$name]))
            self::$instance[$name] = self::createEntityManager($name);
        return self::$instance[$name];
    }

    /**
     * Instantiate a new doctrine entity manager.
     *
     * This method will instantiate and configure the entity manage for use in Alinex.
     * - the proy will bet set
     * - the alinex cache system will be linked in
     * - the sql logging will be enabled if system is under development
     * - the entity directories will be added
     * - a prefix patch will be added
     * @param string $name name of the configuration
     * @return \Doctrine\ORM\EntityManager
     */
    private static function createEntityManager($name)
    {
        // use doctrine in memory array cache for development
        $cache = !defined('PRODUCTIVE') || !PRODUCTIVE
            ? new \Doctrine\Common\Cache\ArrayCache()
            : null;        
        $metadataCache = new Cache();
        $metadataCache->setFlags(Engine::PERFORMANCE_HIGH & Engine::SCOPE_GLOBAL);
        $metadataCache->setNamespace(self::CACHEMETADATA_GROUP);
        $queryCache = new Cache();
        $queryCache->setFlags(Engine::PERFORMANCE_HIGH & Engine::SCOPE_GLOBAL);
        $queryCache->setNamespace(self::CACHEQUERY_GROUP);
        // configure doctrine
        $config = new Configuration();
        $config->setMetadataCacheImpl(isset($cache) ? $cache : $metadataCache);
        
        $entities = array(__DIR__.'/../Entity');
#        // can also be used with array of directories
#        foreach (getPackageList() as $package)
#            if (is_dir($package.'/Entity'))
#                $entities[] = $package.'/Entity';
        $driverImpl = $config->newDefaultAnnotationDriver($entities);
        $config->setMetadataDriverImpl($driverImpl);
        $config->setQueryCacheImpl(isset($cache) ? $cache : $queryCache);
        
        // config proxy classes
        $config->setProxyDir(__DIR__.'/../../data/db-proxy');
        $config->setProxyNamespace('data\db-proxy');
        // auto generate proxies only if not in productive mode
        $config->setAutoGenerateProxyClasses(
            !defined('PRODUCTIVE') || !PRODUCTIVE
        );
        // add sql logger in development mode
        if (!defined('PRODUCTIVE') || !PRODUCTIVE)
            $config->setSQLLogger(new SQLLogger());

        // create the entity manager
        return \Doctrine\ORM\EntityManager::create(
            Connection::get($name),
            $config
        );
    }
}