<?php
/**
 * @file
 * Storage keeping values in the local filesystem.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Storage\Engine;

use Alinex\Storage\Engine;
use Alinex\Util\String;

/**
 * Storage keeping values in the local filesystem.
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
    protected function setDirectory($dir)
    {
        assert(\Alinex\Validator::is(
            $dir, 'storage-directory', 'IO::path',
            array('writable' => true)
        ));
        if (substr($dir, -1) != '/')
            $dir .= '/';
        $this->_dir = $dir;
        // create directory if not existing
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
        for ($i=4; $i<strlen($key); $i+=5) 
            $key = substr_replace($key, '/', $i, null);
        return $path.'$';
    }

    /**
     * Get key from pathname.
     * 
     * @param string $path relativ path
     * @return string key name
     */
    private function pathToKey($path)
    {
        return str_replace('/', '', substr($path, 0 ,-1));
    }
    
    /**
     * Method to set a storage variable
     *
     * @param string $key   name of the entry
     * @param string $value Value of storage key null to remove entry
     *
     * @return mixed value which was set
     * @throws \Alinex\Validator\Exception
     */
    function set($key, $value = null)
    {
        if (!isset($value)) {
            $this->remove($key);
            return null;
        }
        if (!isset($this->_dir))
            throw new Exception(
                tr("Engine not configured, need directory to store")
            );
        // get path and dir
        $path = $this->_dir.$this->keyToPath($this->checkKey($key));
        $dir = dirname($path);
        // store
        if (!file_exists($dir))
            mkdir($dir, 1);
        file_put_contents($path, json_encode($value));
        return $value;
    }

    /**
     * Unset a storage variable
     *
     * @param string $key   name of the entry
     * @return bool    TRUE on success otherwise FALSE
     */
    public function remove($key)
    {
        $path = $this->_dir.$this->keyToPath($this->checkKey($key));
        if (!file_exists($path))
            return false;
        return unlink($path);
    }

    /**
     * Method to get a variable
     *
     * @param  string  $key   array key
     * @return mixed value on success otherwise NULL
     */
    public function get($key)
    {
        $path = $this->_dir.$this->keyToPath($this->checkKey($key));
        $value = json_decode(file_get_contents($path));
        if (json_last_error())
            throw new Exception(json_last_error());
        return $value;
    }

    /**
     * Check if storage variable is defined
     *
     * @param string $key   name of the entry
     * @return bool    TRUE on success otherwise FALSE
     */
    public function has($key)
    {
        $path = $this->_dir.$this->keyToPath($this->checkKey($key));
        return file_exists($path);
    }

    
    
    
    
    /**
     * Get the list of keys
     *
     * @note This is done by searching through the APC cache and collecting all
     * registry keys because of the prefix.
     *
     * @return array   list of key names
     */
    public function keys()
    {
        return $this->fileKeys($this->_dir);
    }
    
    /**
     * Get the list of keys by directory scanning
     * 
     * @param string $dir dirctory to scan
     * @param string $prefix prefix path to be prepended to files
     * @return array list of keys for this directory and below.
     */
    private function fileKeys($dir, $prefix = '') {
        $dir = rtrim($dir, '\\/');
        $result = array();
        foreach (scandir($dir) as $f) {
            if ($f !== '.' and $f !== '..') 
                continue;
            if (is_dir("$dir/$f"))
                $result = array_merge(
                    $result, $this->fileKeys($dir.'/'.$f, $prefix.$f.'/')
                );
            else
                $result[] = $this->pathToKey(
                    substr($prefix.$f, strlen($this->_dir))
                );
        }

      return $result;
    }
    
    /**
     * Persistence level of the engine.
     * @var int
     */
    protected $_persistence = Engine::PERSISTENCE_MEDIUM;

    /**
     * Performance level of the engine.
     * @var int
     */
    protected $_performance = Engine::PERFORMANCE_HIGH;

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
    
}