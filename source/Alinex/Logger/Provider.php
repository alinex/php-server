<?php
/**
 * @file
 * Abstract provider to get additional information for logging.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Logger;

/**
 * Abstract provider to get additional information for logging.
 */
abstract class Provider
{
    /**
     * Get the specific data.
     */
    abstract function getData();
}