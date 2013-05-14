<?php
/**
 * @file
 * Handler for uncatched exceptions.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de
 */

namespace Alinex\Code;
use Alinex\Logger;

/**
 * Handler for uncatched exceptions.
 *
 * This handler will log the exception only.
 */
class ExceptionHandler
{

    /**
     * Exception handler
     *
     * @param \Exception $ex Exception which was thrown
     */
    public static function handle(\Exception $ex)
    {
        if (isset($GLOBALS['initialized']) && $GLOBALS['initialized'])
            Logger::getInstance()->critical(
                $ex->getMessage(),
                array(
                    'file' => $ex->getFile(),
                    'line' => $ex->getLine(),
                    'trace' => $ex->getTraceAsString()
                )
            );
        else
            // fallback handling if not properly initialized
            error_log(
                'CRITICAL: '.$ex->getMessage()
                .' in '.$ex->getFile().' on line '.$ex->getLine()
            );
    }

    /**
     * Register exception handler
     */
    public static function register()
    {
        set_exception_handler(array(__CLASS__, 'handle'));
    }

}
