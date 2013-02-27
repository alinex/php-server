<?php

namespace Alinex\Validator;

class ExceptionTest extends \PHPUnit_Framework_TestCase
{
    function testGetDetail()
    {
        try {
            Type::integer(5.4, 'test');
        } catch (Exception $ex) {
            $this->assertTrue(is_string($ex->getDetail()));
        }
    }

    function testCreateOuter()
    {
        try {
            Type::integer(5.4, 'test');
        } catch (Exception $ex) {
            $this->assertInstanceOf('\Alinex\Validator\Exception',
                $ex->createOuter('unknown'));
        }
    }
}
