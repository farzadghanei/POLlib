<?php
//Parsonline/CliScriptAbstract.php
/**
 * Defines the Parsonline_CliScriptAbstract class.
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
 * @package     Parsonline
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.3.0 2012-07-08
 */

/**
 * Parsonline_CliScriptAbstract
 * 
 * Abstract class for command line scripts. Provides common functionality
 * for CLI based applications.
 * 
 * @abstract
 */
abstract class Parsonline_CliScriptAbstract
{
    const INFO_AUTHOR = 'author';
    const INFO_COPY_RIGHT = 'copyright';
    const INFO_DESCRIPTION = 'description';
    const INFO_DIRECTORY_PATH = 'directory_path';
    const INFO_FILE_NAME = 'file_name';
    const INFO_NAME = 'name';
    const INFO_VERSION = 'version';
    
    const CONFIG_DEBUG = 'debug';
    
    /**
     * Array of meta data of the script
     * 
     * @var array
     */
    protected $_info = array();

    /**
     * Timestamp of when the process has started
     * 
     * @var int
     */
    protected $_processStartTime = null;
    
    /**
     * Array of configurations of the script
     * 
     * @var array
     */
    protected $_config = array();
    
    /**
     * Array of arguments
     * 
     * @var array
     */
    protected $_args = array();
    
    /**
     * The name of the lock file for the process
     * 
     * @var string
     */
    protected $_lockFilename = '';
    
    /**
     * Array of loggers. each logger is a callable.
     * 
     * @var array
     */
    protected $_loggers = array();
    
    /**
     * The argument parser used to parse command line parameters.
     * 
     * @var object
     */
    protected $_argParser = null;
    
    /**
     * Callable functions/methods to be notified on each data output call
     * 
     * @var array
     */
    protected $_listenersOnOutput = array();
    
    /**
     * A stream resource to use as the standard output stream.
     * Null to use the process stdout.
     * False to disbale output.
     * 
     * @var resource|null|false
     */
    protected $_stdout = null;
    
    /**
     * A stream resource to use as the standard error stream.
     * Null to use the process stderr.
     * 
     * @var resource|null|false
     */
    protected $_stderr = null;
    
    /**
     * A stream resource to use as the standard input stream.
     * 
     * @var resource|null
     */
    protected $_stdin = null;
    
    /**
     * Constructor.
     * 
     * Registers a clean shutdown method for shutdown callback, and
     * provides some information.
     * 
     * Setups the default streams for STDOUT, STDERR, STDIN.
     */
    public function __construct()
    {
        $this->_processStartTime = microtime(true);
        
        if ( is_resource(STDERR) ) {
            $this->_stderr = STDERR;
        } else {
            $this->_stderr = null;
        }
        
        if ( is_resource(STDIN) ) {
            $this->_stdin = STDIN;
        } else {
            $this->_stdin = null;
        }
        
        if ( is_resource(STDOUT) ) {
            $this->_stdout = STDOUT;
        } else {
            $this->_stdout = null;
        }
        
        $this->_init();
    } // public function __construct()
    
    /**
     * Destructor.
     * 
     * make a clean shutdown on object destruction (probably at the end of the script).
     */
    public function __destruct()
    {
        $this->cleanSemiShutdown();
    }
    
    /**
     * Initializes the script object.
     * could be used in child classes for more configurations.
     * 
     * By default initializes the default information and default
     * configurations of the script object.
     * 
     * @return  Parsonline_CliScriptAbstract
     */
    protected function _init()
    {
        $this->_info[self::INFO_DIRECTORY_PATH] = realpath( dirname(__FILE__) );
        $this->_info[self::INFO_FILE_NAME] = basename(__FILE__);
        $this->setConfig($this->_getDefaultConfig());
        return $this;
    }
    
    /**
     * Initializes the script confugrations, setting the default
     * configurations.
     * 
     * @return  Parsonline_CliScriptAbstract
     */
    protected function _getDefaultConfig()
    {
        $config = array();
        $config[self::CONFIG_DEBUG] = false;
        return $config;
    }
    
    /**
     * Returns the timestamp of the time process started
     *
     * @return  float
     */
    public function getProcessStartTime()
    {
        return $this->_processStartTime;
    }
    
    /**
     * Returns the date and time of the time process started.
     *
     * @params  string      $format     the time format, used in PHP date function. defualt is 'Y-m-d H:i:s'
     * @return  string
     */
    public function getProcessStartDateTime($format='')
    {
        $format = $format ? strval($format) : 'Y-m-d H:i:s';
        return date($format, intval($this->getProcessStartTime()));
    }
    
