<?php
//Parsonline/Network/SSH2/Shell.php
/**
 * Defines Parsonline_Network_SSH2_Shell class.
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
 * @version     0.3.8 2012-02-26
*/

/**
 * @uses    Parsonline_Network_SSH2
 * @uses    Parsonline_Network_SSH2_Shell_Command
 */
require_once('Parsonline/Network/SSH2.php');
require_once('Parsonline/Network/SSH2/Shell/Command.php');

/**
 * Parsonline_Network_SSH2_Shell
 * 
 * Provides an Object Oriented interface to a SSH2 shell data channel.
 *
 * @see     Parsonline_Network_SSH2
 */
class Parsonline_Network_SSH2_Shell
{
    /**
     * Should automaticlly detect remote prompt string from the first response
     * 
     * @var bool
     */
    protected $_autoDetectPrompt = true;

    /**
     * Associative array of envrionment variables to propose to remote host
     *
     * @var array
     */
    protected $_environmentVariables = array();

    /**
     * Number of microseconds to sleep between command executions
     *
     * @var int
     */
    protected $_haltDelay = 100000;

    /**
     * Stack of executed commands as Parsonline_Network_SSH2_Shell_Command
     * objects
     *
     * @var array
     */
    protected $_history = array();

    /**
     * Array of callable references to be notified on each log emition
     *
     * @var array
     */
    protected $_loggers = array();

    /**
     * The prompt string of the remote shell
     * 
     * @var string
     */
    protected $_prompt = '';
    
    /**
     * If should record a history of all executed commands
     *
     * @var bool
     */
    protected $_recordHistory = false;

    /**
     * The SSH object hosting the Shell.
     *
     * @var Parsonline_Network_SSH2
     */
    protected $_ssh = null;
    
    /**
     * Shell data channel stream
     *
     * @var Parsonline_Network_SSH2_Stream
     */
    protected $_stream = null;

    /**
     * System resource unique ID
     *
     * @var string
     */
    protected $_systemResourceID = '';

    /**
     * Shell data transfer time out in seconds
     * 
     * @var int
     */
    protected $_timeout = 60;

    /**
     * Width and height of the virtual terminal
     *
     * @var array
     */
    protected $_terminalSize = array(SSH2_DEFAULT_TERM_WIDTH, SSH2_DEFAULT_TERM_HEIGHT);

    /**
     * Terminal size units. use SSH2_TERM_UNIT_* constants.
     *
     * @var int
     */
    protected $_terminalSizeUnit = SSH2_TERM_UNIT_CHARS;

    /**
     * Remote host virtual terminal type
     * 
     * @var string
     */
    protected $_terminalType = SSH2_DEFAULT_TERMINAL;
    
    /**
     * Constructor.
     * 
     * Creates a data shell over an SSH connection channel, to transfer data.
     *
     * @param   Parsonline_Network_SSH2    $ssh
     * @throws  Parsonline_Network_SSH2_Exception on disconnected SSH
     */
    public function __construct(Parsonline_Network_SSH2 $ssh)
    {
        if (!$ssh || !$ssh->getConnectionChannel(true) ) {
            /**
             * @uses    Parsonline_Network_SSH2_Exception
             */
            require_once('Parsonline/Network/SSH2/Exception.php');
            throw new Parsonline_Network_SSH2_Exception(
                "Failed to initialize the SSH Shell. The SSH could not be connected",
                Parsonline_Network_SSH2_Exception::CONNECTION
            );
        }
        $this->_ssh = $ssh;
    } // public function __construct()

    /**
     * Automatically called right before object is deleted.
     * closes the shell data stream.
     */
    public function __destruct()
    {
        $this->shutdown();
    }

    /**
     * Automatically called right before object is serialized.
     * closes the shell data stream.
     */
    public function __sleep()
    {
        $this->shutdown();
    }

    /**
     * If should automatically detect the remote shell prompt string from the first
     * Response after the shell has started.
     * 
     * @return  bool
     */
    public function getAutoDetectPromptString()
    {
        return $this->_autoDetectPrompt;
    }

    /**
     * Sets if should automatically detect the remote shell prompt string from the first
     * Response after the shell has started.
     *
     * @param   bool        $detect
     * @return  Parsonline_Network_SSH2_Shell
     */
    public function setAutoDetectPromptString($detect)
    {
        $this->_autoDetectPrompt = true && $detect;
        return $this;
    }

