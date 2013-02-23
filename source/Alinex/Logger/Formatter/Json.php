<?php
/**
 * @file
 * Formatter storing messages as Json structure.
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
 * Formatter storing messages as Json structure.
 * 
 * Each log message will be added under the unix timestamp with milliseconds.
 */
class Json extends Formatter
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
        // return export string
        return json_encode($result);
    }
    
}