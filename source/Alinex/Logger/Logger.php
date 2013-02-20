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

namespace Alinex\Logger;

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
 * Through the use of filters each handler may ignore the message or buffer
 * them. This brings the highest flexibility. The formatter defines the concrete
 * output and additional provider give more information to the output.
 */
class Logger
{
    // Make these corresponding with PEAR
    // Ensures compatibility while maintaining independency

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
        self::EMERGENCY => 'emergency',
        self::ALERT => 'alert',
        self::CRITICAL => 'critcal',
        self::ERROR => 'error',
        self::WARNING => 'warning',
        self::NOTICE => 'notice',
        self::INFO => 'info',
        self::DEBUG => 'debug',
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
        'E_RECOVERABLE_ERROR' => array(self::LOG_WARNING, 'Recoverable Error'),
        'E_DEPRECATED' => array(self::LOG_NOTICE, 'Deprecated'),
        'E_USER_DEPRECATED' => array(self::LOG_NOTICE, 'User Deprecated'),
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
    public function __construct($name)
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
     * @return int number of successful log providers
     */
    public function log($level, $message, array $context = array())
    {
        $success = 0;
        foreach($this->_handler as $handler)
            if ($handler->log($level, $message, $context))
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
     * @return Boolean Whether the record has been processed
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
     * @return Boolean Whether the record has been processed
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
     * @return Boolean Whether the record has been processed
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
     * @return Boolean Whether the record has been processed
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
     * @return Boolean Whether the record has been processed
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
     * @return Boolean Whether the record has been processed
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
     * @return Boolean Whether the record has been processed
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
     * @return Boolean Whether the record has been processed
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
     * @return Boolean Whether the record has been processed
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
     * @return Boolean Whether the record has been processed
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
     * @return Boolean Whether the record has been processed
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
     * @return Boolean Whether the record has been processed
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
     * Adding Handler instance to process the log messages.
     * @param string $name name for later access
     * @param \Alinex\Logger\Handler\Handler $handler handler instance
     */
    public function addHandler($name, Handler\Handler $handler)
    {
        assert(is_string($name));

        $this->_handler[$name] = $handler;
    }
    
    /**
     * Remove handler from logger.
     * @param string $name name to be removed
     */
    public function removeHandler($name)
    {
        assert(is_string($name));
        
        unset($this->_handler[$name]);
    }
    
    
    
    
    
    
    
    
    
    
    
    public static function xlog($level, $str)
    {
        $arguments = func_get_args();
        $level     = $arguments[0];
        $format    = $arguments[1];

        if (is_string($level)) {
            if (false === ($l = array_search($level, self::$_logLevels))) {
                self::log(LOG_EMERG, 'No such loglevel: '. $level);
            } else {
                $level = $l;
            }
        }

        unset($arguments[0]);
        unset($arguments[1]);

        $str = $format;
        if (count($arguments)) {
            foreach ($arguments as $k => $v) {
                $arguments[$k] = self::semantify($v);
            }
            $str = vsprintf($str, $arguments);
        }

        $history  = 2;
        $dbg_bt   = @debug_backtrace();
        $class    = (isset($dbg_bt[($history-1)]['class'])?(string)@$dbg_bt[($history-1)]['class']:"");
        $function = (isset($dbg_bt[($history-1)]['function'])?(string)@$dbg_bt[($history-1)]['function']:"");
        $file     = (isset($dbg_bt[$history]['file'])?(string)@$dbg_bt[$history]['file']:"");
        $line     = (isset($dbg_bt[$history]['line'])?(string)@$dbg_bt[$history]['line']:"");
        return self::log($level, $str, $file, $class, $function, $line);
    }
    
    
    /**
     * Abbreviate a string. e.g: Kevin van zonneveld -> Kevin van Z...
     *
     * @param string  $str    Data
     * @param integer $cutAt  Where to cut
     * @param string  $suffix Suffix with something?
     *
     * @return string
     */
    static public function abbr($str, $cutAt = 30, $suffix = '...')
    {
        if (strlen($str) <= 30) {
            return $str;
        }

        $canBe = $cutAt - strlen($suffix);

        return substr($str, 0, $canBe). $suffix;
    }

    /**
     * Tries to return the most significant information as a string
     * based on any given argument.
     *
     * @param mixed $arguments Any type of variable
     *
     * @return string
     */
    static public function semantify($arguments)
    {
        if (is_object($arguments)) {
            return get_class($arguments);
        }
        if (!is_array($arguments)) {
            if (!is_numeric($arguments) && !is_bool($arguments)) {
                $arguments = '\''.$arguments.'\'';
            }
            return $arguments;
        }
        $arr = array();
        foreach ($arguments as $key=>$val) {
            if (is_array($val)) {
                $val = json_encode($val);
            } elseif (!is_numeric($val) && !is_bool($val)) {
                $val = '\''.$val.'\'';
            }

            $val = self::abbr($val);

            $arr[] = $key.': '.$val;
        }
        return join(', ', $arr);
    }


    static $logStdout = true;

    /**
     * It logs a string according to error levels specified in array:
     * self::$_logLevels (0 is fatal and handles daemon's death)
     *
     * @param integer $level    What function the log record is from
     * @param string  $str      The log record
     * @param string  $file     What code file the log record is from
     * @param string  $class    What class the log record is from
     * @param string  $function What function the log record is from
     * @param integer $line     What code line the log record is from
     *
     * @return boolean
     * @see _logLevels
     * @see logLocation
     */
    static public function log ($level, $str, $file = false, $class = false,
    $function = false, $line = false) {
        // If verbosity level is not matched, don't do anything
        if ($level > LOGGER_VERBOSITY)
            return true;

        // Make the tail of log massage.
        $log_tail = '';
        if ($level < self::LOG_NOTICE) {
            if (LOGGER_FILE_POSITION) {
                if (LOGGER_TRIM_APP_DIR)
                    $file = substr($file, strlen(dirname(__DIR__)));
                $log_tail .= ' [f:'.$file;
                if (LOGGER_LINE_POSITION)
                    $log_tail .= ':'.$line;
                $log_tail .= ']';
            }
        }

        // Save resources if arguments are passed.
        // But by falling back to debug_backtrace() it still works
        // if someone forgets to pass them.
        if (function_exists('debug_backtrace') && (!$file || !$line)) {
            $dbg_bt   = @debug_backtrace();
            $class    = (isset($dbg_bt[1]['class'])?$dbg_bt[1]['class']:'');
            $function = (isset($dbg_bt[1]['function'])?$dbg_bt[1]['function']:'');
            $file     = $dbg_bt[0]['file'];
            $line     = $dbg_bt[0]['line'];
        }

        // Determine what process the log is originating from and forge a logline
        //$str_ident = '@'.substr(self::_whatIAm(), 0, 1).'-'.posix_getpid();
        $str_date  = '[' . date('M d H:i:s') . ']';
        $str_level = str_pad(self::$_logLevels[$level] . '', 8, ' ', STR_PAD_LEFT);
        $pid = str_pad('['.getmypid() . ']', 7, ' ', STR_PAD_LEFT);
        $log_line  = $str_date . ' ' . $pid . ' ' . $str_level . ': ' . $str . $log_tail; // $str_ident

        $non_debug      = ($level < self::LOG_DEBUG);

        if (LOGGER_STDOUT && self::$logStdout && $non_debug) {
            // It's okay to echo if you're running as a foreground process.
            // Maybe the command to write an init.d file was issued.
            // In such a case it's important to echo failures to the
            // STDOUT
            echo $log_line . "\n";
            $log_succeeded = true;
        }

        if (LOGGER_STDERR && $non_debug) {
            // It's okay to echo if you're running as a foreground process.
            // Maybe the command to write an init.d file was issued.
            // In such a case it's important to echo failures to the
            // STDOUT
            file_put_contents('php://stderr', $log_line . "\n");
            $log_succeeded = true;
        }

        if (LOGGER_APACHE_LOG && $non_debug) {
            // It's okay to echo if you're running as a foreground process.
            // Maybe the command to write an init.d file was issued.
            // In such a case it's important to echo failures to the
            // STDOUT
            error_get_last($log_line);
            $log_succeeded = true;
        }

        if (LOGGER_FILE) {

            $log_succeeded = true;
            // 'Touch' logfile
            if (!file_exists(LOGGER_FILE))
                file_put_contents(LOGGER_FILE, '');

            // Not writable even after touch? Allowed to echo again!!
            if (!is_writable(LOGGER_FILE))
                throw new Exception("Could not log to ".LOGGER_FILE."!!!");

            // Append to logfile
            $f = file_put_contents(
                LOGGER_FILE,
                $log_line . "\n",
                FILE_APPEND
            );
            if (!$f) {
                $log_succeeded = false;
            }
        }

        // These are pretty serious errors
        if ($level < self::LOG_ERR) {
            // An emergency logentry is reason for the deamon to
            // die immediately
            if ($level === self::LOG_EMERG) {
                self::_die();
            }
        }

        return $log_succeeded;
    }

}
