<?php
//Parsonline/System/Process.php
/**
 * Defines Parsonline_System_Process class.
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
 * @version     0.1.0 2012-07-08
 */

/**
 * @uses    Parsonline_System.php
 */
require_once('Parsonline/System.php');

/**
 * Parsonline_System_Process
 * 
 * Represents an operating system process.
 *
 */

class Parsonline_System_Process
{
    const STATUS_FINISHED = 0;
    const STATUS_RUNNING = 1;
    const STATUS_UNKNOWN = 2;
    const STATUS_ZOMBIE = 3;

    /**
     * type of the operating system
     * 
     * @var     string
     */
    protected $_os = '';

    /**
     * the command to run
     * 
     * @var string
     */
    protected $_command = '';

    /**
     * associative array of command line options
     * 
     * @var array
     */
    protected $_options = array();

    /**
     * indexed array of command line arguments
     * 
     * @var array
     */
    protected $_arguments = array();

    /**
     * the process ID
     * 
     * @var int
     */
    protected $_pid = null;

    /**
     * timestamp of when the process started
     * 
     * @var int
     */
    protected $_startTime = null;

    /**
     * if the output of the process is being recorded
     * 
     * @var bool
     */
    protected $_recordOutput = false;

    /**
     * name of a file to store output of the program
     * 
     * @var string
     */
    protected $_outputFilename;

    /**
     * Constructor
     * 
     * Create a new process. The process deos not start.
     * 
     * @param   $string         $command    [optioanl]
     * @param   array|object    $options    [optional]
     */
    public function __construct($command='', $options=array())
    {
        $this->setCommand($command);
        $this->setOptions($options);
    }

    /**
     * Returns the command to be executed.
     * if the useOptions parameter is set,
     * then the returned command will have all the options set for the command.
     * 
     * @return  string
     */
    public function getCommand($useOptions=true, $useArguments=true)
    {
        $command = $this->_command;
        if ($useOptions) {
            foreach( $this->_options as $opt => $value ) {
                if ( $value === true ) {
                    $command .= " $opt";
                } else {
                    $command .= "$opt $value";
                }
            }
        }
        if ($useArguments) {
            foreach( $this->_arguments as $arg ) {
                $command .= " $arg";
            }
        }
        return $command;
    }

    /**
     * Sets the command to be executed.
     *
     * @param   string      $command         a command/script name to be executed
     * @param   bool        $restOptions     if command options should be reset or not
     * @return  Parsonline_System_Process
     */
    public function setCommand($command, $resetOptions=true)
    {
        $this->_command = (string) $command;
        if ($resetOptions) {
            $this->_options = array();
        }
        return $this;
    }

    /**
     * Returns command options of the process as an associative array
     * of command options => values
     * 
     * @return  array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Adds a command line option to the process. if the option has no special
     * value (like mode enabling options) use a boolean True value for the value
     * of the option.
     *
     * Note: both option and value are escaped
     * 
     * @param   string      $name       name of the option
     * @param   mixed       $vlaue      value of the option
     * @return  Parsonline_System_Process
     */
    public function addOption($name, $value)
    {
        $this->_options[escapeshellarg($name)] = escapeshellarg($value);
        return $this;
    }
    
    /**
     * Sets command options for the process. each option is a key in the array.
     * if the option has no value (like mode enableing options), use a boolean True
     * value for the value of that option.
     * 
     * Options could be an object with toArray() method.
     * 
     * Note: All the  values and options are escaped.
     *
     * @param   array|object    $options    associative array of command option => values
     * @return  Parsonline_System_Process
     * @throws  Parsonline_Exception_ValueException
     */
    public function setOptions($options=array())
    {
        if ( is_object($options) ) {
            if (method_exists($options, 'toArray')) {
                $options = $options->toArray();
            } else {
                $options = get_object_vars($options);
            }
        } elseif (!is_array($options) ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException("Options should be an associative array");
        }
        $this->_options = array();
        foreach($options as $key => $value) {
            $this->_options[escapeshellarg($key)] = escapeshellarg($value);
        }
        return $this;
    }

    /**
     * Returns command arguments of the process an an indexed array
     *
     * @return  array
     */
    public function getArguments()
    {
        return $this->_arguments;
    }

    /**
     * Sets command arguments for the process.
     * 
     * Note: arguments are escaped.
     *
     * @param   array|object    $args   associative array of command option => values
     * @return  Parsonline_System_Process
     * @throws  Parsonline_Exception_ValueException
     */
    public function setArguments($args=array())
    {
        if ( is_object($args) ) {
            if (method_exists($args, 'toArray')) {
                $args = $args->toArray();
            } else {
                $args = get_object_vars($args);
            }
        } elseif (!is_array($args) ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException("arguments should be an associative array");
        }
        $this->_arguments = array();
        foreach($args as $value) {
            $this->_arguments[] = escapeshellarg($value);
        }
        return $this;
    }
    
    /**
     * Adds a command line argument to the process.
     *
     * @param   mixed      $arg
     * @return  Parsonline_System_Process
     */
    public function addArgument($arg)
    {
        array_push($this->_arguments, escapeshellarg($arg));
        return $this;
    }

    /**
     * Returns timestamp of start time of the process
     * 
     * @return  int
     */
    public function getStartTime()
    {
        return $this->_startTime;
    }

    /**
     * Sets the timestamp of start time of the process
     * 
     * @param   int|null     $time  [optional] leave null for current time
     */
    public function setStartTime($time=null)
    {
        if ( is_null($time) ) {
            $time = time();
        } else {
            $time = intval($time);
        }
        $this->_startTime = $time;
    }


    /**
     * Retuns the process id of the command after it is ran. this is only
     * available for processe that start in background.
     * 
     * @return  int     process id
     */
    public function getProcessId()
    {
        return $this->_pid;
    }

