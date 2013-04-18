<?php

/**
 * @file
 * Manager to run multiple Process objects serial or parallel.
 *
 * @author    Alexander Schilling <info@alinex.de>
 * @copyright 2009-2013 Alexander Schilling (\ref Copyright)
 * @license   All Alinex code is released under the GNU General Public \ref
 *            License.
 * @see       http://alinex.de Alinex Project
 */

namespace Alinex\Proc;

/**
 * Manager to run multiple Process objects serial or parallel.
 *
 * The proccesses to run are collected in a mixed list of parallel and serial
 * process calls. See below the simplified structure of such an list structure:
 *
 * @dotfile Proc/Manager-list
 *
 * This have to be added as:
 * @code
 * $manager->add( array($process_1, $process_2, $process_3, $process_4) );
 * $manager->add( $process_5 );
 * $manager->add(
 *        array(
 *               array( $process_6, $process_7 ),
 *               $process_8,
 *        array( $process_9, $process_10 )
 * );
 * $manager->add( array($process_11, $process_12, $process_13, $process_14) );
 * @endcode
 * This will result in an internal structure like the following:
 * @code
 * array( // run parallel
 *        array( // run serial
 *               $process_1, $process_2, $process_3, $process_4
 *        ),
 *        $process_5,
 *        array( // run serial
 *               array( // run parallel
 *                      $process_6, $process_7
 *               ),
 *               $process_8,
 *               array( // run parallel
 *                      $process_9, $process_10
 *               )
 *        ),
 *        array( // run serial
 *               $process_11, $process_12, $process_13, $process_14
 *        )
 * );
 * @endcode
 *
 * The following four graphs show the actual process order for different number
 * of parallel processes (if every process takes the same time):
 * @dotfile Proc/Manager-exec1
 * @dotfile Proc/Manager-exec2
 * @dotfile Proc/Manager-exec3
 * @dotfile Proc/Manager-exec4
 *
 * @pattern{Chaining} To add() multiple times.
 * @event{progress} - called after starting an process
 */
class Manager
{
    /**
     * Flatten the list of processes.
     * @return array
     */
    static function flatten(array &$list = null)
    {
        $result = array();
        foreach ($list as $value)
            if (is_array($value))
                array_push($result, self::flatten($value));
            else
                $result[] = $value;
        return $result;
    }

    /**
     * List of processes.
     * @var array
     */
    private $_list = array();

    /**
     * Maximum number of parallel Processes to run.
     * This will default to the number of cpu cores.
     * @var int
     */
    private $_maxParallel = null;

    /**
     * Create a new process manager group.
     */
    function __construct()
    {

    }

    /**
     * Add a process or a list to be proccessed parallel.
     * @param Process|array $list
     */
    function add($list) // serial processlist
    {
        // manager should not be started or create a new instance
        assert(!$this->isStarted());
        // only a single process or structured list of process are possible
        assert(
            $list instanceof Process
            || is_array($list)
        );
        if (is_array($list)) {
            assert(
                \Alinex\Validator\Type::arraylist(
                    self::flatten($list), 'process structure',
                    array(
                        'notEmpty' => true,
                        'keySpec' => array(
                            '' => array(
                                'Code::phpClass',
                                array(
                                    'exists' => true,
                                    'instanceof' => 'Alinex\Proc\Process'
                                )
                            )
                        )
                    )
                )
            );
            array_push($this->_list, $list);
        } else {
            $this->_list[] = $list;
        }
        return $this;
    }

    /**
     * Get the current list of processes.
     * @return type
     */
    function getList()
    {
        return $this->_list;
    }

    /**
     * Set the maximum number of parallel Process runs.
     *
     * The default will be the number of cpu cores. This means that every php
     * process may create as many subprocesses.
     *
     * @param int $max Maximum number of parallel processes
     */
    function setMaxParallel($max)
    {
        $this->_maxParallel = $max;
    }

