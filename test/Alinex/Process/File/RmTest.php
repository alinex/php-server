<?php

namespace Alinex\Proc\File;

class RmTest extends \PHPUnit_Framework_TestCase
{

    function testInitial()
    {
        $pr = new Rm();
        $this->assertEmpty($pr->getOutput());
    }

    function XXXXtestSimple()
    {
        $pr = new Rm(__DIR__);
        $this->assertEquals(1, count($pr->getFiles()));
#        error_log(print_r($pr->getFiles(), 1));
#        error_log(print_r($pr->getMeta(), 1));
    }


}
