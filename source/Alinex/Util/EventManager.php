<?php
/**
 * @file
 * The event manager enables a multi-to-multi dependency through events.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Util;

/**
 * The event manager enables a multi-to-multi dependency through events.
 *
 * @pattern{Singleton} To work with only one instance of EventManager.
 */
class EventManager implements EventSubject, EventObserver
{
    /**
     * Singleton instance
     * @var EventManager
     */
    static private $_instance = null;

    /**
     * Get the instance to work on.
     * @return EventManager
     */
    static function getInstance()
    {
        if (!isset(self::$_instance))
            self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * List of event listeners for specific events.
     *
     * The key defines the class and the name for which events should be send.
     * A * represents all. The key '*-*' will therefore represent a general
     * listener.
     *
     * @var array of EventObservers
     */
    private $_listener = array();

    /**
     * Create a new instance
     */
    private function __construct()
    {
        // nothing to do
    }

    /**
     * @copydoc EventSubject::attach()
     * @param string|array $class classe names to listen on
     * @param string|array $name event names to listen on
     */
    public function attach(EventObserver $observer, $class = null,
        $name = null)
    {
        assert(isset($observer));
        // class has to be string or array of strings
        assert(
            !isset($class)
            || is_array($class)
            || is_string($class)
        );
        // name has to be string or array of strings
        assert(
            !isset($name)
            || is_array($name)
            || is_string($name)
        );

        // get into array structure
        if (!isset($class))
            $class = array('*');
        else if (is_string($class))
            $class = array($class);
        if (!isset($name))
            $name = array('*');
        else if (is_string($name))
            $class = array($name);
        // add listener entries
        foreach ($class as $classEntry) {
            foreach($name as $nameEntry) {
                $key = $classEntry.'-'.$nameEntry;
                $cid = spl_object_hash($observer);
                if (!isset($this->_listener[$key]))
                    $this->_listener[$key] = array();
                $this->_listener[$key][$cid] = $observer;
            }
        }
    }

    /**
     * @copydoc EventSubject::detach()
     * @param string|array $class classe names to listen on
     * @param string|array $name event names to listen on
     */
    public function detach(EventObserver $observer, $class = null,
        $name = null)
    {
        // class has to be string or array of strings
        assert(
            !isset($class)
            || is_array($class)
            || is_string($class)
        );
        // name has to be string or array of strings
        assert(
            !isset($name)
            || is_array($name)
            || is_string($name)
        );

        // if nothing set remove all
        if (!isset($class) && !isset($name)) {
            foreach ($this->_listener as $key => $list)
                unset($list[spl_object_hash($observer)]);
            return;
        }
        // get into array structure
        if (!isset($class))
            $class = array('*');
        else if (is_string($class))
            $class = array($class);
        if (!isset($name))
            $name = array('*');
        else if (is_string($name))
            $class = array($name);
        // add listener entries
        foreach ($class as $classEntry) {
            foreach($name as $nameEntry) {
                $key = $classEntry.'-'.$nameEntry;
                $cid = spl_object_hash($observer);
                unset($this->_listener[$key][$cid]);
            }
        }
    }

    /**
     * @copydoc EventObject::update()
     */
    public function update(Event $subject)
    {
        $class = $subject->getClass();
        $name = $subject->getName();
        // get list of types to notify
        $types = array(
            $class.'-'.$name,
            $class.'-*',
            '*-'.$name,
            '*-*'
        );
        // call observers for notification
        foreach ($types as $name)
            if (isset($this->_listener[$name]))
                $this->notify($this->_listener[$name], $subject);
    }

    /**
     * Notify all listeners in the list.
     * @param array $list of observers
     * @param \Alinex\Util\Event $subject
     */
    private function notify(array $list, Event $subject)
    {
        foreach ($list as $object)
            call_user_func($object, $subject);
    }
}
