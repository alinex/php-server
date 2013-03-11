<?php

namespace Alinex\Logger\Handler;

use Alinex\Logger;
use Alinex\Storage;

class StorageTest extends \PHPUnit_Framework_TestCase
{

    function testInitial()
    {
        $log = Storage\Engine\ArrayList::getInstance('testlog');
        $handler = new Logger\Handler\Storage($log);
        $message = new Logger\Message(
            Logger::ALERT, 'This is a Test'
        );
        $message->data['time'] = array('sec' => '1362084595', 'msec' => '834');
        $this->assertTrue($handler->log($message));
        $this->assertTrue($log->has('1362084595.834'));
        $result = $log->get('1362084595.834');
        $this->assertEquals('This is a Test',$result['message']);
    }

}