    /**
     * Returns the standard output stream of the script object.
     * By default returns null, which means the process stdout would be
     * used.
     * 
     * @return  resource
     */
    public function &getStdOut()
    {
        return $this->_stdout;
    }
    
    /**
     * Sets the standard output stream of the script object.
     * 
     * @param   resource    $stream
     * @return  Parsonline_CliScriptAbstract 
     */
    public function setStdOut(&$stream)
    {
        $this->_stdout =& $stream;
        return $this;
    }
    
    /**
     * Returns the standard error stream of the script object.
     * By default returns null, which means the process stderr would be
     * used.
     * 
     * @return  resource|null
     */
    public function &getStdErr()
    {
        return $this->_stderr;
    }
    
    /**
     * Sets the standard error stream of the script object.
     * If null is passed in, the process stdout would be used.
     * 
     * @param   resource    $stream
     * @return  Parsonline_CliScriptAbstract 
     */
    public function setStdErr(&$stream)
    {
        $this->_stderr =& $stream;
        return $this;
    }
    
    /**
     * Returns the standard input stream of the script object.
     * By default returns null, which means the process stdin would be
     * used.
     * 
     * @return  resource|null
     */
    public function &getStdIn()
    {
        return $this->_stdin;
    }
    
    /**
     * Sets the standard input stream of the script object.
     * If null is passed in, the process stdin would be used.
     * 
     * @param   resource   $stream
     * @return  Parsonline_CliScriptAbstract 
     */
    public function setStdIn(&$stream)
    {
        $this->_stdin =& $stream;
        return $this;
    }

    /**
     * Returns the information about the script.
     * By default returns all information as an array. If a key is specified,
     * returns the specified information.
     * If the specified key does not exists, returns null.
     *
     * @param   string|null $key
     * @return  mixed
     */
    public function getInfo($key=null)
    {
        if (!$key) return $this->_info;
        if ( !isset($this->_info[$key]) ) return null;
        return $this->_info[$key];
    }
    
    /**
     * Returns the configuration of script.
     * By default returns all configurations as an array. If a key is specified,
     * returns the specified configuration.
     * if the specified key does not exists, returns null.
     *
     * @param   string|null     $key
     * @return  minxed
     */
    public function getConfig($key=null)
    {
        if (!$key) return $this->_config;
        if ( !isset($this->_config[$key]) ) return null;
        return $this->_config[$key];
    }

    /**
     * Sets a configuration for the script. If the configuration is an array,
     * the whole configuration will be replaced, otherwise it will be used as
     * the config key and the second argument is used as the value.
     *
     * @param   string|array        $config
     * @param   mixed               $value
     * @return  Parsonline_CliScriptAbstract        object self reference
     */
    public function setConfig($config=null, $value=null)
    {
        if ( is_array($config) ) {
            $this->_config = $config;
        } else {
            $this->_config[$config] = $value;
        }
        return $this;
    }
    
    /**
     * Returns the argument of script.
     * By default returns all arguments as an array. If a key is specified,
     * returns the specified argument.
     * if the specified key does not exists, returns null.
     *
     * @param   string      $key
     * @return  minxed
     */
    public function getArgument($key=null)
    {
        if (!$key) return $this->_args;
        if (!isset($this->_args[$key])) return null;
        return $this->_args[$key];
    }

    /**
     * Sets a argument for the script.
     * If the argument is an array, All of the arguments will be replaced.
     * otherwise it will be used as the arg key and the second argument is
     * used as the value.
     *
     * @param   string|array        $arg
     * @param   mixed               $value
     * @return  Parsonline_CliScriptAbstract        object self reference
     */
    public function setArgument($arg=null, $value=null)
    {
        if ( is_array($arg) ) {
            $this->_args = $arg;
        } else {
            $this->_args[$arg] = $value;
        }
        return $this;
    }
    
    /**
     * Returns the object used to parse comamnd line arguments.
     * 
     * An instance of PEAR Console_CommandLine class is suggested, but not
     * required.
     * 
     * @link    http://pear.php.net/package/Console_CommandLine
     * 
     * @return  object
     */
    public function getArgumentParser()
    {
        return $this->_argParser;
    }

    /**
     * Sets the object used to parse comamnd line arguments.
     * An instance of PEAR Console_CommandLine class is suggested, but
     * not required.
     *
     * @link    http://pear.php.net/package/Console_CommandLine
     * 
     * @param   object  $parser
     * @return  Parsonline_CliScriptAbstract    object self reference
     */
    public function setArgumentParser($parser)
    {
        $this->_argParser = $parser ? $parser : null;
        return $this;
    }
    
