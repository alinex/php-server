<?php

namespace Alinex\Storage\ImportExport;

use Alinex\Storage\Engine;

/**
 * ErrorHandler test case
 */
class YamlFileTest extends \PHPUnit_Framework_TestCase
{
    function setUp()
    {
        if (!extension_loaded('yaml'))
            $this->markTestSkipped('The yaml extension have to be loaded.');
    }
    
    function testExport()
    {
        // setup storage
        $storage = Engine\ArrayList::getInstance();
        $storage->clear();
        // fill up registry
        $storage->set('boolean', true);
        $storage->set('integer', 5);
        $storage->set('float', 5.3);
        $storage->set('text.simple', "short text");
        $storage->set('text.multiline', "line1\nline2\nline3");
        $storage->set('text.empty', "");
        $storage->set('array.simple', array(1,2,3));
        $storage->set('array.hash', array('eins' => 1, 'zwei' => 2, 'drei' => 3));
        $storage->set('array.array', array(array(1,2,3),array(4,5,6)));
        // export
        $file = __DIR__.'/../../../data/storage.yaml';
        $exporter = new YamlFile($storage);
        $exporter->setFile($file);
        $exporter->addHeader("Test of storage export/import");
        $exporter->export();
        // check file
        $file = __DIR__.'/not/existing/not-existing-registry.yaml';
        $exporter->setFile($file);
        $this->setExpectedException('Alinex\Storage\ImportExport\Exception');
        $exporter->export();
    }

    /**
     * @ depends testExport
     */
    function testImport()
    {
        // setup storage
        $storage = Engine\ArrayList::getInstance();
        $storage->clear();
        // import
        $file = __DIR__.'/../../../data/storage.yaml';
        $importer = new YamlFile($storage);
        $importer->setFile($file);
        $importer->import();
        // check registry
        $this->assertEquals(true, $storage->get('boolean')); // wrong datatype because of PHP lack
        $this->assertTrue($storage->get('integer') === 5);
        $this->assertTrue($storage->get('float') === 5.3);
        $this->assertTrue($storage->get('text.simple') === "short text");
        $this->assertTrue($storage->get('text.empty') === "");
        $this->assertEquals("line1\nline2\nline3", $storage->get('text.multiline'));
        $this->assertEquals(array(1,2,3), $storage->get('array.simple'));
        $this->assertEquals(array(array(1,2,3),array(4,5,6)), $storage->get('array.array'));
        $this->assertEquals(array('eins' => 1, 'zwei' => 2, 'drei' => 3), $storage->get('array.hash'));
    }

}
