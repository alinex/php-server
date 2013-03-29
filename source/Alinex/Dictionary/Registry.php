<?php
/**
 * @file
 * Registry to pass global information and objects between classes.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Dictionary;

use Alinex\Logger;
use Alinex\Validator;

/**
 * Use of the Registry in default mode without any specification. This is the
 * most used case. Access to the registry is done using the get() and set()
 * methods.
 * @example Registry-default.php
 */
/**
 * This shows how to access the registry like a normal array.
 * @example Registry-arrayAccess.php
 */
/**
 * Show how to easy load registry from file if not available.
 * @example Registry-autoload.php
 */
/**
 * Registry to pass global information and objects between classes.
 *
 * The registry is aimed to hold configuration data and specific settings in
 * a performant quick access engine. Through the additional validators and
 * the import/export possibilities is best to be used with file based
 * configuration management.
 *
 * The ArrayRegistry is used as one of the simplest, but any other can be used
 * in the same way.
 *
 * Additionally checks may be defined using the \Alinex\Validator class methods
 * which will be called on set(). Add this checks using setValidator().
 *
 * <b>Array Access</b>
 *
 * The registry is also usable like normal Arrays with:
 * @code
 * count($registry);
 * isset($registry[$offset]);
 * $value = $registry[$offset];
 * $registry[$offset] = $value;
 * unset($registry[$offset]);
 * @endcode
 *
 * <b>Group</b>
 *
 * A group is a subpart of the registry with the same group name as key start
 * in storage. This will be prepended on set and removed on get to use with
 * shorter array keys.
 *
 * <b>Validation</b>
 *
 * The registry can also store validation methods, which will be called any
 * time a value is set in a second storage instance. The validation methods have
 * to return the value and throw an Exception on problems like the
 * \Alinex\Validator classes.
 * Use of validators makes most sense for registry entries which will be
 * directly loaded from an external resoutce.
 *
 * <b>Registries</b>
 *
 * The following registries are possible:
 * - ArrayStructure
 * - Session
 * - Apc
 * - XCache
 * - Redis
 * - Memcache
 *
 * The best way is to use a registry  through the Factory class. This have
 * a fallback mechanism to ensure you use the best possible solution.
 *
 * <b>Transfer to/from storage</b>
 *
 * To export and import values this engines translates the strustures into
 * different formats use the Transfer subclasses.
 *
 * **Automatic Configuration**
 * 
 * This is possible through the registry files itself using the data:
 *   registry.data[type] = 'Redis'
 *   registry.data[prefix] = 'ax:reg-d:',
 *   registry.data[server][0] = 'tcp://localhost:3456'
 *   registry.data[type] = 'Redis'
 *   registry.data[prefix] = 'ax:reg-v:',
 *   registry.data[server][0] = 'tcp://localhost:3456'
 * 
 * @see Cache for more open multiple engine storage
 * @see Session to easy integrate any engine as session storage
 * @see Dictionary for overview of use
 */
class Registry implements \Countable, \ArrayAccess
{
    /**
     * Used as start of default context.
     */
    const PREFIX = 'ax:reg-';

    /**
     * Dictionary Engine to use for data storage.
     * @registry
     */
    const REGISTRY_DATA_ENGINE = 'registry.data';

    /**
     * Dictionary Engine to use for validator storage.
     * @registry
     */
    const REGISTRY_VALIDATOR_ENGINE = 'registry.validator';

    /**
     * Singleton instance of registry class
	 * @var Registry
	 */
    protected static $_instance = null;

