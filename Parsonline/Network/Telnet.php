<?php
//Parsonline/Network/Telnet.php
/**
 * Defines Parsonline_Network_Telnet class.
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
 * @package     Parsonline_Network
 * @subpackage  Telnet
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     2.4.0 2012-07-08
*/

/**
 * Parsonline_Network_Telnet 
 * 
 * Handles a telnet client connection, and details of the Telnet protocol.
 */
class Parsonline_Network_Telnet
{
    /**
     * Keeps the list of telnet protocol specific character codes
     *
     * @staticvar   array
     */
    protected static $_telnetProtocolChars = array();

    /**
     * Keeps the list of telnet protocol specific option character codes
     *
     * @staticvar   array
     */
    protected static $_telnetProtocolOptions = array();

    /**
     * socket connection
     *
     * @var resource
     */
    protected $_socket = null;

    /**
     * remote hostname
     *
     * @var string
     */
    protected $_host = '';

    /**
     * port number
     * 
     * @var int 
     */
    protected $_port = 23;

    /**
     * connection time out in seconds
     * 
     * @var int
     */
    protected $_connectionTimeout = 60;
    
    /**
     * socket data transfere time out in seconds
     * 
     * @var int
     */
    protected $_timeout = 60;

    /**
     * number of microseconds to sleep between command executions
     * 
     * @var int
     */
    protected $_commandDelay = 1000;

    /**
     * host prompt string
     * 
     * @var string
     */
    protected $_prompt = '\$';

    /**
     * host confirmation prompt
     * 
     * @var string
     */
    protected $_confirmationPrompt = '[confirm]';

    /**
     * host confirmation prompt when response if long and paginated
     * 
     * @var string
     */
    protected $_nextPagePrompt = '--More--';

    /**
     * response buffer string
     * 
     * @var string
     */
    protected $_responseBuffer = '';

    /**
     * stack of saved responses
     * 
     * @var array
     */
    protected $_responseBufferStack = array();

    /**
     * if should auto handle long response
     * 
     * @var bool
     */
    protected $_autoHandleLongResponse = true;

    /**
     * if the object should profile its operaions
     * 
     * @var bool
     */
    protected $_profile = false;

    /**
     * gathered time information for methods when profile is enabled
     * 
     * @var array
     */
    protected $_profileData = array();

    /**
     * keeps the connectio state of the object.
     * 
     * @var     bool
     */
    protected $_isConnected = false;

    /**
     * returns a list of telnet protocol special characters.
     *
     * @return array command => code
     */
    public static function getTelnetProtocolCharacters()
    {
        if ( !self::$_telnetProtocolChars ) {
            $characters = array();
            $characters['NULL'] = chr(0); // null character
            $characters['EOF'] = chr(236); // end of file character
            $characters['SUSP'] = chr(237); // suspend process
            $characters['ABORT'] = chr(238); // abort process
            $characters['EOR'] = chr(239); // end of record character
            $characters['SE'] = chr(240); // end of subnegociation
            $characters['NOP'] = chr(241); // no operaion
            $characters['DM'] = chr(242); // data mark
            $characters['BRK'] = chr(243); // break
            $characters['IP'] = chr(244); // interrupt suspend
            $characters['AO'] = chr(245); // abort output
            $characters['AYT'] = chr(246); // are you there
            $characters['EC'] = chr(247); // erase character
            $characters['EL'] = chr(248); // erase line
            $characters['GA'] = chr(249); // go ahead
            $characters['SB'] = chr(250); // subnegociation
            $characters['WILL'] = chr(251); // will
            $characters['WONT'] = chr(252); // wont
            $characters['DO'] = chr(253); // do
            $characters['DONT'] = chr(254); // dont
            $characters['IAC'] = chr(255); // interpret as command
            self::$_telnetProtocolChars = $characters;
        }
        return self::$_telnetProtocolChars;
    } // public static function getTelnetProtocolCharacters()

    /**
     * returns a list of telnet protocol options.
     *
     * @return array option name => code
     */
    public static function getTelnetProtocolOptions()
    {
        if ( !self::$_telnetProtocolOptions ) {
            $options = array();
            $options['BINARY'] = chr(0); // allows devices to send data in 8-bit binary form instead of 7-bit ASCII
            $options['ECHO'] = chr(1); // change the command echo mode
            $options['RCP'] = chr(2); // prepare to reconnect
            $options['SGA'] = chr(3); // suppress go ahead
            $options['NAMS'] = chr(4); // approximate message size
            $options['STATUS'] = chr(5); // request for the status of an option
            $options['TM'] = chr(6); // negotiate the insertion of a special timing mark into the data stream, which is used for synchronization
            $options['RCTE'] = chr(7); // remote controlled transmission and echo
            $options['NAOL'] = chr(8); // negitiation about output line width
            $options['NAOP'] = chr(9); // negitiation about output page size
            $options['NAOCRD'] = chr(10); // negotiate how carriage returns will be handled
            $options['NAOHTS'] = chr(11); // negotiate how the horizental tab stop
            $options['NAOHTD'] = chr(12); // negotiate how the horizental tab character will be handled
            $options['NAOFFD'] = chr(13); // negotiate how the form feed character will be handled
            $options['NAOVTS'] = chr(14); // negotiate how the vertical tab stop
            $options['NAOVTD'] = chr(15); // negotiate how the vertical tab character will be handled
            $options['NAOLFD'] = chr(16); // negotiate how the line feed character will be handled
            $options['XASCII'] = chr(17); // agree to use extended ASCII for transmissions and negotiate how it will be used
            $options['LOGOUT'] = chr(18); // force logout
            $options['BM'] = chr(19); // byte macro
            $options['DEL'] = chr(20); // data entry terminal
            $options['SUPDUP'] = chr(21); // supdup protocol
            $options['SUPDUPOUTPUT'] = chr(22); // supdup output
            $options['SNDLOC'] = chr(23); // send location
            $options['TTYPE'] = chr(24); // terminal type
            $options['EOR'] = chr(25); // end of record
            $options['TUID'] = chr(26); // TACACS user identifiaction
            $options['OUTMRK'] = chr(27); // output marking
            $options['TTYLOC'] = chr(28); // termial locatio number
            $options['VT3270REGIME'] = chr(29); // 3270 regime
            $options['X3PAD'] = chr(30); // X.3 PAD
            $options['NAWS'] = chr(31); // negicaite about window size
            $options['TSPEED'] = chr(32); // report about current terminal speed
            $options['LFLOW'] = chr(33); // enable/disable flow control between client and server
            $options['LINEMODE'] = chr(34); // client sends data one line at a time, instead of sending character-by-character. this will improve preformnce.
            $options['XDISPLOC'] = chr(35); // X display location
            $options['OLD_ENVIRON'] = chr(36); // old environment variabels
            $options['AUTHENTICATION'] = chr(37); // negociate over an authentication method
            $options['ENCRYPT'] = chr(38); // encryption option
            $options['NEW_ENVIRON'] = chr(39); // new environment variabels
            $options['TN3270'] = chr(40);
            $options['XAUTH'] = chr(41);
            $options['CHARSET'] = chr(42);
            $options['RSP'] = chr(43); // telnet remote serial port
            $options['COM_PORT_OPTION'] = chr(44); // com port control option
            $options['SUPPRESS_LOCAL_ECHO'] = chr(45); // telnet suppress local echo
            $options['TLS'] = chr(46); // telnet start TLS
            // these values are not standard telent options.
            $options['KERMIT'] = chr(47);
            $options['SEND_URL'] = chr(48);
            $options['FORWARD_X'] = chr(49);
            $options['PRAGMA_LOGON'] = chr(138);
            $options['SSPI_LOGON'] = chr(139);
            $options['PRAGMA_HEARBEAT'] = chr(140);
            //$options['EXOPL'] = chr(255);
            //$options['NOOPT'] = chr(0);
            self::$_telnetProtocolOptions = $options;
        }
        return self::$_telnetProtocolOptions;
    } // public static function getTelnetProtocolOptions()

