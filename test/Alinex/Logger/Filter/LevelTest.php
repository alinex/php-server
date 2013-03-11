<?php

namespace Alinex\Logger\Filter;

use Alinex\Logger;

class LevelTest extends \PHPUnit_Framework_TestCase
{

    function testInitial()
    {
        $message = new Logger\Message(
            Logger::ALERT, 'This is a Test'
        );
        $filter = new Level();
        $this->assertFalse($filter->check($message));
    }

    /**
     * @depends testInitial
     */
    function testLevels()
    {
        $message = new Logger\Message(
            Logger::ALERT, 'This is a Test'
        );
        $filter = new Level();
        $filter->setMinimum(Logger::WARNING);
        $this->assertTrue($filter->check($message));
        $filter->disable(Logger::ALERT);
        $this->assertFalse($filter->check($message));
        $filter->enable(Logger::ALERT);
        $this->assertTrue($filter->check($message));
        $filter->setMinimum(Logger::EMERGENCY);
        $this->assertFalse($filter->check($message));
        $filter->setMinimum(Logger::ALERT);
        $this->assertTrue($filter->check($message));
    }

}
