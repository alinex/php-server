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

use Alinex\Logger;

/**
 * Log message object.
 *
 * This is only used internally in the logger to pass messages through the
 * chain and collect data.
 *
 * The general data elements are:
 * - the context variables from message call
 * - time.sec - only the unixtem (seconds)
 * - time.msec - microseconds
 * - level.num - level number
 * - level.name - name of the log severity
 * - message - original message
 *
 * More data elements will be added using the Provider classes.
 */
class Message extends \Alinex\Util\Event
{
    /**
     * Alias names for the event.
     * @var array of strings
     */
    private static $_alias = array(
        Logger::EMERGENCY => 'emergency',
        Logger::ALERT => 'alert',
        Logger::CRITICAL => 'critical',
        Logger::ERROR => 'error',
        Logger::WARNING => 'warning',
        Logger::NOTICE => 'notice',
        Logger::INFO => 'info',
        Logger::DEBUG => 'debug'
    );

    /**
     * Get i18n title for the log level.
     * @param int $level log level
     * @return string title for level
     */
    static function levelName($level)
    {
        // use LOGGER::... constants for level name
        assert(
            is_int($level)
            && $level >= Logger::EMERGENCY
            && $level <= Logger::DEBUG
        );

        $title = array(
            Logger::EMERGENCY => tr(__NAMESPACE__, 'Emergency'),
            Logger::ALERT => tr(__NAMESPACE__, 'Alert'),
            Logger::CRITICAL => tr(__NAMESPACE__, 'Critical'),
            Logger::ERROR => tr(__NAMESPACE__, 'Error'),
            Logger::WARNING => tr(__NAMESPACE__, 'Warning'),
            Logger::NOTICE => tr(__NAMESPACE__, 'Notice'),
            Logger::INFO => tr(__NAMESPACE__, 'Info'),
            Logger::DEBUG => tr(__NAMESPACE__, 'Debug')
        );
        return $title[$level];
    }

    /**
     * Formatted message.
     * @var mixed
     */
    public $formatted = null;

    /**
     * Create new message object to work with.
     * @param mixed $subject Object in which the event occured
     * @param  mixed   $level   The log level
     * @param  string  $message The log message
     * @param  array   $context The log context
     */
    public function __construct($subject, $level, $message,
        array $context = array())
    {
        // use LOGGER::... constants for level name
        assert(
            is_int($level)
            && $level >= Logger::EMERGENCY
            && $level <= Logger::DEBUG
        );
        assert(is_string($message));

        // set context data as base
        $data = $context;
        // set the time
        list($msec, $sec) = explode(' ', microtime());
        $data['time'] = array(
            'sec' => (int)$sec,
            'msec' => (int)$msec
        );
        // set the level
        $data['level'] = array(
            'num' => $level,
            'name' => self::levelName($level)
        );
        // set message
        $data['message'] = $message;
        // create event
        parent::__construct($subject, self::$_alias[$level], $data);
    }
}