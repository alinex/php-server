<?php
/**
 * @file
 * Interface between the doxygen logger and the alinex logger.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de
 */

namespace Alinex\DB;

/**
 * Interface between the doxygen logger and the alinex logger.
 *
 * The log message will contain the complete sql statement send as debug level
 * log entry. Timing and all the parameters are added, too.
 *
 * @note The category \c sql will be used with proiority \c info
 *
 */
class SQLLogger implements \Doctrine\DBAL\Logging\SQLLogger
{
    /**
     * Time when the query was started.
     * @var float
     */
    private $_start = null;

    /**
     * SQL query which was executed.
     * @var string
     */
    private $_sql = null;

    /**
     * List of parameters to query.
     * @var array
     */
    private $_params = null;

    /**
     * Types of query parameters.
     * @var array
     */
    private $_types = null;

    /**
     * Stores the sql query for later logging.
     *
     * @param string $sql The SQL to be executed.
     * @param array $params The SQL parameters.
     * @param array $types The SQL parameter types.
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        $this->_sql = $sql;
        $this->_params = $params;
        $this->_types = $types;
        $this->_start = microtime(true);
    }

    /**
     * Logs the sql query with timing information.
     */
    public function stopQuery()
    {
        \Alinex\Logger::getInstance()->debug(
            $this->_sql,
            array(
                'timing' => microtime(true) - $this->_start,
                'params' => $this->_params,
                'types' => $this->_types
            )
        );
    }

}