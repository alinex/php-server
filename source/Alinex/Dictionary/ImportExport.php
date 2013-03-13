<?php
/**
 * @file
 * Import and export of registry values.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Dictionary;

use Alinex\Validator;
use Alinex\Util\String;

/**
 * Import and export of registry values.
 *
 * The transfer classes enable the export and import into different external
 * file types or other systems:
 * - ImportExport\IniFile
 * - ImportExport\JsonFile
 * - ImportExport\PhpFile
 * - ImportExport\YamlFile
 * - and more to come
 *
 * You may also use the easy ImportExport\Autodetect class with it's two static
 * import and export methods.
 */
abstract class ImportExport
{
    /**
     * Create a new import export instance.
     *
     * @param Engine $data Dictionary class to import to or export from
     */
    function __construct(Engine $data = null)
    {
        if (isset($data))
            $this->setDictionary($data);
    }

    /**
     * Add a storage engine
     *
     * @param Engine $data Dictionary class to import to or export from
     */
    function setDictionary(Engine $data)
    {
        $this->_data = $data;
    }

    /**
     * Check if a storage engine is added.
     *
     * @return bool true on success
     * @throws \UnderflowException if no storage engine is set
     */
    protected function check()
    {
        if (!isset($this->_data))
            throw new \UnderflowException(
                tr(__NAMESPACE__, 'No storage engine connected')
            );
        return true;
    }

    /**
     * Dictionary instance
     *
     * This have to be set in __construct().
	 * @var Engine
	 */
    protected $_data = null;

    /**
	 * Export/import only subgroup
     *
     * Set this value using setGroup()
	 * @var string
	 */
    protected $_group = null;

    /**
	 * Specify a subgroup for export/import
	 *
     * With group the prefix will be removed in the resulting data, use
     * setFilter() instead to keep the full key.
     *
	 * @param string $group prefix for group
     * @see setFilter()
	 */
    function setGroup($group)
    {
        assert(is_string($group));

        $this->_group = $group;
    }

    /**
	 * Filter for import/export entries
     *
     * Set this value using setFilter()
	 * @var string
	 */
    protected $_filter = null;

    /**
	 * Set an filter to only import/export specific entries
	 *
	 * @param string $filter content phrase for entries
     * @see setGroup()
	 */
    function setFilter($filter)
    {
        assert(is_string($filter));

        $this->_filter = $filter;
    }

    /**
     * Filter data entries according to filter value
     *
     * @param array $data list of entries
     * @return array filtered subset of entries
     *
     * @see $_filter
     */
    private function filter($data)
    {
        // filter values
        if (isset($this->_filter)) {
            foreach (array_keys($data) as $key) {
                if (!String::startsWith($key, $this->_filter))
                    unset($data[$key]);
            }
        }
        return $data;
    }

    /**
     * Comment callback function to get key comments from.
     * @var function
     */
    protected $_commentCallback = null;

    /**
     * Set a method to get key comments.
     *
     * This is used in Registry only.
     *
     * @param callback $callback method to get key comments
     */
    function setCommentCallback($callback)
    {
        $callback = Validator\Code::callable(
            $callback, 'callback', array('relative' => __CLASS__)
        );
        $this->_commentCallback = $callback;
    }

    /**
     * Get a list of values (using group/filkter settings)
     * @return array list of values
     */
    protected function getValues()
    {
        if (!$this->_data)
            return array();
        // read values from group or all
        $data = $this->_data->groupGet(
            isset($this->_group) ? $this->_group : ''
        );
        // filter values
        $list = $this->filter($data);
        ksort($list);
        return $list;
    }

    /**
     * Set the given values (using group/filter settings)
     * @param array $data list of values
     */
    protected function setValues(array $data)
    {
        if (!$this->_data)
            return;
        // set values using group or all
        $this->_data->groupSet(
            isset($this->_group) ? $this->_group : '',
            $this->filter($data) // use filtered data
        );
    }

    /**
     * Header comment for export.
     * @var string
     */
    protected $_header = '';

    /**
     * Add a header comment for export.
     * @param string $text information to add as comment
     */
    function addHeader($text)
    {
        assert(is_string($text) && strlen($text));

        $this->_header .= $text.PHP_EOL;
    }

    /**
     * Import registry entries from storage
     *
     * @return bool TRUE on success
     * @throws ImportExport\Exception if storage can't be used
     */
    abstract function import();

    /**
     * Export registry entries to storage
     *
     * @return bool TRUE on success
     * @throws ImportExport\Exception if storage can't be used
     */
    abstract function export();

}
