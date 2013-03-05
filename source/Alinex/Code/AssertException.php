<?php
/**
 * @file
 * Exception if assert call failed
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de
 */

namespace Alinex\Code;

/**
 * Exception if assert call failed.
 *
 * Additional information in this exceptions are the code which could not be
 * asserted and if found the comment line above the assertion as description.
 */
class AssertException extends \Exception
{
    /**
     * Filename that the error was raised in
     * @var string
     */
    private $_file;

    /**
     * Line number the error was raised at
     * @var integer
     */
    private $_line;

    /**
     * Code run in assert function call
     * @var string
     */
    private $_code;

    /**
     * Short description if given
     * @var string
     */
    private $_desc;

    /**
     * Create new Exception.
     * @param string $message abstract message
     * @param string $file Filename that the error was raised in
     * @param int    $line Line number the error was raised at
     * @param string $code ode run in assert function call
     * @param string $desc Short description if given
     */
    function __construct($message, $file, $line, $code, $desc)
    {
        parent::__construct($message);
        $this->_file = $file;
        $this->_line = $line;
        $this->_code = $code;
        $this->_desc = $desc;
    }

}