    /**
     * Returns an associative array of key => value pairs that are proposeed
     * to remote terminal as envrionment variables.
     * 
     * @return  array
     */
    public function getEnvironmentVariables()
    {
        return $this->_environmentVariables;
    }

    /**
     * Sets the associative array of key => value pairs that are proposeed
     * to remote terminal as envrionment variables.
     *
     * @param   array       $vars
     * @return  Parsonline_Network_SSH2_Shell
     */
    public function setEnvironmentVariables(array $vars)
    {
        $this->_environmentVariables = $vars;
        return $this;
    }

    /**
     * Returns the halt delay (microseconds) for shell.
     * This is the value the stream would block so resources get available.
     *
     * @return int
     */
    public function getHaltDelay()
    {
        return $this->_haltDelay;
    }

    /**
     * Sets the halt delay (microseconds) for shell.
     * This is the value the stream would block so resources get available.
     *
     * @param   int         $delay
     * @return  Parsonline_Network_SSH2_Shell
     * @throws  Parsonline_Exception_InvalidParameterException on negative time
     */
    public function setHaltDelay($delay)
    {
        if ( 0 > $delay) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                "Halt delay time should not be negative", 0, null,
                'none-negative integer', $delay
            );
        }
        $this->_haltDelay = intval($delay);
        return $this;
    } // public function setHaltDelay()

    /**
     * Clears the recorded history of executed command.
     * 
     * @return  Parsonline_Network_SSH2_Shell
     */
    public function clearHistory()
    {
        $this->_history = array();
        return $this;
    }

    /**
     * Returns an stack of executed command recorded in the history.
     * Each index is a Parsonline_Network_SSH2_Shell_Command object.
     * 
     * @return  array
     */
    public function getHistory()
    {
        return $this->_history;
    }
    
    /**
     * Returns the prompt string of the remote shell
     *
     * @return  string
     */
    public function getPromptString()
    {
        return $this->_prompt;
    }

    /**
     * Sets the prompts string of the remote shell
     *
     * @param   string  $prompt
     * @return  Parsonline_Network_SSH2_Shell
     */
    public function setPromptString($prompt)
    {
        $this->_prompt = strval($prompt);
        return $this;
    }

    /**
     * Returns if should record all executed commands
     *
     * @return  bool
     */
    public function getRecordHistory()
    {
        return $this->_recordHistory;
    }

    /**
     * Sets if should record all executed commands
     *
     * @param   bool        $record
     * @return  Parsonline_Network_SSH2_Shell
     */
    public function setRecordHistory($record)
    {
        return $this->_recordHistory = true && $record;
        return $this;
    }

    /**
     * Returns the SSH connection that the shell is attached to.
     *
     * @return  Parsonline_Network_SSH2
     */
    public function getSSH()
    {
        return $this->_ssh;
    }

    /**
     * Returns the data stream that is beeing used underhood to transfer data.
     * If the shell is not started, no stream is initialized so returns null.
     *
     * @return  Parsonline_Network_SSH2_Stream|null
     */
    public function getStream()
    {
        return $this->_stream;
    }

    /**
     * Returns the size of the remote vitual terminal as an array
     * of width and height values.
     *
     * @return  array
     */
    public function getTerminalSize()
    {
        return $this->_terminalSize;
    }

    /**
     * Sets the size of the remote virutal terminal.
     *
     * NOTE: does not affect the started shell.
     *
     * @param   int     $width
     * @param   int     $height
     * @return Parsonline_Network_SSH2_Shell
     * @throws  Parsonline_Exception_InvalidParameterException on none-positive size values
     */
    public function setTerminalSize($width, $height)
    {
        if (1 > $width) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                "Terminal width should be positive integer", 0, null, 'width > 0', $width
            );
        }
        if (1 > $height) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                "Terminal height should be positive integer", 0, null, 'height > 0', $height
            );
        }
        $this->_terminalSize = array($width, $height);
        return $this;
    } // public function setTerminalSize()

    /**
     * Returns the unit of size of the remote vitual terminal.
     *
     * @return  int
     */
    public function getTerminalSizeUnit()
    {
        return $this->_terminalSizeUnit;
    }

    /**
     * Sets the unit of size of the remote virutal terminal.
     * Use SSH2_TERM_UNIT_* constants.
     *
     * NOTE: does not affect the started shell.
     *
     * @param   int     $unit
     * @return Parsonline_Network_SSH2_Shell
     */
    public function setTerminalSizeUnit($unit)
    {
        $this->_terminalSizeUnit = $unit;
        return $this;
    }

    /**
     * Returns the timeout of shell channel stream in seconds.
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->_timeout;
    }

    /**
     * Sets the timeout of shell channel stream in seconds.
     *
     * @param   int         $timeout
     * @return  bool        success or fail
     * @throws  Parsonline_Exception_InvalidParameterException on negative timeout
     */
    public function setTimeout($timeout)
    {
        if (0 > $timeout) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                "SSH stream data transfere timeout should not be negative", 0, null,
                'none-negative integer', $timeout
            );
        }
        $this->_timeout = intval($timeout);
        if ($this->_stream) {
            return $this->_stream->setTimeout($timeout);
        }
        return false;
    } // public function setTimeout()
    
    /**
     * Sets data of the object from an array.
     * Returns an array, the first index is an array of keys used
     * to configure the object, and the second is an array of keys
     * that were not used.
     *
     * @param   array       $options        associative array of property => values
     * @return  array       array(sucess keys, not used keys)
     */
    public function setOptionsFromArray(array $options)
    {
        $result = array(array(), array());
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if ( in_array($method, $methods) ) {
                $this->$method($value);
                array_push($result[0], $key);
            } else {
                array_push($result[1], $key);
            }
        }
        return $result;
    } // public function setOptionsFromArray()

    /**
     * Removes all registered callable loggers.
     *
     * @return  Parsonline_Network_SSH2
     */
    public function clearLoggers()
    {
        $this->_loggers = array();
        return $this;
    }

    /**
     * Returns an array of callable references (function, or object method) that
     * are called on each log.
     *
     * @return  array
     */
    public function getLoggers()
    {
        return $this->_loggers;
    }

    /**
     * Registers a callable reference (function, or object method) so on
     * each initiated log, the loggers would be notified.
     *
     * Each logger should accept these paramters (with appropriate default values
     * incase some paramters are not provided):
     *
     *  <li>string  $message    log message</li>
     *  <li>int     $priority   standard log priority, 7 for DEBUG and 0 for EMERG. default is 6 INFO</li>
     *
     * NOTE: no validation is applied on the registered callable. any valid callable
     * reference could be registered, but they should be able to handle the
     * specified parameters.
     *
     * NOTE: Only those loggers loggers are assinged to the I/O stream of the shell,
     * that are registered before the shell is connected.
     *
     * @param   string|array    $logger   a string for function name, or an array of object, method name.
     * @return  Parsonline_Network_SSH2
     * @throws  Parsonline_Exception_InvalidParameterException on none callable parameter
     */
    public function registerLogger($logger)
    {
        if (!$logger || !is_callable($logger, false)) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                "Logger should be a string for function name, or an array of object, method name",
                0, null, 'callable', $logger
            );
        }
        array_push($this->_loggers, $logger);
        return $this;
    } // public function registerLogger()
    
    /**
     * Executes a command on the remote host, and returns a command object
     * that holds information about the execution.
     *
     * NOTE: The returned command object is locked() and can no longer be
     * modified.
     * 
     * NOTE: If shell is set to record the history, a reference to the executed
     * command would be recorded in the history.
     *
     * NOTE: This method call keeps reading until the stream is timed out or the
     * maximum number of response size is read from the stream.
     * Using executeAndWaitFormPrompt() is recommended.
     *
     * @see     executeAndWaitFormPrompt()
     *
     * @param   Parsonline_Network_SSH2_Shell_Command|string    $command
     * @param   null|int                                        $halt               number of microseconds to halt for command exectuion. null [default] is object halt delay
     * @param   int                                             $maxResponse        maximum size of the read response in bytes. 0 to read all.
     * @param   array                                           $responseObservers  array of callables to observe reading the response process
     * @param   int                                             $responsePacketSize size of packets of response to fetch, between each observer
     * @return  Parsonline_Network_SSH2_Shell_Command
     *
     * @throws  Parsonline_Network_SSH2_Exception on shell not started or disconnected
     *          Parsonline_Exception_InvalidParameterException on locked command object or negative numeric params
     */
    public function execute($command, $halt=null, $maxResponse=0, array $reasponseObservers=array(), $responsePackeSize=1024)
    {
        if (!$this->_stream) {
            /**
             * @uses    Parsonline_Network_SSH2_Exception
             */
            require_once('Parsonline/Network/SSH2/Exception.php');
            throw new Parsonline_Network_SSH2_Exception(
                "Failed to execute command. Data stream is not available. Connection might be lost or shell is not started yet.",
                Parsonline_Network_SSH2_Exception::STREAM_NOT_AVAILABLE
            );
        }

        if (!$command instanceof Parsonline_Network_SSH2_Shell_Command) {
            $command = new Parsonline_Network_SSH2_Shell_Command($command);
        } elseif($command->isLocked()) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                "Failed to execute command. The specified command is locked and can not be modified",
                0, null, 'Parsonline_Network_SSH2_Shell_Command', $command
            );
        }
        
        if ($halt === null) {
            $halt = $this->_haltDelay;
        } else {
            $halt = intval($halt);
        }
        
        /*@var $command Parsonline_Network_SSH2_Shell_Command */
        $command->setShell($this);
        $command->setStartTime();

        $bytes = $this->_stream->write($command->getCommand() . PHP_EOL);
        $this->_log(__METHOD__, "wrote '{$bytes}' bytes to remote host", Parsonline_Network_SSH2::LOG_DEBUG);
        $this->_stream->flush();

        /**
         * @TODO
         * 
         * Currently the stream_select() can not be used on SSH shell stream
         * due to a bug in PECL SSH client.
         * So we would just use the halt command to wait for some time before
         * fetching resposne from SSH shell stream.
         * 
         * When the bug is fixed, you could remove the "false" below to make sure
         * the read is called right after data is arrived to the stream.
         * 
         * @see https://bugs.php.net/bug.php?id=58974
         * 
         */
        if ( false && function_exists('stream_select') ) {
            $this->_log(__METHOD__, "using stream_select() call to wait for data to be available on stream", Parsonline_Network_SSH2::LOG_DEBUG);
            $readStreams = array();
            $readStreams[] =& $this->_stream->getStream();
            $writeStreams = array();
            $modified = stream_select($readStreams, $writeStreams, $readStreams, $this->_timeout);
            unset($readStreams);
            if ($modified) {
                $this->_log(__METHOD__, "stream_select() reported data is ready to fetch on SSH read stream", Parsonline_Network_SSH2::LOG_DEBUG);
            } elseif ($modified === 0) {
                $this->_log(
                    __METHOD__,
                    sprintf("stream_select() returned 0 so no data is available to read on SSH read stream. halting SSH shell for %d microseconds before force reading data from stream.", $halt),
                    Parsonline_Network_SSH2::LOG_DEBUG
                );
                $this->halt($halt);
            }
        } else {
            $this->_log(
                    __METHOD__,
                    sprintf("stream_select() is not available for SSH shell stream. halting for %d microseconds before force reading data from stream.", $halt),
                    Parsonline_Network_SSH2::LOG_DEBUG
                );
            $this->halt($halt);
        }
        
        $response = (string) $this->_stream->read($maxResponse, $reasponseObservers, $responsePackeSize);
        
        /*
         * check to make sure the command itself is not in the response
         */
        $pos = strpos($response, $command->getCommand());
        if ($pos === 0) {
            $response = substr($response, $pos + strlen($command->getCommand()) + 2); // exclude the executed command + the PHP_EOL from the beginnign of response
        }
        
        $this->_log(__METHOD__, sprintf("read '%d' bytes from remote host", strlen($response)), Parsonline_Network_SSH2::LOG_DEBUG);
        $command->setResponse($response);
        unset($response);
        $command->setEndTime();
        $command->lock();
        if ($this->_recordHistory) array_push($this->_history, $command);
        $this->_log(__METHOD__, sprintf("command executed after '%f' seconds", $command->getExecutionDuration()), Parsonline_Network_SSH2::LOG_DEBUG);
        return $command;
    } // public function execute()

    /**
     * Executes a command on the remote host, and reads the output until it reached
     * the specified string.
     *
     * Returns a command object that holds information about the execution.
     *
     * NOTE: The target string is NOT stripped out of the reponse.
     *
     * NOTE: The returned command object is locked() and can no longer be
     * modified.
     *
     * NOTE: If shell is set to record the history, a reference to the executed
     * command would be recorded in the history.
     *
     * @see     execute()
     * @see     executeAndWaitForPrompt()
     *
     * @param   Parsonline_Network_SSH2_Shell_Command|string    $command            command to execute
     * @param   string                                          $needle             the string to read until
     * @param   null|int                                        $halt               number of microseconds to halt for command exectuion. null [default] is object halt delay
     * @param   int                                             $maxResponse        maximum size of the read response in bytes. 0 to read all.
     * @param   array                                           $responseObservers  array of callables to observe reading the response process
     * @param   int                                             $$bufferMaxSize     size of local buffer of response to keep, between each observer call
     * @return  Parsonline_Network_SSH2_Shell_Command
     *
     * @throws  Parsonline_Network_SSH2_Exception on shell not started or disconnected
     *          Parsonline_Exception_InvalidParameterException on locked command object or negative numeric params
     *          Parsonline_Exception_ContextException on no prompt string set yet
     */
    public function executeAndReadUntil($command, $needle, $halt=null, $maxResponse=0, array $reasponseObservers=array(), $bufferMaxSize=1024)
    {
        if (!$this->_stream) {
            /**
             * @uses    Parsonline_Network_SSH2_Exception
             */
            require_once('Parsonline/Network/SSH2/Exception.php');
            throw new Parsonline_Network_SSH2_Exception(
                "Failed to execute command. Data stream is not available. Connection might be lost or shell is not started yet.",
                Parsonline_Network_SSH2_Exception::STREAM_NOT_AVAILABLE
            );
        }

        $needle = strval($needle);
        if ( empty($needle) ) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "Failed to execute command and wait for string. No needle string is specified."
            );
        }

        if (!$command instanceof Parsonline_Network_SSH2_Shell_Command) {
            $command = new Parsonline_Network_SSH2_Shell_Command($command);
        } elseif($command->isLocked()) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                "Failed to execute command. The specified command is locked and can not be modified",
                0, null, 'Parsonline_Network_SSH2_Shell_Command', $command
            );
        }
        
        if ($halt === null) {
            $halt = $this->_haltDelay;
        } else {
            $halt = intval($halt);
        }
        
        /*@var $command Parsonline_Network_SSH2_Shell_Command */
        $command->setShell($this);
        $command->setStartTime();
        
        $bytes = $this->_stream->write($command->getCommand() . PHP_EOL);
        $this->_stream->flush();
        $this->_log(__METHOD__, "wrote '{$bytes}' bytes to remote host", Parsonline_Network_SSH2::LOG_DEBUG);
        
        /**
         * @TODO
         * 
         * Currently the stream_select() can not be used on SSH shell stream
         * due to a bug in PECL SSH client.
         * So we would just use the halt command to wait for some time before
         * fetching resposne from SSH shell stream.
         * 
         * When the bug is fixed, you could remove the "false" below to make sure
         * the read is called right after data is arrived to the stream.
         * 
         * @see https://bugs.php.net/bug.php?id=58974
         * 
         */
        if ( false && function_exists('stream_select') ) {
            $this->_log(__METHOD__, "using stream_select() call to wait for data to be available on stream", Parsonline_Network_SSH2::LOG_DEBUG);
            $readStreams = array();
            $readStreams[] =& $this->_stream->getStream();
            $writeStreams = array();
            $modified = stream_select($readStreams, $writeStreams, $readStreams, $this->_timeout);
            unset($readStreams);
            if ($modified) {
                $this->_log(__METHOD__, "stream_select() reported data is ready to fetch on SSH read stream", Parsonline_Network_SSH2::LOG_DEBUG);
            } elseif ($modified === 0) {
                $this->_log(
                    __METHOD__,
                    sprintf("stream_select() returned 0 so no data is available to read on SSH read stream. halting SSH shell for %d microseconds before force reading data from stream.", $halt),
                    Parsonline_Network_SSH2::LOG_DEBUG
                );
                $this->halt($halt);
            }
        } else {
            $this->_log(
                    __METHOD__,
                    sprintf("stream_select() is not available for SSH shell stream. halting for %d microseconds before force reading data from stream.", $halt),
                    Parsonline_Network_SSH2::LOG_DEBUG
                );
            $this->halt($halt);
        }
        
        $response = (string) current($this->_stream->readUntil($needle, $maxResponse, true, $reasponseObservers, $bufferMaxSize));
        
        $commandLength = strlen($command->getCommand());
        $responseLength = strlen($response);
        $promptLength = strlen($this->_prompt);
        
        /*
        * check to make sure the command itself is not in the response
        */
        if ($responseLength) {
            $pos = strpos($response, $command->getCommand());
            if ($pos === 0) {
                $response = substr($response, $pos + $commandLength + 2); // exclude the executed command + the PHP_EOL from the beginnign of response
                $responseLength = strlen($response);
            }
            unset($pos);
        }
        
        $this->_log(__METHOD__, sprintf("read '%d' bytes from remote host", $responseLength), Parsonline_Network_SSH2::LOG_DEBUG);
        $command->setResponse($response);
        unset($response, $responseLength, $commandLength, $promptLength);

        $command->setEndTime();
        $command->lock();
        if ($this->_recordHistory) array_push($this->_history, $command);
        $this->_log(__METHOD__, sprintf("command executed after '%f' seconds", $command->getExecutionDuration()), Parsonline_Network_SSH2::LOG_DEBUG);
        return $command;
    } // public function executeAndReadUntil()
    
    /**
     * Executes a command on the remote host, and reads the output until it reached
     * the prompt string.
     *
     * Returns a command object that holds information about the execution.
     *
     * NOTE: The prompt string is stripped out of the remote reponse.
     *
     * NOTE: The returned command object is locked() and can no longer be
     * modified.
     *
     * NOTE: If shell is set to record the history, a reference to the executed
     * command would be recorded in the history.
     *
     * @see     execute()
     *
     * @param   Parsonline_Network_SSH2_Shell_Command|string    $command            command to execute
     * @param   null|int                                        $halt               number of microseconds to halt for command exectuion. null [default] is object halt delay
     * @param   int                                             $maxResponse        maximum size of the read response in bytes. 0 to read all.
     * @param   array                                           $responseObservers  array of callables to observe reading the response process
     * @param   int                                             $$bufferMaxSize     size of local buffer of response to keep, between each observer call
     * @return  Parsonline_Network_SSH2_Shell_Command
     *
     * @throws  Parsonline_Network_SSH2_Exception on shell not started or disconnected
     *          Parsonline_Exception_InvalidParameterException on locked command object or negative numeric params
     *          Parsonline_Exception_ContextException on no prompt string set yet
     */
    public function executeAndWaitForPrompt($command, $halt=null, $maxResponse=0, array $reasponseObservers=array(), $bufferMaxSize=1024)
    {
        if (!$this->_stream) {
            /**
             * @uses    Parsonline_Network_SSH2_Exception
             */
            require_once('Parsonline/Network/SSH2/Exception.php');
            throw new Parsonline_Network_SSH2_Exception(
                "Failed to execute command. Data stream is not available. Connection might be lost or shell is not started yet.",
                Parsonline_Network_SSH2_Exception::STREAM_NOT_AVAILABLE
            );
        }

        if ( empty($this->_prompt) ) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "Failed to execute command and waite for prompt. No prompt string is set yet."
            );
        }

        if (!$command instanceof Parsonline_Network_SSH2_Shell_Command) {
            $command = new Parsonline_Network_SSH2_Shell_Command($command);
        } elseif($command->isLocked()) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                "Failed to execute command. The specified command is locked and can not be modified",
                0, null, 'Parsonline_Network_SSH2_Shell_Command', $command
            );
        }
        
        if ($halt === null) {
            $halt = $this->_haltDelay;
        } else {
            $halt = intval($halt);
        }
        
        /*@var $command Parsonline_Network_SSH2_Shell_Command */
        $command->setShell($this);
        $command->setStartTime();
        $responseCommand = $this->executeAndReadUntil( (string) $command->getCommand(), $this->_prompt, $halt, $maxResponse, $reasponseObservers, $bufferMaxSize);
        $response = $responseCommand->getResponse();
        unset($responseCommand);
        
        $commandLength = strlen($command->getCommand());
        $responseLength = strlen($response);
        $promptLength = strlen($this->_prompt);
        
        /*
        * check to make sure the prompt string is not in the response
        */
        if ( $responseLength >= $promptLength ) {
            $pos = strrpos($response, $this->_prompt);
            if ($pos !== false) {
                $response = substr($response, 0, -$promptLength);
                $responseLength = strlen($response);
            }
        }
        
        $this->_log(__METHOD__, sprintf("read '%d' bytes from remote host", $responseLength), Parsonline_Network_SSH2::LOG_DEBUG);
        $command->setResponse($response);
        unset($response, $responseLength, $commandLength, $promptLength);

        $command->setEndTime();
        $command->lock();
        if ($this->_recordHistory) array_push($this->_history, $command);
        $this->_log(__METHOD__, sprintf("command executed after '%f' seconds", $command->getExecutionDuration()), Parsonline_Network_SSH2::LOG_DEBUG);
        return $command;
    } // public function executeAndWaitForPrompt()

    /**
     * Returns the shell system resource ID. The ID is the resource ID of the
     * SSH connection that the shell uses, separated by a colon
     * from the stream ID that the shell is using underneath.
     *
     * @return  string          PID:SSH connection ID:Stream ID
     */
    public function getSystemResourceID()
    {
        if (!$this->_systemResourceID) {
            $this->_systemResourceID = $this->_ssh->getSystemResourceID() . ':'. ($this->_stream ? intval($this->_stream->getStream()) : '_');
        }
        return $this->_systemResourceID;
    } // public function getSystemResourceID()

    /**
     * Halts the current process (blocking) for the specified value
     * in the getHaltDelay().
     *
     * @see     getHaltDelay()
     *
     * @param   int|null        $time       time to halt the process in microseconds
     * @return  Parsonline_Network_SSH2_Shell
     */
    public function halt($time=null)
    {
        $time = ($time === null) ? $this->_haltDelay : intval($time);
        $this->_log(__METHOD__, "halting process for {$time} microseconds", Parsonline_Network_SSH2::LOG_DEBUG);
        usleep($time);
        $this->_log(__METHOD__, 'resuming process', Parsonline_Network_SSH2::LOG_DEBUG);
        return $this;
    } // public function halt()

    /**
     * Determines if the SSH connection is stablished and open.
     *
     * @return  bool
     */
    public function isConnected()
    {
        if ($this->_stream) {
            return $this->_stream->isConnected();
        }
        return false;
    } // public function isConnected()

    /**
     * Initializes the shell stream based on the configurations.
     *
     * Since most SSH connections provide a user command prompt after
     * loging, after the shell is started, the first output from the remote
     * host (wich could be the prompt string) is returned as a psudo command.
     * Returning Parsonline_Network_SSH2_Command has an empty string command string
     * and the response is actually the the first output from the remote host,
     * if any available.
     *
     * If the shell is set to keep command history, this command is also recorded
     * in the history.
     * 
     * NOTE: Closes any previously openned data streams assigned to the shell.
     * 
     * @return  Parsonline_Network_SSH2_Command
     * @throws  Parsonline_Network_SSH2_Exception on SSH2 connection loss, or failure to achieve the shell
     */
    public function start()
    {
        $ssh = $this->getSSH();
        if ( !$ssh->isConnected() ) {
            /**
             * @uses    Parsonline_Network_SSH2_Exception
             */
            require_once('Parsonline/Network/SSH2/Exception.php');
            throw new Parsonline_Network_SSH2_Exception(
                "Failed to request the shell. SSH connection is lost",
                Parsonline_Network_SSH2_Exception::CONNECTION
            );
        }
        
        $this->_close();
        $channel =& $ssh->getConnectionChannel();

        $this->_log(__METHOD__, 'requesting shell from the SSH connection channel');

        /*
         * psudo command to gather information about the first bytes transferred
         * over the shell. this is mostly to record the first response from the
         * remote host, which is mostly the prompt string of remote shell
         */
        $command = new Parsonline_Network_SSH2_Shell_Command();
        $command->setCommand('');
        $command->setStartTime();
        
        $streamResource = ssh2_shell($channel, $this->_terminalType, $this->_environmentVariables, $this->_terminalSize[0], $this->_terminalSize[1], $this->_terminalSizeUnit);
        if (!$streamResource) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "Failed to request the shell from the SSH connection channel"
            );
        }
        $this->_log(__METHOD__, 'shell achieved from the SSH connection channel. configuring the data stream', Parsonline_Network_SSH2::LOG_DEBUG);
        
        /**
         * @uses    Parsonline_Network_SSH2_Stream
         */
        require_once('Parsonline/Network/SSH2/Stream.php');
        $this->_stream = new Parsonline_Network_SSH2_Stream(
                            array(
                                'blocking' =>  true,
                                'strictModeChecking' => true,
                                'timeout' =>  $this->_timeout,
                                'ssh' => $ssh,
                                'writeBufferSize' => 0,
                                'readBufferSize' => 0,
                                'stream' => &$streamResource
                            )
                        );

        // invalidate pervious resource ID so new stream ID would be in the future system resource ID
        $this->_systemResourceID = '';
        
        $this->_log(__METHOD__, 'reading first response from remote host ...', Parsonline_Network_SSH2::LOG_DEBUG);
        $resp = $this->_stream->read();
        $this->_log(__METHOD__, "first response from remote host is '{$resp}'", Parsonline_Network_SSH2::LOG_DEBUG);
        $command->setEndTime();
        $command->setResponse($resp);
        unset($resp);
        $command->setShell($this);
        $command->lock();
        
        if ($this->_autoDetectPrompt) {
            // pick the last line of the output as the prompt string of remote shell
            $responseLines = explode(PHP_EOL, trim($command->getResponse()));
            $this->setPromptString(array_pop($responseLines));
            unset($responseLines);
        }

        $this->_init($command);
        $this->_log(__METHOD__, "shell started after '{$command->getExecutionDuration()}' seconds successfully!");
        return $this;
    } // public function start()

    /**
     * Closes the shell, with termination routines.
     *
     * Calls the termination routine, and closes the shell data channel
     * stream, if not disconnected yet.
     *
     * @return  bool    true on a clean shutdown, false if there were problems
     */
    public function shutdown()
    {
        $this->_log(__METHOD__, 'shutting down the shell');
        $this->_terminate();
        return $this->_close();
    } // public function shutdown()

    /**
     * Force Closes the shell data stream.
     *
     * NOTE: no termination routine is called and the stream is forced to close.
     *
     * @see shutdown()
     *
     * @return  bool    true on a clean close, false if there were problems
     */
    protected function _close()
    {
        $closed = true;
        if ($this->_stream) {
            $this->_log(__METHOD__, 'closing the data stream of the shell', Parsonline_Network_SSH2::LOG_DEBUG);
            $closed = $this->_stream->close();
        }
        $this->_stream = null;
        $this->_systemResourceID = '';
        return $closed;
    } // protected function _close()

    /**
     * Initiate the shell. Is called automatically right after the shell
     * has started.
     *
     * @see     start()
     * @see     _terminate()
     *
     * @param   Parsonline_Network_SSH2_Shell_Command|null      $command    the first response from remote host
     */
    protected function _init(Parsonline_Network_SSH2_Shell_Command $command=null)
    {
    } // public function _init()
    
    /**
     * Notifies all registered loggers.
     *
     * @param   string  $signature      shell ID and the name of the method sending the log
     * @param   string  $message        message string being logged
     * @param   int     $priority       the standard priority of the log. 7 for DEBUG 0 for EMERG. default is 6 INFO
     */
    protected function _log($signature, $message, $priority=Parsonline_Network_SSH2::LOG_INFO)
    {
        $message = "(SSH shell [{$this->getSystemResourceID()}]) " . $signature . ' > ' . $message;
        foreach($this->_loggers as $log) {
            call_user_func($log, $message, $priority);
        }
    } // protected function _log()

    /**
     * Terminate the shell by doing last chance cleanup code,
     * right before the shell is closed. Could be overriden to provide clean
     * shell termination routine for different platforms.
     *
     * NOTE: By default sends an 'exit' command to remote host.
     *
     * @see     _init()
     * @see     shutdown()
     */
    protected function _terminate()
    {
        $this->_log(__METHOD__, 'running the termination routine', Parsonline_Network_SSH2::LOG_DEBUG);
        try {
            $this->execute('exit');
        } catch(Exception $exp) {
        }
    } // public function _terminate()
}