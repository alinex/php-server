<?php

namespace Alinex\Dictionary\Engine;

use Alinex\Dictionary\Engine;

/**
 * ErrorHandler test case
 */
class ArrayListTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = ArrayList::getInstance();
        $this->object->clear();
    }

    function testInitial()
    {
        $this->assertFalse($this->object->has('not_existing'));
        $this->assertNull($this->object->get('not_existing'));
    }

    /**
     * @depends testInitial
     */
    function testSetGet()
    {
        $this->assertEquals(123, $this->object->set('normalValue', 123));
        $this->assertEquals(123, $this->object->get('normalValue'));
        $this->assertTrue($this->object->has('normalValue'));
        $this->assertTrue($this->object->remove('normalValue'));
        $this->assertFalse($this->object->has('normalValue'));
        $this->assertFalse($this->object->remove('normalValue'));
        $this->assertNull($this->object->get('normalValue'));
        $this->assertEquals(123, $this->object->set('normalValue', 123));
        $this->assertTrue($this->object->has('normalValue'));
        $this->assertNull($this->object->set('normalValue'));
        $this->assertFalse($this->object->has('normalValue'));
    }

    /**
     * @depends testSetGet
     */
    function testContext()
    {
        $this->assertEquals(123, $this->object->set('normalValue', 123));
        $this->assertTrue($this->object->has('normalValue'));
        $object2 = ArrayList::getInstance('n2-');
        $object2->clear();
        $this->assertFalse($object2->has('normalValue'));
        $this->assertEquals(321, $object2->set('specialValue', 321));
        $this->assertTrue($object2->has('specialValue'));
        $this->assertFalse($this->object->has('specialValue'));
    }

    /**
     * @depends testSetGet
     */
    function testArrayAccess()
    {
        $this->assertEquals(123, $this->object['normalValue'] = 123);
        $this->assertEquals(123, $this->object['normalValue']);
        $this->assertTrue(isset($this->object['normalValue']));
        unset($this->object['normalValue']);
        $this->assertFalse(isset($this->object['normalValue']));
        $this->assertNull($this->object['normalValue']);
    }

    /**
     * @depends testSetGet
     */
    function testGroupSetGet()
    {
        $this->assertTrue($this->object->groupSet('group_', array('v1' => 123, 'v2' => 234, 'v3' => 345)));
        $this->assertTrue($this->object->has('group_v1'));
        $this->assertTrue($this->object->has('group_v2'));
        $this->assertTrue($this->object->has('group_v3'));
        $this->assertEquals(3, count($this->object->groupGet('group_')));
        $this->assertTrue($this->object->groupClear('group_'));
        $this->assertCount(0, $this->object->keys());
        $this->assertFalse($this->object->groupClear('group_'));
        $this->assertEquals(0, count($this->object->groupGet('group_')));
    }

    function testKeysClear()
    {
        $this->object->set('firstValue', 1);
        $this->object->set('secondValue', 2);
        $this->assertEquals(2, $this->object->count());
        $this->assertCount(2, $this->object->keys());
        $this->assertTrue($this->object->clear());
        $this->assertCount(0, $this->object->keys());
        $this->assertFalse($this->object->clear());
    }

    function testAllow()
    {
        $this->assertCount(1, $this->object->limitSize());
        $this->assertCount(0, $this->object->limitSize(0, 1));
        $this->assertCount(1, $this->object->limitSize(10, 0.5));
        $this->assertEquals(array(10 => 0.5), $this->object->limitSize());
        $this->assertEquals(1, $this->object->allow(5));
        $this->assertEquals(0.5, $this->object->allow('a too long value'));
        $this->assertEquals(0, $this->object->allow(5, Engine::SCOPE_SESSION));
        $this->assertEquals(1, $this->object->allow(5, Engine::SCOPE_LOCAL));
        $this->assertEquals(0.8, $this->object->allow(5, Engine::SCOPE_GLOBAL));
        $this->assertEquals(1, $this->object->allow(5, Engine::PERSISTENCE_SHORT));
        $this->assertEquals(0.5, $this->object->allow(5, Engine::PERSISTENCE_MEDIUM));
        $this->assertEquals(0.2, $this->object->allow(5, Engine::PERSISTENCE_LONG));
        $this->assertEquals(0.5, $this->object->allow(5, Engine::PERFORMANCE_LOW));
        $this->assertEquals(0.8, $this->object->allow(5, Engine::PERFORMANCE_MEDIUM));
        $this->assertEquals(1, $this->object->allow(5, Engine::PERFORMANCE_HIGH));
        $this->assertEquals(0.125, $this->object->allow('a too long value', Engine::PERSISTENCE_MEDIUM | Engine::PERFORMANCE_LOW));
    }
}