    /**
     * Returns the array of loggers.
     *
     * @return  array
     */
    public function getLoggers()
    {
        return $this->_loggers;
    }

    /**
     * Registers a logger for the application.
     * 
     * Logger should be a callable (function string ro array of object, method),
     * or an object with a method named (log).
     * 
     * The logger should accept the log message and the priority as an integer.
     * Priorities are passed as standard syslog priorities.
     *
     * @param   callable|object  $log
     * @return  Parsonline_CliScriptAbstract    object self reference
     * @throws  Parsonline_Exception_ValueException
     */
    public function registerLogger($logger)
    {
        if ( is_object($logger) ) {
            $logger = array($logger, 'log');
        }
        if ( !is_callable($logger,true) ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("Failed to register the logger. It is not a valid callable");
        }
        $this->_loggers[] = $logger;
        return $this;
    } // public function registerLogger()
    
    /**
     * Clears all registered loggers of the CLI script.
     * 
     * @return  Parsonline_CliScriptAbstract
     */
    public function clearLoggers()
    {
        $this->_loggers = array();
        return $this;
    }
    
    /**
     * Returns the name of the lock file of the script.
     * 
     * @return  string
     */
    public function getLockFilename()
    {
        return $this->_lockFilename;
    }

    /**
     * Sets the name of the lock file
     *
     * @param   string      $lock
     * @return  CliApplicationAbstract      object self reference
     */
    public function setLockFilename($lock)
    {
        $this->_lockFilename = strval($lock);
        return $this;
    }
    
    /**
     * Registeres a callable object as the listener on output call.
     * The callable will be called with a string parameter.
     *
     * @param   string|array    $listener
     * @return  CliApplicationAbstract  object self reference
     * @throws  Parsonline_Exception_ValueException
     */
    public function registerListenerOnOutput($listener)
    {
        if ( is_callable($listener, false) ) {
            array_push($this->_listenersOnOutput, $listener);
        } else {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("Listener is not a valid callable");
        }
        return $this;
    } // public function registerListenerOnOutput()

    /**
     * Returns the array of registered listeners objects that will be called
     * on data output call.
     *
     * @return  array
     */
    public function getListenersOnOutput()
    {
        return $this->_listenersOnOutput;
    }

    /**
     * unregisters all registered listeners on data output calls.
     *
     * @return  array
     */
    public function unregisterListenersOnVerbose()
    {
       $this->_listenersOnOutput = array();
    }
    
    /**
     * Parses script arguments from the specified array.
     * Reads script configurations and arguments.
     * Returns an array whose first index is an associative array
     * of congigurations key => values, and the second is an array of
     * script arguments.
     *
     * @return  array   array(array(config => value, ...), array(arg1, arg2,...))
     */
    abstract public function parseArguments(array $argv);
    
    /**
     * Logs the message using the objects logger, If a logger has been set.
     * Returns the logged message.
     * A signature could be passed to specify who is initiating the log.
     * 
     * @param   string      $message        log message
     * @param   int         $level          log level. default is 6 for INFO
     * @param   string      $signature
     * @return  string      $message
     * @throws  Parsonline_Exception
     */
    protected function _log($message, $level=LOG_INFO, $signature='')
    {
        if ($signature) {
            $message = "<{$signature}> {$message}";
            unset($signature);
        }        
        foreach ($this->_loggers as $logger) {
            call_user_func($logger, $message, $level);
        }
        return $message;
    } // protected function _log()
    
    /**
     * Notifies all registered listeners on data output call.
     *
     * @param   string  $message    the message passed to each listener
     * @return  int     number of notified listeners
     */
    protected function _notifyListenersOnOutput($message)
    {
        foreach($this->_listenersOnOutput as $listener) {
            call_user_func($listener, $message);
        }
    }
    
    /**
     * Outputs the string.
     * notifies listeners on output.
     * All data output methods of the script object use this method internally
     * to output data.
     * 
     * @param   string  $string
     * @return  int     bytes written to stdout
     */
    public function print_($string)
    {
        $string = strval($string);
        $this->_notifyListenersOnOutput($string);
        $bytes = 0;
        
        if ($this->_stdout) {
            $bytes = fwrite($this->_stdout, $string);
        } elseif ($this->_stdout === null) {
            echo $string;
            $bytes = strlen($string);
        }
        return $bytes;
    } // public function print_
    
    /**
     * Outputs the string to the standard error stream.
     * 
     * @param   string  $string
     * @return  int     bytes written to stderr
     */
    public function printError($string)
    {
        $string = strval($string);
        if ( is_resource($this->_stderr) ) {
            $bytes = fwrite($this->_stderr, $string);
        } else {
            $bytes = fwrite(STDERR, $string);
        }
        return $bytes;
    } // public function printError()
    
