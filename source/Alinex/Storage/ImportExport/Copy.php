<?php
/**
 * @file
 * Copy entries between two registries.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Storage\ImportExport;

use Alinex\Storage;

/**
 * Copy entries between two registries.
 */
class Copy extends \Alinex\Storage\ImportExport
{
    /**
     * Create a new transfer.
     *
     * @param Storage\Engine $storage registry class to import from or export to
     * @param Storage\Engine $destination registry class to export to or import from
     * @throws Exception if storage can't be used
     */
    function __construct(Storage\Engine $storage, Storage\Engine $destination = null)
    {
        parent::__construct($storage);
        $this->_destination = $destination;
    }

    /**
     * INI File to read from or write to.
     * @var Storage\Engine
     */
    protected $_destination = null;

    /**
     * Set the file to read from or write to.
     * @param Storage\Engine $destination registry class to export to or import from
     * @return bool true on success
     */
    function setDestination(Storage\Engine $destination)
    {
        $this->_destination = $destination;
        return true;
    }

    /**
     * Import registry entries from registry 2
     *
     * @return bool TRUE on success
     */
    function import()
    {
        // read from file
        return $this->setValues($this->_destination->groupGet(''));
    }

    /**
     * Export registry entries to registry 2
     *
     * @return bool TRUE on success
     */
    function export()
    {
        return $this->_destination->groupSet('', $this->getValues());
    }
}
