<?php

namespace Alinex;

use Alinex\Validator;

class ValidatorTest extends \PHPUnit_Framework_TestCase
{
    function testCheck()
    {
        $this->assertEquals(5, Validator::check("5", 'test', 'Alinex\Validator\Type::integer'));
        $this->assertEquals(5, Validator::check("5", 'test', 'Type::integer'));
        $this->setExpectedException('Alinex\Validator\Exception');
        Validator::check("5.4", 'test', 'Type::integer');
    }

    function testIs()
    {
        $this->assertTrue(Validator::is("5", 'test', 'Alinex\Validator\Type::integer'));
        $this->assertTrue(Validator::is("5", 'test', 'Type::integer'));
        $this->assertFalse(Validator::is("5.4", 'test', 'Type::integer'));
    }

    function testDescribe()
    {
        $this->assertTrue(is_string(Validator::describe('test', 'Alinex\Validator\Type::integer')));
        $this->assertTrue(is_string(Validator::describe('test', 'Type::integer')));
    }

}