    /**
     * Get an instance of registry class
     *
     * For the default instance the best choice for local registry will be
     * detected based on the environment. The decision is made by one of:
     * - ArrayList (if in CLI mode)
     * - Apc (if possible)
     * - Xcache (if possible)
     * - ArrayList
     *
     * Other engines are not considered because they need configuration, or
     * are not as globally as these.
     *
     * The context names will be 'ax:reg-d:' for data entries and 'ax:reg-v:'
     * for validator entries.
     *
     * If an import uri is given, the the registry is loaded with the stored
     * information on the first call. The registry engine will be set like
     * defined in the given file or by autodetecting.
     *
     * @dotfile Dictionary/Registry/getInstance
     *
     * @note Additionally you may create your own registry directly without
     * engine auto detection and the full choice of selection.
     *
     * @param string $data uri to import data from see more at
     * ImportExport\Autodetect
     * @param string $validator uri to import validation rules from see more at
     * ImportExport\Autodetect
     * @return Registry Instance of registry class or null if not set
     *
     * @codeCoverageIgnore
     */
    public final static function getInstance($data = null, $validator = null)
    {
        // get instance if already set
        if (isset(self::$_instance))
            return self::$_instance;
        $instance = null;
        if (isset($data)) {
            Logger::getInstance()->info(
                'Loading registry data from '.$data
            );
            // load into temporary Registry
            $temp = Engine\ArrayList::getInstance('reg-tmp:');
            $temp->clear();
            ImportExport\Autodetect::import($data, $temp);
            // check for registry settings
            if ($temp->has(self::REGISTRY_DATA_ENGINE)) {
                # create engine
                $dataEngine = Engine::getInstance(
                    Validator::check(
                        $temp->get(self::REGISTRY_DATA_ENGINE),
                        'registry-data',
                        'Dictionary::engine'
                    )
                );
                // analyze validator
                $validatorEngine = null;
                if (isset($validator)) {
                    $temp->clear();
                    Logger::getInstance()->info(
                        'Loading registry validators from '.$validator
                    );
                    ImportExport\Autodetect::import($validator, $temp);
                    if ($temp->has(self::REGISTRY_VALIDATOR_ENGINE)) {
                        $validatorEngine = Engine::getInstance(
                            Validator::check(
                                $temp->get(self::REGISTRY_VALIDATOR_ENGINE),
                                'registry-validator',
                                'Dictionary::engine'
                            )
                        );
                    }
                }
                $instance = new Registry($dataEngine, $validatorEngine);
                // reset all entries through registry to check them
                if ($instance->validatorCheck())
                    foreach ($this->keys() as $key)
                        $this->set($key, $this->get($key));
            }
        }
        if (!isset($instance))
            $instance = self::autodetectInstance();
        // fill up registry if empty
        if (!$instance->count()) {
            if (isset($data)) {
                $importer = ImportExport\Autodetect::findInstance($data);
                $instance->import($importer);
            }
            if (isset($validator)) {
                $importer = ImportExport\Autodetect::findInstance($validator);
                $instance->validatorImport($importer);
            }
        }
        // set instance and return
        self::$_instance = $instance;
        return self::$_instance;
    }

    /**
     * Automatic detection of best registry instance.
     *
     * Only engines without special configuration nedds or preconfigured will
     * be used.
     *
     * @return \Alinex\Dictionary\Registry new instance
     */
    private static function autodetectInstance()
    {
        if (php_sapi_name() == 'cli')
            return new Registry(
                Engine\ArrayList::getInstance(self::PREFIX.'d:'),
                Engine\ArrayList::getInstance(self::PREFIX.'v:')
            );
        else if (Engine\Apc::isAvailable())
            return new Registry(
                Engine\Apc::getInstance(self::PREFIX.'d:'),
                Engine\Apc::getInstance(self::PREFIX.'v:')
            );
        else if (Engine\XCache::isAvailable())
            return new Registry(
                Engine\Apc::getInstance(self::PREFIX.'d:'),
                Engine\Apc::getInstance(self::PREFIX.'v:')
            );
        else
            return new Registry(
                Engine\ArrayList::getInstance(self::PREFIX.'d:'),
                Engine\ArrayList::getInstance(self::PREFIX.'v:')
            );
    }

    /**
     * Set the dictionary engines for the default engine.
     *
     * This have to be done before the first call to getInstance().
     *
     * @param Engine $dataEngine place to store data entries
     * @param Engine $validatorEngine place to store validation rules
     * @return bool true on success
     */
    public static function setDefaultEngine(
        Engine $dataEngine, Engine $validatorEngine = null
    )
    {
        if (! isset(self::$_instance)) {
            self::$_instance = new self($dataEngine, $validatorEngine);
            return true;
        }
        return false;
    }

    /**
     * Dictionary for registry data.
     * @var Engine
     */
    protected $_data = null;

    /**
     * Dictionary dor field validators.
     * @var Engine
     */
    protected $_validator = null;

