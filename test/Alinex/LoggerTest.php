<?php

namespace Alinex;

use Alinex\Logger;
    
class LoggerTest extends \PHPUnit_Framework_TestCase
{
    
    function testInitial()
    {
        $logger = Logger::getInstance('testlog');
        $log = Dictionary\Engine\ArrayList::getInstance('testlog');
        $this->assertEquals(1, $logger->handlerPush(
            new Logger\Handler\Dictionary($log)
        ));
        $this->assertEquals(1, $logger->alert('test alert'));
    }

}
