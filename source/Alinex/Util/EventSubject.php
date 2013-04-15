<?php
/**
 * @file
 * Interface for an Event providing class.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Util;

/**
 * Interface for an Event providing class.
 *
 * The EventSubject interface is used alongside EventObserver and Event to
 * implement the Event Observer Design Pattern. This is an extension to hte
 * classical Observer Pattern and sends an Event with additional event
 * specific data.
 *
 * @pattern{EventObserver} Interface for implementing the Subject.
 * @see Event for a detailed overview
 */
interface EventSubject
{
    /**
     * Attach an observer so that it can be notified
     * @param EventObserver $observer observer object to add
     */
    public function attach(EventObserver $observer);

    /**
     * Detaches an observer from the subject to no longer notify it
     * @param EventObserver $observer observer object to remove
     */
    public function detach(EventObserver $observer);

}
