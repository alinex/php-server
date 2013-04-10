<?php
/**
 * @file
 * Put the log messages to the given stream.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Logger\Handler;

use Alinex\Logger\Handler;
use Alinex\Logger\Message;
use Alinex\Validator\IO;

/**
 * Put the log messages to the given stream.
 *
 * Can be used to store into php://stderr, remote and local files, etc.
 */
class Stream extends Handler
{
    /**
     * If this flag is set the stream will close and reopen after each message.
     *
     * This makes it possible to use global shared resources but is less
     * performant.
     *
     * @note It only is effective if resource is opened by handler itself, not
     * if a preopened resource handle was given.
     */
    const FLAG_CLOSE = 1;

    /**
     * Specification of stream to open.
     * @var string
     */
    private $_uri = null;

    /**
     * Stream context definition.
     * @var array
     */
    private $_context = null;

    /**
     * Opened stream resource handle.
     * @var resource
     */
    private $_stream = null;

    /**
     * Flags to use.
     * @var int
     */
    private $_flags = 0;

    /**
     * Initialize the stream.
     *
     * The stream may be selected in different ways:
     * - stream is an already opened strem handle
     *   therefore the FLAG_CLOSE will be ignored
     * - stream specifies a scheme like "scheme://..."\n
     *   it is assumed to be a URL and PHP will search for a protocol handler
     *   (also known as a wrapper) for that scheme. If no wrappers for that
     *   protocol are registered, PHP will emit a notice to help you track
     *   potential problems in your script and then continue as though filename
     *   specifies a regular file.
     * - stream references a local file\n
     *   then it will try to open a stream on that file. The file must be
     *   accessible to PHP, so you need to ensure that the file access
     *    permissions allow this access.
     * - if stream specifies a registered network protocol
     *   PHP will check to make sure that allow_url_fopen is enabled. If it is
     *   switched off, PHP will emit a warning and the fopen call will fail.
     *
     * The following protocols and wrappers will be supported:
     * - file:// — Accessing local filesystem
     * - http:// — Accessing HTTP(s) URLs
     * - ftp:// — Accessing FTP(s) URLs
     * - php:// — Accessing various I/O streams
     * - zlib:// — Compression Streams
     * - data:// — Data (RFC 2397)
     * - glob:// — Find pathnames matching pattern
     * - phar:// — PHP Archive
     * - ssh2:// — Secure Shell 2
     * - rar:// — RAR
     * - ogg:// — Audio streams
     * - expect:// — Process Interaction Streams
     *
     * Read more details at http://www.php.net/manual/en/wrappers.php
     * 
     * The stream will be opened for writing only.
     *
     * @param string $stream local file, stream wrapper or url specification
     * @param array $context set of parameters and wrapper specific options
     * which modify or enhance the behavior of a stream.
     * Read more at http://www.php.net/manual/en/function.stream-context-create.php
     * @param int $flags special options to use
     */
    function __construct($stream, array $context = null, $flags = null)
    {
        // $stream is text uri or real resource stream
        assert(is_string($stream) || is_resource($stream));
        assert(is_bool($flags));
        
        $this->_context = $context;
        $this->_flags = $flags;
        if (is_string($stream)) {
            $this->_uri = $stream;
        } else {
            // hopefully it is a stream
            $stream = IO::stream($stream, 'stream');
            $this->_stream = $stream;
            // never close if already opened
            $this->_flags = $this->_flags & ~ self::FLAG_CLOSE;
        }
        $this->_formatter = new \Alinex\Logger\Formatter\Line();
    }

    /**
     * This will open the given stream if not done.
     * @return resource strem to be used
     * @throws \Exception if stream could not be reopened or opened.
     * @throws \UnexpectedValueExceptionproblem opening stream
     */
    private function openStream()
    {
        if (isset($this->_stream)) {
            IO::stream($this->_stream, 'stream');
            return $this->_stream; // don't reopen it
        }
        if (!isset($this->_uri))
            throw new \Exception(
                tr(
                    __NAMESPACE__,
                    'Stream could not be opened or reopened'
                )
            );
        // create context
        if (isset($this->_context))
            $context = stream_context_create($context);
        // open handle
        $errorMessage = null;
        set_error_handler(
            function ($code, $msg) use (&$errorMessage)
            {
                $errorMessage = preg_replace('{^fopen\(.*?\): }', '', $msg);
            }
        );
        if (isset($context))
            $this->_stream = fopen($this->_uri, 'a', false, $context);
        else
            $this->_stream = fopen($this->_uri, 'a');            
        restore_error_handler();
        if (!is_resource($this->_stream)) {
            $this->_stream = null;
            throw new \UnexpectedValueException(
                tr(
                    __NAMESPACE__,
                    'The stream to {stream} could not be opened: {cause}',
                    array('stream' => $this->_uri, 'cause' => $errorMessage)
                )
            );
        }
        return $this->_stream;
    }

    /**
     * This will close a previously opened stream.
     * @return bool true if stream could be closed
     */
    private function closeStream()
    {
        if (!($this->_flags & self::FLAG_CLOSE))
            return;
        if (is_resource($this->_stream))
            fclose($this->_stream);
        $this->_stream = null;
        return true;
    }

    /**
     * Write the log message down.
     * @param  Message  $message Log message object
     */
    protected function write(Message $message)
    {
        $this->openStream();
        fwrite($this->_stream, $message->formatted);
        $this->closeStream();
    }
}