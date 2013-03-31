<?php
/**
 * @file
 * Storage keeping values in globals array.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Dictionary\Engine;

use Alinex\Dictionary\Engine;

/**
 * Storage keeping values in globals array.
 *
 * **Specification**
 * - Scope: request
 * - Performance: very high
 * - Persistence: none
 * - Size: limited by php max-memory setting
 * - Objects: native support
 * - Manipulation: native support
 * - Garbage collection: ttl, manual call
 * - Requirements: none
 *
 * This class will store the values locally in an array. It's stored simialar to
 * the ArrayList engine.
 *
 * This engine can be used as a fallback with fast access but the whole
 * space is limited by the php max. memory setting.
 *
 * This is the simplest storage, it will last till the end of the php process
 * (mostly the request). It has no dependencies, the performance is high
 * but its usability is poor because of the restricted scope and size.
 *
 * **Garbage Collection**
 *
 * The ArrayList has a minimal implementation of an garbage collector. This is
 * only used in long running processes because the collection will be purged
 * after each request.
 *
 * To do so the ___gc key is holding a list of keys with the timeout date. The
 * timeout is set on each set() method and read on gc() call to check.
 *
 * @attention
 * To prevent out-of-memory crashes all values with a set time-to-live will be
 * removed automatically on set() if free php memory goes under defined limit.
 *
 * @see Engine overview chart
 * @see Dictionary for usage examples
 */
class GlobalList extends ArrayList
{
    /**
     * Constructor
     *
     * This will set the context store and memory limit.
     *
     * @param string $context special name of this instance
     */
    protected function __construct($context)
    {
        parent::__construct($context);
        $this->_storage =& $GLOBALS[$this->_context];
    }
}