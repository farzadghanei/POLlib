<?php
//Parsonline/Network/SSH2.php
/**
 * Defines Parsonline_Network_SSH2 class.
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
 * @copyright  Copyright (c) 2010-2011-2012 ParsOnline, Inc. (www.parsonline.com)
 * @license    Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Parsonline_Network_SSH2
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.5.0 2012-07-08
*/

/**
 * Parsonline_Network_SSH2
 * 
 * Provides an Object Oriented interface to the SSH2 extension.
 * PHP extension SSH2 is a wrapper arround the libssh2
 *
 * @link    http://www.libssh2.org/
 */
class Parsonline_Network_SSH2
{
    const AUTH_NONE = 'none';
    const AUTH_PASSWORD = 'password';
    const AUTH_PUBLIC_KEY = 'pubkey';
    
    const METHOD_KEY_EXCHANGE = 'kex';
    const METHOD_HOST_KEY = 'hostkey';
    const METHOD_CLIENT_TO_SERVER = 'client_to_server';
    const METHOD_SERVER_TO_CLIENT = 'server_to_client';
    
    const SERVER_CLIENT_CRYPT_CIPHER = 'crypt';
    const SERVER_CLIENT_COMPRESSION = 'comp';
    const SERVER_CLIENT_MAC_METHOD = 'mac';
    
    const CALLBACK_IGNORE = 'ignore';
    const CALLBACK_DEBUG = 'debug';
    const CALLBACK_MAC_ERROR = 'macerror';
    const CALLBACK_DISCONNECT = 'disconnect';
    
    /**
     * SSH2 connection channel resource
     *
     * @var resource
     */
    protected $_connectionChannel = null;

    /**
     * Authentication state of the SSH2 connection.
     *
     * @var bool
     */
    protected $_isAuthenticated = false;

    /**
     * Connection state of the SSH2 connection.
     *
     * @var bool
     */
    protected $_isConnected = false;

    /**
     * Default microseconds wait for the created shell
     *
     * @var int
     */
    protected $_haltDelay = 100000;

    /**
     * Remote hostname
     *
     * @var string
     */
    protected $_hostname = '';

    /**
     * Remote IP address
     *
     * @var string
     */
    protected $_hostAddress = '';

    /**
     * Array of callable references to be notified on each log emition
     *
     * @var array
     */
    protected $_loggers = array();


    /**
     * Password for password based authenticateion
     *
     * @var string
     */
    protected $_password = '';

    /**
     * Connection port number
     *
     * @var int
     */
    protected $_port = 22;
    
    /**
     * File path to local private key file
     *
     * @var string
     */
    protected $_privateKeyFile = '';

    /**
     * Passphrase to decrypte local private key file
     *
     * @var string
     */
    protected $_privateKeyPassphrase = '';

    /**
     * File path to public key file
     *
     * @var string
     */
    protected $_publicKeyFile = '';

    /**
     * Shell data channel
     * 
     * @var Parsonline_Network_SSH2_Shell
     */
    protected $_shell;

    /**
     * Array of subsystem data channel streams.
     *
     * @var array
     */
    protected $_subsystemStreams = array();

    /**
     * Array of supported authentication methods by remote host
     *
     * @var array
     */
    protected $_supportedAuthMethods = array();

    /**
     * System resource unique ID
     * 
     * @var string
     */
    protected $_systemResourceID = '';

    /**
     * Number of seconds for default stream timeout
     * @var int
     */
    protected $_timeout = 60;

    /**
     * Username to authenticate
     *
     * @var string
     */
    protected $_username = '';

    /**
     * Checks for availablity of core functionality used by the SSH2 class.
     * Core SSH functions and constants should be defined on the system.
     * If this method returns true, it means this class could be used to
     * SSH to remote hosts.
     * 
     * @return  bool
     */
    public static function isSSH2Available()
    {
        return (
                    function_exists('ssh2_connect') &&
                    function_exists('ssh2_auth_password') &&
                    function_exists('ssh2_methods_negotiated') &&
                    defined('SSH2_STREAM_STDIO')
                );
    } // public static function isSSH2Available()

