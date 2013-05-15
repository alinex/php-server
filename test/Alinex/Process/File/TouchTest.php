<?php

namespace Alinex\Process\File;

class TouchTest extends \PHPUnit_Framework_TestCase
{
    const TESTDIR = '/../../../data/proc/';

    function testInitial()
    {
        $testdir = __DIR__.self::TESTDIR;
        // cleanup
        $pr = new Rm(
            array(
                $testdir.'touch1',
                $testdir.'touch2',
                $testdir.'touch3'
            )
        );
        $pr->exec();
        // run test
        $pr = new Touch($testdir.'touch1');
        $this->assertTrue($pr->isSuccess());
        $this->assertFileExists($testdir.'touch1');
        // time
        $pr = new Touch($testdir.'touch2');
        $pr->setTime(time()-6000);
        $this->assertTrue($pr->isSuccess());
        $this->assertFileExists($testdir.'touch2');
        // copy time
        $pr = new Touch($testdir.'touch3');
        $pr->copyTimeFrom($testdir.'touch2');
        $this->assertTrue($pr->isSuccess());
        $this->assertFileExists($testdir.'touch3');
        // cleanup
        $pr = new Rm(
            array(
                $testdir.'touch1',
                $testdir.'touch2',
                $testdir.'touch3'
            )
        );
        $pr->exec();
    }

    /**
     * @depends testInitial
     */
    function testMultiple()
    {
        $testdir = __DIR__.self::TESTDIR;
        $pr = new Touch(
            array($testdir.'touch4', $testdir.'touch5')
        );
        $this->assertTrue($pr->isSuccess());
        $this->assertFileExists($testdir.'touch4');
        $this->assertFileExists($testdir.'touch5');
        // cleanup
        $pr = new Rm(
            array($testdir.'touch4', $testdir.'touch5')
        );
        $pr->exec();
    }
}
