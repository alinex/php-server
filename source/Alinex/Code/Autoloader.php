<?php
/**
 * @file
 * ClassLoader implements a PSR-0 class loader
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de
 */

namespace Alinex\Code;

/**
 * ClassLoader implements a PSR-0 class loader
 *
 * This class loader supports lazy loading in which classes are only loaded
 * if they are going to be used to spend less memory.
 *
 * @code
 *     $loader = \Alinex\Code\Autoloader::getInstance();
 *
 *     // register classes with namespaces
 *     $loader->add('Symfony\\Component', __DIR__.'/component');
 *     $loader->add('Symfony',           __DIR__.'/framework');
 *
 *     // activate the autoloader
 *     $loader->register();
 *
 *     // to enable searching the include path (eg. for PEAR packages)
 *     $loader->setUseIncludePath(true);
 * @endcode
 *
 * In this example, if you try to use a class in the Symfony\\Component
 * namespace or one of its children (Symfony\\Component\\Console for instance),
 * the autoloader will first look for the class under the component/
 * directory, and it will then fallback to the framework/ directory if not
 * found before giving up.
 *
 * @note
 * You may additionally register specific classloaders for some libraries
 * but this one can easily be setup to work for nearly every third party
 * library, too.
 *
 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
 */
class Autoloader
{
    /**
     * Singleton instance.
     * @var Autoloader
     */
    private static $_instance = null;

    /**
     * Get the singleton autoloader instance.
     * @return Autoloader singleton instance
     */
    static function getInstance()
    {
        if (!isset(self::$_instance))
            self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * List of prefixes with their search directories
     * @var array
     */
    private $_prefixes = array();

    /**
     * List of general directories to search for
     * @var array
     */
    private $_fallbackDirs = array();

    /**
     * Flag, indicating if the search should also be done in the include path
     * @var bool
     */
    private $_useIncludePath = false;

    /**
     * List of classes with their file mapping
     * @var array
     */
    private $_classMap = array();

    /**
     * Get list of prefixes with their search directories
     *
     * @return array
     */
    public function getPrefixes()
    {
        return $this->_prefixes;
    }

    /**
     * Get list of general directories to search for
     *
     * @return array
     */
    public function getFallbackDirs()
    {
        return $this->_fallbackDirs;
    }

    /**
     * Get list of classes with their file mapping
     *
     * @return array
     */
    public function getClassMap()
    {
        return $this->_classMap;
    }

    /**
     * @param array $classMap Class to filename map
     */
    public function addClassMap(array $classMap)
    {
        if ($this->_classMap) {
            $this->_classMap = array_merge($this->_classMap, $classMap);
        } else {
            $this->_classMap = $classMap;
        }
    }

    /**
     * Registers a set of classes.
     *
     * All classe swith the defined prefix will be searched in the given
     * directories.
     *
     * @note If no prefix is given the paths will be added as general path for
     * all lasses.
     *
     * @param string       $prefix The classes prefix
     * @param array|string $paths  The location(s) of the classes
     */
    public function add($prefix, $paths)
    {
        if (!$prefix) {
            foreach ((array) $paths as $path)
                $this->_fallbackDirs[] = $path;
            return;
        }
        // remove starting backslash
        if ('\\' == $prefix[0])
            $prefix = substr($prefix, 1);
        if (isset($this->_prefixes[$prefix])) {
            // add directories to existing array
            $this->_prefixes[$prefix] = array_merge(
                $this->_prefixes[$prefix],
                (array) $paths
            );
        } else {
            // add directories array
            $this->_prefixes[$prefix] = (array) $paths;
        }
#        error_log('add: '.$prefix.'->'.$paths);
    }

    /**
     * Turns on/off searching the include path for class files.
     *
     * @param bool $useIncludePath
     */
    public function setUseIncludePath($useIncludePath)
    {
        assert(is_bool($useIncludePath));
        $this->_useIncludePath = $useIncludePath ? true : false;
    }

    /**
     * Can be used to check if the autoloader uses the include path to check
     * for classes.
     *
     * @return bool
     */
    public function getUseIncludePath()
    {
        return $this->_useIncludePath;
    }

    /**
     * Registers this instance as an autoloader.
     *
     * @param bool $prepend Whether to prepend the autoloader or not
     */
    public function register($prepend = false)
    {
        spl_autoload_register(array($this, 'loadClass'), true, $prepend);
    }

    /**
     * Unregisters this instance as an autoloader.
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

    /**
     * Loads the given class or interface.
     *
     * The defined repositories will be searched.
     *
     * @param  string    $class The name of the class to load
     * @return bool|null True, if loaded
     * @throws \Exception for impossible load classes
     */
    public function loadClass($class)
    {
        // check if class is already loaded
        if (class_exists($class))
            return true;
        // find class file
        $file = $this->findFile($class);
#error_log($class.'->'.$file);
        $backtrace = debug_backtrace();
        if ($backtrace[2]['function'] == 'class_exists'
            && (!isset($backtrace[2]['args'][1]) || !$backtrace[2]['args'][1]))
            return isset($file) ? true : false;
        if ($file) {
            include $file;
            return true;
        }
        if ($backtrace[2]['function'] != 'class_exists')
            throw new \Exception("Could not load class '$class'");
    }

    /**
     * Finds the path to the file where the class is defined.
     *
     * The filename is calculated out of the class name like defined in PSR-0
     * or PEAR.
     *
     * The file eill be searched in:
     * # in the class map
     * # for this prefix defined directories
     * # all general directories
     * # in the php include path
     *
     * @param string $class The name of the class to load
     * @return string|null The path, if found
     * @see
     * https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
     */
    public function findFile($class)
    {
        // remove first namespace backslash
        if ('\\' == $class[0])
            $class = substr($class, 1);

        // check if class mapping is defined
        if (isset($this->_classMap[$class]))
            return $this->_classMap[$class];

        // calculate relativ file path
        if (false !== $pos = strrpos($class, '\\')) {
            // namespaced class name
            $classPath =
                str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 0, $pos))
                . DIRECTORY_SEPARATOR;
            $className = substr($class, $pos + 1);
        } else {
            // PEAR-like class name
            $classPath = null;
            $className = $class;
        }
        // replace _ with directory separator
        $classPath .= str_replace('_', DIRECTORY_SEPARATOR, $className)
            . '.php';

