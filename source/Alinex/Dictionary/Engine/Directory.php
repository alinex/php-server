<?php
/**
 * @file
 * Storage keeping values in the filesystem.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Dictionary\Engine;

use Alinex\Dictionary\Engine;
use Alinex\Util\String;
use Exception;

/**
 * Storage keeping values in the filesystem.
 *
 * **Specification**
 * - Scope: local or global
 * - Performance: low
 * - Persistence: high
 * - Size: very large
 * - Objects: will be serialized
 * - Manipulation: emulated
 * - Garbage collection: NRU, manual call
 * - Requirements: None
 *
 * Therefor a self managed structure under the given directory will be created.
 * Each value will be stored in its own file making it perfect for big values.
 * To gain the best performance out of the filesystem the key will be structured
 * as a directory path mmaking less files per directory.
 *
 * Best used for large values, which are needed not so often.
 *
 * The values itself will be stored using JSON Format and can also be read
 * directly from the file. A typicat entry with key 'database.default.hostname'
 * will be stored as:
 *   /data/base/.def/ault/.hos/tnam/e$
 *
 * Removing all slashes and the trailing $ you can read the keyname out of the
 * filesystem.
 *
 * The whole size will only be limited by the harddisk capacity.
 *
 * **Garbage collection**
 *
 * The default time-to-live setting is used for all entries. The specification
 * within the set() call is ignored. An entry will time out if it is not
 * accessed in the last default time-to-live range. This workes in an NRU
 * (not recently used) algorithm.
 *
 * The garbage collector has to be called manual and may run some time.
 *
 * @see Engine overview chart
 * @see Dictionary for usage examples
 */
class Directory extends Engine
{
    /**
     * Directory to store values as files.
     * @var string
     */
    protected $_dir = null;

    /**
     * Set the storage directory.
     *
     * @param string $dir path to store files to
     * @return string value set
     */
    public function setDirectory($dir)
    {
        assert(
            \Alinex\Validator::is(
                $dir, 'storage-directory', 'IO::path',
                array('writable' => true, 'allowBackreferences' => true)
            )
        );
        if (substr($dir, -1) != '/')
            $dir .= '/';
        $this->_dir = $dir;
        // create directory if not existing
        if (!file_exists($dir))
            mkdir($dir);
    }

    /**
     * Translate the key into path.
     *
     * @param string $key name of the value
     * @return string relative path for the entry
     */
    private function keyToPath($key)
    {
        $key = $this->_context.$key;
        for ($i=4; $i<strlen($key); $i+=5)
            $key = substr_replace($key, '/', $i, null);
        return $key.'$';
    }

    /**
     * Get key from pathname.
     *
     * @param string $path relativ path
     * @return string key name
     */
    private function pathToKey($path)
    {
        $key = str_replace('/', '', substr($path, 0, -1));
        return substr($key, strlen($this->_context));
    }

    /**
     * @name Normal access methods
     * @{
     */

    /**
     * @copydoc Engine::set()
     */
    function set($key, $value = null, $ttl = null)
    {
        if (!isset($value)) {
            $this->remove($key);
            return null;
        }
        if (!isset($this->_dir))
            throw new Exception(
                tr(
                    __NAMESPACE__,
                    'Engine not configured, need directory to store'
                )
            );
        // get path and dir
        $path = $this->_dir.$this->keyToPath($this->checkKey($key));
        $dir = dirname($path);
        // store
        if (!file_exists($dir))
            mkdir($dir, 0777, true);
        file_put_contents($path, json_encode($value));
        return $value;
    }

    /**
     * @copydoc Engine::remove()
     */
    public function remove($key)
    {
        if (!isset($this->_dir))
            throw new Exception(
                tr(
                    __NAMESPACE__, 'Directory for engine not set'
                )
            );
        $path = $this->_dir.$this->keyToPath($this->checkKey($key));
        if (!file_exists($path))
            return false;
        $result = unlink($path);
        do {
            $path = dirname($path);
            if (strlen($path) <= strlen($this->_dir))
                break;
        } while (@rmdir($path));
        return $result;
    }

