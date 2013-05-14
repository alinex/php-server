<?php

namespace Alinex\Code;

/**
 * ErrorHandler test case
 */
class ErrorHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test ErrorHandler handles notices
     */
    public function testErrorHandlerCaptureNotice()
    {
        ini_set('xdebug.scream', TRUE); // to also test the warning
        ErrorHandler::register();
        $array = array('foo' => 'bar');
        ErrorHandler::setExceptionLevel(E_ALL);
        $this->setExpectedException('\ErrorException', 'Undefined index: baz');
        $array['baz'];
    }

    /**
     * Test ErrorHandler handles warnings
     */
    public function testErrorHandlerCaptureWarning()
    {
        ErrorHandler::register();
        $this->setExpectedException('\ErrorException', 'array_merge(): Argument #2 is not an array');
        array_merge(array(), 'string'); // only notice

    }

    /**
     * Test ErrorHandler handles warnings
     */
    public function testErrorHandlerRespectsAtOperator()
    {
        ErrorHandler::register();
        ini_set('xdebug.scream', FALSE);

        @trigger_error('test', E_USER_NOTICE);
    }
}