        // search file in dirs
        foreach ($this->_prefixes as $prefix => $dirs) {
            if (0 === strpos($class, $prefix)) {
                foreach ($dirs as $dir) {
#error_log("check a: ".$dir . DIRECTORY_SEPARATOR . $classPath);
                    if (file_exists($dir . DIRECTORY_SEPARATOR . $classPath))
                        return $dir . DIRECTORY_SEPARATOR . $classPath;
                }
            }
        }

        // search in general directories
        foreach ($this->_fallbackDirs as $dir) {
#error_log("check b: ".$dir . DIRECTORY_SEPARATOR . $classPath);
            if (file_exists($dir . DIRECTORY_SEPARATOR . $classPath))
                return $dir . DIRECTORY_SEPARATOR . $classPath;
        }

        // search for file in standard include path
        if ($this->_useIncludePath) {
            $file = stream_resolve_include_path($classPath);
            if ($file)
                return $file;
        }

        // notify in classmap if not found
        return $this->_classMap[$class] = false;
    }

    /**
     * Add backport functions.
     *
     * This will include backported classes from newer php version to make
     * them available on older installations.
     * The classes have to be available in directories named after their php
     * version number.
     *
     * @param string $path to the backport directory
     * @return bool true if backports added
     */
    public function addBackports($path)
    {
        $paths = array();
        foreach (
            new \FilesystemIterator(
                $path, \FilesystemIterator::SKIP_DOTS
            ) as $dir
        ) {
            if (!$dir->isDir())
                continue;
            // add if version lower than directory name
            if (version_compare(PHP_VERSION, $dir->getFilename()) < 0)
                $paths[] = $dir->getPathname();
        }
        // add as fallback
        if (count($paths))
            $this->add('', $paths);
    }
}