    /**
     * Constructor.
     * 
     * Creates a new SSH2 object. accepts an array of options key => values
     * or a single string as the remote hostname (or host address).
     *
     * 
     * @param   array|string    $options
     * @throws  Parsonline_Exception_ContextException on ssh2 unavailable
     *          Parsonline_Exception_ValueException on invalid param
     */
    public function __construct($options=array())
    {
        if (!self::isSSH2Available()) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "SSH2 functionality is not available"
            );
        }
        if ( is_string($options) ) {
            $options = array('hostname' => $options);
        }
        /**
         * @uses    Parsonline_Exception_ValueException
         */
        if ( !is_array($options) ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException(
                "Options should be a host address string, or an associative array of options"
            );
        }
        $this->setOptionsFromArray($options);
    } // public function __construct()

    /**
     * Automatically called right before object is deleted.
     * disconnects from the remote host before that.
     */
    public function __destruct()
    {
        $this->disconnect(true);
    }

    /**
     * Automatically called before object serialization.
     * Disconnects from the remote host, keeps the connection state of the
     * object for unserialization process.
     * So if the TCP connection is stablished at the time of serilization, when unserializing
     * a connection will automatically be made.
     */
    public function __sleep()
    {
        $lastConnection = $this->_isConnected;
        $this->disconnect(true);
        $this->_isConnected = $lastConnection;
    }

    /**
     * Automatically called after object unserialization.
     * If TCP connection was connected before serialization,
     * will automatically reconnect.
     */
    public function __wakeup()
    {
        if ($this->_isConnected) $this->connect();
    }

    /**
     * Returns the default halt delay (microseconds) for shell streams.
     * This is the value each stream would block so resources get available.
     *
     * @return int
     */
    public function getHaltDelay()
    {
        return $this->_haltDelay;
    }

    /**
     * Sets the default halt delay (microseconds) for shell streams.
     * This is the value each stream would block so resources get available.
     *
     * @param   int         $delay
     * @return  Parsonline_Network_SSH2
     * @throws  Parsonline_Exception_ValueException on negative time
     */
    public function setHaltDelay($delay)
    {
        if ( 0 > $delay) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("Halt delay time should not be negative");
        }
        $this->_haltDelay = intval($delay);
        return $this;
    } // public function setHaltDelay()

    /**
     * Returns the IP address of the server
     *
     * @param   bool        $lookup     if no host address available, yet hostname is, lookup the address
     * @return  string
     */
    public function getHostAddress($lookup=true)
    {
        if ($lookup && !$this->_hostAddress && $this->_hostname) {
            $this->_hostAddress = gethostbyname($this->_hostname);
        }
        return $this->_hostAddress;
    }

    /**
     * Sets the IP address of the server.
     *
     * NOTE: will not affect current connection.
     *
     * @param   string      $host
     * @return  Parsonline_Network_SSH2   object self reference
     */
    public function setHostAddress($host)
    {
        $this->_hostAddress = strval($host);
        return $this;
    }

    /**
     * Returns the hostname of the remote server
     *
     * @param   bool        $lookup     if no hostname available, yet address is, lookup the name
     * @return  string
     */
    public function getHostname($lookup=true)
    {
        if ($lookup && !$this->_hostname && $this->_hostAddress) {
            $this->_hostname = gethostbyaddr($this->_hostAddress);
        }
        return $this->_hostname;
    }

    /**
     * Sets the hostname of the remote server.
     *
     * NOTE: will not affect current connection.
     *
     * @param   string      $hostname
     * @param   bool        $lookup     if should automatically lookup the host address
     * @return  Parsonline_Network_SSH2   object self reference
     */
    public function setHostname($hostname, $lookup=true)
    {
        $this->_hostname = strval($hostname);
        if ($lookup) {
            $this->_hostAddress = gethostbyname($hostname);
        }
        return $this;
    }

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
     * NOTE: Only those loggers are assinged to the shell (or other streams),
     * that are registered before the shell/stream is started.
     *
     * @param   string|array|object $logger   a string for function name, or an array of object, method name, or object with 'log' method
     * @return  Parsonline_Network_SSH2
     * @throws  Parsonline_Exception_ValueException on none callable parameter
     */
    public function registerLogger($logger)
    {
        if ( is_object($logger) ) {
            $logger = array($logger, 'log');
        }
        if (!$logger || !is_callable($logger, false)) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("Logger should be a string for function name, or an array of object, method name");
        }
        array_push($this->_loggers, $logger);
        return $this;
    } // public function registerLogger()

    /**
     * Returns the password to authenticate to remote host with
     *
     * @return  string
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * Sets the password to authenticate to remote host with
     *
     * @param   string      $password
     * @return  Parsonline_Network_SSH2   object self reference
     */
    public function setPassword($password)
    {
        $this->_password = strval($password);
        return $this;
    }

    /**
     * Returns the path to file containing the private key data
     *
     * @return  string
     */
    public function getPrivateKeyFile()
    {
        return $this->_privateKeyFile;
    }

    /**
     * Sets the path to file containing the private key data
     *
     * @param   string      $filename
     * @return  Parsonline_Network_SSH2   object self reference
     * @throws  Parsonline_Exception_IOException on not accessible file
     */
    public function setPrivateKeyFile($filename)
    {
        $filename = strval($filename);
        if ( !file_exists($filename) || !is_readable($filename) || is_dir($filename)) {
            /**
             * @uses    Parsonline_Exception_IOException
             */
            require_once('Parsonline/Exception/IOException');
            throw new Parsonline_Exception_IOException("Failed to access private key file '{$filename}'");
        }
        $this->_privateKeyFile = realpath($filename);
        return $this;
    } // public function setPrivateKeyFile()

    /**
     * Returns the passphrase to decrypt local private key
     *
     * @return  string
     */
    public function getPrivateKeyPassphrase()
    {
        return $this->_privateKeyPassphrase;
    }

    /**
     * Sets the passphrase to decrypt local private key
     *
     * @param   string      $passphrase
     * @return  Parsonline_Network_SSH2   object self reference
     */
    public function setPrivateKeyPassphrase($passphrase)
    {
        $this->_privateKeyPassphrase = strval($passphrase);
        return $this;
    }

    /**
     * Returns the the SSH connection TCP port number.
     *
     * @return  int
     */
    public function getPort()
    {
        return $this->_port;
    }

    /**
     * Sets the the SSH connection TCP port number.
     *
     * NOTE: will not affect current connection
     *
     * @param   int     $port
     * @return  Parsonline_Network_SSH2   object self reference
     * @throws  Parsonline_Exception_ValueException on out of range port number
     */
    public function setPort($port)
    {
        if ( 1 > $port || 65535 < $port) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("TCP port number '{$port}' is out of range 1-65535");
        }
        $this->_port = intval($port);
        return $this;
    } // public function setPort()

    /**
     * Returns the path to file containing the public key data
     *
     * @return  string
     */
    public function getPublicKeyFile()
    {
        return $this->_publicKeyFile;
    }

    /**
     * Sets the path to file containing the public key data
     *
     * @param   string      $filename
     * @return  Parsonline_Network_SSH2   object self reference
     * @throws  Parsonline_Exception_IOException on not accessible file
     */
    public function setPublicKeyFile($filename)
    {
        $filename = strval($filename);
        if ( !file_exists($filename) || !is_readable($filename) || is_dir($filename)) {
            /**
             * @uses    Parsonline_Exception_IOException
             */
            require_once('Parsonline/Exception/IOException');
            throw new Parsonline_Exception_IOException("Failed to access public key file '{$filename}'");
        }
        $this->_publicKeyFile = realpath($filename);
        return $this;
    } // public function setPublicKeyFile()

    /**
     * Returns the default timeout (seconds) of the data transfer
     * on all subsystem/shell streams.
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->_timeout;
    }

    /**
     * Sets the default timeout (seconds) of the data transfer
     * on all subsystem/shell streams.
     *
     * NOTE: will not affect current connection
     *
     * @param   int         $timeout
     * @return  Parsonline_Network_SSH2
     * @throws  Parsonline_Exception_ValueException on negative timeout
     */
    public function setTimeout($timeout)
    {
        if (0 > $timeout) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("SSH stream data transfere timeout should not be negative");
        }
        $this->_timeout = intval($timeout);
        return $this;
    } // public function setTimeout()

    /**
     * Returns the username to authenticate to remote host with
     *
     * @return  string
     */
    public function getUsername()
    {
        return $this->_username;
    }

    /**
     * Sets the username to authenticate to remote host with
     *
     * @param   string      $username
     * @return  Parsonline_Network_SSH2   object self reference
     */
    public function setUsername($username)
    {
        $this->_username = strval($username);
        return $this;
    }

    /**
     * Sets data of the object from an array.
     * Returns an array, the first index is an array of keys used
     * to configure the object, and the second is an array of keys
     * that were not used.
     *
     * @param   array       $options        associative array of property => values
     * @return  array       array(sucess keys, not used keys)
     */
    public function setOptionsFromArray(array &$options)
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
     * Authenticates as None using the specified username.
     *
     * @return  bool
     * @throws Parsonline_Network_SSH2_Exception if connection is lost
     */
    public function authenticateAsNone()
    {
        $this->_preAuth();
        $result = ssh2_auth_none($this->_connectionChannel, $this->_username);
        if (is_array($result) && !$this->_supportedAuthMethods) {
            $this->_supportedAuthMethods = $result;
            $result = false;
        } elseif (!in_array(self::AUTH_NONE, $this->_supportedAuthMethods)) {
            $this->_supportedAuthMethods[] = self::AUTH_NONE;
        }
        if ( is_array($result) ) $result = false;
        if ($result && !$this->_isAuthenticated) $this->_isAuthenticated = true;
        return $result;
    } // public function authenticateAsNone()

    /**
     * Authenticates using the specified username and password.
     *
     * @see     login()
     * @see     authenticateByPublicKey()
     *
     * @return  bool
     * @throws  Parsonline_Network_SSH2_Exception if connection is lost
     *          Parsonline_Exception_ContextException if username/password are not specified
     */
    public function authenticateByPassword()
    {
        $this->_preAuth();
        if (!$this->_username || !$this->_password) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException');
            throw new Parsonline_Exception_ContextException("Failed to authenticate with remote host using password. Cridentials are insufficient");
        }
        $result = ssh2_auth_password($this->_connectionChannel, $this->_username, $this->_password);
        if ($result && !$this->_isAuthenticated) $this->_isAuthenticated = true;
        return $result;
    } // public function authenticateByPassword()

    /**
     * Authenticates using the specified Public/Private key files.
     *
     * @see     login()
     * @see     authenticateByPassword()
     *
     * @return  bool
     * @throws  Parsonline_Network_SSH2_Exception if connection is lost
     *          Parsonline_Exception_ContextException if username/key data is not specified
     */
    public function authenticateByPublicKey()
    {
        $this->_preAuth();
        if (!$this->_username || !$this->_publicKeyFile || !$this->_privateKeyFile) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException');
            throw new Parsonline_Exception_ContextException("Failed to authenticate with remote host using public key. Information is insufficient");
        }
        $result = ssh2_auth_pubkey_file($this->_connectionChannel, $this->_username, $this->_publicKeyFile, $this->_privateKeyFile, $this->_privateKeyPassphrase);
        if ($result && !$this->_isAuthenticated) $this->_isAuthenticated = true;
        return $result;
    } // public function authenticateByPublicKey()

    /**
     * Closes all openned subsystem data streams.
     * Returns an array of arrays, the first is an array of closed stream ID values,
     * the second is an array of stream ID vlaues who failed to close.
     *
     * NOTE: Since the SSH2 object would remove all references to the streams,
     * If there are not exernal references to the streams, those that failed to
     * close successfully, would automatically be garbage collected and thus
     * resources would be freed.
     * 
     * @return  array     array(array of closed stream IDs, array of failed stream IDs)
     */
    public function closeSubsystemStreams()
    {
        $result = array(array(),array());
        foreach ( $this->_subsystemStreams as $key => $stream ) {
            /*@var $stream Parsonline_Network_SSH2_Stream */
            $id = intval(substr($key, 1));
            $this->_log(__METHOD__, "closing open data stream id '{$id}' ...", LOG_DEBUG);
            if ($stream && $stream->close()) {
                array_push($result[0], $id);
            } else {
                array_push($result[1], $id);
            }
        }
        $this->_subsystemStreams = array();
        return $result;
    } // public function closeSubsystemStreams()
    
   /**
    * Connects to server and initializes some options.
    *
    * @param    array   $methods    array of methods
    * @return   Parsonline_Network_SSH2   object self reference
    * @throws   Parsonline_Exception_ContextException for no host specified
    * @throws   Parsonline_Network_SSH2 code CONNECTION for connection failure
    */
    public function connect(array $methods=array())
    {
        if (!$this->_hostAddress) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                'No host address is specified to connect to.'
            );
        }

        $this->_log(__METHOD__, "connecting to {$this->_hostAddress}:{$this->_port} ...");
        
        if (!$methods) $methods = $this->_getConnectionMethods();
        
        $connection = ssh2_connect(
                            $this->_hostAddress, $this->_port,
                            $methods,
                            array(
                                self::CALLBACK_DISCONNECT => array($this,'disconnect'),
                                self::CALLBACK_DEBUG => array($this,'_protocolDebug')
                            )
                        );
        
        if ( !is_resource($connection) ) {
            /**
             * @uses    Parsonline_Network_SSH2_Exception
             */
            require_once('Parsonline/Network/SSH2/Exception.php');
            throw new Parsonline_Network_SSH2_Exception(
                "Failed to connect to '{$this->_hostAddress}:{$this->_port}'", Parsonline_Network_SSH2_Exception::CONNECTION
            );
        }

        $this->_log(__METHOD__, "connected to {$this->_hostAddress}:{$this->_port}. configuring connection stream ...", LOG_DEBUG);
        $this->_connectionChannel =& $connection;
        
        // invalidate current system resource ID so getSystemResourceID() would create a freshed ID
        $this->_systemResourceID = '';
        $this->_isConnected = true;
        $this->_init();
        $this->_log(__METHOD__, "connection stablished to {$this->_hostAddress}:{$this->_port}");
        return $this;
    } // pubilc function connect()
    
    /**
     * Disconnects the connection.
     * 
     * NOTE: empties the read buffer, flushes/closes the shell and all streams.
     *
     * @return  bool        true on a clean shutdown, false if there were problems
     */
    public function disconnect()
    {
        $this->_log(__METHOD__, "disconnecting from {$this->_hostAddress}:{$this->_port} ...");
        
        if ($this->_shell) {
            $this->_log(__METHOD__, "shutting down the open shell ...", LOG_DEBUG);
            $this->_shell->shutdown();
            $this->_shell = null;
        }
        $this->closeSubsystemStreams();
        $this->_connectionChannel = null;
        $this->_isConnected = false;
        $this->_systemResourceID = '';
        $this->_log(__METHOD__, "disconnected from {$this->_hostAddress}:{$this->_port}");
        return $this;
    } // public function disconnect()

    /**
     * Returns the SSH2 connection resource that is beeing used underhood
     *
     * @param   bool        $autoConnect
     * @return  resource    SSH2 connection resource
     */
    public function & getConnectionChannel($autoConnect=true)
    {
        if ($autoConnect && !is_resource($this->_connectionChannel)) {
            $this->connect();
        }
        return $this->_connectionChannel;
    } // public function getConnectionChannel()

    /**
     * Returns the unique system resource ID of SSH2 connection.
     * It is a string the process ID and the resource descriptor ID separted
     * By a colon.
     * If the SSH connection has not been stablished, returns _ as the connection
     * ID.
     * 
     * @return  string  PID:SSH connection resource ID
     */
    public function getSystemResourceID()
    {
        if (!$this->_systemResourceID) {
            if ($this->_connectionChannel) {
                $this->_systemResourceID = getmypid() . ':' . intval($this->_connectionChannel);
            } else {
                $this->_systemResourceID = getmypid() . ':_';
            }
        }
        return $this->_systemResourceID;
    } // public function getSystemResourceID()

    /**
     * Returns (initiates if none exists) the shell over the SSH2 connection.
     *
     * @param   bool    $refresh        if should recreate the shell
     * @param   bool    $autostart      if should automatically start the shell
     * 
     * @return  Parsonline_Network_SSH2_Shell
     * @throws  Parsonline_Network_SSH2_Exception on connection lost
     */
    public function getShell($refresh=false, $autostart=true)
    {
        if ($refresh || !$this->_shell) {
            if (!$this->isConnected()) {
                /**
                 * @uses    Parsonline_Network_SSH2_Exception
                 */
                require_once('Parsonline/Network/SSH2/Exception.php');
                throw new Parsonline_Network_SSH2_Exception(
                    "Failed to request the shell. SSH connection is lost", Parsonline_Network_SSH2_Exception::CONNECTION
                );
            }
            $this->_log(__METHOD__, "creating shell ...", LOG_DEBUG);
            /**
             * @uses    Parsonline_Network_SSH2_Shell
             */
            require_once('Parsonline/Network/SSH2/Shell.php');
            $shell = new Parsonline_Network_SSH2_Shell($this);

            $this->_log(__METHOD__, "configuring the shell ...", LOG_DEBUG);
            $shell->setTimeout($this->_timeout);
            $shell->setHaltDelay($this->_haltDelay);
            $shell->setAutoDetectPromptString(true);
            
            foreach($this->_loggers as $logger) {
                $shell->registerLogger($logger);
            }

            if ($autostart) {
                $this->_log(__METHOD__, "starting the shell ...", LOG_DEBUG);
                $shell->start();
                $this->_log(__METHOD__, "shell started ...", LOG_DEBUG);
            }
            $this->_shell = $shell;
        }
        return $this->_shell;
    } // public function getShell()

    /**
     * Returns (initiates if none exists) a subsystem data stream specified
     * by its ID. use SSH2_STREAM_* constants as ID. If the specified ID
     * is not valid or the stream is not available, returns null.
     * If the stream ID is valid yet failed to acheive the stream, throws
     * an exception.
     *
     * @param   int     $streamId          the ID of subsystem streams
     * @param   bool    $refresh            close the previous open stream and create a new
     *
     * @return  Parsonline_Network_SSH2_Stream|null
     * @throws  Parsonline_Network_SSH2_Exception on connection lost or failed to achieve the stream
     */
    public function getSubsystemStream($streamId, $refresh=false)
    {
        $streamId = intval($streamId);
        $key = '_' . $streamId;

        if ( array_key_exists($key, $this->_subsystemStreams) ) {
            $this->_log(__METHOD__, "SSH2 data stream ID '{$streamId}' is already openned", LOG_DEBUG);
            if ($refresh) {
                $stream = $this->_subsystemStreams[$key];
                /*@var $stream Parsonline_Network_SSH2_Stream */
                $this->_log(__METHOD__, "closing SSH2 data stream ID '{$streamId}' to referesh the stream", LOG_DEBUG);
                $stream->close();
                unset($stream, $this->_subsystemStreams[$key]);
            } else {
                return $this->_subsystemStreams[$key];
            }
        }

        if (!$this->isConnected()) {
            /**
             * @uses    Parsonline_Network_SSH2_Exception
             */
            require_once('Parsonline/Network/SSH2/Exception.php');
            throw new Parsonline_Network_SSH2_Exception(
                "Failed to request the data stream. SSH connection is lost", Parsonline_Network_SSH2_Exception::CONNECTION
            );
        }

        switch($streamId) {
            case SSH2_STREAM_STDIO:
                        // ok
            case SSH2_STREAM_STDERR:
                        // ok
                        break;
            default:
                $this->_log(__METHOD__, "SSH2 data stream ID '{$streamId}' is invalid", LOG_DEBUG);
                return null;
        }

        $this->_log(__METHOD__, "requesting SSH2 data stream ID '{$streamId}' ...", LOG_DEBUG);
        $streamResource = ssh2_fetch_stream($this->_connectionChannel, $streamId);
        if ( is_resource($streamResource) ) {
            $this->_log(__METHOD__, "SSH2 data stream ID '{$streamId}' achieved successfully", LOG_DEBUG);
        } else {
            /**
             * @uses    Parsonline_Network_SSH2_Exception
             */
            require_once('Parsonline/Network/SSH2/Exception.php');
            throw new Parsonline_Network_SSH2_Exception(
                "Failed to achieve the SSH data stream id '{$streamId}'.", Parsonline_Network_SSH2_Exception::STREAM_NOT_AVAILABLE
            );
        }
        /**
         * @uses    Parsonline_Network_SSH2_Stream
         */
        require_once('Parsonline/Network/SSH2/Stream.php');
        $stream = new Parsonline_Network_SSH2_Stream(
                            array(
                                'timeout' => $this->getTimeout(),
                                'strictModeChecking' => true,
                                'blocking' => true,
                                'ssh' => $this,
                                'stream' => &$streamResource
                            )
                       );
        $this->_subsystemStreams[$key] = $stream;
        return $stream;
    } // public function getSubsystemStream()

    /**
     * Returns an array of authentication method names supported by the remote
     * host.
     *
     * @return array
     */
    public function getSupportedAuthenticationMethods()
    {
        if (!$this->_supportedAuthMethods) {
            $this->authenticateAsNone();
        }
        return $this->_supportedAuthMethods;
    }

    /**
     * Determines if the SSH connection is authenticated or not.
     *
     * @return  bool
     */
    public function isAuthenticated()
    {
        return $this->_isAuthenticated;
    } // public function isAuthenticated()

    /**
     * Determines if the SSH connection is stablished and open.
     *
     * @return  bool
     */
    public function isConnected()
    {
        if ( !is_resource($this->_connectionChannel) ) {
            $this->_isConnected = false;
            return false;
        }
        $this->_isConnected = true;
        return true;
    } // public function isConnected()


    /**
     * A quick one-call authentication method. Tries to login using available methods
     * supported by remote host, and available object authentication information.
     *
     * NOTE: If the connection is already authenticated, does not reauthenticate autmatilly.
     * 
     * @see     authenticateAsNone()
     * @see     authenticateByPublicKey()
     * @see     authenticateByPassword()
     * @see     getSupportedAuthenticationMethods()
     *
     * @return  bool        authentication result
     * @throws Parsonline_Network_SSH2_Exception if connection is lost
     */
    public function login()
    {
        $loggedIn = $this->_isAuthenticated;
        if ($loggedIn) {
            $this->_log(__METHOD__, "session is already authenticated");
            return true;
        }
        
        if (!$this->_username) {
            $this->_log(__METHOD__, "no username is available for SSH session. using none authentication");
            $loggedIn = $this->authenticateAsNone();
        } elseif ($this->_password && in_array(self::AUTH_PASSWORD, $this->getSupportedAuthenticationMethods()) ) {
            $this->_log(__METHOD__, "password based authentication is supported, logging in with username/password values");
            $loggedIn = $this->authenticateByPassword();
        } elseif ($this->_privateKeyFile && $this->_publicKeyFile && in_array(self::AUTH_PUBLIC_KEY, $this->getSupportedAuthenticationMethods())) {
            $this->_log(__METHOD__, "public key based authentication is supported, logging in with username/public key values");
            $loggedIn = $this->authenticateByPublicKey();
        } else {
            $this->_log(__METHOD__, "failed to find a support authentication method. no authentication were done!", Zend_Log::WARN);
        }
        return $loggedIn;
    } // public function login()
    
    /**
     * Initiate connection with the remote host, right after the SSH
     * connection is made.
     *
     * NOTE: Could be overriden to provide connection to different platforms.
     */
    protected function _init()
    {
    } // protected function _init()

    /**
     * Notifies all registered loggers.
     *
     * @param   string  $signature      name of the method sending the log
     * @param   string  $message        message string being logged
     * @param   int     $priority       the standard priority of the log. 7 for DEBUG 0 for EMERG. default is 6 INFO
     */
    protected function _log($signature, $message, $priority=LOG_INFO)
    {
        $message = '(SSH [' . $this->getSystemResourceID() . ']) ' . $signature . ' > ' . $message;
        foreach($this->_loggers as $log) {
            call_user_func($log, $message, $priority);
        }
    } // protected function _log()
    
    /**
     * Return default connection methods used by ssh_connect call.
     * The methods would tune the communication with SSH server.
     * 
     * @return array 
     */
    protected function _getConnectionMethods()
    {
        return array(
                    self::METHOD_KEY_EXCHANGE => 'diffie-hellman-group1-sha1,diffie-hellman-group14-sha1,diffie-hellman-group-exchange-sha1',
                    self::METHOD_HOST_KEY => 'ssh-rsa,ssh-dss',
                    self::METHOD_CLIENT_TO_SERVER => array(
                                                        self::SERVER_CLIENT_MAC_METHOD => 'hmac-sha1,hmac-sha1-96,hmac-ripemd160,hmac-ripemd160@openssh.com',
                                                        self::SERVER_CLIENT_COMPRESSION => 'none',
                                                        self::SERVER_CLIENT_CRYPT_CIPHER => 'blowfish-cbc,3des-cbc,aes256-cbc,aes192-cbc,aes128-cbc,rijndael-cbc@lysator.liu.se,,cast128-cbc,arcfour'
                                                    ),
                    self::METHOD_SERVER_TO_CLIENT => array(
                                                        self::SERVER_CLIENT_MAC_METHOD => 'hmac-sha1,hmac-sha1-96,hmac-ripemd160,hmac-ripemd160@openssh.com',
                                                        self::SERVER_CLIENT_COMPRESSION => 'none',
                                                        self::SERVER_CLIENT_CRYPT_CIPHER => 'blowfish-cbc,3des-cbc,aes256-cbc,aes192-cbc,aes128-cbc,rijndael-cbc@lysator.liu.se,,cast128-cbc,arcfour'
                                                    )
                );
    }

    /**
     * Pre authentication hook.
     * Makes sure the SSH2 is still connected before authenticating.
     *
     * NOTE: Could be overriden to provide connection to different platforms.
     *
     * @throws Parsonline_Network_SSH2_Exception if connection is lost
     */
    protected function _preAuth()
    {
        $this->getConnectionChannel(true);
        if (!$this->isConnected()) {
            /**
             * @uses    Parsonline_Network_SSH2_Exception
             */
            require_once('Parsonline/Network/SSH2/Exception.php');
            throw new Parsonline_Network_SSH2_Exception(
                "Failed to authenticate over SSH2. SSH connection is lost", Parsonline_Network_SSH2_Exception::CONNECTION
            );
        }
    } // protected function _preAuth()
    
    /**
     * This methos is used to be called on debug packets on SSH protocol.
     * 
     * Note: Since the method is used in callback manner, it is defined as public
     * Please do not use this method directly.
     * 
     * @param   string  $msg        the debug message
     * @param   string  $lang       the language
     * @param   int     $always_display Message SHOULD be displayed by the server
     */
    public function _protocolDebug($msg='', $lang='', $always_display=0)
    {
        $this->_log('protocol_debug', ($msg . ($lang ? "[$lang]" : '')), LOG_DEBUG);
    }
}
