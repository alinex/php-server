<?php
/**
 * @file
 * Buffer messages of this reuest till specified severity reached.
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
use Alinex\Logger;

/**
 * Buffer messages of this reuest till specified severity reached.
 *
 * This helps in debugging because it will also give you all information but
 * only if something went wrong and reached a minimal severity level.
 * 
 * If the message will be logged the older ones will be added as 'buffer' array
 * in the message (newest last).
 */
class LevelBuffer extends Filter
{
    /**
     * List of severity levels to log.
     * @var int
     */
    private $_threshold = Logger::ERROR;

    /**
     * Buffer for messages which were under threshold.
     * @var array
     */
    private $_buffer = array();
    
    /**
     * Set the threshold for logging.
     *
     * All messages will be buffered till this level is reached.
     * @param int $level threshold level
     */
    public function setThreshold($level)
    {
        $this->_threshold = $level;
    }

    /**
     * Check if this Message should  be further processed.
     *
     * If the level is above the defined minimum severity it will be processed
     * or if it has the specified exact level.
     *
     * @param  Message  $message Log message object
     * @return bool whether the record has to be further processed
     */
    public function check(Message $message)
    {
        if ($message->data['level']['num'] < $this->_threshold) {
            $this->_buffer[] = $message;
            return false;
        } else {
            $message['buffer'] = $this->_buffer;
            $this->_buffer = array();
            return true;
        }
    }
}