    /**
     * Constructor.
     * 
     * Creates a new Telnet object. accepts an array of options key => values
     * or a single string as the remote hostname.
     * 
     * @param   array|string    $options
     * @throws  Parsonline_Exception_ValueException on invalid param
     */
    public function __construct($options=array())
    {
        if ( is_string($options) ) {
            $options = array('host' => $options);            
        }
        /**
         * @uses    Parsonline_Exception_ValueException
         */
        if ( !is_array($options) ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("options should be a hostname string, or an associative array of options");
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
     * Tries to fetch a telent option, or command code. if no such option/command
     * exist, then tries to call a suitable getter method. if no such getter method
     * exists, throws an exception.
     *
     * @param   string      $name
     * @return  mixed
     * @throws  Parsonline_Exception_NameException
     */
    public function __get($name)
    {
        $result = $this->getTelnetProtocolCharacter($name);
        if ($result !== null ) return $result;

        $result = $this->getTelnetProtocolOption($name);
        if ($result !== null ) return $result;
        
        $method = 'get' . ucfirst($name);
        if ( method_exists($this, $method) ) return $this->$method;
        $method = 'get' . $name;
        if ( method_exists($this, $method) ) return $this->$method;
        /**
         * @uses    Parsonline_Exception_NameException
         */
        require_once('Parsonline/Exception/NameException.php');
        throw new Parsonline_Exception_NameException(
            "invalid object property. " . get_class($this) . " has no property as {$name}.",
            0, null, $name
        );
    } // public function __get($name)

    /**
     * Is automatically called before object serialization.
     * Disconnects from the remote host, keeps the connection state of the
     * object for unserialization process.
     * So if the object is connected at the time of serilization, when unserializing
     * a connection will automatically be made.
     */
    public function __sleep()
    {
        $lastConnection = $this->_isConnected;
        $this->disconnect(true);
        $this->_isConnected = $lastConnection;
    }

    /**
     * Is automatically called after object unserialization.
     * If object was connected before serialization, will automatically reconnect.
     */
    public function __wakeup()
    {
        if ($this->_isConnected) $this->connect(true);
    }

    /**
     * Returns the character code of the specified option name. option name is case-insensitive.
     * if no shuch option exsits, returns null. if no parameter for option is set,
     * returns an associative array of all option name => codes.
     * if the option is an integer value, returns the name of the option as string.
     *
     * @param   string      $option     telnet option name
     * @return  string|array|int|null
     */
    public function getTelnetProtocolOption($option=null)
    {
        $telnetOptionsList = self::getTelnetProtocolOptions();
        if ($option === null) return $telnetOptionsList;
        if ( is_int($option) ) {
            $key = array_search( chr($option), $telnetOptionsList);
            return (($key !== false) ? $key : null);
        }
        $option = strtoupper($option);
        if ( !array_key_exists($option, $telnetOptionsList) ) return null;
        return $telnetOptionsList[ $option ];
    } // public function getTelnetProtocotOption()
    
    /**
     * Returns the character code of the specified character name.
     * character name is case-insensitive.
     * if no shuch character exsits, returns null. if no parameter for character is set,
     * returns an associative array of all characters name => codes.
     *
     * @param   string      $character        telnet character name
     * @return  string|array|int|null
     */
    public function getTelnetProtocolCharacter($character=null)
    {
        $telnetChars = self::getTelnetProtocolCharacters();
        if ($character === null) return $telnetChars;
        if ( is_int($character) ) {
            $key = array_search( chr($character), $telnetChars);
            return (($key !== false) ? $key : null);
        }
        $character = strtoupper($character);
        if ( !array_key_exists($character, $telnetChars) ) return null;
        return $telnetChars[ $character ];
    }

    /**
     * Returns the hostname of the remote host
     *
     * @return  string
     */
    public function getHost()
    {
        return $this->_host;
    }

    /**
     * Sets the hostname of the server.
     *
     * NOTE: will not affect current connection
     *
     * @param   string $host
     * @return  Parsonline_Network_Telnet   object self reference
     */
    public function setHost($host)
    {
        $this->_host = strval($host);
        return $this;
    }

    /**
     * Returnes the number of the connection port
     *
     * @return  int
     */
    public function getPort()
    {
        return $this->_port;
    }

    /**
     * Sets the number of the connection port.
     *
     * NOTE: will not affect current connection
     * 
     * @param   int $port
     * @return  Parsonline_Network_Telnet   object self reference
     * @throws  Parsonline_Exception_ValueException on out of range port
     */
    public function setPort($port=23)
    {
        if ( 1 > $port || 65535 < $port) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("TCP port '{$port}' is out of range of 1-65535");
        }
        $this->_port = intval($port);
        return $this;
    } // public function setPort()

    /**
     * Returns the timeout of the connection.
     * 
     * NOTE: for data transfere timeout use getTimeout()
     * 
     * @see     getTimeout()
     * @return   int    number of seconds to timout connection
     */
    public function getConnectionTimeout()
    {
        return $this->_connectionTimeout;
    }

    /**
     * Sets the timeout of the connection.
     * 
     * NOTE: for data transfere timeout use setTimeout()
     * 
     * @see     setTimeout()
     * @param   int     $timeout            number of seconds to timout connection
     * @return  Parsonline_Network_Telnet
     * @throws  Parsonline_Exception_ValueException on negative timeout
     */
    public function setConnectionTimeout($timeout)
    {
        if (0 > $timeout) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("connection timeout should not be negative");
        }
        $this->_connectionTimeout = intval($timeout);
        return $this;
    }

