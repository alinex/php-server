<?php
/**
 * @file
 * Validator for database connections.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de
 */

namespace Alinex\DB;

use Alinex\Validator\Exception;
use Alinex\Validator\Type;

/**
 * Validator for database connections.
 */
class Validator
{
    /**
     * Structure definition for the engine entry.
     * @var array
     */
    private static $_connDefList = array(
        'notEmpty' => true,
        'mandatoryKeys' => array('driver'),
        'allowedKeys' => array(
            'host', 'dbname', 'user', 'password', 'path', 'memory', 'port',
            'unix_socket', 'charset'
        ),
        'keySpec' => array(
            'driver' => array(
                'Type::string',
                array(
                    'values' => array(
                        'pdo_mysql', 'pdo_sqlite', 'pdo_pgsql', 'pdo_oci',
                        'oci8', 'pdo_sqlsrv'
                    )
                )
            ),
            'host' => array(
                'Net::host',
                array()
            ),
            'dbname' => array(
                'Type::string',
                array()
            ),
            'user' => array(
                'Type::string',
                array()
            ),
            'password' => array(
                'Type::string',
                array()
            ),
            'path' => array(
                'IO::path',
                array('writable' => true)
            ),
            'memory' => array(
                'Type::boolean',
                array()
            ),
            'port' => array(
                'Net::port',
                array()
            ),
            'unix_socket' => array(
                'IO::path',
                array('exists' => true)
            ),
            'charset' => array(
                'Type::string',
                array()
            )
        )
    );
    
    /**
     * Add description Text to the structure information.
     */
    private static function connectionTextInit()
    {
        if (isset(self::$_connDefList['keySpec']['driver'][1]['description']))
            return;
        self::$_connDefList['keySpec']['driver'][1]['description'] = 
            tr(__NAMESPACE__, 'The built-in driver implementation to use.');
        self::$_connDefList['keySpec']['host'][1]['description'] = 
            tr(__NAMESPACE__, 'Hostname of the database to connect to.');
        self::$_connDefList['keySpec']['dbname'][1]['description'] = 
            tr(__NAMESPACE__, 'Name of the database/schema to connect to.');
        self::$_connDefList['keySpec']['user'][1]['description'] = 
            tr(__NAMESPACE__, 'Username to use when connecting to the database.');
        self::$_connDefList['keySpec']['password'][1]['description'] = 
            tr(__NAMESPACE__, 'Password to use when connecting to the database.');
        self::$_connDefList['keySpec']['path'][1]['description'] = 
            tr(__NAMESPACE__, 'The filesystem path to the database file. Mutually exclusive with memory. path takes precedence.');
        self::$_connDefList['keySpec']['memory'][1]['description'] = 
            tr(__NAMESPACE__, 'True if the SQLite database should be in-memory (non-persistent). Mutually exclusive with path. path takes precedence.');
        self::$_connDefList['keySpec']['port'][1]['description'] = 
            tr(__NAMESPACE__, 'Port of the database to connect to.');
        self::$_connDefList['keySpec']['unix_socket'][1]['description'] = 
            tr(__NAMESPACE__, 'Name of the socket used to connect to the database.');
        self::$_connDefList['keySpec']['charset'][1]['description'] = 
            tr(__NAMESPACE__, 'The charset used when connecting to the database.');
    }

    /**
     * Optimize the options.
     *
     * @param array $options specific settings
     * @return array optimized options
     *
     * @see integer()
     */
    private static function connectionOptions(array $options = null)
    {
        if (!isset($options))
            $options = array();
        
        // options have to be an array
        assert(is_array($options));
        // check for invalid options
        assert(
            count(
                array_diff(
                    array_keys($options),
                    array(
                        'denyDriver',
                        'fixedDriver'
                    )
                )
            ) == 0
        );
        // check options format
        assert(
            !isset($options['denyDriver'])
            || is_array($options['denyDriver'])
            || \Alinex\Validator::is(
                $options['denyDriver'], 'option', 'Type::arraylist',
                array(
                    'notEmpty' => true,
                    'keySpec' => array(
                        '' => array(
                            'Type::string',
                            array(
                                'values' => array(
                                    'pdo_mysql', 'pdo_sqlite', 'pdo_pgsql', 'pdo_oci',
                                    'oci8', 'pdo_sqlsrv'
                                )
                            )
                        )
                    )
                )
            )
        );
        assert(
            !isset($options['fixedDriver'])
            || is_bool($options['fixedDriver'])
        );
        // denyDriver not necessarry when using fixedDriver
        assert(
            isset($options['fixedDriver'])
            && isset($options['denyDriver']) && $options['denyDriver']
        );
    }

