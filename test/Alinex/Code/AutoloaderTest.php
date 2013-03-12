<?php

namespace Alinex\Code;

class AutoloaderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Autoloader
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = Autoloader::getInstance();
    }

    function testGetInitial()
    {
        $this->assertCount(0, $this->object->getPrefixes());
        $this->assertCount(0, $this->object->getFallbackDirs());
        $this->assertCount(0, $this->object->getClassMap());
        $this->assertFalse($this->object->getUseIncludePath());
    }

    function testSetUseIncludePath()
    {
        $this->object->setUseIncludePath(true);
        $this->assertTrue($this->object->getUseIncludePath(), 'after set to true');
        $this->object->setUseIncludePath(false);
        $this->assertFalse($this->object->getUseIncludePath(), 'after set to 0');
    }

    function testAddClassMap()
    {
        $this->object->addClassMap(array('class1' => 'file1'));
        $this->assertCount(1, $this->object->getClassMap());
        $this->object->addClassMap(array('class2' => 'file2'));
        $this->assertCount(2, $this->object->getClassMap());
        $this->object->addClassMap(array('class2' => 'file2', 'class3' => 'file3'));
        $this->assertCount(3, $this->object->getClassMap());
    }

    function testAdd()
    {
        $this->object->add('x1', 'dir1a');
        $this->assertCount(1, $this->object->getPrefixes());
        $this->object->add('x2', array('dir2a','dir2b'));
        $this->assertCount(2, $this->object->getPrefixes());
        $this->object->add('x1', 'dir1b');
        $this->assertCount(2, $this->object->getPrefixes());

        $this->object->add(null, 'fallback');
        $this->assertCount(1, $this->object->getFallbackDirs());
    }

    function testRegister()
    {
        $this->object->register();
        $this->object->unregister();
    }

    function testLoadClass()
    {
        $this->object->add('Alinex', __DIR__.'/../../../source');
        $this->object->add('Xxx', 'dir1');
        $this->object->add(null, 'fallback');
        $this->object->setUseIncludePath(true);
        $this->assertTrue($this->object->loadClass('Alinex\Util\ArrayStructure'));
        $this->setExpectedException('Exception');
        $this->assertFalse(class_exists('not_existing_class'));
        $this->object->loadClass('not_existing_class');
    }

    function testLoadClass2()
    {
        $this->object->add('Alinex', __DIR__.'/../../../source');
        $this->object->add('Xxx', 'dir1');
        $this->object->add(null, 'fallback');
        $this->setExpectedException('Exception');
        $this->assertFalse(class_exists('\\Xxx\\not_existing_class'));
        $this->assertNull($this->object->loadClass('\\Xxx\\not_existing_class'));
    }
}
