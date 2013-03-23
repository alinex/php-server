<?php
/**
 * @file
 * Validator for dictionary package.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de
 */

namespace Alinex\Validator;

use Alinex\Dictionary\Engine;

/**
 * Validator for dictionary package.
 *
 * This validator is specific for the dictionary package. It provides enhanced
 * checks over the cory types.
 */
class Dictionary
{
    /**
     * Names for the scope settings
     * @var array
     */
    private static $_engineScopes = null;

    /**
     * Names for the persistence settings
     * @var array
     */
    private static $_enginePersistence = null;

    /**
     * Names for the performance settings
     * @var array
     */
    private static $_enginePerformance = null;

    /**
     * Optimize the options.
     *
     * @param array $options specific settings
     * @return array optimized options
     *
     * @see integer()
     */
    private static function engineOptions(array $options = null)
    {
        if (!isset($options))
            $options = array();

        assert(!isset($options['exclude']) || is_array($options['exclude']));
        assert(
            $options['scope'] == Engine::SCOPE_SESSION
            || $options['scope'] == Engine::SCOPE_LOCAL
            || $options['scope'] == Engine::SCOPE_GLOBAL
        );
        assert(
            $options['persistence'] == Engine::PERSISTENCE_SHORT
            || $options['persistence'] == Engine::PERSISTENCE_MEDIUM
            || $options['persistence'] == Engine::PERSISTENCE_LONG
        );
        assert(
            $options['performance'] == Engine::PERFORMANCE_LOW
            || $options['performance'] == Engine::PERFORMANCE_MEDIUM
            || $options['performance'] == Engine::PERFORMANCE_HIGH
        );

        // fill description
        if (!isset(self::$_engineScopes))
            self::$_engineScopes = array(
                // TRANS: title for session scope
                Engine::SCOPE_SESSION => tr(__NAMESPACE__, 'session'),
                // TRANS: title for local scope
                Engine::SCOPE_LOCAL => tr(__NAMESPACE__, 'local'),
                // TRANS: title for global scope
                Engine::SCOPE_GLOBAL => tr(__NAMESPACE__, 'global')
            );
        if (!isset(self::$_enginePersistence))
            self::$_enginePersistence = array(
                // TRANS: title for short persistence
                Engine::PERSISTENCE_SHORT => tr(__NAMESPACE__, 'short'),
                // TRANS: title for medium persistence
                Engine::PERSISTENCE_MEDIUM => tr(__NAMESPACE__, 'medium'),
                // TRANS: title for long persistence
                Engine::PERSISTENCE_LONG => tr(__NAMESPACE__, 'long')
            );
        if (!isset(self::$_enginePerformance))
            self::$_enginePerformance = array(
                // TRANS: title for low performance
                Engine::PERFORMANCE_LOW => tr(__NAMESPACE__, 'low'),
                // TRANS: title for medium performance
                Engine::PERFORMANCE_MEDIUM => tr(__NAMESPACE__, 'medium'),
                // TRANS: title for high performance
                Engine::PERFORMANCE_HIGH => tr(__NAMESPACE__, 'high')
            );
    }

