<?php
/**
 * @file
 * Interface for an Event observing class.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Util;

/**
 * Interface for an Event observing class.
 *
 * The EventObserver interface is used alongside EventSubject and Event to
 * implement the Event Observer Design Pattern. This is an extension to hte
 * classical Observer Pattern and sends an Event with additional event
 * specific data.
 * 
 * @pattern{EventObserver} Interface for the observer.
 */
interface EventObserver
{
    /**
     * Receive update from subject
     * @param Event $subject
     */
    public function update(Event $subject);
}