    /**
     * Constructor
     *
     * This may be overwritten to implement some initialization of the storage
     * engine.
     *
     * @param Engine $dataEngine place to store data entries
     * @param Engine $validatorEngine place to store validation rules
     */
    function __construct(
        Engine $dataEngine, Engine $validatorEngine = null
    )
    {
        $this->_data = $dataEngine;
        $this->_validator = $validatorEngine;
        // add validators
        if (isset($this->_validator)) {
            if (!$this->validatorHas(self::REGISTRY_DATA_ENGINE))
                $this->validatorSet(
                    self::REGISTRY_DATA_ENGINE, 'Dictionary::engine'
                );
            if (!$this->validatorHas(self::REGISTRY_VALIDATOR_ENGINE))
                $this->validatorSet(
                    self::REGISTRY_VALIDATOR_ENGINE, 'Dictionary::engine'
                );
            // validators of general static classes
            \Alinex\Util\Http::addRegistryValidators($this);
        }
    }

    /**
     * Method to set a registry variable
     *
     * @param string $key   Registry array key
     * @param string $value Value of registry key
     *
     * @return bool    TRUE on success otherwise FALSE
     * @throws Validator\Exception
     */
    public final function set($key, $value = null)
    {
        // check through validator
        if (isset($value) && $this->_validator->has($key)) {
            $validator = $this->_validator->get($key);
            $value = Validator::check(
                $value, $key, $validator[0], $validator[1]
            );
        }
        // set value
        return $this->_data->set($key, $value);
    }

    /**
     * Method to get a registry variable
     *
     * @param string $key Registry array key
     *
     * @return bool    TRUE on success otherwise NULL
     */
    public final function get($key)
    {
        return $this->_data->get($key);
    }

    /**
     * Check if registry variable is defined
     *
     * @param string $key Registry array key
     *
     * @return bool    TRUE on success otherwise FALSE
     */
    public final function has($key)
    {
        return $this->_data->has($key);
    }

    /**
     * Unset a registry variable
     *
     * @param  string $key Registry array key
     *
     * @return bool    TRUE
     */
    public final function remove($key)
    {
        return $this->_data->remove($key);
    }

    /**
     * Get the list of keys from registry
     *
     * @return boolean   FALSE
     */
    public function keys()
    {
        return $this->_data->keys();
    }

    /**
     * Reset registry
     *
     * This will be done in a common way by removing every single element.
     * For storage engines, which allow easier purging this may be overwritten.
     *
     * @return bool    TRUE on success otherwise FALSE
     */
    public function clear()
    {
        return $this->_data->clear();
    }

    /**
     * Get all values which start with the given string.
     *
     * The key name will be shortened by cropping the group name from the start.
     *
     * @param string $group start phrase for selected values
     * @return array list of values
     */
    public function groupGet($group)
    {
        return $this->_data->groupGet($group);
    }

    /**
     * Set a list of values as group.
     *
     * The key names will be pretended with the group name given.
     *
     * @param string $group start name of the group
     * @param array $values values found
     */
    public function groupSet($group, array $values)
    {
        return $this->_data->groupSet($group, $values);
    }

    /**
     * Clear all entries of this group.
     * @param string $group start name of the group
     */
    public function groupClear($group)
    {
        return $this->_data->groupClear($group);
    }

    /**
     * Get the number of elements in the registry.
     *
     * This method will called also with:
     * @code
     * count($registry).
     * @endcode
     *
     * @return integer number of values in the registry
     */
    public function count()
    {
        return $this->_data->count();
    }

    /**
     * Check if key exists for ArrayAccess
     *
     * @code
     * isset($registry[$offset])
     * @endcode
     *
     * @param string $offset name of registry entry
     *
     * @return boolean true if key exists
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Get key for ArrayAccess
     *
     * @code
     * isset($registry[$offset])
     * @endcode
     *
     * @param string $offset name of registry entry
     *
     * @return mixed registry entry
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Set key through ArrayAccess
     *
     * @code
     * isset($registry[$offset])
     * @endcode
     *
     * @param string $offset name of registry entry
     * @param mixed $value value to store
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Unset key using ArrayAccess
     *
     * @code
     * unset($registry[$offset])
     * @endcode
     *
     * @param string $offset name of registry entry
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * Check if a validator storage is added.
     *
     * If not it have to be done in the constructor already.
     *
     * @return bool true on success
     * @throws \UnderflowException if no validator storage is set
     */
    public function validatorCheck()
    {
        if (!isset($this->_validator))
            throw new \UnderflowException(
                tr(
                    __NAMESPACE__,
                    'No validatore storage set in constructor'
                )
            );
        return true;
    }

