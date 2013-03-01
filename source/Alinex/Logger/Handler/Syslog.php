<?php
/**
 * @file
 * Generate log messages that will be distributed by the system logger.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Logger\Handler;

use Alinex\Logger\Handler;
use Alinex\Logger\Message;

/**
 * Generate log messages that will be distributed by the system logger.
 */
class Syslog extends Handler
{
    /**
     * Translation to php syslog levels.
     *
     * @var array
     */
    static protected $syslogLevels = array(
        self::EMERGENCY => LOG_EMERG,
        self::ALERT => LOG_ALERT,
        self::CRITICAL => LOG_CRIT,
        self::ERROR => LOG_ERR,
        self::WARNING => LOG_WARNING,
        self::NOTICE => LOG_NOTICE,
        self::INFO => LOG_INFO,
        self::DEBUG => LOG_DEBUG
    );

    /**
     * The string ident is added to each message.
     * @var string
     */
    private $_ident = null;

    /**
     * Specify what type of program is logging the message.
     * @var int
     */
    private $_facility = LOG_USER;

    /**
     * Initialize syslogger.
     * @param string $ident string added to each message.
     * @param int $facility specify what type of program is logging the message
     * - LOG_AUTH 	security/authorization messages (use LOG_AUTHPRIV instead
     * in systems where that constant is defined)
     * - LOG_AUTHPRIV 	security/authorization messages (private)
     * - LOG_CRON 	clock daemon (cron and at)
     * - LOG_DAEMON 	other system daemons
     * - LOG_KERN 	kernel messages
     * - LOG_LOCAL0 ... LOG_LOCAL7 	reserved for local use, these are not
     * available in Windows
     * - LOG_LPR 	line printer subsystem
     * - LOG_MAIL 	mail subsystem
     * - LOG_NEWS 	USENET news subsystem
     * - LOG_SYSLOG 	messages generated internally by syslogd
     * - LOG_USER 	generic user-level messages
     * - LOG_UUCP 	UUCP subsystem
     *
     * LOG_USER is the only valid log type under Windows operating systems
     */
    function __construct($ident = 'Alinex', $facility = LOG_LOCAL0)
    {
        assert(is_string($ident));
        assert(is_int($facility));

        $this->_ident = $ident;
        if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN')
            $this->_facility = $facility;
        $this->_formatter = new \Alinex\Logger\Formatter\Line();
    }

    /**
     * Write the log message down.
     * @param  Message  $message Log message object
     */
    protected function write(Message $message)
    {
        openlog($this->_ident, LOG_PID | LOG_ODELAY, $this->_facility);
        syslog(self::$syslogLevels[$message->level], $message->formatted);
        closelog();
    }
}