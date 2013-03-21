<?php
/**
 * @file
 * Dictionary keeping values in user sessiom.
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

/**
 * Dictionary keeping values in user session.
 *
 * This class will store the key-value pairs in the user session storage. This
 * scope will be kept locally, globally or persistent depending on the session
 * configuration.
 *
 * All values stored through this registry will be prefixed and stored in the
 * APC user-cache. To use more than one instance of this registry you may use
 * different prefixes. Also use the prefix wisely to prevent collision with
 * other librarys and php routines on the same machine.
 * 
 * @attention
 * This engine should not be used if the Registry\Session is used with an engine
 * because it may interfere.
 */
class Session extends Engine
{
    /**
     * Constructor
     *
     * The session handling will be started if not allready done and the
     * storage array will be added.
     *
     * @param string $context special name of this instance
     */
    protected function __construct($context)
    {
        parent::__construct($context);
        // start session if not done
        @session_start();
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
        $this->checkKey($key);
        return $_SESSION[$this->_context.$key] = $value;
    }

    /**
     * Unset a storage variable
     *
     * @param string $key   name of the entry
     * @return bool    TRUE on success otherwise FALSE
     */
    public function remove($key)
    {
        if (isset($_SESSION[$this->_context.$key])) {
            unset($_SESSION[$this->_context.$key]);
            return TRUE;
        }
        return FALSE;
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
        return isset($_SESSION[$this->_context.$key])
            ? $_SESSION[$this->_context.$key]
            : NULL;
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
        return isset($_SESSION[$this->_context.$key]);
    }

    /**
     * Get the list of keys
     *
     * @return array   list of key names
     */
    public function keys()
    {
        if (!isset($_SESSION) || !count($_SESSION))
            return array();
        $context = $this->_context;
        return array_filter(
            array_keys($_SESSION),
            function($item) use ($context) {
                return String::startsWith($item, $context);
            }
        );
    }

    /**
     * Scope of the engine.
     * @var int
     */
    protected $_scope = Engine::SCOPE_SESSION;

    /**
     * Performance level of the engine.
     * @var int
     */
    protected $_performance = Engine::PERFORMANCE_MEDIUM;

    /**
     * Size quotes to select best Cache engine.
     * @var array
     */
    protected $_limitSize = array(
        100000 => 0.2,
        1000 => 0.5
    );
    
}
