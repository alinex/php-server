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
 * This is implemented using an extended version of the Observer Pattern. Where
 * an EventObserver can itself attach to an EventSubject and will get the
 * Event object send each time it occures.
 *
 * The Event may transport an additional array structure with specific
 * information which may be interpreted by the EventObserver.
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
