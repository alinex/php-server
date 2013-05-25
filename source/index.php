<h1>Testsystem</h1>
<?php
/**
 * @file
 * RPC Server start.
 *
 * This file will only invoke the rpc server to handle incomming requests. The
 * further processing of each request will be
 * handled completely by the RPC server. Therefore the incomming data has to
 * correspond to at least one of the supported
 * RPC server schemas. The result will also adopt the requesting format.
 *
 * On any fatal errors like misconfigured system, the RPC will not start and
 * won't send any responses but log
 * the reasons as fatal message in category 'core'.
 *
 * @todo rewrite code
 * @todo document overview of rpc possibilities
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref License.
 * @see       http://alinex.de
 */

/**
 * Configuration file to use.
 */
define('CONFIGFILE', 'file://'.__DIR__.'/config.ini');

/**
 * Is the system productively used or for test and debugging.
 */
define('PRODUCTIVE', false);

use Alinex\Dictionary\ImportExport;

include_once 'bootstrap.php';

// init session handling
Alinex\Dictionary\Session::getInstance()->start();

echo('<pre>'.Alinex\Util\String::wordbreak(
        \Alinex\DB\Validator::connectionDescription()
));
exit;

$registry = Alinex\Dictionary\Registry::getInstance();
// add database connection
if (!$registry->has(Alinex\DB\Connection::REGISTRY_BASE.'default'))
    $registry->set(
        Alinex\DB\Connection::REGISTRY_BASE.'default',
        array(
            'dbname' => 'a3',
            'user' => 'alinex',
            'password' => 'test',
            'host' => 'localhost',
            'driver' => 'pdo_mysql',
        )
    );
// initial creation of config file
if (!file_exists(CONFIGFILE)) {
    try {
        \Alinex\DB\Connection::get();
    } catch (\Exception $ex) {}
    $registry->export(
        ImportExport\Autodetect::findInstance(CONFIGFILE)
    );
}


$test = new Test();
$test->setName('alex 2');

$entityManager = Alinex\DB\EntityManager::getInstance();
$entityManager->persist($test);
$entityManager->flush();
echo "Created Test with ID " . $test->getId() . "\n";
// TEST STUFF

echo('kkkk');

#$conn = Alinex\DB\Connection::get();
#$sql = "SELECT * FROM test";
#$stmt = $conn->query($sql);
#while ($row = $stmt->fetch()) {
#    echo $row['name'];
#}

?>
<h1>Ende</h1>