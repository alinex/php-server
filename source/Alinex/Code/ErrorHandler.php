<?php
/**
 * @file
 * Convert PHP errors into exceptions
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de
 */

namespace Alinex\Code;

/**
 * Convert PHP errors into exceptions
 *
 * To use this type:
 * @code
 * Alinex\Util\ErrorHandler::register();
 * @endcode
 *
 * After that all normal errors will be converted into an ErrorException.
 *
 * The error will look like:
 *
 * @verbatim
 * Fatal error: Uncaught exception 'ErrorException': 'Wrong parameter count for strpos()' in ex.php:8
 * Stack trace:
 * #0 [internal function]: exception_error_handler(2, 'Wrong parameter...', '/php...', 8, Array)
 * #1 /test.php(8): strpos()
 * #2 {main}
  thrown in /ex.php on line 8
 * @endverbatim
 *
 * You may specify which errors have to be logged (using Logger) and which are
 * thrown as Exception using the setLogLevel(), setExceptionLevel().
 * 
 * This helps in development because it can be thrown and catched anywhere.
 */
class ErrorHandler
{
    /**
     * Current setting of levels to log.
     * @var int
     */
    private static $_logLevel = E_ALL;
    
    /**
     * Set the error levels which have to be logged using Logger.
     * 
     * This defaults to all.
     * @param int $level error levels to log
     */
    public static function setLogLevel($level)
    {
        assert(is_int($level));
        self::$_logLevel = $level;
    }
    
    /**
     * Current setting of levels to throw exceptions.
     * @var int
     */
    private static $_exceptionLevel = 341;
    
    /**
     * Set the error levels on which to throw an exception.
     * 
     * This defaults to E_ERROR...
     * @param int $level error levels to log
     */
    public static function setExceptionLevel($level)
    {
        assert(is_int($level));
        self::$_exceptionLevel = $level;        
    }

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
        'E_STRICT' => array(self::DEBUG, 'Strict Warning'),
    );
    
    /**
     * Error handler
     *
     * @param int    $level   Level of the error raised
     * @param string $message Error message
     * @param string $file    Filename that the error was raised in
     * @param int    $line    Line number the error was raised at
     *
     * @throws \ErrorException
     */
    public static function handle($level, $message, $file, $line)
    {
        // respect error_reporting being disabled
        if (!error_reporting())
            return;

        if (ini_get('xdebug.scream'))
            $message .= PHP_EOL.PHP_EOL.
'Warning: You have xdebug.scream enabled, the warning above may be
a legitimately suppressed error that you were not supposed to see.';

        if ($level & self::$_logLevel)
            \Alinex\Logger::getInstance()->log(
                self::$_logPhpMapping[$level][0], 
                $message,
                array(
                    'type' => self::$_logPhpMapping[$level][1],
                    'file' => $file,
                    'line' => $line
                )
            );
        
        if ($level & self::$_exceptionLevel)
            throw new \ErrorException($message, 0, $level, $file, $line);
    }

    /**
     * Register error handler
     */
    public static function register()
    {
        set_error_handler(array(__CLASS__, 'handle'));
    }
    
}
