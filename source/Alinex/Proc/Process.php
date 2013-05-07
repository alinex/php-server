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

use Alinex\Util;

/**
 * System program execution and control.
 *
 * This class can be used as wrapper over the PHP proc_ methods to create
 * system calls. It adds an easy interface with simplified functions for secure
 * use with all abilities like:
 * - complete control
 * - unblocked buffered reading
 * - full control
 * - interactions
 * - additional pipes possible
 * - pseudo terminal support
 *
 * This class should not be used directly but using its specific subclasses.
 * Most of the control mechanism are not public available to not pollute all
 * the subclass's public interface.
 *
 * Use of this class is parted in three steps:
 * - setup phase
 * - processing phase
 * - analyzing phase
 *
 * The process goes automatically on if a method of the next step is used.
 *
 * @pattern{Chaining} In most public methods.
 *
 * @event{start} - called after starting an process
 * @event{output} - called after something was output
 * @event{error} - called after some error was output
 * @event{end} - called after process has finished
 * @event{success} - called after process finished successfull
 * @event{fail} - called after process finished with failure
 */
class Process
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
    const FLAG_USESHELL = 1;

    /**
     * Use a pseudo terminal.
     * Helpful for commands which use /dev/tty
     */
    const FLAG_PTY = 2;

    /**
     * Status flag if process has not started.
     */
    const STATUS_INIT = 0;

    /**
     * Status flag if process is running.
     */
    const STATUS_RUNNING = 1;

    /**
     * Status flag if process has been terminated.
     */
    const STATUS_TERMINATED = 2;

    /**
     * Status flag if process is finished.
     */
    const STATUS_DONE = 4;

    /**
     * Status flag if process is finished.
     */
    const STATUS_FAILED = 8;

    /**
     * Weight for the process in time.
     * This will be used in comparing and calculating the progress and optimal
     * order by the Manager.
     */
    const WEIGHT = 1;

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
     * Current status of Process.
     * Use the const STATUS_... to check.
     * @var int
     */
    protected $_status = self::STATUS_INIT;

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
     * Direct call of command
     * @var string
     */
    protected $_call;

    /**
     * Special execution flags.
     * @var int
     */
    private $_flags = 0;

    /**
     * The working directory context for the command.
     * @var string
     */
    protected $_cwd;

    /**
     * Array of environment variables to me made available to the command.
     * @var array
     */
    protected $_environment = array();

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
     * Starttime of process run in seconds.
     * @var float
     */
    protected $_starttime;

    /**
     * The process id of the system call
     * @var int
     */
    protected $_pid;

    /**
     * Starttime of process run in secomds.
     * @var float
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
     * Protocol of all actions with time.
     * Array of array(time, pipe, string)
     * @var array
     */
    protected $_protocol = array();

    /**
     * Exit status code of the command that was executed
     * @var int
     */
    protected $_exit = 0;

    /**
     * @name Setup Phase
     * @{
     */

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
    protected function setCommandFlags($flags)
    {
        // possible flags are the FLAG_... constants
        assert(is_int($flags) && $flags >= 0);

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
        // time range in seconds
        assert(is_int($time) && $time >= 0);

        $this->_timeout = $time;
        return $this;
    }

    /**
     * Sets the path of the working directory for the command.
     *
     * @param string $path Directory path
     * @return Process
     */
    protected function setWorkingDirectory($path)
    {
        // this can only be done before opening the process
        assert($this->_status == self::STATUS_INIT);
        // the working directory have to exist
        assert(is_string($path) && is_dir($path));

        $this->_cwd = $path;
        return $this;
    }

    /**
     * Additional ssh conection string to use
     * @var String
     */
    private $_ssh = '';

    /**
     * Use this command through the secure shell
     * @param string $connect [user@]host
     * @param string $options additional connection settings
     */
    function useSsh($connect, $options = '')
    {
        assert(is_string($connect));
        assert(is_string($options));

        $this->_ssh = 'ssh '.$options.' '.$connect.' ';
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
    protected function setEnvironmentVars(array $vars)
    {
        $this->_environment = $vars;
        return $this;
    }

    /**
     * @}
     */

    /**
     * Gets the process status.
     *
     * The status is one of the STATUS_... constants
     *
     * @return int status number
     */
    public function getStatus()
    {
        $this->read(); // update status
        return $this->_status;
    }

    /**
     * @name Processing Phase
     * @{
     */

    /**
     * Open the process handle and start the command execution.
     *
     * The given parameter will be sorted by key. This means the ones with
     * indexed numbers coming first, bevor this with alphanumeric indexes.
     * That makes it possible to create a specific order while adding the
     * parameters in scrambled mode.
     *
     * @return Process
     * @throws RuntimeException
     */
    protected function open()
    {
        // process should only be opened once
        assert($this->_status == self::STATUS_INIT);

        // get systemcall
        $this->_call = $this->_command;
        // add parameters
        ksort($this->_params);
        if (isset($this->_params))
            $this->_call .= ' '.implode(' ', $this->_params);
        // if wildcard support not neccessary replace use exec to replace the
        // shell process
        if ($this->_ssh || (!$this->_flags & self::FLAG_USESHELL))
            $this->_call = 'exec '.$this->_ssh.$this->_call;

        // open handle and start process
        $handle = proc_open(
            $this->_call,
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
            $this->_cwd,
            $this->_environment
        );
        // check the handle
        if (false === $handle)
            throw new RuntimeException(
                'Failed to open the process using proc_open'
            );
        // set the references
        $this->_handle = $handle;
        $this->_status = self::STATUS_RUNNING;
        $this->_starttime = microtime(true);
        // set non-blocking mode for output pipes
        foreach (self::$_outPipes as $pipe)
            stream_set_blocking($this->_pipes[$pipe], 0);
        $status = proc_get_status($this->_handle);
        if (isset($status['pid']))
            $this->_pid = $status['pid'];
        // protocol call
        $this->_protocol[] = array(time(), 0, $this->_call);
        // call observers
        Util\EventManager::getInstance()
            ->update(
                new Util\Event($this, 'start')
            );
        return $this;
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
        if ($this->_status == self::STATUS_INIT)
            $this->open();
        if ($this->_status != self::STATUS_RUNNING)
            return false;
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
                // protocol output
                $this->_protocol[] = array(time(), $pipe, $str);
                // call observers
                if ($pipe == self::STDOUT || $pipe == self::STDERR)
                    Util\EventManager::getInstance()
                        ->update(
                            new Util\Event(
                                $this,
                                $pipe == self::STDOUT ? 'output' : 'error',
                                array('text' => $str)
                            )
                        );
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
    protected function write($text, $pipe = self::STDIN)
    {
        fwrite($this->_pipes[$pipe], $text);
        fflush($this->_pipes[$pipe]);
        // store internally
        $this->_input[$pipe] .= $text;
        // protocol input
        $this->_protocol[] = array(time(), $pipe, $text);
        return $this;
    }

    /**
     * Read all ouput pipes till process is finished.
     * @return Process
     */
    function exec()
    {
        while ($this->read())
            usleep(10000); // 10ms
        $this->close();
        return $this;
    }

    /**
     * Closes the process and all open pipes.
     *
     * @return int|null Exit status code of the command that was executed
     */
    protected function close()
    {
        // if already closed return last exit status
        if ($this->_status != self::STATUS_RUNNING)
            return $this->_exit;
        // store endtime
        $this->_endtime = microtime(true);
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
        $status = false;
        if (is_resource($this->_handle)) {
            $status = proc_get_status($this->_handle);
            $this->_exit = proc_close($this->_handle);
        }
        $this->_exit = isset($status["running"]) && $status["running"]
            ? $this->_exit
            : $status["exitcode"];
        $this->_status = $this->_exit
            ? self::STATUS_TERMINATED
            : self::STATUS_DONE;
        // protocol close
        $this->_protocol[] = array(time(), 0, 'Call closed: '.$this->_exit);
        // call observers
        Util\EventManager::getInstance()
            ->update(
                new Util\Event($this, 'end')
            );
        Util\EventManager::getInstance()
            ->update(
                new Util\Event($this, $this->_exit ? 'success' : 'fail')
            );
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
     * Get the value of progress as percent float.
     *
     * This may be accurate, estimnated or only a big stepped value depending
     * on the comands possibility to measure.
     * @return float value between 0 = not started and 1 = finished
     */
    function getProgress()
    {
        $this->read(); // read if something there
        if ($this->isFinished())
            return 1;
        if ($this->isRunning())
            return 0.1;
        return 0;
    }

    /**
     * Get the weight value for this process.
     * @return float
     */
    function getWeight()
    {
        return static::WEIGHT;
    }

    /**
     * Is the process (still) running?
     * @return bool true if process is (still) running
     */
    function isRunning()
    {
        $this->read(); // read if something there
        return $this->_status == self::STATUS_RUNNING;
    }

    /**
     * Check if the process is outdated.
     *
     * Close the process if it is outdated.
     * @return bool true if process is running to long
     */
    private function isTimeout()
    {
        if (!isset($this->_timeout))
            return false; // no timeout set
        $close = $this->_starttime+$this->_timeout < microtime(true);
        if ($close)
            $this->close();
        return $close;
    }

    /**
     * Is the process finished wtih success
     * @return bool true if process is (still) running
     */
    function isFinished()
    {
        $this->read(); // read if something there
        return $this->_status > self::STATUS_RUNNING;
    }

    /**
     * @}
     */

    /**
     * @name Analyzation Phase
     * @{
     */

    /**
     * Is the process finished wtih success
     * @return bool true if process has successful finished
     */
    function isSuccess()
    {
        $this->read(); // read if something there
        return $this->_status == self::STATUS_DONE;
    }

    /**
     * Is the process finished wtih failure
     * @return bool true if process finished with failure
     */
    function isFailed()
    {
        return $this->isFinished() && !$this->isSuccess();
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
            'cwd' => isset($this->_cwd) ? $this->_cwd : getcwd(),
            'environment' => $this->_environment,
            'ssh' => $this->_ssh,
            'usePty' => $this->_flags & self::FLAG_PTY,
            'call' => $this->_call,
            'timeout' => $this->_timeout,
            'pid' => $this->_pid,
            'start' => $this->_starttime,
            'end' => isset($this->_endtime) ? $this->_endtime : '',
            'duration' => isset($this->_endtime)
                ? $this->_endtime - $this->_starttime
                : microtime(true) - $this->_starttime
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
        $this->exec();
        return $this->_output[self::STDOUT];
    }

    /**
     * Get error text of process
     * @return string
     */
    function getErrors()
    {
        // read rest first, wait for finish
        $this->exec();
        return $this->_output[self::STDERR];
    }

    /**
     * Get output of additional pipe tp process
     * @return string
     */
    function getAdditionalOutput()
    {
        // read rest first, wait for finish
        $this->exec();
        return $this->_output[self::ADDOUT];
    }

    /**
     * Get a combined string of all input and output.
     *
     * This may be scrambled up because of the different time slots for reading
     * the pipes.
     * @return string
     */
    function getProtocolString()
    {
        $result = '';
        foreach ($this->_protocol as $entry)
            $result .= $entry[2];
        return $result;
    }

    /**
     * Get the exit code.
     * @return int 0 => normal end -1 => process killed >0 => something went
     * wrong
     */
    function getExitCode()
    {
        // read rest first, wait for finish
        $this->exec();
        return $this->_exit;
    }

    /**
     * Exit codes translation table.
     * This is set up on first call to exitDescription().
     * @var array
     */
    private static $_exitCodes;

    /**
     * Get a descriptive description for the exit code.
     *
     * @param int $code exit code from command
     * @return string description text
     */
    protected static function exitDescription($code)
    {
        // valid exit code between -255 and 255
        assert(is_int($code) && $code >= -255 && $code <= 255);

        if (!isset(self::$_exitCodes))
            self::$_exitCodes = array(
                0 => tr(__NAMESPACE__, 'OK'),
                1 => tr(__NAMESPACE__, 'General error'),
                2 => tr(__NAMESPACE__, 'Misuse of shell builtins'),
                126 => tr(__NAMESPACE__, 'Invoked command cannot execute'),
                127 => tr(__NAMESPACE__, 'Command not found'),
                128 => tr(__NAMESPACE__, 'Invalid exit argument'),
                // signals
                129 => tr(__NAMESPACE__, 'Hangup'),
                130 => tr(__NAMESPACE__, 'Interrupt'),
                131 => tr(__NAMESPACE__, 'Quit and dump core'),
                132 => tr(__NAMESPACE__, 'Illegal instruction'),
                133 => tr(__NAMESPACE__, 'Trace/breakpoint trap'),
                134 => tr(__NAMESPACE__, 'Process aborted'),
                135 => tr(__NAMESPACE__, 'Bus error: "access to undefined portion of memory object"'),
                136 => tr(__NAMESPACE__, 'Floating point exception: "erroneous arithmetic operation"'),
                137 => tr(__NAMESPACE__, 'Kill (terminate immediately)'),
                138 => tr(__NAMESPACE__, 'User-defined 1'),
                139 => tr(__NAMESPACE__, 'Segmentation violation'),
                140 => tr(__NAMESPACE__, 'User-defined 2'),
                141 => tr(__NAMESPACE__, 'Write to pipe with no one reading'),
                142 => tr(__NAMESPACE__, 'Signal raised by alarm'),
                143 => tr(__NAMESPACE__, 'Termination (request to terminate)'),
                // 144 - not defined
                145 => tr(__NAMESPACE__, 'Child process terminated, stopped (or continued*)'),
                146 => tr(__NAMESPACE__, 'Continue if stopped'),
                147 => tr(__NAMESPACE__, 'Stop executing temporarily'),
                148 => tr(__NAMESPACE__, 'Terminal stop signal'),
                149 => tr(__NAMESPACE__, 'Background process attempting to read from tty ("in")'),
                150 => tr(__NAMESPACE__, 'Background process attempting to write to tty ("out")'),
                151 => tr(__NAMESPACE__, 'Urgent data available on socket'),
                152 => tr(__NAMESPACE__, 'CPU time limit exceeded'),
                153 => tr(__NAMESPACE__, 'File size limit exceeded'),
                154 => tr(__NAMESPACE__, 'Signal raised by timer counting virtual time: "virtual timer expired"'),
                155 => tr(__NAMESPACE__, 'Profiling timer expired'),
                // 156 - not defined
                157 => tr(__NAMESPACE__, 'Pollable event'),
                // 158 - not defined
                159 => tr(__NAMESPACE__, 'Bad syscall'),
            );
        return isset(self::$_exitCodes[$code])
            ? self::$_exitCodes[$code]
            : 'Unknown code: '.$code;
    }

    /**
     * @}
     */

}

