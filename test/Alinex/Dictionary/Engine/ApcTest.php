<?php

namespace Alinex\Dictionary\Engine;

/**
 * ErrorHandler test case
 */
class ApcTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ArrayRegistry
     */
    protected $object;

    function setUp()
    {
        if (!Apc::isAvailable())
            $this->markTestSkipped('The APC extension have to be loaded.');
    }

    function testInitial()
    {
        $this->object = Apc::getInstance();
        $this->object->clear();
        $this->assertFalse($this->object->has('not_existing'));
        $this->assertNull($this->object->get('not_existing'));
    }

    /**
     * @depends testInitial
     */
    function testSetGet()
    {
        $this->object = Apc::getInstance();
        $this->object->clear();
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
        $this->object = Apc::getInstance();
        $this->object->clear();
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
        $this->object = Apc::getInstance();
        $this->object->clear();
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
        $this->object = Apc::getInstance();
        $this->object->clear();
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

    /**
     * @depends testSetGet
     */
    function testKeysClear()
    {
        $this->object = Apc::getInstance();
        $this->object->clear();
        $this->object->set('firstValue', 1);
        $this->object->set('secondValue', 2);
        $this->assertEquals(2, $this->object->count());
        $this->assertCount(2, $this->object->keys());
        $this->assertTrue($this->object->clear());
        $this->assertCount(0, $this->object->keys());
        $this->assertFalse($this->object->clear());
    }

    /**
     * @depends testSetGet
     */
    function testEditing()
    {
        $this->object = Apc::getInstance();
        $this->object->clear();
        $this->assertEquals(123, $this->object->set('normalValue', 123));
        $this->assertEquals(124, $this->object->inc('normalValue'));
        $this->assertEquals(130, $this->object->inc('normalValue',6));
        $this->assertEquals(129, $this->object->inc('normalValue',-1));
        $this->assertEquals(128, $this->object->dec('normalValue'));
        $this->assertEquals(120, $this->object->dec('normalValue', 8));
        $this->assertEquals(121, $this->object->dec('normalValue', -1));
        $this->assertEquals('a', $this->object->set('normalValue','a'));
        $this->assertEquals('ab', $this->object->append('normalValue','b'));
    }

    /**
     * @depends testSetGet
     */
    function testHash()
    {
        $this->object = Apc::getInstance();
        $this->object->clear();
        $this->assertFalse($this->object->hashHas('hash', 'element1'));
        $this->assertEquals(111, $this->object->hashSet('hash', 'element1', 111));
        $this->assertEquals(111, $this->object->hashGet('hash', 'element1'));
        $this->assertTrue($this->object->hashHas('hash', 'element1'));
        $this->assertEquals(array('element1' => 111), $this->object->get('hash'));
        $this->assertEquals(1, $this->object->hashCount('hash'));
        $this->assertTrue($this->object->hashRemove('hash', 'element1'));
        $this->assertFalse($this->object->hashHas('hash', 'element1'));
    }

    /**
     * @depends testSetGet
     */
    function testList()
    {
        $this->object = Apc::getInstance();
        $this->object->clear();
        $this->assertEquals(1, $this->object->listPush('list', 111));
        $this->assertEquals(2, $this->object->listPush('list', 222));
        $this->assertEquals(3, $this->object->listPush('list', 333));
        $this->assertEquals(333, $this->object->listPop('list'));
        $this->assertEquals(3, $this->object->listUnshift('list', 444));
        $this->assertEquals(444, $this->object->listShift('list'));
        $this->assertEquals(222, $this->object->listGet('list', 1));
        $this->assertEquals(555, $this->object->listSet('list', null, 555));
        $this->assertEquals(3, $this->object->listCount('list'));
    }


}
