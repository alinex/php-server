<?php
/**
 * @file
 * Check the namespace to decide if it should be logged.
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
 * Check the namespace to decide if it should be logged.
 */
class Category extends Filter
{
    /**
     * Does this filter use provider data or message buffer.
     */
    const isPostfilter = false;
    
    /**
     * Providers which should be added automatically.
     */
    const needProvider = array('Code');
    
    /**
     * List of namespaces to log.
     * @var array
     */
    private $_allow = array();
    
    /**
     * Enable the given namespace for logging.
     * @param int $level severity level to add for logging
     */
    public function enable($namespace)
    {
        $this->_allow[$namespace] = true;
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
        return isset($this->_allow[$message->data['code.namespace']]);
    }
}