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

/**
 * Validator for dictionary package.
 *
 * This validator is specific for the dictionary package. It provides enhanced
 * checks over the cory types.
 */
class Dictionary
{
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
     * - \c scope - concret scope: Engine::SCOPE_SESSION, Engine::SCOPE_LOCAL,
     * Engine::SCOPE_GLOBAL
     * - \c persistence - minimum persistence: Engine::PERSISTENCE_SHORT,
     * Engine::PERSISTENCE_MEDIUM, Engine::PERSISTENCE_LONG
     * - \c performance - minimum Performance: Engine::PERFORMANCE_LOW,
     * Engine::PERFORMANCE_MEDIUM, Engine::PERFORMANCE_HIGH
     * - \c size - maximum value size
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
        // check for engine selection
#        if (isset($options['scope'])) {
#            call_user_func(array($value['type'], '', )
#        }
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
        // TRANS: description for the possible values for type boolean
        return tr(
            __NAMESPACE__,
            'The value has to be a boolean. The value will be true for 1, "true", "on", "yes" and it will be considered as false for 0, "false", "off", "no", "". Other values are not allowed.'
        );
    }



}
