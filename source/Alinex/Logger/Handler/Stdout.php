<?php
/**
 * @file
 * Logging to stdout which may be the terminal or apache log.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Logger\Handler;

/**
 * Logging to stdout which may be the terminal or apache log.
 */
class Stdout extends Stream
{
    /**
     * Initialize the stream.
     */
    function __construct()
    {
        parent::__construct('php://stdout');
    }
}