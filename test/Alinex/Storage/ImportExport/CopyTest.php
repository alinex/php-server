<?php

namespace Alinex\Storage\ImportExport;

use Alinex\Storage\Engine;

/**
 * ErrorHandler test case
 */
class CopyTest extends \PHPUnit_Framework_TestCase
{
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
        // create destination
        $destination = Engine\ArrayList::getInstance('dest-');
        $destination->clear();
        // export
        $exporter = new Copy($storage);
        $exporter->setDestination($destination);
        $exporter->export();
        // check destination
        $this->assertTrue($destination->get('boolean'));
        $this->assertTrue($destination->get('integer') === 5);
        $this->assertTrue($destination->get('float') === 5.3);
        $this->assertTrue($destination->get('text.simple') === "short text");
        $this->assertTrue($destination->get('text.empty') === "");
        $this->assertEquals("line1\nline2\nline3", $destination->get('text.multiline'));
        $this->assertEquals(array(1,2,3), $destination->get('array.simple'));
        $this->assertEquals(array(array(1,2,3),array(4,5,6)), $destination->get('array.array'));
        $this->assertEquals(array('eins' => 1, 'zwei' => 2, 'drei' => 3), $destination->get('array.hash'));
    }

    function testImport()
    {
        // setup storage
        $storage = Engine\ArrayList::getInstance();
        $storage->clear();
        // create destination
        $destination = Engine\ArrayList::getInstance('dest-');
        $destination->clear();
        // fill up registry
        $destination->set('boolean', true);
        $destination->set('integer', 5);
        $destination->set('float', 5.3);
        $destination->set('text.simple', "short text");
        $destination->set('text.multiline', "line1\nline2\nline3");
        $destination->set('text.empty', "");
        $destination->set('array.simple', array(1,2,3));
        $destination->set('array.hash', array('eins' => 1, 'zwei' => 2, 'drei' => 3));
        $destination->set('array.array', array(array(1,2,3),array(4,5,6)));
        // export
        $importer = new Copy($storage);
        $importer->setDestination($destination);
        $importer->import();
        // check destination
        $this->assertTrue($storage->get('boolean'));
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
