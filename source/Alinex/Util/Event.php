<?php
/**
 * @file
 * Event object used as information exchange between classes.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Util;

/**
 * Event object used as information exchange between classes.
 *
 * The event object allows loose coupling of Objects.
 *
 * Event Propagation
 * @image html event.png
 * @image latex event.png "Event Propagation" width=10cm
 *
 * This may be done in two possible ways like shown in the graphic above.
 *
 * **Private Listener**
 *
 * If the event origin class implements the EventSubject interface some
 * classes implementing EventObserver may directly attach themselves to the
 * origin object. They will be informed for any event which occure.
 *
 * **Global Events**
 *
 * If the origin class supports the EventManager by notifying it using
 * EventManager::update() any other class implementing EventObserver may
 * attach to the EventManager and be notified for specific events.
 *
 * **Event Information**
 *
 * The Event may transport an additional array structure with specific
 * information which may be interpreted by the EventObserver.
 *
 * @pattern{EventObserver} Implementing the Transport layer for the pattern.
 * @see event for a list of classes sending events
 */
class Event
{
    /**
     * Subject on which the event occures.
     * @var mixed
     */
    private $_subject = null;

    /**
     * String name of the event
     * @var string
     */
    private $_name = null;

    /**
     * Data structure to be passed on with event
     * @var array
     */
    public $data = array();

    /**
     * Create a new Event
     * @param mixed $subject Object in which the event occured
     * @param string $name short name of the event
     * @param mixed $data information to be passed on
     */
    function __construct($subject, $name, array $data = null)
    {
        // object has to be gibven to create an event
        assert(is_object($subject));
        // the name should be a short text as identifier of the type
        assert(isstring($name));

        $this->_subject = $subject;
        $this->_name = $name;
        if (isset($data))
            $this->data = $data;
    }

    /**
     * Get the subject on which the event occured
     * @return mixed the originating object
     */
    function getSubject()
    {
        return $this->_subject;
    }

    /**
     * Get the origin class of this event.
     * @return string class name of origin
     */
    function getClass()
    {
        return get_class($this->_subject);
    }

    /**
     * Get the identification name of the event type
     * @return mixed the identification name of the event type
     */
    function getName()
    {
        return $this->_name;
    }

    /**
     * Get a string representation of the event.
     *
     * This may be used for debugging or logging events
     * @return string explaining which event on which type of object occured
     */
    function getString()
    {
        return tr(
            '{event} for {name} occured on {subject}.',
            array(
                'event' => __CLASS__,
                'name' => $this->_name,
                'subject' => $this->_subject)
        );
    }
}
