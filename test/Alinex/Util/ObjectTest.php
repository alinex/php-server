<?php

namespace Alinex\Util;

class ObjectTest extends \PHPUnit_Framework_TestCase
{

    public function testId()
    {
        $x = new \stdClass();
        $y = new \DOMElement('aaa');
        $z = clone $y;
        $this->assertNotEquals(Object::getID($x), Object::getID($y));
        $this->assertNotEquals(Object::getID($y), Object::getID($z));
    }

}