    /**
     * Prints the specified message to the output stream, and adds a new line
     * character in the end.
     * Uses the platform new line character by default, but this can be changed.
     * Use false for the new line character to disable appending the new line.
     *
     * @param   string      $message        the message to print out
     * @param   string      $eol            [optional] if specified, this string will be used as the new line character.
     * @return  string      the printed string
     */
    public function println($message, $eol=PHP_EOL)
    {
        if ( $eol !== false ) {
            $message .= $eol;
        }
        $this->print_($message);
        return $message;
    }
    
    /**
     * Reports and exception by logging the exception, and outputing
     * useful information.
     *
     * @param   Exception   $exception
     * @return  Parsonline_CliScriptAbstract    object self reference
     */
    protected function _reportException(Exception $exception)
    {
        $isDebug = $this->getConfig(self::CONFIG_DEBUG);
        $code = $exception->getCode();
        $type = get_class($exception);
        $error = sprintf("exception: '%s' | code: '%d' | message: '%s' | file: '%s' | line: '%d'", $type, $code, $exception->getMessage(), $exception->getFile(), $exception->getLine());
        if ($isDebug) $error .= PHP_EOL . "trace:" . PHP_EOL . $exception->getTraceAsString();
        $this->printError($error . PHP_EOL);
        $this->_log($error, LOG_ERR);
        return $this;
    }
    
    /**
     * Creates a lock file to make sure only one instance of the process
     * would execute on the system. Handls dead locks by removing the dead lock
     * file.
     *
     * @param   string  $lockFile           name of the lock, by default uses objects lock name.
     * @return  string  $lockName           canonical file name of the lock file
     * @throws  Parsonline_Exception_IOException on failure to write to lock file
     *          Parsonline_Exception on invalid lock file name or lock exists already
     */
    public function createLockFile($lockFile='')
    {
        if ( !$lockFile) $lockFile = $this->getLockFilename();
        if ( !$lockFile ) {
            /**
             * @uses    Parsonline_Exception
             */
            require_once('Parsonline/Exception.php');
            throw new Parsonline_Exception("Failed to create lock. No lock file name is specified");
        }
        
        $this->_log("creating lock file '{$lockFile}'", LOG_DEBUG, __METHOD__);
        
        if ( file_exists($lockFile) ) {
            $pid = @file_get_contents($lockFile);
            if (!$pid) $pid = '-';   
            /**
             * @uses    Parsonline_Exception
             */
            require_once('Parsonline/Exception.php');
            throw new Parsonline_Exception( sprintf("Process '%s' owns the lock", $pid) );
        }
        if ( !file_put_contents($lockFile, getmypid()) ) {
            /**
             * @uses    Parsonline_Exception_IOException
             */
            require_once('Parsonline/Exception/IOException.php');
            throw new Parsonline_Exception_IOException("Failed to write to lock file '{$lockFile}'");
        }
        return $lockFile;
    } // public function createLockFile()
    
    /**
     * Flushes data in the data streams.
     * 
     * @return  Parsonline_CilScriptAbstract
     */
    public function flushStreams()
    {
        if ($this->_stdout) {
            fflush($this->_stdout);
        } elseif (STDOUT) {
            fflush(STDOUT);
        }
        
        if ($this->_stderr) {
            fflush($this->_stderr);
        } elseif (STDERR) {
            fflush(STDERR);
        }
        return $this;
    } // public function flushStreams()
    
    /**
     * Shutdowns the script, shows an exit message.
     * 
     * Reports the exectuaiton duration of the script by default by specifying
     * a string message. the $TIME parameter in the string would be replaced
     * by the real duration. use false for this parameter to disable reporting.
     *
     * @param   string        $message        message
     * @param   int           $code           exit code
     * @param   string        $reportDuration [optional] 
     * @param   bool          $keepExecution  [optional] if the method should not explicitly call the exit() to quit the process.
     *
     * @return  int     return code of the application
     * @throws  Parsonline_Exception
     */
    public function shutdown($message='', $code=0, $reportDuration=false, $keepExecution=false)
    {
        $lockFilename = $this->getLockFilename();
        if ( $lockFilename && file_exists($lockFilename) ) {
            $this->_log("removing lock file '{$lockFilename}'", LOG_DEBUG, __METHOD__);
            if ( !unlink($lockFilename) ) {
                $this->_log("failed to remove the lock file '{$lockFilename}'", LOG_ALERT, __METHOD__);
            }
        }

        $code = intval($code);
        if ($reportDuration) {
            $duration = (microtime(true) - $this->getProcessStartTime());
            $message .= sprintf('terminating process after %.3f seconds', $duration);
        }
        
        if ($message != '') {
            $this->println($message);
            $this->_log($message, LOG_INFO, __METHOD__);
        }
        
        $this->flushStreams();

        if ($keepExecution) {
            return $code;
        }
        
        exit($code);
    } // public function shutdown()

