<?php

/**
 * @file
 * System program execution and control.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Proc;

/**
 * System program execution and control.
 * 
 * This class can be used as wrapper over the PHP proc_ methods to create
 * system calls. It adds an easy interface with simplified functions for secure 
 * use with all abilities like:
 * - complete control
 * - unblocked buffer reading
 * - interactions
 * - additional pipes possible
 * - pseudo terminal support
 */
class Process // implements \Psr\Log\LoggerInterface
{
    /**
     * stdin identifier (used for pipe index).
     */
    const STDIN = 0;

    /**
     * stdout identifier (used for pipe index).
     */
    const STDOUT = 1;

    /**
     * stderr identifier (used for pipe index).
     */
    const STDERR = 2;

    /**
     * Additional input pipe may be accessed from commands like GPG
     */
    const ADDIN = 3;

    /**
     * Additional output pipe may be accessed from siome commands
     */
    const ADDOUT = 4;

    /**
     * Allow the use of wildcards within the command line
     */
    const FLAG_WILDCARD = 1;

    /**
     * Use a pseudo terminal.
     * Helpful for commands which use /dev/tty
     */
    const FLAG_PTY = 2;

    /**
     * List of all pipes which are possibly used
     * @var array
     */
    private static $_allPipes = array(
        self::STDIN, self::STDOUT, self::STDERR,
        self::ADDIN, self::ADDOUT
    );

    /**
     * List of all pipes which are possibly used as input to the process
     * @var array
     */
    private static $_inPipes = array(
        self::STDIN, self::ADDIN
    );

    /**
     * List of all pipes which are possibly used as output from the process
     * @var array
     */
    private static $_outPipes = array(
        self::STDOUT, self::STDERR, self::ADDOUT
    );

    /**
     * The command to be executed.
     * @var string
     */
    protected $_command;

    /**
     * Parameters to be given to command.
     * @var array
     */
    protected $_params;

    /**
     * Special execution flags.
     * @var int
     */
    private $_flags = 0;

    /**
     * The working directory context for the command.
     * @var string
     */
    protected $_workingDirectory;

    /**
     * Array of environment variables to me made available to the command.
     * @var array
     */
    protected $_environment;

    /**
     * Max execution time, process will, be kiled after this time.
     * @var array
     */
    protected $_timeout;

    /**
     * Resource handle for the process.
     * @var resource
     */
    protected $_handle;

    /**
     * Handles to stdin, stdout and stderr streams.
     * @var array
     */
    protected $_pipes;

    /**
     * Determines if the process is currently open.
     * @var boolean
     */
    protected $_isOpen;

    /**
     * Starttime of process run.
     * @var int
     */
    protected $_starttime;

    /**
     * The process id of the system call
     * @var int
     */
    protected $_pid;

    /**
     * Starttime of process run.
     * @var int
     */
    protected $_endtime;

    /**
     * Output through pipes.
     * @var array of pipes with output string
     */
    protected $_input = array(
        self::STDIN => '',
        self::ADDIN => ''
    );

    /**
     * Output through pipes.
     * @var array of pipes with output string
     */
    protected $_output = array(
        self::STDOUT => '',
        self::STDERR => '',
        self::ADDOUT => ''
    );

    /**
     * Exit status code of the command that was executed
     * @var type
     */
    protected $_exit;

    /**
     * Constructs the object, optionally setting the command to be executed.
     *
     * If you use wildcard expansion from the shell an additional subprocess
     * will be created.
     *
     * @param string $command command to run
     * @param array $params additional parameter list
     */
    function __construct($command, array $params = null)
    {
        assert(is_string($command) && $command);

        $this->_command = $command;
        $this->_params = isset($params) ? $params : array();
    }

    /**
     * Set the execution flags.
     * Use a combination of FLAG_... constants here.
     * @param int $flags
     */
    function setCommandFlags($flags)
    {
        $this->_flags = $flags;
    }

