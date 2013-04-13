<?php
/**
 * @file
 * Storage keeping values in user sessiom.
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
 * Storage keeping values in user session.
 *
 * **Specification**
 * - Scope: session
 * - Performance: high
 * - Persistence: medium
 * - Size: small
 * - Objects: will be serialized after request
 * - Manipulation: natively supported
 * - Garbage collection: None
 * - Requirements: None
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
 *
 * **Garbage Collection**
 *
 * **No garbage collection available** for this engine. The settings of $ttl
 * will be ignored.
 *
 * @see Engine overview chart
 * @see Dictionary for usage examples
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
        $this->checkKey($key);
        return $_SESSION[$this->_context.$key] = $value;
    }

    /**
     * @copydoc Engine::remove()
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
     * @copydoc Engine::get()
     */
    public function get($key)
    {
        $this->checkKey($key);
        return isset($_SESSION[$this->_context.$key])
            ? $_SESSION[$this->_context.$key]
            : NULL;
    }

    /**
     * @copydoc Engine::has()
     */
    public function has($key)
    {
        $this->checkKey($key);
        return isset($_SESSION[$this->_context.$key]);
    }

    /**
     * @copydoc Engine::keys()
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
     * @}
     */
    
    /**
     * @name Value modification
     * @{
     */
    
    /**
     * @copydoc Engine::inc()
     */
    public function inc($key, $num = 1)
    {
        assert(is_int($num));

        $this->checkKey($key);
        if (!isset($_SESSION[$this->_context.$key]))
            $_SESSION[$this->_context.$key] = $num;
        else if (!is_integer($_SESSION[$this->_context.$key]))
            throw new \Exception(
                tr(
                    __NAMESPACE__,
                    'Incrementing or decrementing a dictionary value is only possible using integer values,'
                )
            );
        else
            $_SESSION[$this->_context.$key] += $num;
        return $_SESSION[$this->_context.$key];
    }

    /**
     * @copydoc Engine::append()
     */
    public function append($key, $text)
    {
        assert(is_string($text));

        $this->checkKey($key);
        if (!isset($_SESSION[$this->_context.$key]))
            $_SESSION[$this->_context.$key] = $text;
        else if (!is_string($_SESSION[$this->_context.$key]))
            throw new \Exception(
                tr(
                    __NAMESPACE__,
                    'Appending to a dictionary value is only possible using string values,'
                )
            );
        else
            $_SESSION[$this->_context.$key] .= $text;
        // return result
        return $_SESSION[$this->_context.$key];
    }

    /**
     * @}
     */
    
    /**
     * @name Hash value access
     * @{
     */
    
    /**
     * @copydoc Engine::hashSet()
     */
    public function hashSet($key, $name, $value)
    {
        assert(is_string($name));

        if (!isset($value)) {
            $this->hashRemove($key, $name);
            return null;
        }
        $this->checkKey($key);
        if (!isset($_SESSION[$this->_context.$key]))
            $_SESSION[$this->_context.$key] = array($name => $value);
        else if (!is_array($_SESSION[$this->_context.$key]))
            throw new \Exception(
                tr(
                    __NAMESPACE__,
                    'The key isn\'t holding a hash'
                )
            );
        else
            $_SESSION[$this->_context.$key][$name] = $value;
        return $value;
    }

    /**
     * @copydoc Engine::hashGet()
     */
    public function hashGet($key, $name)
    {
        return isset($_SESSION[$this->_context.$key][$name])
            ? $_SESSION[$this->_context.$key][$name]
            : null;
    }

    /**
     * @copydoc Engine::hashHas()
     */
    public function hashHas($key, $name)
    {
        return isset($_SESSION[$this->_context.$key][$name]);
    }

    /**
     * @copydoc Engine::hashRemove()
     */
    public function hashRemove($key, $name)
    {
        if (isset($_SESSION[$this->_context.$key][$name])) {
            unset($_SESSION[$this->_context.$key][$name]);
            return true;
        }
        return false;
    }

    /**
     * @copydoc Engine::hashCount()
     */
    public function hashCount($key)
    {
        return isset($_SESSION[$this->_context.$key])
            ? $this->count($_SESSION[$this->_context.$key])
            : 0;
    }

    /**
     * @}
     */
    
    /**
     * @name List value access
     * @{
     */
    
    /**
     * @copydoc Engine::listPush()
     */
    public function listPush($key, $value)
    {
        $this->checkKey($key);
        if (!isset($_SESSION[$this->_context.$key])) {
            $_SESSION[$this->_context.$key] = array($value);
            return 1;
        }
        return array_push($_SESSION[$this->_context.$key], $value);
    }

    /**
     * @copydoc Engine::listPop()
     */
    public function listPop($key)
    {
        return isset($_SESSION[$this->_context.$key])
            ? array_pop($_SESSION[$this->_context.$key])
            : null;
    }

    /**
     * @copydoc Engine::listShift()
     */
    public function listShift($key)
    {
        return isset($_SESSION[$this->_context.$key])
            ? array_shift($_SESSION[$this->_context.$key])
            : null;
    }

    /**
     * @copydoc Engine::listUnshift()
     */
    public function listUnshift($key, $value)
    {
        $this->checkKey($key);
        if (!isset($_SESSION[$this->_context.$key])) {
            $_SESSION[$this->_context.$key] = array($value);
            return 1;
        }
        return array_unshift($_SESSION[$this->_context.$key], $value);
    }

    /**
     * @copydoc Engine::listGet()
     */
    public function listGet($key, $num)
    {
        return isset($_SESSION[$this->_context.$key][$num])
            ? $_SESSION[$this->_context.$key][$num]
            : null;
    }

    /**
     * @copydoc Engine::listSet()
     */
    public function listSet($key, $num, $value)
    {
        $this->checkKey($key);
        if (!isset($_SESSION[$this->_context.$key]))
            $_SESSION[$this->_context.$key] = array($value);
        else if (isset($_SESSION[$this->_context.$key][$num]))
            $_SESSION[$this->_context.$key][$num] = $value;
        else
            $_SESSION[$this->_context.$key][] = $value;
        return count($_SESSION[$this->_context.$key]);
    }

    /**
     * @copydoc Engine::listCount()
     */
    public function listCount($key)
    {
        return isset($_SESSION[$this->_context.$key]) ? count($_SESSION[$this->_context.$key]) : 0;
    }

    /**
     * @}
     */
    
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