    /**
     * @copydoc Engine::get()
     */
    public function get($key)
    {
        if (!$this->has($key))
            return null;
        $path = $this->_dir.$this->keyToPath($this->checkKey($key));
        $value = json_decode(file_get_contents($path), true);
        if (json_last_error())
            throw new Exception(json_last_error());
        return $value;
    }

    /**
     * @copydoc Engine::has()
     */
    public function has($key)
    {
        if (!isset($this->_dir))
            throw new Exception(
                tr(__NAMESPACE__, 'Directory for engine not set')
            );
        $path = $this->_dir.$this->keyToPath($this->checkKey($key));
        return file_exists($path);
    }

    /**
     * @copydoc Engine::keys()
     */
    public function keys()
    {
        if (!isset($this->_dir))
            throw new Exception(
                tr(__NAMESPACE__, 'Directory for engine not set')
            );
        return $this->fileKeys($this->_dir);
    }

    /**
     * @}
     */

    /**
     * Get the list of keys by directory scanning
     *
     * @param string $dir dirctory to scan
     * @return array list of keys for this directory and below.
     */
    private function fileKeys($dir)
    {
        $dir = rtrim($dir, '\\/');
        $result = array();
        foreach (scandir($dir) as $f) {
            if ($f == '.' || $f == '..')
                continue;
            if (is_dir("$dir/$f"))
                $result = array_merge(
                    $result, $this->fileKeys($dir.'/'.$f)
                );
            else if (substr($f, -1) == '$')
                $result[] = $this->pathToKey(
                    substr($dir.$f, strlen($this->_dir))
                );
        }

        return $result;
    }

    /**
     * @name Group access
     * @{
     */

    /**
     * @copydoc Engine::groupGet()
     */
    public function groupGet($group)
    {
        assert(is_string($group));

        if (!isset($this->_dir))
            throw new Exception(
                tr(__NAMESPACE__, 'Directory for engine not set')
            );
        $dir = dirname($this->keyToPath($group));
        if ($dir)
            return parent::groupGet($group);
        // search for keys in group dir
        $result = array();
        foreach ($this->fileKeys($dir) as $key) {
            $key = $this->pathToKey($dir).$key;
            if (strlen($group) == 0)
                $result[$key] = $this->get($key);
            else if (String::startsWith($key, $group))
                $result[substr($key, strlen($group))] = $this->get($key);
        }
        return $result;
    }

    /**
     * @}
     */

    /**
     * Reset storage for this context
     *
     * This will be done in a common way by removing every single element.
     * For storage engines, which allow easier purging this may be overwritten.
     *
     * @return bool    TRUE on success otherwise FALSE
     */
    public function clear()
    {
        if (!isset($this->_dir) || !file_exists($this->_dir))
            return false;
        // recursive remove
        $removed = 0;
        foreach (
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $this->_dir, \FilesystemIterator::SKIP_DOTS
                ), \RecursiveIteratorIterator::CHILD_FIRST
            ) as $path) {
            $path->isFile() ? unlink($path->getPathname()) : rmdir($path->getPathname());
            $removed++;
        }
        return (bool) $removed;
    }

    /**
     * Persistence level of the engine.
     * @var int
     */
    protected $_persistence = Engine::PERSISTENCE_LONG;

    /**
     * Performance level of the engine.
     * @var int
     */
    protected $_performance = Engine::PERFORMANCE_LOW;

    /**
     * Size quotes to select best Cache engine.
     * @var array
     */
    protected $_limitSize = array(
        1000000 => 0,
        100000 => 0.2,
        10000 => 0.5,
        1000 => 0.8
    );

    /**
     * Garbage collector run.
     *
     * @return bool true on success
     */
    public function gc()
    {
        if (!isset($this->_dir) || !isset($this->_ttl))
            return false;
        // scan directory to find files
        $timecheck = time() - $this->_ttl;
        foreach (
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $this->_dir, \FilesystemIterator::SKIP_DOTS
                ), \RecursiveIteratorIterator::CHILD_FIRST
            ) as $path) {
            if ($path->isFile()) {
                // remove old files
                if ($path->getATime() < $timecheck)
                    unlink($path->getPathname());
            } else {
                // remove empty directory
                $d=glob($path->getPathname().'/*');
                if (empty($d))
                    rmdir($path->getPathname());
            }
        }
        return true;
    }

}