    /**
     * Set a timeout after this the process may be killed.
     *
     * @param int $time max execution time in seconds
     * @rturn Process
     */
    function setTimeout($time)
    {
        $this->_timeout = $time;
        return $this;
    }

    /**
     * Sets the path of the working directory for the command.
     *
     * @param string $path Directory path
     * @return Process
     */
    public function setWorkingDirectory($path)
    {
        // this can only be done before opening the process
        assert(!$this->_isOpen);
        // the working directory have to exist
        assert(is_string($path) && is_dir($path));

        $this->_workingDirectory = $path;
        return $this;
    }

    /**
     * Sets the array of environment variables to be made available to the
     * command.
     *
     * This will replace ALL environment variables for the command, which can
     * include the PATH variable and may cause the command to not even be found,
     * or other undesired effects.
     *
     * @param array $vars environment vars to set
     * @return Process
     */
    public function setEnvironmentVars(array $vars)
    {
        $this->_environment = $vars;
        return $this;
    }

    /**
     * Open the process handle and start the command execution.
     *
     * @return Process
     * @throws RuntimeException
     */
    function open()
    {
        if ($this->_isOpen)
            throw new RuntimeException('Process is already open');

        // get systemcall
        $call = $this->_command;
        // add parameters
        if (isset($this->_params))
            foreach ($this->_params as $param)
                $call .= ' '.escapeshellarg($param);
        // if wildcard support not neccessary replace use exec to replace the
        // shell process
        if (!$this->_flags & self::FLAG_WILDCARD)
            $call = 'exec '.$call;

        // open handle and start process
        $handle = proc_open(
            $call,
            $this->_flags & self::FLAG_PTY
            ? array(
                self::STDIN => array('pty'),
                self::STDOUT => array('pty'),
                self::STDERR => array('pty'),
                self::ADDIN => array('pipe', 'r'),
                self::ADDOUT => array('pipe', 'w')
            )
            : array(
                self::STDIN => array('pipe', 'r'),
                self::STDOUT => array('pipe', 'w'),
                self::STDERR => array('pipe', 'w'),
                self::ADDIN => array('pipe', 'r'),
                self::ADDOUT => array('pipe', 'w')
            ),
            $this->_pipes,
            $this->_workingDirectory,
            $this->_environment
        );
        // check the handle
        if (false === $handle)
            throw new RuntimeException(
                'Failed to open the process using proc_open'
            );
        // set the references
        $this->_handle = $handle;
        $this->_isOpen = true;
        $this->_starttime = time();
        // set non-blocking mode for output pipes
        foreach (static::$_outPipes as $pipe)
            stream_set_blocking($this->_pipes[$pipe], 0);
        $status = proc_get_status($this->_handle);
        if (isset($status['pid']))
            $this->_pid = $status['pid'];
        return $this;
    }

    /**
     * Is the process (still) running?
     * @return bool true if process is (still) running
     */
    function isRunning()
    {
        $this->read(); // read if something there
        return !$this->_closed;
    }

    /**
     * Check if the process is outdated.
     *
     * Close the process if it is outdated.
     * @return bool true if process is running to long
     */
    function isTimeout()
    {
        if (!isset($this->_timeout))
            return false; // no timeout set
        $close = $this->_starttime+$this->_timeout < time();
        if ($close)
            $this->close();
        return $close;
    }

    /**
     * Flags indicating if the pipe is active.
     * @var array of pipes with boolean value
     */
    private $_readMore = array(
        self::STDOUT => true,
        self::STDERR => true,
        self::ADDOUT => true
    );

    /**
     * Read next chunk from process if anything output.
     * @return bool true if something was read
     */
    protected function read()
    {
        $this->isTimeout(); // check for timeoutd
        $readMore = false;
        foreach (self::$_outPipes as $pipe) {
            // only for active pipes
            if (!$this->_readMore[$pipe])
                continue;
            $readMore = true;
            // check if already active
            if (feof($this->_pipes[$pipe])) {
                // close pipe, mark as inactive
                fclose($this->_pipes[$pipe]);
                $this->_readMore[$pipe] = false;
            } else {
                // read next chunk
                $str = fgets($this->_pipes[$pipe], 1024);
                // store if something got
                if (strlen($str))
                    $this->_output[$pipe] .= $str;
            }
        }
        // if everything read close the process
        if (!$readMore)
            $this->close();
        return $readMore;
    }