    /**
     * Set check routine for registry key.
     *
     * Here the short version with classname and method may be used for
     * Validator classes.
     *
     * @param string $key       name of registry entry, which should be checked
     * @param string|array  $callback  class and method name of the Validator
     * to use
     * @param array  $options   additional validator options
     * @return mixed value set or null if removed
     */
    public function validatorSet($key, $callback = null, array $options = null)
    {
        $this->validatorCheck();
        // unset method if no callback given
        if (!isset($callback)) {
            $this->_validator->remove($key);
            return null;
        }
        // set validator method
        $callback = Validator\Code::callable(
            $callback, 'callback', array('relative' => 'Alinex\Validator')
        );
        $result = $this->_validator->set($key, array($callback, $options));
        // re-set the value if already set
        if ($result && $this->_data->has($key))
            $this->_data->set($key, $this->_data->get($key));
        return $result;
    }

    /**
     * Unset a validator
     *
     * @param string $key   name of the entry
     * @return bool    TRUE on success otherwise FALSE
     */
    public function validatorRemove($key)
    {
        return $this->_validator->remove($key);
    }

    /**
     * Set check routine for registry key.
     *
     * @param string $key       name of registry entry, which should be checked
     * @return array  validator method and options
     */
    function validatorGet($key)
    {
        $this->validatorCheck();
        return $this->_validator->get($key);
    }

    /**
     * Description of validator.
     *
     * @param string $key human readable description of validator
     * @param string $name name of data field
     *
     * @return string human readable format description
     */
    function validatorDescription($key, $name = null)
    {
        $this->validatorCheck();
        if (!$this->validatorHas($key))
            return false;
        $validator = $this->validatorGet($key);
        return Validator::describe(
            $name, $validator[0], $validator[1]
        );
    }

    /**
     * Set check routine for registry key.
     *
     * @param string $key       name of registry entry, which should be checked
     * @return bool    TRUE on success otherwise FALSE
     */
    function validatorHas($key)
    {
        $this->validatorCheck();
        return $this->_validator->has($key);
    }

    /**
     * Remove all validators.
     *
     * @return bool    TRUE
     */
    function validatorClear()
    {
        $this->validatorCheck();
        return $this->_validator->clear();
    }

    /**
     * Get the list of keys from registry validators
     *
     * @return boolean   FALSE
     */
    public function validatorKeys()
    {
        $this->validatorCheck();
        return $this->_validator->keys();
    }

    /**
     * Get all validators which start with the given string.
     *
     * The key name will be shortened by cropping the group name from the start.
     *
     * @param string $group start phrase for selected values
     * @return array list of values
     */
    public function validatorGroupGet($group)
    {
        $this->validatorCheck();
        return $this->_validator->groupGet($group);
    }

    /**
     * Set a list of validators as group.
     *
     * The key names will be pretended with the group name given.
     *
     * @param string $group start name of the group
     * @param array $values validators for this group
     */
    public function validatorGroupSet($group, array $values)
    {
        $this->validatorCheck();
        assert(is_string($group));

        foreach ($values as $key => $value)
            $this->validatorSet($group.$key, $value[0], $value[1]);

        return true;
    }

    /**
     * Export registry entries
     *
     * @param ImportExport $exporter export/import format instance
     * @return bool true on success
     */
    public function export(ImportExport $exporter)
    {
        $exporter->setDictionary($this->_data);
        // TRANS: header in output file
        $exporter->addHeader(tr(__NAMESPACE__, 'Registry data'));
        $exporter->setCommentCallback(array($this, 'validatorDescription'));
        return $exporter->export();
    }

    /**
     * Import registry entries
     *
     * @param ImportExport $importer export/import format instance
     * @return bool true on success
     */
    public function import(ImportExport $importer)
    {
        $importer->setDictionary($this->_data);
        return $importer->import();
    }

    /**
     * Export registry entries
     *
     * @param ImportExport $exporter export/import format instance
     * @return bool true on success
     */
    public function validatorExport(ImportExport $exporter)
    {
        $exporter->setDictionary($this->_validator);
        // TRANS: header in output file
        $exporter->addHeader(tr(__NAMESPACE__, 'Registry validators'));
        return $exporter->export();
    }

    /**
     * Import registry entries
     *
     * @param ImportExport $importer export/import format instance
     * @return bool true on success
     */
    public function validatorImport(ImportExport $importer)
    {
        $importer->setDictionary($this->_validator);
        return $importer->import();
    }
}
