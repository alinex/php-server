<?php
/**
 * @file
 * Only log if event occurred multiple times.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Logger\Filter;

use Alinex\Logger\Message;
use Alinex\Dictionary\Cache;

/**
 * Only log if event occurred multiple times.
 *
 * This will use the Cache to collect the messages so also there will be no
 * duplicate messages accross multiple servers.
 * 
 * The message will also get the number of occurrencies added as:
 * - occurrence = int
 */
class MinOccurrence extends NoDuplicate
{
    /**
     * Name of the group in cache under which the log hashes will be kept.
     */
    const CACHE_GROUP = 'log.minoccurre';

    /**
     * The default occurence for the message to log.
     */
    const DEFAULT_OCCURRENCE = 10;

    /**
     * Number of occurrence of message to process further.
     * @var int
     */
    protected $_occurrence = self::DEFAULT_OCCURRENCE;

    /**
     * Set number of occurrence of message to process further.
     *
     * @param int $num minimum number
     * @return int value set
     */
    public function setOccurrence($num = self::DEFAULT_OCCURRENCE)
    {
        $this->_occurrence = $num;
        return $num;
    }

    /**
     * Check if this Message should  be further processed.
     *
     * If this message is already in the duplicate log it is removed.
     *
     * @param  Message  $message Log message object
     * @return bool whether the record has to be further processed
     */
    public function check(Message $message)
    {
        // init
        $cache = Cache::getInstance();
        $hash = $this->getHashKey($message);
        // check and store
        $num = $cache->has($hash)
            ? $cache->set($hash, 1, Engine::SCOPE_GLOBAL, $this->_ttl)
            : $cache->inc($hash);
        // set occurrence to message
        $message->data['occurrence'] = $this->_occurrence;
        return $num > $this->_occurrence;
    }
}