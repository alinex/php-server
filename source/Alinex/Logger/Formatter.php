<?php
/**
 * @file
 * Abstract formatter to create log output.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Logger;

/**
 * Abstract formatter to create log output.
 */
abstract class Formatter
{
    /**
     * Adds a log record to this handler.
     *
     * @param  mixed   $level   The log level
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @param  array   $data result data from providers
     * @return string Whether the record has been processed
     */
    abstract public function format(
        $level, $message, array $context = array(), $data = array()
    );
}