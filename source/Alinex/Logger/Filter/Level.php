<?php
/**
 * @file
 * Check the message's severity level to decide if it should be logged.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Logger\Filter;

use Alinex\Logger\Filter;
use Alinex\Logger\Message;

/**
 * Check the message's severity level to decide if it should be logged.
 *
 * You may setMinimum() level which will enable the given level and all higher
 * ones or enable()/disable() every single level alone.
 */
class Level extends Filter
{
    /**
     * List of severity levels to log.
     * @var array
     */
    private $_allow = array();

    /**
     * Set the minimum severity for logging.
     *
     * All messages with this or higher severity levels will be logged.
     * @param int $level minimum severity level
     */
    public function setMinimum($level)
    {
        $this->_allow = array(); // cleanup
        for (; $level >= 0; $level--)
            $this->_allow[$level] = true;
    }

    /**
     * Enable the given severity level for logging.
     * @param int $level severity level to add for logging
     */
    public function enable($level)
    {
        $this->_allow[$level] = true;
    }

    /**
     * Disable the given severity level for logging.
     * @param int $level severity level to remove from logging
     */
    public function disable($level)
    {
        unset($this->_allow[$level]);
    }

    /**
     * Check if this Message should  be further processed.
     *
     * If the level is above the defined minimum severity it will be processed
     * or if it has the specified exact level.
     *
     * @param  Message  $message Log message object
     * @return Boolean Whether the record has been processed
     */
    public function check(Message $message)
    {
        return isset($this->_allow[$message->data['level']['num']]);
    }
}