    /**
     * Returnes the timeout of the socket data transfere in seconds
     * 
     * NOTE: for connection timeout, use getConnectionTimeout()
     *
     * @see     getConnectionTimeout()
     * @return int
     */
    public function getTimeout()
    {
        return $this->_timeout;
    }

    /**
     * Sets the timeout of the socket in seconds. will not affect current connection
     * 
     * NOTE: for connection timeout use setConnectionTimeout()
     *
     * @see     setConnectionTimeout()
     * @param   int         $timeout
     * @return  Parsonline_Network_Telnet
     * @throws  Parsonline_Exception_ValueException on negative timeout
     */
    public function setTimeout($timeout=0)
    {
        if ( 0 > $timeout) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("timeout should not be negative");
        }
        $this->_timeout = intval($timeout);
        return $this;
    }

    /**
     * Returns the number of microseconds of delay
     * between commands
     * 
     * @return  int
     */
    public function getCommandDelay()
    {
        return $this->_commandDelay;
    }

    /**
     * Sets the number of microseconds of delay between commands
     *
     * @param   int      $microsec
     * @return  Parsonline_Network_Telnet   object self reference
     * @throws  Parsonline_Exception_ValueException on negative delay
     */
    public function setCommandDelay($microsec)
    {
        if (0 > $microsec) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("delay should be none-negative integer");
        }
        $this->_commandDelay = intval($microsec);
        return $this;
    }

    /**
     * Returnes the string for the command prompt
     *
     * @return  string
     */
    public function getPrompt()
    {
        return $this->_prompt;
    }

    /**
     * Sets the string for the command prompt
     *
     * @param   string          $prompt
     * @return  Parsonline_Network_Telnet   object self reference
     */
    public function setPrompt($prompt)
    {
        $this->_prompt = strval($prompt);
        return $this;
    }
    
    /**
     * Returnes the string that is used for confirmation prompt
     *
     * @return  string
     */
    public function getConfirmationPrompt()
    {
        return $this->_confirmationPrompt;
    }

    /**
     * Sets the string that is used for confirmation prompt
     *
     * @param   string $prompt
     * @return  Parsonline_Network_Telnet   object self reference
     */
    public function setConfirmationPrompt($prompt)
    {
        $this->_confirmationPrompt = strval($prompt);
        return $this;
    }

    /**
     * Returnes the string that is used for when output is in pages
     * and server is waiting for a command to show more results.
     *
     * @return  string
     */
    public function getNextPagePrompt()
    {
        return $this->_nextPagePrompt;
    }

    /**
     * Sets the string that is used for for when output is in pages
     * and server is waiting for a command to show more results.
     *
     * @param   string $prompt
     * @return  Parsonline_Network_Telnet   object self reference
     */
    public function setNextPagePrompt($prompt)
    {
        $this->_nextPagePrompt = strval($prompt);
        return $this;
    }

    /**
     * Returns the bufferred response of remote host
     *
     * @param   bool    $clearLastLine  if set to true, the last line of the buffer will be trimmed.
     * @param   string  $endOfLine the  character to be used as end of lines of the buffer. default is "\n"
     * @return  string
     */
    public function getResponse($clearLastLine=false, $endOfLine="\n")
    {
        $buffer = explode("\n", $this->_responseBuffer);
        $endOfLine = strval($endOfLine);
        if ( !!$clearLastLine ) {
            // cut last line, which could be the prompt
            array_pop($buffer);
        }
        $buffer = implode($endOfLine,$buffer);
        return $buffer;
    }

    /**
     * Returns the global bufferred output of telnet since the beginning of the connection
     * 
     * @return  array
     */
    public function getResponseStack()
    {
        return $this->_responseBufferStack;
    }

    /**
     * If read/write operations should collect information for profiling
     * 
     * @param   bool    $use
     * @return  Parsonline_Network_Telnet   object self reference
     */
    public function profileInputOutput($use=true)
    {
        $this->_profile = true && $use;
        return $this;
    }

    /**
     * Returns an associative array of profiling information. each key is the name
     * of a method, and the value is number of miliseconds that took during that
     * method execution. if the optioal parameter is specified, the profiling
     * information of that method will be returned.
     *
     * @param   string  $methodName     name of the method
     * @return  array
     * @throws  Parsonline_Exception_ValueException code 0 for none-existing method
     * @throws  Parsonline_Exception_ValueException code 1 for not profield method
     */
    public function getProfileInfo($methodName=null)
    {
        if ( $methodName === null ) return $this->_profileData;
        $methodName = strval($methodName);
        if ( !method_exists($this, $methodName) ) {
            /**
             * @uses Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("invalid method name '{$methodName}'", 0);
        }
        if ( !array_key_exists($methodName, $this->_profileData) ) {
            /**
             * @uses Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("method '{$methodName}' is not profiled", 1);
        }
        return $this->_profileData[$methodName];
    } // public function getProfileInfo()

    /**
     * Sets data of the object from an array.
     * 
     * @param   array       $options        associative array of property => values
     * @return  Parsonline_Network_Telnet
     */
    public function setOptionsFromArray(array $options)
    {
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst( $key );
            if ( in_array($method, $methods) ) {
                $this->$method($value);
            }
        }
        return $this;
    } // public function setOptionsFromArray()

    /**
     * sets data of the object from another object
     *
     * @param   object          $optionsObject          an object with getOptions methods related to setOptions
     * @return  Parsonline_Network_Telnet   object self reference
     * @throws  Parsonline_Exception_ValueException
     */
    public function setOptionsFromObject($optionsObject)
    {
        if ( !is_object($optionsObject) ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("parameter should be an object.");
        }
        $internalMethods = get_class_methods($this);
        // inject from public properties
        $externalProperties = get_object_vars($optionsObject);
        foreach ($externalProperties as $key => $value) {
            $method = 'set' . ucfirst( $key );
            if (in_array($method, $internalMethods)) {
                $this->$method($value);
            }
        }
        // inject from getter methods
        $externalMethods = get_class_methods($optionsObject);
        foreach ($externalMethods as $otherMethod ) {
            $matchedPattern = array();
            if ( preg_match('/^get(\S+\S*)$/',$otherMethod,$matchedPattern) ) {
                $method = 'set' . $matchedPattern[1];
                if (in_array($method, $internalMethods)) {
                    $this->$method( $optionsObject->$otherMethod() );
                }
            }
        }
        return $this;
    }

   /**
    * Connects to server and initializes some options, if set to do so.
    *
    * @param    bool        $init       if should send some initialization options. default is true.
    * @return   Parsonline_Network_Telnet   object self reference
    * @throws   Parsonline_Exception_ContextException for no host specified
    * @throws   Parsonline_Network_Telnet code CONNECTION for connection failure
    */
    public function connect($init=true)
    {
        if (!$this->_host) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException('no host is specified to connect to.');
        }
        if ($this->_profile) $profileStart = microtime(true);
        $errorCode = 0;
        $errorMessage = '';
        $socket = fsockopen($this->_host, $this->_port, $errorCode, $errorMessage, $this->_connectionTimeout);
        
        if ( !is_resource($socket) ) {
            /**
             * @uses    Parsonline_Network_Telnet_Exception
             */
            require_once('Parsonline/Network/Telnet/Exception.php');
            throw new Parsonline_Network_Telnet_Exception(
                "Failed to open a TCP connection to '{$this->_host}:{$this->_port}'. error {$errorCode}: {$errorMessage}", Parsonline_Network_Telnet_Exception::CONNECTION
            );
        }
        
        stream_set_blocking($socket, 1);
        stream_set_timeout($socket, $this->_timeout, 0);
        
        $this->_responseBufferStack = array();
        $this->_responseBuffer = '';
        $this->_pofileData = array();
        $this->_socket =& $socket;
        $this->_isConnected = true;

        if ($init) $this->_init();

        if ($this->_profile) $this->_profileData['connect'] = (microtime(true) - $profileStart);
        return $this;
    } // pubilc function connect()
    
    /**
     * Disconnects the telnect connection.
     * 
     * NOTE: empties the read buffer
     *
     * @return  Parsonline_Network_Telnet   object self reference
     */
    public function disconnect()
    {
        $this->emptyBuffer();
        if ( is_resource($this->_socket) ) {
            $this->_terminate();
            fclose($this->_socket);
        }
        $this->_socket = null;
        $this->_isConnected = false;
        return $this;
    }

    /**
     * Shows if the connection is stablished and open. accepts an optional param
     * to poke the server to make sure connection is still open. if poke is set,
     * will empty the buffer.
     * 
     * @param   bool    $poke       if should poke the host to make sure if we are connected or not
     * @return  bool
     */
    public function isConnected($poke=true)
    {
        if ( !is_resource($this->_socket) ) {
            $this->_isConnected = false;
            return false;
        }
        
        if ( $this->isConnectionTimedOut() ) {
            $this->_isConnected = false;
            return false;
        }

        if ($poke) {
            $this->emptyBuffer();
            $this->writeCommand( $this->IAC . $this->AYT );
            $response = $this->readBytes(1);
            if ( empty($response) ) {
                $this->_isConnected = false;
                return false;
            }
            $this->emptyBuffer(); // we do not need the response of poking
        }
        
        $this->_isConnected = true;
        return true;
    } // public function isConnected()

    /**
     * Returns the socket resource that is beeing used for the connection
     *
     * @param   bool        $autoConnect    if should automatical
     * @return  resource        file handler of the socket
     */
    public function getConnection($autoConnect=true)
    {
        if ( !is_resource($this->_socket) && $autoConnect ) {
            $this->connect();
        }
        return $this->_socket;
    }

    /**
     * Checks if the connection is timed out or not.
     * this method only checks for timed out situaion. if the status of the
     * socket stream could not be queried, then it is not considered as timedout.
     * use isConnected() method to make sure there is no connection.
     *
     * @return  bool
     */
    public function isConnectionTimedOut()
    {
        if ( !is_resource($this->_socket) ) return true;
        $metaData = stream_get_meta_data($this->_socket);
        /**
         * if the status of the stream could not be queried,
         * then it would be considered as timed out.
         */
        if ( is_array($metaData) && array_key_exists('timed_out', $metaData) && $metaData['timed_out'] ) {
            return true;
        }
        return false;
    }

    /**
     * Initiate connection with the remote host, right after the TCP
     * connection is made.
     * 
     * NOTE: Could be overriden to provide connection to different platforms.
     *
     * @return  Parsonline_Network_Telnet       object self reference
     */
    protected function _init()
    {
        $initOptions =
            $this->IAC . $this->WILL . $this->NAWS .
            $this->IAC . $this->WILL . $this->TSPEED .
            $this->IAC . $this->WILL . $this->TTYPE .
            $this->IAC . $this->WILL . $this->NEW_ENVIRON .
            $this->IAC . $this->DO . $this->ECHO .
            $this->IAC . $this->WILL . $this->SGA .
            $this->IAC . $this->DO . $this->SGA .
            $this->IAC . $this->WONT . $this->XDISPLOC .
            $this->IAC . $this->WONT . $this->OLD_ENVIRON .
            $this->IAC . $this->SB .
                $this->NAWS . $this->NULL . chr(80) . $this->NULL . $this->TTYPE .
            $this->IAC . $this->SE .
            $this->IAC . $this->SB . $this->TSPEED . $this->NULL .
                chr(51) . chr(56) . chr(52) . chr(48) . chr(48) . chr(44) . chr(51) .
                chr(56) . chr(52) . chr(48) . chr(48) . $this->IAC . $this->SE .
            $this->IAC . $this->SB . $this->NEW_ENVIRON . $this->NULL . $this->IAC . $this->SE .
            $this->IAC . $this->SB . $this->TTYPE . $this->NULL .
                chr(88) . chr(84) . chr(69) . chr(82) . chr(77) .
            $this->IAC . $this->SE;

        $this->writeCommand($initOptions);
        $this->halt();

        $initOptions =
            $this->IAC . $this->WONT . $this->ECHO .
            $this->IAC . $this->WONT . $this->LINEMODE .
            $this->IAC . $this->DONT . $this->STATUS .
            $this->IAC . $this->WONT . $this->LFLOW;

        $this->writeCommand($initOptions);
        $this->halt();
        return $this;
    } // public function _init()
    
    /**
     * Terminate the connection with the remote host, right before the TCP
     * connection is disconnected.
     * 
     * NOTE: Could be overriden to provide clean disconnection from different platforms.
     *
     * @return  Parsonline_Network_Telnet       object self reference
     */
    protected function _terminate()
    {    
        try {
            $this->write('exit');
            $this->setTelnetOption($this->LOGOUT);
        } catch(Exception $exp) {
            $exp;
        }
    } // public function _init()

    /**
     * Halts current process for a number of microseconds specified by the parameter,
     * or if left out, uses the object command delay property.
     *
     * @param   float   $time   number of microseconds to halt (default is command delay property of the object)
     * @return  Parsonlien_Network_Telnet
     * @throws  Parsonline_Exception_ValueException
     */
    public function halt($time=null)
    {
        if ($time === null) {
            $time = $this->_commandDelay;
        } elseif ( 0 > $time ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException('halt time should not be negative');
        } else {
            $time = floatval($time);
        }
        usleep($time);
        return $this;
    } // public function halt()

    /**
     * Reads a character from the stream socket.
     * 
     * NOTE: this method does not use the local response buffer.
     * so if you use this method, the read character would bypass the buffer
     * and will not be accessible on later calls
     *
     * NOTE: this method does not check for the validity of the connection.
     * if socket stream is closed, issues a PHP warning and returns false.
     * 
     * @return  string|false        on end of response or if connection is lost
     */
    protected function _getChar()
    {
        if ($this->_profile) $profileStart = microtime(true);
        $char = $this->_socket ? fgetc( $this->_socket ) : false;
        if ($this->_profile) $this->_profileData['getChar'] = (microtime(true) - $profileStart);
        if ( $char === false ) return false;
        return $char;
    } // public function _getChar()

    /**
     * Adds current buffer to buffer stack
     *
     * @param   bool    $emptyCurrentBuffer     if should empty the current buffer.
     * @return  Parsonline_Network_Telnet   object self reference
     */
    public function saveResponseBuffer($emptyCurrentBuffer=true)
    {
        $buffer = $this->_responseBuffer;
        if ( $emptyCurrentBuffer ) {
            $this->_responseBuffer = '';
        }
        if ( !empty($buffer) ) {
            return array_push($this->_responseBufferStack, $buffer);
        }
        return $this;
    } // public function saveResponseBuffer()

    /**
     * empty the read buffer
     *
     * @return  Parsonline_Network_Telnet   object self reference
     */
    public function emptyBuffer()
    {
        $this->_responseBuffer = '';
        return $this;
    } // public function emptyBuffer()

    /**
     * Checks if the string is a request for negociation over an option. if no
     * string is specified, reads from the connection.
     * returns an associative array with keys for 'do','dont','will','wont' whose
     * values would be an array of option codes. if the string was not a request
     * for option negociation, all those subarrays would be empty.
     * 
     * @param   string      $string
     * @return  false|int   option code or false if request is not negociation over an option
     */
    public function parseOptionNegociationString($string=null)
    {
        $telnetCommand = self::getTelnetProtocolCharacters();
        if ($string === null) $string = $this->readAvailableResponse();

        if ( strpos($telnetCommand['IAC'], $string) === false ) return false;

        $optionSequences = array(
                                'do'        =>  $this->IAC . $this->DO,
                                'dont'      =>  $this->IAC . $this->DONT,
                                'will'      =>  $this->IAC . $this->WILL,
                                'wont'      =>  $this->IAC . $this->WONT
                            );
        

        $options = array(
                            'do'    =>  array(),
                            'dont'  =>  array(),
                            'will'  =>  array(),
                            'wont'  =>  array()
                    );

        foreach($optionSequences as $optionType => $sequence) {
            $parts = array();
            $part = null;
            if ( strpos($string, $sequence) !== false ) {
                $parts = expolode($sequence, $string);
                if (!$parts) continue;
                foreach($parts as $part) {
                    if ( strlen($part) !== 1 ) continue;
                    /**
                     * if current character is IAC, it means that
                     * next part had been IAC.IAC.DO which could be
                     * an IAC.DO data, prefixed by escaped by the first IAC.
                     * so jump to the first index of array after the next one.
                     */
                    if ( $part === $this->IAC ) {
                        next($parts);
                        continue;
                    }
                    array_push($options[ $optionType ],$part);
                }
            }
        }
        
        return $options;
    } // public function parseOptionNegociationString()

    /**
     * Returns an array of command string that refuses the remote host request for options.
     * accepts the options as an array. you should send the returned array of string
     * to the remote host to refuse the options.
     *
     * @param   array   $options        associative array with keys 'do','dont','will','wont'. each one is another array of option codes.
     * @return  array   indexed array of command options to refuse the options.
     */
    public function getRefuseResponseForOptionNegociation($options)
    {
        $wontOptions = array(); // array of options we refuse to accept
        $dontOptions = array(); // arra yof options we ask the remote host to ignore

        if ( array_key_exists('do', $options) && is_array($options['do']) ) $wontOptions = array_merge($wontOptions, $options['do']);
        if ( array_key_exists('dont', $options) && is_array($options['dont']) ) $wontOptions = array_merge($wontOptions, $options['dont']);
        if ( array_key_exists('will', $options) && is_array($options['will']) ) $dontOptions = array_merge($dontOptions, $options['will']);
        if ( array_key_exists('wont', $options) && is_array($options['wont']) ) $dontOptions = array_merge($dontOptions, $options['wont']);

        $response = array();
        $option = null;
        
        foreach ($dontOptions as $option) {
            array_push($response, $telnetCommand['IAC'] . $telnetCommand['WONT'] . $option);
        }
        
        foreach($wontOptions as $option) {
            array_push($response, $telnetCommand['IAC'] . $telnetCommand['DONT'] . $option );
        }
        
        return $response;
    } // public function getRefuseResponseForOptionNegociation()

    /**
     * Negociates with the host to enable an option and returns the results.
     * this method is used to ask if the remote host will accept some option
     * or not.
     * If the host response is not in a negocation format, will throw an Parsonline_Network_Telnet_Exception
     *
     * @param   string      option
     * @return  bool
     * @throws  Parsonline_Exception_InvalidParamterException
     * @throws  Parsonline_Network_Telnet_Exception code NEGOCIATION_FAILED
     */
    public function negociateTelnetOption($option=null)
    {
        $telnetOptions = self::getTelnetProtocolOptions();
        if ( !array_key_exists($option, $telnetOptions) ) {
            /**
             * @uses Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("invalid telnet option '{$option}'.");
        }
        $telnetChars = self::getTelnetProtocolCharacters();

        // send negociation request
        $this->writeCommand( $telnetChars['IAC'] . $telnetChars['WILL'] . $option );
        $this->halt();
        $results = $this->readBytes(3);
        $remoteOptions = $this->parseOptionNegociationString($results);
        $this->emptyBuffer();
        if ( $remoteOptions['do'] ) {
            return true;
        } else if ( $remoteOptions['dont'] ) {
            return false;
        } else {
            // host is supposed to send some valid response.
            $this->emptyBuffer();
            /**
             * @uses    Parsonline_Network_Telnet_Exception
             */
            require_once('Parsonline/Network/Telnet/Exception.php');
            throw new Parsonline_Network_Telnet_Exception("invalid response from the host. buffer dump: " . $results, Parsonline_Network_Telnet_Exception::NEGOCIATION_FAILED);
        }
    } // public function negociateTelnetOption()

    /**
     * Orders the host to enable an option from the host.
     * The remote host might accept or not, so the mothod retursn the response of
     * the remote host.
     * If the host response is not in a negocation format, will throw an Parsonline_Network_Telnet_Exception
     *
     * @param   string  option
     * @return  bool
     * @throws  Parsonline_Exception_ValueException on invalid option
     * @throws  Parsonline_Network_Telnet_Exception code NEGOCIATION_FAILED for bad response
     */
    public function orderHostForTelnetOption($option=null)
    {
        $telnetOptions = self::getTelnetProtocolOptions();
        if ( !array_key_exists($option, $telnetOptions) ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("invalid telnet option '{$option}'.", 0);
        }

        $telnetChars = self::getTelnetProtocolCharacters();
        // send negociation request
        $this->writeCommand( $telnetChars['IAC'] . $telnetChars['DO'] . $option );
        $this->halt();
        $results = $this->readBytes(3);
        $this->emptyBuffer();
        $remoteOptions = $this->parseOptionNegociationString($results);
        
        if ( $remoteOptions['will'] ) {
            return true;
        } else if ( $remoteOptions['wont'] ) {
            return false;
        } else {
            // host is supposed to send IAC . WILL / IAC . WONT. bad response from server
            /**
             * @uses    Parsonline_Network_Telnet_Exception
             */
            require_once('Parsonline/Network/Telnet/Exception.php');
            throw new Parsonline_Network_Telnet_Exception("invalid response from the host. buffer dump: " . $results, Parsonline_Network_Telnet_Exception::NEGOCIATION_FAILED);
        }
    } // public function orderHostForTelnetOption()

    /**
     * Sets a telnet option, if set to prenegociate, negociates with the host and and then sends the option.
     * incase of prenegociation, if remote host refuses the option, returns false.
     * 
     * NOTE: Empties the read buffer.
     *
     * @param   string          $option         telnet option
     * @param   string          $value          [optional]
     * @param   bool            $preNegociate   if should negiciate if the remote will accept the option or not, before sending the option. default is false.
     * @return  false|string    remote output, or false if negociation failed
     * @throws  Parsonline_Network_Telnet_Exception
     */
    public function setTelnetOption($option, $value=null, $preNegociate=false)
    {
        if ($preNegociate && !$this->negociateTelnetOption($option) ) {
            return false;
        }
        
        // request the host to set the value of the option
        $command = $this->IAC . $this->SB . $option;
        if ( $value !== null ) $command .= $this->NULL . $value;
        $command .= $this->IAC . $this->SE;
        $this->writeCommand( $command );
        $this->halt();
        // empty the rest of the response
        $output = $this->readAvailableResponse();
        $this->emptyBuffer();
        return $output;
    } // public function setTelnetOption()
    
    /**
     * Reads all data from telnet connection until connection times out.
     * if no data was read, throws a Parsonline_Network_Telnet_Exception
     * with code REACHED_EOF.
     *
     * NOTE: will empty the buffer before reading.
     * NOTE: DOES NOT REMOVE/INTERPRET TELNET SPECIAL CHARACTERS.
     * NOTE: If you do not want to catch exceptions, or check connections, use the
     * readAvailableResponse() instead.
     * 
     * @return  string
     * @throws  Parsonline_Network_Telnet_Exception code CONNCETION if not connected
     * @throws  Parsonline_Network_Telnet_Exception code REACHED_EOF if no data read until timeout
     */
    public function read()
    {
        if ( !$this->isConnected(false) ) {
            /**
             * @uses    Parsonline_Network_Telnet_Exception
             */
            require_once('Parsonline/Network/Telnet/Exception.php');
            throw new Parsonline_Network_Telnet_Exception('read operation failed. connection is lost', Parsonline_Network_Telnet_Exception::CONNECTION);
        }
        $this->emptyBuffer();
        if ($this->_profile) $profileStart = microtime(true);
        $this->_responseBuffer = stream_get_contents($this->_socket);        
        if ($this->_profile) $this->_profileData['read'] = (microtime(true) - $profileStart);
        if ( empty($this->_responseBuffer) ) {
            /**
             * @uses    Parsonline_Network_Telnet_Exception
             */
            require_once('Parsonline/Network/Telnet/Exception.php');
            throw new Parsonline_Network_Telnet_Exception("reached end of the stream but read nothing", Parsonline_Network_Telnet_Exception::REACHED_EOF );
        }
        return $this->_responseBuffer;
    } // public function read()

    /**
     * Reads from current response available locally on the stream.
     * and does not throw exceptions.
     * returns empty string if nothing is available.
     *
     * NOTE: will empty read buffer before reading new data.
     * NOTE: DOES NOT REMOVE/INTERPRET TELNET SPECIAL CHARACTERS.
     * @return  string
     */
    public function readAvailableResponse()
    {
        if ($this->_profile) $profileStart = microtime(true);

        $this->emptyBuffer();
        
        if ( is_resource($this->_socket) ) {
            $buffer = stream_get_contents($this->_socket);
        } else {
            $buffer = '';
        }

        $this->_responseBuffer = is_string($buffer) ? $buffer : '';
        unset($buffer);

        if ($this->_profile) $this->_profileData['readAvailableResponse'] = (microtime(true) - $profileStart);
        return $this->_responseBuffer;
    } // public function readAvailableResponse()

    /**
     * Reads from the stream until the buffer reached a number of bytes,
     * or reached end of stream, or connection timesout. If after the read
     * no data has been read, will throw a Parsonline_Network_Telnet_Exception
     * with code REACHED_EOF.
     *
     * NOTE: will empty read buffer before reading new data.
     * NOTE: DOES NOT REMOVE/INTERPRET TELNET SPECIAL CHARACTERS.
     *
     * @param   int     $bytes      number of bytes to read at maximum. default is 1 byte
     * @return  string
     * @throws  Parsonline_Network_Telnet_Exception with codes CONNECTION, REACHED_EOF
     * @throws  Parsonline_Exception_ValueException on none-positive bytes
     */
    public function readBytes($bytes=1)
    {
        $bytes = floatval($bytes);
        if ( 1 > $bytes ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException('number of bytes should be positive number');
        }

        if ( !$this->isConnected(false) ) {
            /**
             * @uses    Parsonline_Network_Telnet_Exception
             */
            require_once('Parsonline/Network/Telnet/Exception.php');
            throw new Parsonline_Network_Telnet_Exception(
                'read operation failed. connection is lost',
                Parsonline_Network_Telnet_Exception::CONNECTION
            );
        }
        if ($this->_profile) $profileStart = microtime(true);

        $this->emptyBuffer();
        $this->_responseBuffer = stream_get_contents($this->_socket, $bytes);

        if ($this->_profile) $this->_profileData['readBytes'] = (microtime(true) - $profileStart);
        if ( empty($this->_responseBuffer) ) {
            /**
             * @uses    Parsonline_Network_Telnet_Exception
             */
            require_once('Parsonline/Network/Telnet/Exception.php');
            throw new Parsonline_Network_Telnet_Exception("reached end of the stream but read nothing", Parsonline_Network_Telnet_Exception::REACHED_EOF );
        }
        return $this->_responseBuffer;
    } // public function readBytes()

    /**
     * will read data from telnet connection until reached the specifed string, or connection timesout.
     * if no match were found for the string, will return all the data read.
     * if no data is read, will throw and Parsonline_Network_Telnet_Exception with code REACHED_EOF.
     * 
     * NOTE: will empty read buffer before reading new data.
     * NOTE: DOES NOT REMOVE/INTERPRET TELNET SPECIAL CHARACTERS.
     *
     * @param   string  $targetString
     * @param   bool    $reportNoMatch  throw an exception with code NO_MATCH_FOUND, if no match found for the string
     * @return  string
     *
     * @throws  Parsonline_Network_Telnet_Exception with code REACHED_EOF if nothing read, and code NO_MATCH_FOUND if failed to find a matching sting.
     */
    public function readUntil($targetString, $reportNoMatch=false)
    {
        if ( !$this->isConnected(false) ) {
            /**
             * @uses    Parsonline_Network_Telnet_Exception
             */
            require_once('Parsonline/Network/Telnet/Exception.php');
            throw new Parsonline_Network_Telnet_Exception(
                'read operation failed. connection is lost',  Parsonline_Network_Telnet_Exception::CONNECTION
            );
        }
        $this->emptyBuffer();
        if ($this->_profile) $profileStart = microtime(true);
        
        $buffer = '';
        
        $targetStringLength = strlen($targetString);
        $targetStringLastChar = $targetString[ $targetStringLength - 1 ];

        $found = false;
        while (true) {
            $char = $this->_getChar();
            if ( $char === false ) { // end of the stream, or timeout
                break;
            }
            $buffer .= $char;
            /**
             * if char is not the last char of targetstring, then for sure it is not read yet.
             * by adding the first condition below, I reduced the number of strpos() calls
             * that will fail for sure.
             */
            if ( $char == $targetStringLastChar && strpos($buffer, $targetString) !== false ) {
                $found = true;
                break;
            }
        } // while()
        $this->_responseBuffer = $buffer;
        unset($buffer);
        if ($this->_profile) $this->_profileData['readUntil'] = (microtime(true) - $profileStart);
        if ( empty($this->_responseBuffer) ) {
            /**
             * @uses    Parsonline_Network_Telnet_Exception
             */
            require_once('Parsonline/Network/Telnet/Exception.php');
            throw new Parsonline_Network_Telnet_Exception("reached end of the stream but read nothing", Parsonline_Network_Telnet_Exception::REACHED_EOF );
        }
        if ($reportNoMatch && !$found) {
            /**
             * @uses    Parsonline_Network_Telnet_Exception
             */
            require_once('Parsonline/Network/Telnet/Exception.php');
            throw new Parsonline_Network_Telnet_Exception("no match found for '$targetString'", Parsonline_Network_Telnet_Exception::NO_MATCH_FOUND);
        }
        return $this->_responseBuffer;
    } // public function readUntil()

    /**
     * will read data from stream until a match for a regex is found in the response data,
     * or connection timesout. if no match were found for the pattern, will return all the data read.
     * if no data is read, will throw and exception with code REACHED_EOF.
     *
     * NOTE: will empty read buffer before reading new data.
     * NOTE: DOES NOT REMOVE/INTERPRET TELNET SPECIAL CHARACTERS.
     *
     * NOTE: this method is much slower than readUntil(). if you do not need fancy regex matching, use
     * readUntil() instead for better performance.
     * 
     * @param   string  $pattern        perl compatible regular expression pattern
     * @param   bool    $reportNoMatch  throw an exception with code NO_MATCH_FOUND, if no match found for the string
     * @return  string
     * @throws  Parsonline_Network_Telnet_Exception with code REACHED_EOF if nothing read, and code NO_MATCH_FOUND
     * if failed to find a matching sting.
     */
    public function readUntilRegex($pattern, $reportNoMatch=false)
    {
        if ( !$this->isConnected(false) ) {
            /**
             * @uses    Parsonline_Network_Telnet_Exception
             */
            require_once('Parsonline/Network/Telnet/Exception.php');
            throw new Parsonline_Network_Telnet_Exception(
                'read operation failed. connection is lost',  Parsonline_Network_Telnet_Exception::CONNECTION
            );
        }
        $this->emptyBuffer();
        if ($this->_profile) $profileStart = microtime(true);

        $buffer = '';
        $found = false;
        
        while (true) {
            $char = $this->_getChar();
            if ( $char === false ) { // end of the stream, or timeout
                break;
            }
            $buffer .= $char;
            $found = preg_match($pattern, $buffer);
            if ( $found ) {
                break;
            }
        } // while()
        $this->_responseBuffer = $buffer;
        unset($buffer);
        if ($this->_profile) $this->_profileData['readUntilRegex'] = (microtime(true) - $profileStart);
        
        if ( empty($this->_responseBuffer) ) {
            /**
             * @uses    Parsonline_Network_Telnet_Exception
             */
            require_once('Parsonline/Network/Telnet/Exception.php');
            throw new Parsonline_Network_Telnet_Exception(
                "reached end of the stream but read nothing", Parsonline_Network_Telnet_Exception::REACHED_EOF
            );
        }
        
        if ($reportNoMatch && !$found) {
            /**
             * @uses    Parsonline_Network_Telnet_Exception
             */
            require_once('Parsonline/Network/Telnet/Exception.php');
            throw new Parsonline_Network_Telnet_Exception(
                "no match found for '$pattern'", Parsonline_Network_Telnet_Exception::NO_MATCH_FOUND
            );
        }
        return $this->_responseBuffer;
    } // public function readUntilRegex()

    /**
     * writes a string buffer to the socket stream. will double all occurrance
     * of IAC characater (ASCII 255) in the buffer string to skip them and ingore
     * the remote host treat them as IAC chars. (could be disabled).
     *
     * @param   string      $buffer
     * @param   bool        appendEndOfLine     if set to true, will append an end of line to buffer
     * @param   bool        skipIAC             if should skip telnet special char IAC by doubling it. default is true.
     * @return  Parsonline_Network_Telnet       object self reference
     * @throws  Parsonline_Network_Telnet_Exception code CONNECTION if connection is lost
     */
    public function write($buffer, $appendEndOfLine=true, $skipIAC=true )
    {
        if ( !$this->isConnected(false) ) {
            /**
             * @uses    Parsonline_Network_Telnet_Exception
             */
            require_once('Parsonline/Network/Telnet/Exception.php');
            throw new Parsonline_Network_Telnet_Exception(
                'write operation failed. connection is lost', Parsonline_Network_Telnet_Exception::CONNECTION
            );
        }
        if ($this->_profile) $profileStart = microtime(true);
        $appendEndOfLine = !!$appendEndOfLine;
        $buffer = $appendEndOfLine ?  ($buffer . "\n") : strval($buffer);
        $IAC = chr(255);
        if ( $skipIAC ) str_replace($IAC, $IAC . $IAC, $buffer);
        if ( fwrite($this->_socket, $buffer) === false ) {
            $errorCode = socket_last_error();
            if ( function_exists('socket_strerror') ) {
                $errorMessage = socket_strerror($errorCode);
            } else {
                $errorMessage = 'unknown';
            }
            if ($this->_profile) $this->_profileData['write'] = (microtime(true) - $profileStart);
            /**
             * @uses    Parsonline_Network_Telnet_Exception
             */
            require_once('Parsonline/Network/Telnet/Exception.php');
            throw new Parsonline_Network_Telnet_Exception(
                "error writing to stream. error: {$errorMessage}. buffer dump: {$buffer}. code: ". $errorCode,
                Parsonline_Network_Telnet_Exception::STREAM_NOT_AVAILABLE
            );
        }
        if ($this->_profile) $this->_profileData['write'] = (microtime(true) - $profileStart);
        return $this;
    } // public function write()

    /**
     * Writes a telnet command buffer to the stream socket.
     * will NOT double occurrance of IAC characater (ASCII 255) in the buffer string,
     * so remote host would treate the buffer as command.
     *
     * @param   string      $command
     * @param   bool        appendEndOfLine     if set to true, will append an end of line to buffer
     * @return  object      Parsonline_Network_Telnet
     * @throws  Parsonline_Network_Telnet_Exception code CONNECTION if connection is lost
     */
    public function writeCommand($command, $appendEndOfLine=true )
    {
        if ( !$this->isConnected(false) ) {
            /**
             * @uses    Parsonline_Network_Telnet_Exception
             */
            require_once('Parsonline/Network/Telnet/Exception.php');
            throw new Parsonline_Network_Telnet_Exception(
                'write operation failed. connection is lost',
                Parsonline_Network_Telnet_Exception::CONNECTION
            );
        }
        if ($this->_profile) $profileStart = microtime(true);
        
        $command = ($appendEndOfLine) ? ($command . "\n") : strval($command);
        
        if ( fwrite($this->_socket, $command) === false ) {
            $errorCode = socket_last_error();
            if ( function_exists('socket_strerror') ) {
                $errorMessage = socket_strerror($errorCode);
            } else {
                $errorMessage = 'unknown';
            }
            if ($this->_profile) $this->_profileData['writeCommand'] = (microtime(true) - $profileStart);
            /**
             * @uses    Parsonline_Network_Telnet_Exception
             */
            require_once('Parsonline/Network/Telnet/Exception.php');
            throw new Parsonline_Network_Telnet_Exception(
                "error writing to stream. error: {$errorMessage}. buffer dump: command: ". $errorCode,
                Parsonline_Network_Telnet_Exception::STREAM_NOT_AVAILABLE
            );
        }
        if ($this->_profile) $this->_profileData['writeCommand'] = (microtime(true) - $profileStart);
        return $this;
    } // public function writeCommand()

    /**
     * Reads buffer up to the prompt string. by default throws a Parsonline_Network_Telnet_Exceptions
     * with code NO_MATCH_FOUND if the response did not contain the maching
     * string (could be disabled).
     * if the remote host does not send any response, throws a Parsonline_Network_Telnet_Exceptions
     * exception with code REACHED_EOF.
     *
     * NOTE: will earase current buffer before fetching data.
     *
     * @param   bool        $reportNoMatch   if should throw Parsonline_Network_Telnet_Exceptions if failed to find the prompt
     * @return  string      server response
     * @throws  Parsonline_Network_Telnet_Exception code REACHED_EOF, or NO_MATCH_FOUND
     */
    public function waitForPrompt($reportNoMatch = true)
    {
        if ($this->_profile) $profileStart = microtime(true);
        $buffer = $this->readUntil($this->_prompt, $reportNoMatch);
        if ($this->_profile) $this->_profileData['waitForPrompt'] = (microtime(true) - $profileStart);
        return $buffer;
    } // public function waitForPrompt()

    /**
     * reads buffer up to the next page string. by default throws a Parsonline_Network_Telnet_Exceptions
     * with code NO_MATCH_FOUND if the response did not contain the maching
     * string (could be disabled).
     * if the remote host does not send any response, throws a Parsonline_Network_Telnet_Exceptions
     * exception with code REACHED_EOF.
     *
     * NOTE: will earase current buffer before fetching data.
     *
     * @param   bool        $reportNoMatch   if should throw Parsonline_Network_Telnet_Exceptions if failed to find the prompt
     * @return  string      server response
     * @throws  Parsonline_Network_Telnet_Exception code REACHED_EOF, or NO_MATCH_FOUND
     */
    public function waitForNextPage($reportNoMatch = true)
    {
        if ($this->_profile) $profileStart = microtime(true);
        $buffer = $this->readUntil($this->_nextPagePrompt, $reportNoMatch);
        if ($this->_profile) $this->_profileData['waitForNextPage'] = (microtime(true) - $profileStart);
        return $buffer;
    } // public function waitForNextPage()

    /**
     * runs the specified command (not a telnet command, but a remote host command)
     * and returns the response string if any.
     *
     * NOTE: will earase current buffer before fetching data.
     * NOTE: to run a Telnet specific command use writeCommand()
     *
     * @param   string  $command    the command to be run on remote host
     * @return  string  remote host response
     * @throws  Parsonline_Network_Telnet_Exception code CONNECTION
     */
    public function runCommand($command='')
    {
        if ( !$this->isConnected(false) ) {
            /**
             * @uses    Parsonline_Network_Telnet_Exception
             */
            require_once('Parsonline/Network/Telnet/Exception.php');
            throw new Parsonline_Network_Telnet_Exception(
                'run operation failed. connection is lost',
                Parsonline_Network_Telnet_Exception::CONNECTION
            );
        }
        if ($this->_profile) $profileStart = microtime(true);

        $this->emptyBuffer();
        $this->write($command);
        $this->halt();
        $buffer = $this->readAvailableResponse();
        /**
         * @TODO
         * I can not remember why I did this regex replace.
         */
        $buffer = preg_replace("/^.*?\n(.*)\n[^\n]*$/", "$1", $buffer);
        $this->_responseBuffer = $buffer;

        if ($this->_profile) $this->_profileData['runCommand'] = (microtime(true) - $profileStart);
        return $buffer;
    } // public function runCommand()
}
