<?php
//Parsonline/Network/SSH2/Shell/Command.php
/**
 * Defines Parsonline_Network_SSH2_Shell_Command class.
 * 
 * Parsonline
 * 
 * Copyright (c) 2010-2011 ParsOnline, Inc. (www.parsonline.com)
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
 * @copyright  Copyright (c) 2010-2011 ParsOnline, Inc. (www.parsonline.com)
 * @license    Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Parsonline_Network_SSH2
 * @subpackage  Shell
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.1.3 2011-03-05
*/

/**
 * @uses    Parsonline_Network_SSH2_Shell
 */
require_once('Parsonline/Network/SSH2/Shell.php');

/**
 * Parsonline_Network_SSH2_Shell_Command
 * 
 * Represents a command executed on an SSH2 shell environment.
 */
class Parsonline_Network_SSH2_Shell_Command
{
    /**
     * The command to execute
     * 
     * @var string
     */
    protected $_command = '';

    /**
     * If the data structure is locked
     * 
     * @var bool
     */
    protected $_locked = false;

    /**
     * The response of the command execution
     * 
     * @var string
     */
    protected $_response = '';

    /**
     * The shell who executed the command
     * 
     * @var Parsonline_Network_SSH2_Shell
     */
    protected $_shell = null;

    /**
     * Timestamp of when the execution finished
     *
     * @var float
     */
    protected $_endTime = null;

    /**
     * Timestamp of when the execution started
     *
     * @var float
     */
    protected $_startTime = null;

    /**
     * Constructor.
     *
     * @param   string  $command
     */
    public function __construct($command=null)
    {
        if ($command !== null) $this->setCommand($command);
    } // public function __construct()
    
    /**
     * Returns the string representation of the command object, which is the
     * actual command string.
     *
     * @return  string
     */
    public function __toString()
    {
        return $this->_command;
    }
    
    /**
     * Returns the command string that were executed
     * 
     * @return  string
     */
    public function getCommand()
    {
        return $this->_command;
    }

    /**
     * Sets the command string that were executed
     *
     * @param   string       $command
     * @return  Parsonline_Network_SSH2_Shell_Command
     * @throws  Parsonline_Exception_ContextException if object is locked
     */
    public function setCommand($command)
    {
        if ($this->_locked) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "Failed to modify object data. The object is locked and is read only"
            );
        }
        $this->_command = $command;
        return $this;
    }

    /**
     * Returns the end time of the command execution process
     *
     * @return  float|null
     */
    public function getEndTime()
    {
        return $this->_endTime;
    }

    /**
     * Sets the end time of the command execution process
     *
     * @param   float       $time
     * @return  Parsonline_Network_SSH2_Shell_Command
     * @throws  Parsonline_Exception_InvalidParameterException on none-positive time
     *          Parsonline_Exception_ContextException if object is locked
     */
    public function setEndTime($time=null)
    {
        if ($this->_locked) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "Failed to modify object data. The object is locked and is read only"
            );
        }
        if ($time === null) {
            $time = microtime(true);
        } elseif (0.0 >= $time) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            throw new Parsonline_Exception_InvalidParameterException(
                "End time should be positive float number", 0, null, 'time > 0', $time
            );
        } else {
            $time = floatval($time);
        }
        $this->_endTime = $time;
        return $this;
    } // public function setEndTime()

    /**
     * Returns the remote response to the command.
     *
     * @return  string
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * Sets the remote response to the command
     *
     * @param   string       $resp
     * @return  Parsonline_Network_SSH2_Shell_Command
     * @throws  Parsonline_Exception_ContextException if object is locked
     */
    public function setResponse($resp)
    {
        if ($this->_locked) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "Failed to modify object data. The object is locked and is read only"
            );
        }
        $this->_response = $resp;
        return $this;
    } // public function setResponse()

    /**
     * Returns the shell who executed the command
     *
     * @return  Parsonline_Network_SSH2_Shell
     */
    public function getShell()
    {
        return $this->_shell;
    }

    /**
     * Sets the shell who executed the command
     *
     * @param   Parsonline_Network_SSH2_Shell   $shell
     * @return  Parsonline_Network_SSH2_Shell_Command
     * @throws  Parsonline_Exception_ContextException if object is locked
     */
    public function setShell(Parsonline_Network_SSH2_Shell $shell)
    {
        if ($this->_locked) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "Failed to modify object data. The object is locked and is read only"
            );
        }
        $this->_shell = $shell;
        return $this;
    } // public function setShell()

    /**
     * Returns the start time of the command execution process
     *
     * @return  float|null
     */
    public function getStartTime()
    {
        return $this->_startTime;
    }

    /**
     * Sets the start time of the command execution process
     *
     * @param   float       $time
     * @return  Parsonline_Network_SSH2_Shell_Command
     * @throws  Parsonline_Exception_InvalidParameterException on none-positive time
     *          Parsonline_Exception_ContextException if object is locked
     */
    public function setStartTime($time=null)
    {
        if ($this->_locked) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "Failed to modify object data. The object is locked and is read only"
            );
        }
        if ($time === null) {
            $time = microtime(true);
        } elseif (0.0 >= $time) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            throw new Parsonline_Exception_InvalidParameterException(
                "Start time should be positive float number", 0, null, 'time > 0', $time
            );
        } else {
            $time = floatval($time);
        }
        $this->_startTime = $time;
        return $this;
    } // public function setStartTime()

    /**
     * Returns a float of seconds that took to execute the command, or false
     * if time data is not available yet.
     *
     * @return  float|false
     */
    public function getExecutionDuration()
    {
        if ($this->_startTime === null || $this->_endTime === null) {
            return false;
        }
        return $this->_endTime - $this->_startTime;
    } // public function getExecutionDuration()

    /**
     * Shows weather or not the command object is locked.
     *
     * @return  bool
     */
    public function isLocked()
    {
        return $this->_locked;
    }

    /**
     * Locks the command object.
     * Once the object is locked, it could not be modified anymore
     * and would continue its life cycle as read only.
     *
     * @return Parsonline_Network_SSH2_Shell_Command
     */
    public function lock()
    {
        $this->_locked = true;
        return $this;
    }
}