    /**
     * Check for dictionary engine definition.
     *
     * The definition of an dictionary engine can be n array structure like:
     * @verbatim
     * array(
     *  type => 'Redis',
     *  prefix' => 'ax:',
     *  server' => array('tcp://localhost:3456')
     * );
     * @endverbatim
     *
     * The validator will check for the right syntax for any engine. The
     * possible engines can be limited by the following parameters:
     * - \c exclude - list of excluded engines
     * - \c scope - concret scope: Engine::SCOPE_SESSION, Engine::SCOPE_LOCAL,
     * Engine::SCOPE_GLOBAL
     * - \c persistence - minimum persistence: Engine::PERSISTENCE_SHORT,
     * Engine::PERSISTENCE_MEDIUM, Engine::PERSISTENCE_LONG
     * - \c performance - minimum Performance: Engine::PERFORMANCE_LOW,
     * Engine::PERFORMANCE_MEDIUM, Engine::PERFORMANCE_HIGH
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
    public static function engine($value, $name, array $options = null)
    {
        // name of origin have to be a string
        assert(is_string($name));

        $options = $this->engineOptions($options);
        // check the base configuration
        try {
            $value = Type::arraylist(
                $value, $name,
                array(
                    'notEmpty' => true,
                    'mandatoryKeys' => array('type', 'prefix'),
                    'allowedKeys' => array('server', 'name'),
                    'keySpec' => array(
                        'type' => array(
                            'Code::class',
                            array(
                                'exists' => 1,
                                'relative' => 'Alinex\Dictionary\Engine'
                            )
                        ),
                        'prefix' => array(
                            'Type::string',
                            array(
                                'maxLength' => 10, // maximal 10 char. prefix is used
                                'match' => '/[A-Za-z_.:]*/'
                                // pipe makes problems in session keys
                                // - used as separator for array contents
                            )
                        )
                    )
                )
            );
        } catch (Exception $ex) {
            throw $ex->createOuter(__METHOD__);
        }
        // check for engine specific options
        try {
            $type = isset($value['type']) ? $value['type'] : '';
            switch($type) {
                case 'Alinex\Dictionary\Engine\Memcache':
                case 'Alinex\Dictionary\Engine\Redis':
                    if (isset($value['server']))
                        $value['server'] = Type::arraylist(
                            $value['server'], $name.'-server',
                            array(
                                'notEmpty' => true,
                                'keySpec' => array(
                                    '' => array(
                                        'Type::string',
                                        array('match' => '#(tcp)://.*#')
                                    )
                                )
                            )
                        );
                    break;
            }
        } catch (Exception $ex) {
            throw $ex->createOuter(__METHOD__);
        }
        // exclude specific engines
        if (isset($options['exclude'])) {
            foreach ($options['exclude'] as $exclude)
                if ($value['type'] == $exclude)
                    throw new Exception(
                        tr(
                            __NAMESPACE__,
                            'The {type} storage is not allowed',
                            array('type' => $value['type'])
                        )
                    );                    
        }
        // check for engine selection
        $engine = Engine::getInstance($value);
        if (!$engine->isAvailable())
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'The {type} storage is not available',
                    array('type' => $value['type'])
                )
            );
        if (isset($options['scope'])) {
            if (!$engine->allow(null, $options['scope']))
                throw new Exception(
                    tr(
                        __NAMESPACE__,
                        'Engine {type} doesn\'t support {option} scope',
                        array(
                            'type' => $value['type'],
                            'option' => self::$_engineScopes[$options['scope']]
                        )
                    )
                );
        }
        if (isset($options['persistence'])) {
            if ($engine->allow(null, $options['persistence']) == 1)
                throw new Exception(
                    tr(
                        __NAMESPACE__,
                        'Engine {type} is not declared as {option} persistence',
                        array(
                            'type' => $value['type'],
                            'option' => self::$_enginePersistence[$options['persistence']]
                        )
                    )
                );
        }
        if (isset($options['performance'])) {
            if (!$engine->allow(null, $options['performance']))
                throw new Exception(
                    tr(
                        __NAMESPACE__,
                        'Engine {type} is not declared as {option} performance',
                        array(
                            'type' => $value['type'],
                            'option' => self::$_enginePerformance[$options['performance']]
                        )
                    )
                );
        }
        // return result
        return $value;
    }

    /**
     * Get a human readable description for validity as boolean.
     *
     * @return string explaining message
     */
    static function engineDescription()
    {
        $options = $this->engineOptions($options);

        $desc = tr(
            __NAMESPACE__,
            'The value has to be a dictionary engine specification structure.'
        );
        $desc .= ' '.Type::arraylistDescription(
            array(
                'notEmpty' => true,
                'mandatoryKeys' => array('type', 'prefix'),
                'allowedKeys' => array('server', 'name'),
                'keySpec' => array(
                    'type' => array(
                        'Code::class',
                        array(
                            'exists' => 1,
                            'relative' => 'Alinex\Dictionary\Engine'
                        )
                    ),
                    'prefix' => array(
                        'Type::string',
                        array(
                            'maxLength' => 10, // maximal 10 char. prefix is used
                            'match' => '/[A-Za-z_.:]*/'
                            // pipe makes problems in session keys
                            // - used as separator for array contents
                        )
                    )
                )
            )
        );
        // check for engine specific options
        $desc .= ' '.tr(__NAMESPACE__, 'For type \'redis\' and \'memcache\' servers are specified as:')
            .Type::arraylistDescription(
                array(
                    'notEmpty' => true,
                    'keySpec' => array(
                        '' => array(
                            'Type::string',
                            array('match' => '#(tcp)://.*#')
                        )
                    )
                )
            );
        // check for engine specific options
        if (isset($options['exclude']))
            $desc .= ' '.trn(
                __NAMESPACE__, 
                'The {list} engine is not allowed here.',
                'The {list} engines are not allowed here.',
                count($options['exclude']),
                array('list' => $options['exclude'])
            );
        // check for engine selection
        $desc .= ' '.tr(__NAMESPACE__, 'The engine have to be avaiable.');
        if (isset($options['scope']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'Only engines of {option} scope are allowed.',
                array('option' => self::$_engineScopes[$options['scope']])
            );
        if (isset($options['persistence']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'Only engines declared as {option} persistence are allowed.',
                array('option' => self::$_enginePersistence[$options['persistence']])
            );
        if (isset($options['performance']))
            $desc .= ' '.tr(
                __NAMESPACE__,
                'Only engines declared as {option} performance are allowed.',
                array('option' => self::$_enginePerformance[$options['performance']])
            );
        
        return $desc;
    }



}
