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
        $this->_dir = $dir;
        // create directory if not existing
        mkdir($dir);
    }
    
    private keyToPath($key)
    {
        
        
        
        return $path;
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
        $this->checkKey($key);
        return apc_store($this->_context.$key, $value, $this->_ttl);
    }

    /**
     * Unset a storage variable
     *
     * @param string $key   name of the entry
     * @return bool    TRUE on success otherwise FALSE
     */
    public function remove($key)
    {
        return apc_delete($this->_context.$key);
    }

    /**
     * Method to get a variable
     *
     * @param  string  $key   array key
     * @return mixed value on success otherwise NULL
     */
    public function get($key)
    {
        $this->checkKey($key);
        return apc_exists($this->_context.$key) ?
                apc_fetch($this->_context.$key) : NULL;
    }

    /**
     * Check if storage variable is defined
     *
     * @param string $key   name of the entry
     * @return bool    TRUE on success otherwise FALSE
     */
    public function has($key)
    {
        $this->checkKey($key);
        return apc_exists($this->_context.$key);
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
        $keys = array();
        $info = apc_cache_info('user');
        foreach ($info['cache_list'] as $entry)
            if (String::startsWith($entry['info'], $this->_context))
                $keys[] = substr($entry['info'], strlen($this->_context));
        return $keys;
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