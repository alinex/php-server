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
     * Adds a log record to this handler.
     *
     * @param  mixed   $level   The log level
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @param  array   $data result data from providers
     * @return Boolean Whether the record has been processed
     */
    abstract public function check(
        $level, $message, array $context = array(), $data = array()
    );
}