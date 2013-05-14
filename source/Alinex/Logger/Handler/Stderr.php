<?php
/**
 * @file
 * Logging to stderr which may be the terminal or apache error log.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Logger\Handler;

/**
 * Logging to stderr which may be the terminal or apache error log.
 *
 * By default this will log multiline entries with trace for debugging using
 * Alinex\Logger\Formatter\Text
 */
class Stderr extends Stream
{
    /**
     * Initialize the stream.
     */
    function __construct()
    {
        parent::__construct('php://stderr');
        // add code output
        $this->setFormatter(new \Alinex\Logger\Formatter\Text());
        $this->addProvider('Code'); // needed to get the information
    }
}