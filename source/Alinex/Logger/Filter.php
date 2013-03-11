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
     * Does this filter use provider data or message buffer.
     */
    const IS_POSTFILTER = false;

    /**
     * Providers which should be added automatically.
     */
    static $needProvider = array();

    /**
     * Check if this Message should  be further processed.
     *
     * Post filters may also store some messages locally to add them later.
     *
     * @param  Message  $message Log message object
     * @return Boolean Whether the record has been processed
     */
    abstract public function check(Message $message);
}