    /**
     * Mimics a clean shutdown, but keeps the execution running.
     * 
     * Could be used to flush streams, close resources, and remove lock files.
     */
    public function cleanSemiShutdown()
    {
        $this->shutdown('', 0, false, true);
        $this->flushStreams();
    }
    
    /**
     * Checks to see if there is data available to be read from a stream.
     * 
     * @param   resource    $stream
     * @return  bool
     */
    public function streamHasData(&$stream)
    {
        $stats = fstat($stream);
        return ( is_array($stats) && array_key_exists('size', $stats) && 0 < $stats['size'] );
    }
    
    /**
     * Specifies if there is data in the standard input to be read.
     * 
     * @return  bool
     */
    public function stdInHasData()
    {
        if ($this->_stdin) {
            return $this->streamHasData($this->_stdin);
        } else {
            $stream = STDIN;
            return $this->streamHasData($stream);
        }
    }

    /**
     * cleans the string from the output.
     * Actually prints BACKSPACE characters to the number of characters
     * in the string.
     * 
     * @param   string  $str
     */
    protected function cleanStringFromOutput($str)
    {
        $this->print_(str_repeat(chr(8), strlen($str)));
    }
    
    /**
     * Alerts the user by outputing a message, and then waiting for use
     * Input. User input to close the alert could be configured.
     * A message is printed so the user would know how to close the alert.
     * 
     * @param   string  $message
     * @param   string  $closeMessage
     * @return  Parsonline_CliScriptAbstract
     */
    public function alert($message, $closeMessage='press <Enter> to continue')
    {
        $this->println($message);
        if ($closeMessage) $this->print_($closeMessage);
        $stdin = $this->getStdIn();
        fgets($stdin);
        return $this;
    }
    
    /**
     * Asks the user for confirmation from standard input and returns the answer
     * as boolean.
     * If there is no default answer, and user input is not in the specified
     * list of values, a warning message is printed. The warning message
     * should contain a %s token to be replaced by the list of YES values and
     * a %s token to be replace by the list of NO values.
     * 
     * @param   string      $message        message to show to user as the question
     * @param   null|bool   $defaultAnswer  [optional] the default answer if user does not enter anything.
     * @param   array       $yesValues      an array of answers that would be considered as YES
     * @param   array       $noValues      an array of answers that would be considered as NO
     * @param   string      $warningMessage string that warns the user to enter valid values.
     * @return  bool
     * @throws  Parsonline_Exception_ValueException on invalid warning message
     */
    public function confirm($message, $defaultAnswer=null, array $yesValues=array('yes','y'), array $noValues=array('no','n'), $warningMessage='please enter %s to confirm, or %s to cancel')
    {
        if ( !is_null($defaultAnswer) ) $defaultAnswer = true && $defaultAnswer;
        $yesValues = $yesValues ? $yesValues : array('yes','y');
        $yesValuesString = implode(', ', $yesValues);
        $noValues = $noValues ? $noValues : array('no', 'n');
        $noValuesString = implode(', ', $noValues);
        
        $yesValuesStringPrompt = ($defaultAnswer === true) ? strtoupper($yesValuesString) : $yesValuesString;
        $noValuesStringPrompt = ($defaultAnswer === false) ? strtoupper($noValuesString) : $noValuesString;

        if ( substr_count($warningMessage, '%s') !== 2 ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException(
                    "warning message should contain 2 %s characters to be replaced by YES/NO values using sprintf"
                );
        }
        $stdin = $this->getStdIn();
        do {
            $this->print_(sprintf('%s [%s/%s] ', $message, $yesValuesStringPrompt, $noValuesStringPrompt));
            $answer = trim( strtolower( fgets($stdin) ) );
            if ( in_array($answer, $noValues) ) {
                return false;
            } elseif ( in_array($answer,$yesValues) ) {
                return true;
            }
            if ( $answer === '' && is_bool($defaultAnswer) ) {
                return $defaultAnswer;
            }
            $this->println( sprintf($warningMessage, $yesValuesString, $noValuesString) );
        } while (true);
    } // public function confirm()
}