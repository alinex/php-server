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

/**
 * Get a doctrine database connection.
 */
class Connection
{

    /**
     * Creates a doctrine connection object.
     *
     * The configuration will be red from the registry.
     *
     * This method returns a Doctrine\DBAL\Connection which wraps the underlying
     * driver connection.
     *
     * $params must contain at least one of the following.
     * Either 'driver' with one of the following values:  pdo_mysql pdo_sqlite pdo_pgsql pdo_oci (unstable) pdo_sqlsrv pdo_ibm (unstable) pdo_sqlsrv mysqli sqlsrv ibm_db2 (unstable) drizzle_pdo_mysql
     * OR 'driverClass' that contains the full class name (with namespace) of the driver class to instantiate.
     *
     * Other (optional) parameters:
     * user (string): The username to use when connecting.
     * password (string): The password to use when connecting.
     * driverOptions (array): Any additional driver-specific options for the driver. These are just passed through to the driver.
     * pdo: You can pass an existing PDO instance through this parameter. The PDO instance will be wrapped in a Doctrine\DBAL\Connection.
     * wrapperClass: You may specify a custom wrapper class through the 'wrapperClass' parameter but this class MUST inherit from Doctrine\DBAL\Connection.
     * driverClass: The driver class to use.
     *
     * @return \Doctrine\DBAL\Connection
     */
    public static function get()
    {
        $config = new \Doctrine\DBAL\Configuration();
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