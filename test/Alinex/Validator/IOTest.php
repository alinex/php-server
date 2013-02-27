<?php

namespace Alinex\Validator;

class IOTest extends \PHPUnit_Framework_TestCase
{
    static $testdir = null;

    static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        $value = __DIR__.'/../../data';
        /* replace '//' or '/./' or '/foo/../' with '/' */
        $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
        for ($n=1; $n>0; $value=preg_replace($re, '/', $value, -1, $n)) {
        }
        self::$testdir = $value;
    }

    function testPath()
    {
        $this->assertTrue(is_string(IO::pathDescription()));
        $this->assertEquals(self::$testdir, IO::path(self::$testdir, 'test directory'));
        $this->setExpectedException('Alinex\Validator\Exception');
        IO::path(5, 'incorect type');
    }

    function testPathRelative()
    {
        $this->assertTrue(is_string(IO::pathDescription(array('disallowRelative' => true))));
        $this->assertEquals('filetest.txt', IO::path('filetest.txt', 'test directory'));
        $this->assertEquals('./filetest.txt', IO::path('./filetest.txt', 'test directory'));
        $this->assertEquals('./filetest.txt', IO::path('./filetest.txt', 'test directory', array('disallowRelative' => false)));
        $this->setExpectedException('Alinex\Validator\Exception');
        IO::path('filetest.txt', 'incorect type', array('disallowRelative' => true));
    }

    function testPathAbsolute()
    {
        $this->assertTrue(is_string(IO::pathDescription(array('disallowAbsolute' => true))));
        $this->assertEquals('/filetest.txt', IO::path('/filetest.txt', 'test directory'));
        $this->setExpectedException('Alinex\Validator\Exception');
        IO::path('/filetest.txt', 'incorect type', array('disallowAbsolute' => true));
    }

    function testPathBackreferences()
    {
        $this->assertTrue(is_string(IO::pathDescription(array('allowBackreferences' => true))));
        $this->assertEquals('path/../filetest.txt', IO::path('path/../filetest.txt', 'test directory', array('allowBackreferences' => true)));
        $this->assertEquals('../filetest.txt', IO::path('../filetest.txt', 'test directory', array('allowBackreferences' => true)));
        $this->setExpectedException('Alinex\Validator\Exception');
        IO::path('path/../filetest.txt', 'incorect type');
    }

    function testPathResolve()
    {
        $this->assertTrue(is_string(IO::pathDescription(array('base' => '/var', 'makeAbsolute' => true, 'resolve' => true))));
        $this->assertEquals('this/filetest.txt', IO::path('this/test/../filetest.txt', 'test directory', array('resolve' => true)));
        $this->assertEquals('filetest.txt', IO::path('test/../filetest.txt', 'test directory', array('resolve' => true)));
        $this->assertEquals('path/filetest.txt', IO::path('filetest.txt', 'test directory', array('base' => 'path', 'makeAbsolute' => true)));
        $this->assertEquals('filetest.txt', IO::path('../filetest.txt', 'test directory', array('base' => 'path', 'makeAbsolute' => true, 'resolve' => true)));
        $this->setExpectedException('Alinex\Validator\Exception');
        IO::path('path/../filetest.txt', 'incorect type');
    }

    function testPathExists()
    {
        $options = array('exists' => true);
        $this->assertTrue(is_string(IO::pathDescription($options)));
        $this->assertEquals(self::$testdir, IO::path(self::$testdir, 'test directory', $options));
        $this->setExpectedException('Alinex\Validator\Exception');
        IO::path('/this/file/should/not/exist/on/this/system', 'incorect type', $options);
    }

    function testPathReadable()
    {
        $options = array('readable' => true);
        $this->assertTrue(is_string(IO::pathDescription($options)));
        $this->assertEquals(self::$testdir, IO::path(self::$testdir, 'test directory', $options));
        $this->setExpectedException('Alinex\Validator\Exception');
        IO::path(self::$testdir.'/not/exist/on/this/system', 'incorect type', $options);
    }

    function testPathWritable()
    {
        $options = array('writable' => true);
        $this->assertTrue(is_string(IO::pathDescription($options)));
        $this->assertEquals(self::$testdir, IO::path(self::$testdir, 'test directory', $options));
        $this->setExpectedException('Alinex\Validator\Exception');
        IO::path(self::$testdir.'/not/exist/on/this/system', 'incorect type', $options);
    }

    function testPathParentExists()
    {
        $options = array('parentExists' => true);
        $this->assertTrue(is_string(IO::pathDescription($options)));
        $this->assertEquals(self::$testdir, IO::path(self::$testdir, 'test directory', $options));
        $this->assertEquals(self::$testdir.'/not-existing-subdir', IO::path(self::$testdir.'/not-existing-subdir', 'test directory', $options));
        $this->setExpectedException('Alinex\Validator\Exception');
        IO::path(self::$testdir.'/not/exist/on/this/system', 'incorect type', $options);
    }

    function testPathFiletypeDir()
    {
        $options = array('filetype' => 'dir');
        $this->assertTrue(is_string(IO::pathDescription($options)));
        $this->assertEquals(self::$testdir, IO::path(self::$testdir, 'test directory', $options));
        $this->setExpectedException('Alinex\Validator\Exception');
        IO::path(self::$testdir.'/filetest.txt', 'incorect filetype', $options);
    }

    function testPathFiletypeFile()
    {
        $options = array('filetype' => 'file');
        $this->assertTrue(is_string(IO::pathDescription($options)));
        $this->assertEquals(self::$testdir.'/filetest.txt', IO::path(self::$testdir.'/filetest.txt', 'test directory', $options));
        $this->setExpectedException('Alinex\Validator\Exception');
        IO::path(self::$testdir, 'incorect filetype', $options);
    }

    function testPathFiletypeLink()
    {
        $options = array('filetype' => 'link');
        $this->assertTrue(is_string(IO::pathDescription($options)));
        $this->assertEquals(self::$testdir.'/linktest.txt', IO::path(self::$testdir.'/linktest.txt', 'test directory', $options));
        $this->setExpectedException('Alinex\Validator\Exception');
        IO::path(self::$testdir, 'incorect filetype', $options);
    }

    function testPathMimetype()
    {
        $options = array('mimetype' => 'text/plain');
        $this->assertTrue(is_string(IO::pathDescription($options)));
        $this->assertEquals(self::$testdir.'/filetest.txt', IO::path(self::$testdir.'/filetest.txt', 'test directory', $options));
        $this->setExpectedException('Alinex\Validator\Exception');
        IO::path(self::$testdir.'/test.jpg', 'incorect mimetype', $options);
    }

}
