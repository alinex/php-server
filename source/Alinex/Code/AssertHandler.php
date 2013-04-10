<?php
/**
 * @file
 * Support assert with detailed info.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de
 */

namespace Alinex\Code;

/**
 * Support assert with detailed info.
 *
 * To reach a high security level while be as performant as possible type and
 * value checks are separated in two categories:
 * 1. data coming from external resources hav to be completely checked at any
 * time
 * 2. data send internaly between the classes should be ok if code was tested
 * and didn't change
 *
 * Therefore the second type of checks are put into assert calls which can be
 * switched off or completely removed from code in productive environment.
 *
 * Example of calls with active asserts:
 * @image html asserts-active.png
 * @image latex asserts-active.png "Asserts active" width=10cm
 *
 * Example of the same calls with asserts inactive or removed:
 * @image html asserts-removed.png
 * @image latex asserts-removed.png "Asserts removed" width=10cm
 *
 * Assertions are used to report internal code problems which are used in
 * functions and methods to check incoming data. This is done in addition to
 * the Validator and UnitTests as folows:
 * @code
 * // check for integer range
 * assert(is_integer($minRange));
 * @endcode
 *
 * If this class is not activated using the AssertHandler::set() method it will
 * send the assert problems as error.
 *
 * @code
 * AssertHandler::set(true);
 * @endcode
 *
 * This will enable the handler to send exceptions instead of errors and
 * extract the asserted code and the comment line before from source code.
 * This setting should be used while in development/test mode, not for
 * production system (see below).
 *
 * @verbatim
 * Uncaught exception: 'Assertion check for integer range
 * (is_integer($minRange)) failed.'
 * @endverbatim
 *
 * This will be send as an AssertException.
 *
 * @code
 * AssertHandler::set(true);
 * @endcode
 *
 * This will disable assertions, that means the code run faster but the
 * assertions won't be checkt and won'T throw any error or exception.
 * This setting should be used in production mode.
 *
 * @section assertiuons What belongs into assertions
 *
 * As more things checked in assertions as earlier you may find some development
 * errors. So don't spare on this.
 *
 * @attention Don't check things using assertion which comes from out of
 * the development system because this may change:
 * - user input
 * - system variables
 * - file contents
 * - database settings
 * - interface data
 *
 * @codeCoverageIgnore
 */
class AssertHandler
{
    /**
     * Error handler
     *
     * This handler will take the file and code line and extract the asserted
     * code and comment in the line before.
     *
     * @param string $file Filename that the error was raised in
     * @param int    $line Line number the error was raised at
     * @param string $code evaluated code (only if given using string): not used
     * @param string $desc PHP 5.4 description as param 2 to assert(): not used
     *
     * @throws AssertException
     */
    public static function handle($file, $line, $code, $desc = null)
    {
        // read code
        $file = file($file);
        // extract assert code
        $buff = $file[$line-1];
        while (strpos($buff, 'assert(') === FALSE)
            $buff = $file[--$line-1] . $buff;
        $code = preg_replace(
            '#\s+#', ' ', preg_replace('#assert\((.*?)\s*\);.*#sm', '$1', $buff)
        );
        // check for comment on line before
        if (preg_match('#\s*//\s*\S.*#', $file[--$line-1]))
           $desc = preg_replace('#\s*//\s*(\S.*)\s*$#', '$1', $file[$line-1]);
        // report
        throw new AssertException(
            'Assertion '.$desc.' ('.$code.') failed',
            $file, $line, $code, $desc
        );
    }

    /**
     * Activate or deactivate asserts
     *
     * For development assert should be enabled to report internal code
     * problems which are checked with the assert function calls. On
     * productive environment this should be deactivated to gain better
     * performance. The Alines build will also remove all assert calls and
     * this class including it's calls from code.\n
     * If not set the default reporting will be used which throws an error with
     * less information.
     *
     * @param bool $active set to false for productive, true for development
     */
    static function enabled($active = true)
    {
        if ($active) {
            assert_options(ASSERT_ACTIVE, 1);
            assert_options(ASSERT_WARNING, 0);
            assert_options(ASSERT_QUIET_EVAL, 0);
            // Set up the callback
            assert_options(ASSERT_CALLBACK, array(__CLASS__, 'handle'));
        } else {
            assert_options(ASSERT_ACTIVE, 0);
        }
    }
}