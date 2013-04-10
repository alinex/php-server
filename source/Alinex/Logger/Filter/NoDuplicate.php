<?php
/**
 * @file
 * Prevent duplicate entries in log.
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
use Alinex\Dictionary\Cache;
use Alinex\Dictionary\Engine;

/**
 * Prevent duplicate entries in log.
 *
 * This will use the Cache to collect the messages so also there will be no
 * duplicate messages accross multiple servers.
 */
class NoDuplicate extends Filter
{
    /**
     * Does this filter use provider data or message buffer.
     */
    const IS_POSTFILTER = true;

    /**
     * Default time-to-live while duplicate messages won't occure
     */
    const DEFAULT_TTL = 3600; // 1 hour

    /**
     * Name of the group in cache under which the log hashes will be kept.
     * @cache
     */
    const CACHE_GROUP = 'log.duplicate';

    /**
     * Time-to-live while duplicate messages won't occure
     * @var int
     */
    protected $_ttl = self::DEFAULT_TTL;

    /**
     * Set the time while duplicate messages are counted.
     *
     * The real time may be longer bacause it depend on the garbage collection
     * of the cache engine.
     *
     * @param int $ttl time in seconds
     * @return int the timerange for which to protect against duplicate logs
     */
    public function setTime($ttl)
    {
        $this->_ttl = isset($ttl) ? $ttl : self::DEFAULT_TTL;
        return $this->_ttl;
    }

    /**
     * Additional meta keys to check as keys.
     * @var array
     */
    protected $_data = array();

    /**
     * Id of this filter class used to separate storage with other instances.
     * @var string
     */
    private $_id = null;

    /**
     * Constructor set the object id
     */
    public function __construct()
    {
        $this->_id = \Alinex\Util\Object::getId($this);
    }

    /**
     * Add or remove data to be included in check.
     *
     * @param string $name data element of message to be included
     * @param bool $enabled flag if it should be true=added or false=removed
     */
    public function checkData($name, $enabled = true)
    {
        if ($enabled)
            $this->_data[$name] = 1;
        else
            unset($this->_data[$name]);
    }

    /**
     * Get an unique hash code for the message to check for duplicates.
     *
     * @param Message $message Log message object
     * @return string the cache key to check for this message
     */
    protected function getHashKey(Message $message)
    {
        $data = $message->data['message'];
        foreach (array_keys($this->_data) as $name)
            if (isset($message->data[$name]))
                $data .= '-'.$message->data[$name];
        return self::CACHE_GROUP.'.'.$this->_id.'.'.md5($data);
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
        if ($cache->has($hash)) {
            $cache->set($hash, 1, Engine::SCOPE_GLOBAL, $this->_ttl);
            return true;
        } else {
            $cache->inc($hash);
            return false;
        }
    }
}