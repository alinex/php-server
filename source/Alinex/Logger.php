<?php

/**
 * @file
 * Logging class.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex;

/**
 * Logging class.
 *
 * This class sends the log messages to different handlers like file, mail,
 * database and more. It is compatible with the PSR-3 logger interface.
 *
 * Every Logger instance has a channel (name) and a stack of handlers. Whenever
 * you add a message to the logger, it traverses the handler stack. Each handler
 * decides whether it handle the message and how to record it.
 *
 * @dotfile Logger/Handler
 *
 * Through the use of filters each handler may ignore the message or buffer
 * them. This brings the highest flexibility. The formatter defines the concrete
 * output and additional provider give more information to the output.
 *
 * The class is compatible with the PEAR standards to enforce an
 * interchangeability in other standard conform projects.
 */
class Logger // implements \Psr\Log\LoggerInterface
{
    /**
     * System is unusable (will throw a LOG_Exception as well)
     */
    const EMERGENCY = 0;

    /**
     * Immediate action required (will throw a LOG_Exception as well)
     */
    const ALERT = 1;

    /**
     * Critical conditions (will throw a LOG_Exception as well)
     */
    const CRITICAL = 2;

    /**
     * Error conditions
     */
    const ERROR = 3;

    /**
     * Warning conditions
     */
    const WARNING = 4;

    /**
     * Normal but significant
     */
    const NOTICE = 5;

    /**
     * Informational
     */
    const INFO = 6;

    /**
     * Debug-level messages
     */
    const DEBUG = 7;

    /**
     * Available log levels
     *
     * @var array
     */
    static protected $_logLevels = array(
        self::EMERGENCY => 'Emergency',
        self::ALERT => 'Alert',
        self::CRITICAL => 'Critcal',
        self::ERROR => 'Error',
        self::WARNING => 'Warning',
        self::NOTICE => 'Notice',
        self::INFO => 'Info',
        self::DEBUG => 'Debug'
    );

    /**
     * Available PHP error levels and their meaning in POSIX loglevel terms
     * Some ERROR constants are not supported in all PHP versions
     * and will conditionally be translated from strings to constants,
     * or else: removed from this mapping at start().
     *
     * @var array
     */
    static protected $_logPhpMapping = array(
        E_ERROR => array(self::ERROR, 'Error'),
        E_WARNING => array(self::WARNING, 'Warning'),
        E_PARSE => array(self::EMERGENCY, 'Parse'),
        E_NOTICE => array(self::DEBUG, 'Notice'),
        E_CORE_ERROR => array(self::EMERGENCY, 'Core Error'),
        E_CORE_WARNING => array(self::WARNING, 'Core Warning'),
        E_COMPILE_ERROR => array(self::EMERGENCY, 'Compile Error'),
        E_COMPILE_WARNING => array(self::WARNING, 'Compile Warning'),
        E_USER_ERROR => array(self::ERROR, 'User Error'),
        E_USER_WARNING => array(self::WARNING, 'User Warning'),
        E_USER_NOTICE => array(self::DEBUG, 'User Notice'),
        'E_RECOVERABLE_ERROR' => array(self::WARNING, 'Recoverable Error'),
        'E_DEPRECATED' => array(self::NOTICE, 'Deprecated'),
        'E_USER_DEPRECATED' => array(self::NOTICE, 'User Deprecated'),
    );

    /**
     * List of active logger instances.
     * @var array
     */
    private static $_instances = array();

    /**
     * Get or create a logger with the defined name.
     *
     * @param string $name to identify this logger later
     * @return Logger instance of this class
     */
    public static function getInstance($name = 'default')
    {
        assert(is_string($name));

        if (! isset(self::$_instances[$name]))
            self::$_instances[$name] = new self($name);
        return self::$_instances[$name];
    }

    /**
     * Name of this logger for identification in code.
     * @var string
     */
    protected $_name = null;

    /**
     * Create a new
     * @param string $name The logging channel
     */
    protected function __construct($name)
    {
        $this->_name = $name;
    }

