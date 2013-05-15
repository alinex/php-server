<?php

namespace Alinex\Logger\Formatter;

use Alinex\Logger;

class LineTest extends \PHPUnit_Framework_TestCase
{

    function testInitial()
    {
        $message = new Logger\Message(
            $this, Logger::ALERT, 'This is a Test'
        );
        $message->data['time'] = array('sec' => 1362084595, 'msec' => 834);
        $formatter = new Line();
        $this->assertTrue($formatter->format($message));
        $this->assertEquals(
            '2013-02-28T21:49:55+0100 ALERT: This is a Test',
            $message->formatted
        );
    }

    function testFormat()
    {
        $message = new Logger\Message(
            $this, Logger::ALERT, 'This is a Test'
        );
        $message->data['time'] = array('sec' => 1362084595, 'msec' => 834);
        $formatter = new Line();
        $formatter->formatString = '{message} on {time.sec|date l}.';
        $this->assertTrue($formatter->format($message));
        $this->assertEquals(
            'This is a Test on Thursday.',
            $message->formatted
        );
    }
}
