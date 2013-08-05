<?php
//Parsonline/System/ProcessManager.php
/**
 * Defines Parsonline_System_ProcessManager class.
 * 
 * Parsonline
 *
 * Copyright (c) 2010-2011-2012 ParsOnline, Inc. (www.parsonline.com)
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 * 
 * @copyright   Copyright (c) 2010-2011-2012 ParsOnline, Inc. (www.parsonline.com)
 * @license     Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Parsonline_System
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.1.0 2012-07-09
 */

/**
 * @uses    Parsonline_System_Process
 */
require_once('Parsonline/System/Process.php');

/**
 * Parsonline_System_ProcessManager
 *
 * Manages system processes. creates a queue of processes
 * with a fixed size (N) and gathers a queue of waiting processes. then starts
 * running N of waiting processes.
 * checks their status every often, and if some processes are finished, fills up the
 * running queue again. can register other objects and functions to be notified of
 * the status of processes.
 */
class Parsonline_System_ProcessManager
{
    /**
     * list of processes waiting to be run
     * array of Parsonline_System_Process objects
     *
     * @var array
     */
    protected $_processListWaiting = array();

    /**
     * list of processes currently being run
     * array of Parsonline_System_Process objects
     *
     * @var array
     */
    protected $_processListRunning = array();

    /**
     * list of finished processes
     * array of Parsonline_System_Process objects
     *
     * @var array
     */
    protected $_processListFinished = array();

    /**
     * number of total processes
     *
     * @var int
     */
    protected $_totalProcessesNumber = 0;

    /**
     * maximum number of processes in the process queue
     * @var int
     */
    protected $_processQueueMaxSize = 100;

    /**
     * Number of microseconds to wait between next process list update.
     *
     * @var int
     */
    protected $_updateDelay = 1000;

    /**
     * array of function references or, array of object and methods
     * to be called as callback on each execution round with updated
     * status.
     *
     * @var array
     */
    protected $_listOfListenersForUpdateStatus = array();

    /**
     * Constructor.
     * 
     * create a new ProcessManager.
     * 
     * @param   int     $queueSize      [optional] the size of the process queue
     * @param   array|Parsonline_System_Process     an array or a single instance of objects
     */
    public function __construct($queueSize=null, $processes=null)
    {
        if ($queueSize) $this->setProcessQueueMaxSize($queueSize);
        if ($processes) $this->addProcess($processes);
    }

    /**
     * Adds a process (or multiple processes) object to the waiting queue.
     * Accepts an array (or a single value) of Parsonline_System_Process objects
     *
     * @param   Parsonline_System_Process|array     $proc
     * @return  Parsonline_System_ProcessManager
     * @throws  Parsonline_Exception_ValueException
     */
    public function addProcess($processList=null)
    {
        if ( !is_array($processList) ) $processList = array($processList);
        foreach ($processList as $proc) {
            if (!$proc || !is_object($proc) || !($proc instanceof Parsonline_System_Process) ) {
                /**
                 *@uses Parsonline_Exception_ValueException 
                 */
                require_once("Parsonline/Exception/ValueException.php");
                throw new Parsonline_Exception_ValueException("Process should be an instance of Parsonline_System_Process");
            }
            array_push($this->_processListWaiting, $proc);
            $this->_totalProcessesNumber++;
        }
        return $this;
    }

    /**
     * Returns maximum number of process queue size.
     * this is the maximum number of concurrent running processes.
     *
     * @return  int
     */
    public function getProcessQueueMaxSize()
    {
        return $this->_processQueueMaxSize;
    }

    /**
     * Sets the maximum number of process queue size.
     * this is the maximum number of* concurrent running processes.
     *
     * @param   int $size
     * @return  Parsonline_System_ProcessManager
     */
    public function setProcessQueueMaxSize($size)
    {
        if ( 1 > $size ) {
            /**
            *@uses Parsonline_Exception_ValueException 
            */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException("queue max size should be a positive integer");
        }
        $this->_processQueueMaxSize = intval($size);
        return $this;
    }

    /**
     * Returns the number of microseconds to wait between next process list update.
     *
     * @return  int
     */
    public function getUpdateDelay()
    {
        return $this->_updateDelay;
    }

    /**
     * Sets the number of microseconds to wait between next process list update.
     *
     * @param   int $delay
     * @return  Parsonline_System_ProcessManager    object self reference
     */
    public function setUpdateDelay($delay)
    {
        if ($delay > 1) {
            /**
            *@uses Parsonline_Exception_ValueException 
            */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException("update delay should be a positive integer");
        }
        $this->_updateDelay = intval($delay);
        return $this;
    }

    /**
     * Returns the number of processes being executed right now
     *
     * @return  int
     */
    public function getNumberOfRunningProcesses()
    {
        return count($this->_processListRunning);
    }

    /**
     * Returns the total number of processes, added to the queue from
     * the beginning.
     *
     * @return  int
     */
    public function getNumberOfTotalProcesses()
    {
        return $this->_totalProcessesNumber;
    }

    /**
     * Returns the number of finished processes.
     *
     * @return  int
     */
    public function getNumberOfFinishedProcesses()
    {
        return count($this->_processListFinished);
    }

