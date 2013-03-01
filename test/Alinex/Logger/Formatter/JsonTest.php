<?php

namespace Alinex\Logger\Formatter;

use Alinex\Logger;

class JsonTest extends \PHPUnit_Framework_TestCase
{

    function testInitial()
    {
        $message = new Logger\Message(
            Logger::ALERT, 'This is a Test.'
        );
        $message->data['time'] = array('sec' => '1362084595', 'msec' => '834');
        $formatter = new Json();
        $this->assertTrue($formatter->format($message));
        $this->assertEquals(
            '{"time":{"sec":"1362084595","msec":"834"},"level":{"num":1,"name":"Alert"},"message":"This is a Test."}',
            $message->formatted
        );
    }

    function testContext()
    {
        $message = new Logger\Message(
            Logger::ALERT, 'This is a Test.', array(1 => 'one')
        );
        $message->data['time'] = array('sec' => '1362084595', 'msec' => '834');
        $formatter = new Json();
        $this->assertTrue($formatter->format($message));
        $this->assertEquals(
            '{"time":{"sec":"1362084595","msec":"834"},"level":{"num":1,"name":"Alert"},"message":"This is a Test.","context":{"1":"one"}}',
            $message->formatted
        );
    }

    function testData()
    {
        $message = new Logger\Message(
            Logger::ALERT, 'This is a Test.', array(1 => 'one')
        );
        $message->data['time'] = array('sec' => '1362084595', 'msec' => '834');
        $message->data['OS'] = 'unix';
        $formatter = new Json();
        $this->assertTrue($formatter->format($message));
        $this->assertEquals(
            '{"time":{"sec":"1362084595","msec":"834"},"level":{"num":1,"name":"Alert"},"message":"This is a Test.","context":{"1":"one"},"OS":"unix"}',
            $message->formatted
        );
    }
}
