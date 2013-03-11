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
use Alinex\Util\String;

/**
 * Check the namespace to decide if it should be logged.
 *
 * This allows to enable or disable messages based on their namepspace and class
 * name. To use you should at least enable one top range. You may also disable
 * some ranges within an enabled category or also reenable in disabled sub
 * ranges (but the last one will make it complicate to understand).
 */
class Category extends Filter
{
    /**
     * Does this filter use provider data or message buffer.
     */
    const IS_POSTFILTER = true;

    /**
     * Providers which should be added automatically.
     */
    static $needProvider = array('Code');

    /**
     * List of namespaces to log.
     * @var array
     */
    private $_allow = array();

    /**
     * List of namespaces to not log.
     * @var array
     */
    private $_deny = array();

    /**
     * Enable the given namespace for logging.
     * @param int $namespace namespace to be enabled
     */
    public function enable($namespace)
    {
        $this->_allow[$namespace] = true;
        unset($this->_deny[$namespace]);
    }

    /**
     * Disable the given namespace for logging.
     * @param int $namespace namespace to be disabled
     */
    public function disable($namespace)
    {
        $this->_deny[$namespace] = true;
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
        if (!isset($message->data['code']['class']))
            return false;
        foreach (array_keys($this->_allow) as $allow) {
            // allow if within given namespace
            if (String::startsWith($message->data['code']['class'], $allow)) {
                $ok = true;
                foreach (array_keys($this->_deny) as $deny) {
                    // disallow if in forbidden namespace, too
                    if (strlen($deny) >= strlen($allow)
                        && String::startsWith(
                            $message->data['code']['class'], $deny
                        )
                    ) $ok = false;
                }
                if ($ok)
                    return true;
            }
        }
        // not defined -> disallow
        return false;
    }
}