    /**
     * Adds a log record at an arbitrary level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  mixed   $level   The log level
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return int number of successful processings 0 = not logged
     */
    public function log($level, $message, array $context = array())
    {
        assert(key_exists($level, self::$_logLevels));

        // create message object
        $logmessage = new Logger\Message($level, $message, $context);
        // process handlers
        $success = 0;
        foreach($this->_handler as $handler)
            if ($handler->log($logmessage))
                $success++;
        return $success;
    }

    /**
     * Adds a log record at the DEBUG level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return int number of successful processings 0 = not logged
     */
    public function debug($message, array $context = array())
    {
        return $this->log(static::DEBUG, $message, $context);
    }

    /**
     * Adds a log record at the INFO level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return int number of successful processings 0 = not logged
     */
    public function info($message, array $context = array())
    {
        return $this->log(static::INFO, $message, $context);
    }

    /**
     * Adds a log record at the INFO level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return int number of successful processings 0 = not logged
     */
    public function notice($message, array $context = array())
    {
        return $this->log(static::NOTICE, $message, $context);
    }

    /**
     * Adds a log record at the WARNING level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return int number of successful processings 0 = not logged
     */
    public function warn($message, array $context = array())
    {
        return $this->log(static::WARNING, $message, $context);
    }

    /**
     * Adds a log record at the WARNING level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return int number of successful processings 0 = not logged
     */
    public function warning($message, array $context = array())
    {
        return $this->log(static::WARNING, $message, $context);
    }

    /**
     * Adds a log record at the ERROR level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return int number of successful processings 0 = not logged
     */
    public function err($message, array $context = array())
    {
        return $this->log(static::ERROR, $message, $context);
    }

    /**
     * Adds a log record at the ERROR level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return int number of successful processings 0 = not logged
     */
    public function error($message, array $context = array())
    {
        return $this->log(static::ERROR, $message, $context);
    }

    /**
     * Adds a log record at the CRITICAL level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return int number of successful processings 0 = not logged
     */
    public function crit($message, array $context = array())
    {
        return $this->log(static::CRITICAL, $message, $context);
    }

    /**
     * Adds a log record at the CRITICAL level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return int number of successful processings 0 = not logged
     */
    public function critical($message, array $context = array())
    {
        return $this->log(static::CRITICAL, $message, $context);
    }

    /**
     * Adds a log record at the ALERT level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return int number of successful processings 0 = not logged
     */
    public function alert($message, array $context = array())
    {
        return $this->log(static::ALERT, $message, $context);
    }

    /**
     * Adds a log record at the EMERGENCY level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return int number of successful processings 0 = not logged
     */
    public function emerg($message, array $context = array())
    {
        return $this->log(static::EMERGENCY, $message, $context);
    }

    /**
     * Adds a log record at the EMERGENCY level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return int number of successful processings 0 = not logged
     */
    public function emergency($message, array $context = array())
    {
        return $this->log(static::EMERGENCY, $message, $context);
    }

    /**
     * List of handlers
     * @var array
     */
    protected $_handler = array();

    /**
     * Adding Handler to the end of the list.
     * @param Logger\Handler $handler handler instance
     * @return int number of handlers in list
     */
    public function handlerPush(Logger\Handler $handler)
    {
        return array_push($this->_handler, $handler);
    }

    /**
     * Adding Handler to the start of the list.
     * @param Logger\Handler $handler handler instance
     * @return int number of handlers in list
     */
    public function handlerUnshift(Logger\Handler $handler)
    {
        return array_unshift($this->_handler, $handler);
    }

    /**
     * Removing Handler from the end of the list.
     * @return Logger\Handler handler instance
     */
    public function handlerPop()
    {
        return array_pop($this->_handler);
    }

    /**
     * Removing Handler from the start of the list.
     * @return Logger\Handler handler instance
     */
    public function handlerShift()
    {
        return array_shift($this->_handler);
    }

}