    /**
     * Check if manager is already started.
     * @returnn bool true if already started
     */
    private function isStarted()
    {
        return (bool) $this->_failed || $this->_success;
    }

    /**
     * Ordered list of processes to run.
     * id => Process
     * @var array
     */
    private $_queue = array();
    
    /**
     * List of just running processes.
     * id => Process
     * @var array
     */
    private $_running = array();
    
    /**
     * List of failed processes (not included skipped ones).
     * id => Process
     * @var array
     */
    private $_failed = array();
    
    /**
     * List of successful processes.
     * id => Process
     * @var array
     */
    private $_success = array();
    
    /**
     * Dependencies for each entry.
     * id => Array(ids)
     * @var array
     */
    private $_depend = array();

    /**
     * Number of processes to run.
     * @var int
     */
    private $_processNum = 0;
    
    /**
     * Initialize the list to be run.
     * 
     * This is called recursively to get the complete dependencies of the
     * serial run.
     * 
     * @param array $list to check
     * @param bool $parallel true if paralell, false if serial list
     * @param array $upper list of upper dependencies
     * @return array list of elements and subelements
     */
    private function init(&$list, $parallel = true, $upper = null)
    {
        $idlist = array();
        $last = null;
        // step through list
        foreach ($list as $elem) {
            if (is_array($elem)) {
                // call sublist with switched parallel/serial
                $ids = $this->init($elem, !$parallel, isset($last) ? $last : $upper);
                // for serial calls store the list as last dependency
                if (!$parallel)
                    $last = $ids;
                // add list to the list of ids in the surrounding array
                array_push($idlist, $ids);
            } else {
                // create new process entry and queue it
                $id = count($this->_queue);
                $this->_queue[$id] = $elem;
                // add to current surrounding list
                $idlist[] = $id;
                if ($parallel) {
                    // in parallel only depend on upper
                    if (isset($upper))
                        $this->_depend[$id] = $upper;
                } else {
                    // in serial depend on previous process/group or upper
                    if (isset($last) || isset($upper))
                        $this->_depend[$id] = isset($last) ? $last : $upper;
                    // set this for possible next serial entry
                    $last = array($id);
                }
            }
        }
        return $idlist;
    }

    /**
     * Start the execution of this list.
     */
    function execute()
    {
        $this->init();
        $this->_processNum = count($this->_queue);
        // run the commands
        while (count($this->_queue)) {
            /* @var $proc Process */
            foreach ($this->_running as $id => $proc) {
                if ($proc->isFinished()) {
                    unset($this->_running[$id]);
                    if ($proc->isSuccess())
                        $this->_success[$id] = $proc;
                    else
                        $this->_failed[$id] = $proc;
                }
            }
            if (count($this->_running) < $this->_maxParallel)
                // add more till max
                $this->runNext();
            else
                // wait
                usleep(100);
            // get progress
            $percent = 1 - (count($this->_queue)/$this->_processNum);
            foreach($this->_running as $proc)
                $percent -= 1 - ($proc->getProgress()/$this->_processNum);
            Util\EventManager::getInstance()
                ->update(
                    new Util\Event($this, 'progress', $percent)
                );
        }
    }

    /**
     * Find the next process to run.
     * @return int number of Process started
     */
    private function runNext()
    {
        foreach ($this->_queue as $id => $proc) {
            if (isset($this->_depend[$id])) {
                $check = true;
                foreach ($this->_depend[$id] as $depend) {
                    if (isset($this->_success[$depend]))
                        continue;
                    $check = false;
                    if (isset($this->_failed[$depend]))
                        // cancel because of prerequisit failed
                        unset($this->_queue[$id]);
                }
                if (!$check)
                    // not possible, yet - use next
                    continue;
            }
            // start process
            $proc->open();
            unset($this->_queue[$id]);
            $this->_running[$id] = $proc;
            return $id;
        }
        // nothing to start, yet
        return null;
    }
}
