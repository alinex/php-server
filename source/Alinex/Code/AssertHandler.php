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
 * Assertions are used to report internal code problems which are used in
 * functions and methods to check incoming data. This is done in addition to
 * the UnitTests as folows:
 * @code
 * // check for integer range
 * assert(is_integer($minRange));
 * @endcode
 *
 * If this class is not activated using the AssertHandler::set() method it will
 * send the assert problems as error.
 *
 * If activated using AssertHandler::set(true) the handler will extract the
 * asserted code from source code and the comment line before, if given.
 *
 * If deactivated using AssertHandler::set(false) the code will run faster and
 * the assert code won't be run.
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