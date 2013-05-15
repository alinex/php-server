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
 *
 * **Context information**
 *
 * If context data is used the array keys have to be choosen wisely, they might
 * interefere with the geneeral or provider data. If so the Provider will
 * overwrite them.
 *
 * @pattern{EventObserver}
 * Used to add other listener objects to the Logger. The following events may
 * be send. The direct attached observers (Handler) will be served first and
 * the EventManager afterwords.
 * @event{debug} - Debugging message with Message object
 * @event{info} - Informational with Message object
 * @event{notice} - Normal but significant with Message object
 * @event{warning} - Warning conditions with Message object
 * @event{error} - Error conditions with Message object
 * @event{critical} - Critical conditions with Message object
 * @event{alert} - Immediate action required with Message object
 * @event{emergency} - System is unusable with Message object
 */
class Logger implements Util\EventSubject // implements \Psr\Log\LoggerInterface
{
    /**
     * System is unusable (will throw a LOG_Exception as well)
     */
    const EMERGENCY = 1;

    /**
     * Immediate action required (will throw a LOG_Exception as well)
     */
    const ALERT = 2;

    /**
     * Critical conditions (will throw a LOG_Exception as well)
     */
    const CRITICAL = 4;

    /**
     * Error conditions
     */
    const ERROR = 8;

    /**
     * Warning conditions
     */
    const WARNING = 16;

    /**
     * Normal but significant
     */
    const NOTICE = 32;

    /**
     * Informational
     */
    const INFO = 64;

    /**
     * Debug-level messages
     */
    const DEBUG = 128;

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

        if (! isset(self::$_instances[$name])) {
            self::$_instances[$name] = new self($name);
        }
        return self::$_instances[$name];
    }

    /**
     * Name of this logger for identification in code.
     * @var string
     */
    protected $_name = null;

    /**
     * List of handlers
     * @var Logger\HandlerEvent
     */
    protected $_handler = array();

    /**
     * Create a new
     * @param string $name The logging channel
     */
    protected function __construct($name)
    {
        assert(is_string($name));

        $this->_name = $name;
    }

    /**
     * Attach an observer so that it can be notified
     * @param Util\EventObserver $handler observer object to aadd
     */
    public function attach(Util\EventObserver $handler)
    {
        // observer has to be a handler
        assert($handler instanceof Logger\Handler);

        $this->_handler[spl_object_hash($handler)] = $handler;
    }

    /**
     * Detaches an observer from the subject to no longer notify it
     * @param Util\EventObserver $handler observer object to remove
     */
    public function detach(Util\EventObserver $handler)
    {
        // observer has to be a handler
        assert($handler instanceof Logger\Handler);

        unset($this->_handler[spl_object_hash($handler)]);
    }

    /**
     * Get the list of registered handlers
     * @return array of Handler
     */
    public function getHandlers()
    {
        return $this->_handler;
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
        // valid loglevel between DEBUG - EMERGENCY
        assert(
            is_int($level)
            && $level <= self::DEBUG
            && $level >= self::EMERGENCY
        );

        // create message object
        $logmessage = new Logger\Message($this, $level, $message, $context);
        // process own handlers
        foreach($this->_handler as $handler)
            $handler->update($logmessage);
        // inform through EventManager
        Util\EventManager::getInstance()
            ->update($logmessage);
        return count($this->_handler);
    }

    /**
     * @name Logging Methods
     * @{
     */

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
    public function emergency($message, array $context = array())
    {
        return $this->log(static::EMERGENCY, $message, $context);
    }

    /**
     * @}
     */
}