    /**
     * Sets the process id of the command after it is ran
     *
     * @param   int     $pid
     * @return  Parsonline_Sysmte_Process
     */
    public function setProcessId($pid=null)
    {
        $this->_pid = intval($pid);
        return $this;
    }

    /**
     * if the output of the process is being recorded
     * 
     * @return bool
     */
    public function isOutputRecorded()
    {
       return $this->_recordOutput;
    }

    /**
     * If and where the output of the process should be recorded.
     * this is used for processes that are started in background.
     *
     * @param   bool        $recordOutput   if output of the process should be recorded
     * @param   string|null $output        name of a file to store output, null for a temp file
     * @return  Parsonine_System_Process
     * @throws  Parsonline_Exception_ValueException on invalid output param
     */
    public function setRecordOutput($recordOutput=true, $output=null)
    {
        $this->_recordOutput = true && $recordOutput;
        if ( $this->_recordOutput ) {
            if (!$output) {
                $output = tempnam();
            }
            $this->_outputFilename = $output;
        }
        return $this;
    }

    /**
     * Retuns the type (family) of the operating system of the process.
     * os type is based on Parsonline_System::getOsType()
     *
     * @return  string
     * @uses    Parsonline_System
     */
    public function getOsType()
    {
        if ( !$this->_os ) {
            /**
             * @uses Parsonline_System
             */
            $this->_os = Parsonline_System::getOsType();
        }
        return $this->_os;
    }

    /**
     * Returns the command that is used to run the process command in the
     * background.
     * 
     * Note: currently only works in unix like systems
     *
     * @return  string
     * @throws  Parsonline_Exception_SystemException
     */
    public function getBackgroundCommand()
    {
        if ( $this->getOsType() != 'win' ) {
            if ( $this->_recordOutput ) {
                $outfile = $this->_outputFilename;
            } else {
                $outfile = '/dev/null';
            }
            $bgCommand = 'nohup ' . $this->getCommand(true, true) . ' > ' . $outfile  . ' 2>&1';
        } else {
            /**
             *@uses Parsonline_Exception_SystemException 
             */
            require_once("Parsonline/Exception/SystemException.php");
            throw new Parsonline_Exception_SystemException("Background execution is not supported on this operating system yet");
            $bgCommand = $this->getCommand(true, true);
        }
        return $bgCommand;
    }

    /**
     * Starts the command process and sets the start time,
     * returns the process output if set to do so.
     *
     * @return  mixed  array of application output lines (if record output is enabled) or null
     */
    public function start($command=null, $recordOutput=null)
    {
        if ( !is_null($command) ) {
            $this->setCommand($command);
        }
        $command = $this->getCommand(true, true);
        if ( !$recordOutput && !$this->_recordOutput ) {
            $recordOutput = false;
        }
        $this->_startTime = time();
        if ( $recordOutput ) {
            $output = array();
            exec($command, $output);
            return $output;
        } else {
            exec($command);
        }
        return null;
    }

    /**
     * Starts the process in background, keeps the process ID of the process.
     * if it is set to record the output, will record the output of the process
     * in the specified process output file.
     *
     * @param   string      $command        [optional] a command to run instead of the process command itself
     * @return  Parsonline_System_Process
     */
    public function startInBackground($command=null)
    {
        if ( !is_null($command) ) {
            $this->setCommand($command);
        }
        $bgCommand = $this->getBackgroundCommand();
        if ( $this->getOsType() != 'win' ) {
            $bgCommand .= ' & echo $!';
            $output = array();
            $this->_startTime = time();
            exec($bgCommand, $output);
            $this->setProcessId($output[0]);
        } else {
            $this->_startTime = time();
            exec($bgCommand);
        }
        return $this;
    }

    /**
     * Returns the status code of the process.
     * status codes can be compared with class constants starting with STATUS.
     * this method is not implemented for not-unix-like operating systems
     *
     * @return  int     status code
     * @throws  Parsonline_Exception_SystemException
     */
    public function getStatus()
    {
        if (!$this->_pid) {
            return self::STATUS_UNKNOWN;
        }
        $osType = $this->getOsType();
        if ( $osType == 'nix'  || $osType == 'mac' ) {
            $command = 'ps '. $this->_pid;
            $output = array();
            exec($command, $output);
            if ( !isset($output[1]) ) return self::STATUS_FINISHED;
            return self::STATUS_RUNNING;
        } else {
            /**
             *@uses Parsonline_Exception_SystemException 
             */
            require_once("Parsonline/Exception/SystemException.php");
            throw new Parsonline_Exception_SystemException(
                "Process status is not implemented for this operating system yet"
            );
        }
    }

    /**
     * If it is set to record output, will return the output of the process.
     * this is mostly used for process started in background.
     *
     * @return  string|null
     * @throws  Parsonline_Exception_IOException on failure to read output file
     */
    public function getOutput()
    {
       if ( !$this->_recordOutput ) {
           return null;
       }
       if ( !file_exists($this->_outputFilename) || !is_file($this->_outputFilename) || !is_readable($this->_outputFilename) ) {
           /**
            *@uses  Parsonline_Exception_IOException 
            */
           require_once("Parsonline/Exception/IOException.php");
           throw new Parsonline_Exception_IOException(
                "Failed to access the output file on '{$this->_outputFilename}'"
            );
       }
       $contents = file_get_contents($this->_outputFilename);
       if ( $contents === false ) {
           /**
            *@uses  Parsonline_Exception_IOException 
            */
           require_once("Parsonline/Exception/IOException.php");
           throw new Parsonline_Exception_IOException("Failed to read output the file on '{$this->_outputFilename}'");
       }
       return $contents;
    }
    
}