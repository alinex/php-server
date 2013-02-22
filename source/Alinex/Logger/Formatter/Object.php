<?php
/**
 * @file
 * Handler storing logs in given storage engine.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Logger\Formatter;

use \Alinex\Logger\Formatter;

/**
 * Handler storing logs in given storage engine.
 * 
 * Each log message will be added under the unix timestamp with milliseconds.
 */
class Object extends Formatter
{

    /**
     * Adds a log record to this handler.
     *
     * @param  mixed   $level   The log level
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @param  array   $info result data from providers
     * @return string Whether the record has been processed
     */
    public function format(
        $level, $message, array $context = array(), $info = array()
    )
    {
        $result = array('level' => $level, 'message' => $message);
        if (isset($context))
            $result['context'] = $context;
        if (isset($info))
            $result['info'] = $info;
        return $result;
    }
    
}