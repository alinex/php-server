<?php
/**
 * @file
 * Abstract filter to check if message should be logged.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Logger;

/**
 * Abstract filter to check if message should be logged.
 */
abstract class Filter
{
    /**
     * Does this filter use a message buffer.
     * @return boolean
     */
    public function hasBuffer()
    {
        return false;
    }

    /**
     * Check if this Message should  be further processed.
     *
     * Buffer filters may also store some messages locally to add them later.
     *
     * @param  Message  $message Log message object
     * @return Boolean Whether the record has been processed
     */
    abstract public function check(Message $message);
}