    /**
     * returns the number of process waiting to be executed
     *
     * @return  int
     */
    public function getNumberOfWaitingProcesses()
    {
        return count($this->_processListWaiting);
    }


    /**
     * Returns the array of process currently waiting to be run
     *
     * @return  array   array of Parsonline_System_Process objects
     */
    public function getWaitingProcessesQueue()
    {
        return $this->_processListWaiting;
    }

    /**
     * Returns the array of process currently being run
     *
     * @return  array   array of Parsonline_System_Process objects
     */
    public function getRunningProcessesQueue()
    {
        return $this->_processListRunning;
    }

    /**
     * Returns the array of finished processes
     *
     * @return  array   array of Parsonline_System_Process objects
     */
    public function getFinishedProcessesQueue()
    {
        return $this->_processListFinished;
    }

    /**
     * Fetches processes from the waiting queue to the running queue.
     *
     * @return  int     number of transferred processes
     */
    public function populateProcessQueue()
    {
        $counter = 0;
        $maxQueueSize = $this->getProcessQueueMaxSize();
        while ( ( $this->getNumberOfRunningProcesses() < $maxQueueSize ) && !empty($this->_processListWaiting) ) {
                $proc = array_pop($this->_processListWaiting);
                if ( !$proc ) break;
                array_push($this->_processListRunning, $proc);
                $counter++;
        }
        return $counter;
    } // public function populateProcessQueue()

    /**
     * Registeres a listener to receive status updates during the execution of
     * the queue.
     * accepts a string as a function name, or an array with first index
     * of an object, and second string value for the name of the method.
     * 
     * the specified function/method will be called on each execution round and an
     * array of status is send to the function/method. the array is an associative array
     * with keys: running, remaining, total, finished, progress. each value shows the number
     * of processes in that state, and the progress is a total progress percentage.
     *
     * @param   array|string        $listener       an array of object, methodname or a string of a function name
     * @return  Parsonline_System_ProcessManager
     * @throws  Parsonline_Exception_ValueException
     */
    public function regsiterUpdateStatusListener($listener=null)
    {
        if ( is_callable($listener) ) {
            array_push($this->_listOfListenersForUpdateStatus, $listener);
        } else {
            /**
             *@uses   Parsonline_Exception_ValueException
             */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException("process status listener should be callable");
        }
        return $this;
    }

    /**
     * calls the registered listeners registered to be notified of udpated status
     * of processing queue.
     * 
     * @param   array       $stats      array of status
     * @return  Parsonline_System_ProcessManager    object self reference
     */
    protected function _callUpdateStatusListeners($stats=array())
    {
        foreach($this->_listOfListenersForUpdateStatus as $listener) {
            call_user_func($listener, $stats);
        } // foreach()
        return $this;
    }

    /**
     * Starts executing the process waiting in the waiting queue.
     *
     * @param   int     $delay      number of microseconds to wait before each process queue check
     * @return  Parsonline_System_ProcessManager
     * @throws  Parsonline_Exception_ValueException
     */
    public function executeProcessQueue($delay=null)
    {
        $delay = ($delay === null) ? $this->_updateDelay : intval($delay);
        if ( 1 > $delay ) {
            /**
            *@uses Parsonline_Exception_ValueException 
            */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException("update delay should be a positive integer");
        }
        
        $numberOfTotalProcesses = $this->getNumberOfTotalProcesses();
        if ( 1 > $numberOfTotalProcesses ) {
            return $this;
        }

        while (true) {
            // fetch process from waiting queue into running queue
            $numberOfAddedProcsToQueue = $this->populateProcessQueue();
            $numberOfFinishedProcs = count($this->getFinishedProcessesQueue());
            $progress = 100 * ($numberOfFinishedProcs/$numberOfTotalProcesses);

            $this->_callUpdateStatusListeners(
                array(
                    'running'   => $this->getNumberOfRunningProcesses(),
                    'remaining' => $this->getNumberOfWaitingProcesses(),
                    'total'     => $numberOfTotalProcesses,
                    'finished'  => $numberOfFinishedProcs,
                    'progress'  => $progress
                )
            );

            $listOfFinishedProcsInThisRound = array();
            $listOfStillRunningProcs = array();
            
            while ( $proc = array_pop($this->_processListRunning) ) {
                switch( $proc->getStatus() ) {
                    case Parsonline_System_Process::STATUS_UNKNOWN: // start unstarted processes
                                    $proc->startInBackground();
                    case Parsonline_System_Process::STATUS_RUNNING:
                                    array_push($listOfStillRunningProcs, $proc);
                                    break;
                    case Parsonline_System_Process::STATUS_FINISHED:
                                    array_push($listOfFinishedProcsInThisRound, $proc);
                                    break;
                }
            } // while

            // removing finished processes
            $this->_processListRunning = $listOfStillRunningProcs;
            unset($listOfStillRunningProcs, $proc);

            if ( !empty($listOfFinishedProcsInThisRound) ) {
                $this->_processListFinished = array_merge($this->_processListFinished, $listOfFinishedProcsInThisRound);
            }

            if ( empty($this->_processListWaiting) && empty($this->_processListRunning) ) {
                return $this;
            }
            usleep($delay);
        } // while ( true )
    } // public function executeProcessQueue()
}