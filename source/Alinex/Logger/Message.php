<?php
/**
 * @file
 * Log message object.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Logger;

/**
 * Log message object.
 *
 * This is only used internally in the logger to pass messages through the
 * chain and collect data.
 */
class Message
{
    /**
     * Severity type of message.
     * @var int
     */
    public $level = null;

    /**
     * Original message text
     * @var string
     */
    public $message = null;

    /**
     * Context information
     * @var array
     */
    public $context = null;

    /**
     * Formatted message for output
     * @var mixed
     */
    public $formatted = null;

    /**
     * Additional information from data Provider or Filter
     * @var array
     */
    public $data = array();

    /**
     * Additional messages to output.
     * @var array
     */
    public $buffer = null;

    /**
     * Create new message object to work with.
     * @param  mixed   $level   The log level
     * @param  string  $message The log message
     * @param  array   $context The log context
     */
    public function __construct($level, $message, array $context = array())
    {
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
    }
}