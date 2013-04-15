<?php

namespace Alinex\Logger\Formatter;

use Alinex\Logger;

class ArrayStructureTest extends \PHPUnit_Framework_TestCase
{

    function testInitial()
    {
        $message = new Logger\Message(
            $this, Logger::ALERT, 'This is a Test.'
        );
        $formatter = new ArrayStructure();
        $this->assertTrue(
            $formatter->format($message)
        );
        $this->assertEquals(
            array(
                'time' => $message->data['time'],
                'level' => array('num' => 1, 'name' => 'Alert'),
                'message' => 'This is a Test.',
            ),
            $message->formatted
        );
    }

    function testContext()
    {
        $message = new Logger\Message(
            $this, Logger::ALERT, 'This is a Test.', array(1 => 'one')
        );
        $formatter = new ArrayStructure();
        $this->assertTrue(
            $formatter->format($message)
        );
        $this->assertEquals(
            array(
                'time' => $message->data['time'],
                'level' => array('num' => 1, 'name' => 'Alert'),
                'message' => 'This is a Test.',
                1 => 'one'
            ),
            $message->formatted
        );
    }

    function testData()
    {
        $message = new Logger\Message(
            $this, Logger::ALERT, 'This is a Test.', array(1 => 'one')
        );
        $message->data['os'] = 'unix';
        $formatter = new ArrayStructure();
        $this->assertTrue(
            $formatter->format($message)
        );
        $this->assertEquals(
            array(
                'time' => $message->data['time'],
                'level' => array('num' => 1, 'name' => 'Alert'),
                'message' => 'This is a Test.',
                'os' => 'unix',
                1 => 'one'
            ),
            $message->formatted
        );
    }
}