    /**
     * Send the given text to the process.
     *
     * @param string $text
     * @param int $pipe number of the pipe use STDIN or ADDIN
     * @return Process
     */
    function write($text, $pipe = self::STDIN)
    {
        fwrite($this->_pipes[$pipe], $text);
        fflush($this->_pipes[$pipe]);
        // store internally
        $this->_input[$pipe] .= $text;
        return $this;
    }

    /**
     * Read all ouput pipes till process is finished.
     * @return Process
     */
    function readAll()
    {
        while ($this->read())
            usleep(10000); // 10ms
        $this->close();
        return $this;
    }

    /**
     * Flag that the process is already closed.
     * To prevent execuzting the close method twice.
     * @var bool
     */
    private $_closed = false;

    /**
     * Closes the process and all open pipes.
     *
     * @return int|null Exit status code of the command that was executed
     */
    private function close()
    {
        // if already closed return last exit status
        if ($this->_closed)
            return $this->_exit;
        // store endtime
        $this->_endtime = time();
        // close all opened pipes
        foreach (self::$_allPipes as $pipe)
            if (is_resource($this->_pipes[$pipe]))
                fclose($this->_pipes[$pipe]);
        // kill all subprocesses
        if (isset($this->_pid)) {
            $ppid = $this->_pid;
            // use ps to get all the children of this process, and kill them
            $pids = preg_split('/\s+/', `ps -o pid --no-heading --ppid $ppid`);
            foreach($pids as $pid)
                if(is_numeric($pid))
                    posix_kill($pid, 9); // send SIGKILL signal
        }
        // close process handle
        if (is_resource($this->_handle))
            $this->_exit = proc_close($this->_handle);
        $this->_isOpen = false;
        // set and return status
        $status = proc_get_status($this->_handle);
        $this->_exit = $status["running"] ? $this->_exit : $status["exitcode"];
        return $this->_exit;
    }

    /**
     * Implicitly closes any open pipes.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Get all informations about the system call
     * @return array
     */
    function getMeta()
    {
        return array(
            'command' => $this->_command,
            'params' => $this->_params,
            'environment' => $this->_environment,
            'timeout' => $this->_timeout,
            'pid' => $this->_pid,
            'start' => $this->_starttime,
            'end' => isset($this->_endtime) ? $this->_endtime : '',
            'duration' => isset($this->_endtime)
                ? $this->_endtime - $this->_starttime
                : time() - $this->_starttime
        );
    }

    /**
     * Get text send to stdin.
     * @return string
     */
    function getInput()
    {
        return $this->_input[self::STDIN];
    }

    /**
     * Get text send to additional input pipe
     * @return string
     */
    function getAdditionalInput()
    {
        return $this->_input[self::ADDIN];
    }

    /**
     * Get output text of process
     * @return string
     */
    function getOutput()
    {
        // read rest first, wait for finish
        $this->readAll();
        return $this->_output[self::STDOUT];
    }

    /**
     * Get error text of process
     * @return string
     */
    function getErrors()
    {
        // read rest first, wait for finish
        $this->readAll();
        return $this->_output[self::STDERR];
    }

    /**
     * Get output of additional pipe tp process
     * @return string
     */
    function getAdditionalOutput()
    {
        // read rest first, wait for finish
        $this->readAll();
        return $this->_output[self::ADDOUT];
    }

    /**
     * Get the exit code.
     * @return int 0 => normal end -1 => process killed >0 => something went
     * wrong
     */
    function getExitCode()
    {
        // read rest first, wait for finish
        $this->readAll();
        return $this->_exit;
    }
}

