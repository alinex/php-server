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
 * This helps in development because it can be thrown and catched anywhere.
 */
class ErrorHandler
{
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

        if (ini_get('xdebug.scream')) {
            $message .= PHP_EOL.PHP_EOL.
'Warning: You have xdebug.scream enabled, the warning above may be
a legitimately suppressed error that you were not supposed to see.';
        }

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
