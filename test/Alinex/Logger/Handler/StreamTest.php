<?php

namespace Alinex\Logger\Handler;

use Alinex\Logger;

class StreamTest extends \PHPUnit_Framework_TestCase
{

    function testInitial()
    {
        $log = $handle = fopen('php://memory', 'a+');
        $handler = new Stream($log);
        $message = new Logger\Message(
            Logger::ALERT, 'This is a Test'
        );
        $message->data['time'] = array('sec' => '1362084595', 'msec' => '834');
        $this->assertTrue($handler->log($message));
        fseek($log, 0);
        $this->assertEquals('2013-02-28T21:49:55+0100 ALERT: This is a Test.', fread($log, 100));
        fclose($log);
        $this->setExpectedException('Alinex\Validator\Exception');
        $handler->log($message);
    }

    function testOutput()
    {
        ob_start();
        $handler = new Stream('php://output');
        $message = new Logger\Message(
            Logger::ALERT, 'This is a Test'
        );
        $message->data['time'] = array('sec' => '1362084595', 'msec' => '834');
        $this->assertTrue($handler->log($message));
        $this->assertEquals('2013-02-28T21:49:55+0100 ALERT: This is a Test.', ob_get_contents());
        ob_end_clean();
    }

    
}