    /**
     * Check for databse connection definition.
     *
     * The validator will check for the right syntax for different databases:
     * - \c fixedDriver - driver which is the only one to use
     * - \c denyDriver - list of drivers which are not allowed
     *
     * The name option is possible and only used for giving the engine a name
     * for configuration files.
     *
     * @param mixed   $value    value to be checked
     * @param string  $name     readable variable identification
     * @param array   $options  specific settings
     *
     * @return boolean
     * @throws Exception if not valid
     */
    public static function connection($value, $name, array $options = null)
    {
        // name of origin have to be a string
        assert(is_string($name));

        $options = self::connectionOptions($options);
        // check the base configuration
        try {
            $value = Type::arraylist($value, $name, self::$_connDefList);
        } catch (Exception $ex) {
            throw $ex->createOuter(__METHOD__, $options);
        }
        // check for database specific options
        try {
            switch($value['driver']) {
                case 'pdo_sqlite':
                    $value = Type::arraylist(
                        $value, $name, array(
                            'mandatoryKeys' => array('driver'),
                            'allowedKeys' => array(
                                'user', 'password', 'path', 'memory'
                            )              
                        )
                    );
                    if (!isset($value['path']) && !isset($value['memory']))
                        throw new Exception(
                            tr(
                                __NAMESPACE__,
                                'One of the parameters {params} have to be set.',
                                array('params' => array('path', 'memory'))
                            )
                        );
                    break;
                case 'pdo_mysql':
                    $value = Type::arraylist(
                        $value, $name, array(
                            'mandatoryKeys' => array('driver'),
                            'allowedKeys' => array(
                                'host', 'dbname', 'user', 'password', 'port',
                                'unix_socket', 'charset'
                            )              
                        )
                    );
                    break;
                case 'pdo_pgsql':
                case 'pdo_sqlsrv':
                    $value = Type::arraylist(
                        $value, $name, array(
                            'mandatoryKeys' => array('driver'),
                            'allowedKeys' => array(
                                'host', 'dbname', 'user', 'password', 'port'
                            )              
                        )
                    );
                    break;
                case 'pdo_oci':
                case 'oci8':
                    $value = Type::arraylist(
                        $value, $name, array(
                            'mandatoryKeys' => array('driver'),
                            'allowedKeys' => array(
                                'host', 'dbname', 'user', 'password', 'port',
                                'charset'
                            )              
                        )
                    );
                    break;
            }
        } catch (Exception $ex) {
            throw $ex->createOuter(__METHOD__, $options);
        }
        // exclude specific database
        if (isset($options['fixedDriver'])
            && $value['driver'] != $options['fixedDriver'])
                throw new Exception(
                    tr(
                        __NAMESPACE__,
                        'Only the {type} driver is allowed here',
                        array('type' => $options['fixedDriver'])
                    )
                );
        if (isset($options['denyDriver'])
            && in_array($value['driver'], $options['denyDriver']))
                throw new Exception(
                    tr(
                        __NAMESPACE__,
                        'The {type} driver is not allowed here',
                        array('type' => $value['driver'])
                    )
                );
        // return result
        return $value;
    }

    /**
     * Get a human readable description for validity as boolean.
     *
     * @param array   $options  specific settings
     * @return string explaining message
     */
    static function connectionDescription($options = null)
    {
        self::connectionTextInit();
        
        $desc = tr(
            __NAMESPACE__,
            'The value has to be a database connection setting.'
        );
        $desc .= ' '.Type::arraylistDescription(
            self::$_connDefList
        );
        // check for engine specific options
        $desc .= ' '.tr(__NAMESPACE__, 'For type \'pdo_sqlite\' the following parameters may be given:')
            .Type::arraylistDescription(
                array(
                    'mandatoryKeys' => array('driver'),
                    'allowedKeys' => array(
                        'user', 'password', 'path', 'memory'
                    )              
                )
            );
        $desc .= ' '.tr(__NAMESPACE__, 'For type \'pdo_mysql\' the following parameters may be given:')
            .Type::arraylistDescription(
                array(
                    'mandatoryKeys' => array('driver'),
                    'allowedKeys' => array(
                        'host', 'dbname', 'user', 'password', 'port',
                        'unix_socket', 'charset'
                    )              
                )
            );
        $desc .= ' '.tr(__NAMESPACE__, 'For type \'pdo_pgsql\' and \'pdo_sqlsrv\' the following parameters may be given:')
            . Type::arraylistDescription(
                array(
                    'mandatoryKeys' => array('driver'),
                    'allowedKeys' => array(
                        'host', 'dbname', 'user', 'password', 'port'
                    )              
                )
            );
        $desc .= ' '.tr(__NAMESPACE__, 'For type \'pdo_oci\' and \'oci\' the following parameters may be given:')
            .Type::arraylistDescription(
                array(
                    'mandatoryKeys' => array('driver'),
                    'allowedKeys' => array(
                        'host', 'dbname', 'user', 'password', 'port',
                        'charset'
                    )              
                )
            );
        // exclude specific database
        if (isset($options['fixedDriver']))
            $desc .= ' '.tr(
                __NAMESPACE__, 'Only the {type} driver is allowed here.',
                array('type' => $options['fixedDriver'])
            );
        if (isset($options['denyDriver']))
            $desc .= ' '.tr(
                __NAMESPACE__, 'The {types} driver are not allowed here.',
                array('types' => $options['denyDriver'])
            );
        
        return $desc;
    }



}
