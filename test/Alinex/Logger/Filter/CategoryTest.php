<?php

namespace Alinex\Logger\Filter;

use Alinex\Logger;

class CategoryTest extends \PHPUnit_Framework_TestCase
{

    function testInitial()
    {
        $message = new Logger\Message(
            $this, Logger::ALERT, 'This is a Test'
        );
        $filter = new Category();
        $this->assertFalse($filter->check($message));
        $message->data['code'] = array('class' => 'Alinex\Test\Namespace\Class');
        $this->assertFalse($filter->check($message));
    }

    /**
     * @depends testInitial
     */
    function testCategories()
    {
        $message = new Logger\Message(
            $this, Logger::ALERT, 'This is a Test'
        );
        $message->data['code'] = array('class' => 'Alinex\Test\Namespace\Class');
        $filter = new Category();
        $filter->enable('Alinex');
        $this->assertTrue($filter->check($message));
        $filter->disable('Alinex');
        $this->assertFalse($filter->check($message));
        $filter->enable('Alinex');
        $this->assertTrue($filter->check($message));
        $filter->disable('Alinex\Test');
        $this->assertFalse($filter->check($message));
        $filter->enable('Alinex\Test\Namespace');
        $this->assertTrue($filter->check($message));
        $filter->disable('Alinex\Test\Namespace');
        $this->assertFalse($filter->check($message));
        $filter->enable('Alinex\Test\Namespace\Class');
        $this->assertTrue($filter->check($message));
    }

}
