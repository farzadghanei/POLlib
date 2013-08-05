<?php
//Parsonline/Stream.php
/**
 * Defines Parsonline_Stream class.
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
 * @package     Parsonline_Stream
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.4.4 2012-02-26
*/

/**
 * Parsonline_Stream
 * 
 * Provides an Object Oriented interface to a data stream.
 * By default creates a blocking/strict mode stream with no write buffer.
 */
class Parsonline_Stream
{
    const META_TIMEDOUT = 'timed_out';
    const META_BLOCKING = 'blocked';
    const META_EOF = 'eof';
    const META_UNREAD_BYTES = 'unread_bytes';
    const META_STREAM_TYPE = 'stream_type';
    const META_WRAPPER_TYPE = 'wrapper_type';
    const META_MODE = 'mode';
    const META_SEEKABLE = 'seekable';
    const META_URI = 'uri';
    const META_FILE_SYSTEM_STATS = 'file_system_stats';

    const FILE_SYSTEM_STAT_ACCESS_TIME = 'atime';
    const FILE_SYSTEM_STAT_ALLOCATED_BLOCKS = 'blocks';
    const FILE_SYSTEM_STAT_BLOCK_SIZE = 'blksize';
    const FILE_SYSTEM_STAT_CHANGE_TIME = 'ctime';
    const FILE_SYSTEM_STAT_DEVICE = 'dev';
    const FILE_SYSTEM_STAT_GROUP_ID = 'gid';
    const FILE_SYSTEM_STAT_INODE = 'ino';
    const FILE_SYSTEM_STAT_INODE_DEVICE_TYPE = 'rdev';
    const FILE_SYSTEM_STAT_INODE_MODE = 'mode';
    const FILE_SYSTEM_STAT_MODIFY_TIME = 'mtime';
    const FILE_SYSTEM_STAT_NUMBER_OF_LINKS = 'nlink';
    const FILE_SYSTEM_STAT_SIZE = 'size';
    const FILE_SYSTEM_STAT_USER_ID = 'uid';

    const SUCCESS = 200;
    const REACHED_EOF = 501;
    const TIMEOUT = 502;
    const NO_MATCH_FOUND = 503;

    /**
     * If should automatically stack all read buffers
     *
     * @var bool
     */
    protected $_autoStackReadBuffer = false;

    /**
     * Connection status of the stream
     *
     * @var     bool
     */
    protected $_isConnected = false;

    /**
     * If the steam is in blocking mode
     *
     * @var bool
     */
    protected $_blocking = true;

    /**
     * Stream strict mode checking
     *
     * @var bool
     */
    protected $_strictModeChecking = true;
    
    /**
     * Stream data transfer time out in seconds.
     * null uses the PHP configured default stream timeout value.
     * 
     * @var int
     */
    protected $_timeout = null;

    /**
     * The last data buffered in a read operation.
     * 
     * @var string
     */
    protected $_readBuffer = '';

    /**
     * Size of the PHP internal read buffer in bytes.
     *
     * @var int
     */
    protected $_readBufferSize = 0;

    /**
     * Array of all buffered data in read operations.
     * 
     * @var array
     */
    protected $_readBufferStack = array();

    /**
     * Size of the PHP internal write buffer in bytes.
     *
     * @var int
     */
    protected $_writeBufferSize = 0;

    /**
     * data stream resource
     *
     * @var resource
     */
    protected $_stream = null;

    /**
     * system internal identifier of the stream resource
     *
     * @var string
     */
    protected $_systemResourceID = '';

    /**
     * Constructor.
     * 
     * Creates a data stream objet over a stream resource, obtained from
     * functions like fopen, fsockopen, etc.
     * Accepts a stream resource, or an associative array of options, that
     * include a 'stream' => resource pair.
     *
     * @param   array|resource $options     associative array of options, or a stream resource
     * @throws  Parsonline_Exception_InvalidParameterException on none resource|array param
     */
    public function __construct($options)
    {
        if ( is_resource($options) ) {
            $this->setStream($options);
        } elseif ( is_array($options) && array_key_exists('stream', $options) && is_resource($options['stream'])) {
            $this->setOptionsFromArray($options);
        } else {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                "Parameter should be a stream, or an array of options containing a stream", 0, null,
                'resource|array', $options
            );
        }
    } // public function __construct()

    /**
     * Automatically called right before object is deleted.
     * closes from the data stream.
     */
    public function __destruct()
    {
        $this->close(true);
    }    

    /**
     * If should automatically save the last read buffer to the read buffer
     * stack.
     *
     * @return  bool
     */
    public function getAutoStackReadBuffer()
    {
        return $this->_autoStackReadBuffer;
    }

    /**
     * If should automatically save the last read buffer to the read buffer
     * stack.
     *
     * @param   bool    $set
     * @return  Parsonline_Stream
     */
    public function setAutoStackReadBuffer($set)
    {
       $this->_autoStackReadBuffer = true && $set;
       return $this;
    }

    /**
     * If the steam is in blocking mode or not
     *
     * @return bool
     */
    public function getBlocking()
    {
        return $this->_blocking;
    }

    /**
     * Sets the stream blocking mode.
     * Returns true of the stream is available and blocking is configured.
     *
     * @param   bool        $blocking       default is true
     * @return  bool
     */
    public function setBlocking($blocking=true)
    {
        $this->_blocking = true && $blocking;
        $set = false;
        if ($this->_stream && is_resource($this->_stream)) {
            $set = stream_set_blocking($this->_stream, intval($this->_blocking));
        }
        return $set;
    } // public function setBlocking()

    /**
     * Returns the size of PHP internal read buffer in bytes.
     *
     * @return int
     */
    public function getReadBufferSize()
    {
        return $this->_readBufferSize;
    }

    /**
     * Sets the size of PHP internal read buffer in bytes.
     * Returns true of the stream is available and buffer size is configured.
     *
     * NOTE: Only available on PHP 5.3.3 and higher
     *
     * @param   int         $bytes
     * @return  bool
     * @throws  Parsonline_Exception_InvalidParameterException on negative size
     */
    public function setReadBufferSize($bytes)
    {
        if (0 > $bytes) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                "Read buffer should not be negative", 0, null,
                'none-negative integer', $bytes
            );
        }
        $this->_readBufferSize = intval($bytes);
        $set = false;
        if ( is_resource($this->_stream) && function_exists('stream_set_read_buffer') ) {
            $set = stream_set_read_buffer($this->_stream, $this->_writeBufferSize);
        }
        return $set;
    } // public function setReadBufferSize()

    /**
     * Returns the data stream resource that is beeing used underhood
     *
     * @return  resource    reference to the data stream resource
     */
    public function & getStream()
    {
        return $this->_stream;
    }

    /**
     * Sets the data stream resource that is beeing used underhood.
     *
     * @param   resource    &$stream     reference to a resource
     * @return  Parsonline_Stream
     */
    public function setStream(&$stream)
    {
        if (!$stream || !is_resource($stream) ) {
            /**
             * @uses Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                "Invalid stream resource", 0, null, 'stream', $stream
            );
        }
        $this->_stream =& $this->_prepareStream($stream);
        return $this;
    } // public function setStream()

    /**
     * If the should strictly check for the I/O mode of the stream.
     *
     * @return bool
     */
    public function getStrictModeChecking()
    {
        return $this->_strictModeChecking;
    }

    /**
     * Sets the should strictly check for the I/O mode of the stream.
     * with the strict mode checking enabled, on streams openned for read operations,
     * write methods would throw exceptions and vice versa.
     *
     * @param   bool        $strict       default is true
     * @return  Parsonline_Stream
     */
    public function setStricktModeChecking($strict=true)
    {
        $this->_strictModeChecking = true && $strict;
        return $this;
    } // public function setStricktModeChecking()

    /**
     * Returnes the timeout of the stream data transfer in seconds.
     *
     * @return int
     */
    public function getTimeout()
    {
        if ($this->_timeout === null) {
            $configTimeout = ini_get('default_socket_timeout');
            if ( $configTimeout !== null && $configTimeout !== '' ) $this->_timeout = intval($configTimeout);
        }
        return $this->_timeout;
    } // public function getTimeout()

    /**
     * Sets the timeout of the stream in seconds.
     * Returns true of the stream is available and timeout is configured.
     * 
     * @param   int         $timeout
     * @return  bool
     * @throws  Parsonline_Exception_InvalidParameterException on negative timeout
     */
    public function setTimeout($timeout)
    {
        $timeout = intval($timeout);
        if ( 0 > $timeout) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                "Timeout should not be negative", 0, null,
                'none-negative integer', $timeout
            );
        }

        $this->_timeout = $timeout;
        $set = false;
        if ( is_resource($this->_stream) ) {
            $set = stream_set_timeout($this->_stream, $this->_timeout, 0);
        }
        return $set;
    } // public function setTimeout()

    /**
     * Returns the size of write buffer in bytes.
     *
     * @return int
     */
    public function getWriteBufferSize()
    {
        return $this->_writeBufferSize;
    }

    /**
     * Sets the size of write buffer in bytes.
     * Returns true of the stream is available and buffer size is configured.
     *
     * @param   int         $bytes
     * @return  bool
     * @throws  Parsonline_Exception_InvalidParameterException on negative size
     */
    public function setWriteBufferSize($bytes)
    {
        if (0 > $bytes) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                "Write buffer should not be negative", 0, null,
                'none-negative integer', $bytes
            );
        }
        $this->_writeBufferSize = intval($bytes);
        $set = false;
        if ( is_resource($this->_stream) ) {
            $set = stream_set_write_buffer($this->_stream, $this->_writeBufferSize);
        }
        return $set;
    } // public function setWriteBufferSize()

    /**
     * Sets data of the stream object from an array.
     * Makes sure the stream option is used the last, so other configurations
     * would affect it.
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
        $stream = false;
        foreach ($options as $key => $value) {
            // keep the stream index the last one
            if ( $key == 'stream') {
                $stream = true;
                continue;
            }
            $method = 'set' . ucfirst($key);
            if ( in_array($method, $methods) ) {
                $this->$method($value);
                array_push($result[0], $key);
            } else {
                array_push($result[1], $key);
            }
        }
        
        if ($stream) {
            $this->setStream($options['stream']);
            array_push($result[0], 'stream');
        }
        
        return $result;
    } // public function setOptionsFromArray()

    /**
     * Returns metadata about the stream. If a metadata key is specified
     * returns the value of that key, otherwise returns an array of data.
     * use class constants META_* as key values.
     *
     * If the requested metadata could not be retreived for the stream,
     * returns null.
     *
     * NOTE: If failed to read the metadata of the stream, throws a steam exception.
     * NOTE: for local file system interfaced streams the additional meta data set
     * 'META_FILE_SYSTEM_STATS' is available wich is an array of file system
     * metadata, whose keys are accessible via the FILE_SYSTEM_STAT_* constants.
     * for other streams, this metadata set is an empty array.
     *
     * @param   string|null     $key
     * @return  mixed
     * @throws  Parsonline_Exception_ContextException on no stream available
     *          Parsonline_Exception_StreamExeption with code DATA_NOT_AVAILABLE on failed to read metadata
     */
    public function getMetadata($key=null)
    {
        if (!$this->_stream) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "Failed to get stream metadata. no stream is available"
            );
        }
        $metadata = stream_get_meta_data($this->_stream);
        if (!$metadata) {
            /**
             * @uses    Parsonline_Exception_StreamException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_StreamException(
                "Failed to get stream metadata. metadata is not available",
                Parsonline_Exception_StreamException::DATA_NOT_AVAILABLE, null,
                $this->_stream
            );
        }

        if ( function_exists('stream_is_local') && stream_is_local($this->_stream) ) {
            $fstatData = fstat($this->_stream);
        } else {
            $fstatData = @fstat($this->_stream);
            if (!$fstatData) $fstatData = array();
        }
        
        $metadata[self::META_FILE_SYSTEM_STATS] = $fstatData;
        if ($key) {
            return (isset($metadata[$key]) ? $metadata[$key] : null);
        }
        return $metadata;
    } // public function getMetadata()

    /**
     * Returns the system resource ID associated to the stream.
     * The resource ID is a mixture of the process ID in the host operating
     * system and the resource internal unique identifier, separated by a colon.
     *
     * @return  string
     */
    public function getSystemResourceID()
    {
        if (!$this->_systemResourceID) {
            $this->_systemResourceID = intval(getmypid()) . ':' . intval($this->_stream);
        }
        return $this->_systemResourceID;
    }
    
    /**
     * Clears the read buffer and returns the number of bytes cleared.
     *
     * @return  int
     */
    public function clearReadBuffer()
    {
        $size = strlen($this->_readBuffer);
        $this->_readBuffer = '';
        return $size;
    }

    /**
     * Clears the saved read buffers in stack, and returns the number of cleared
     * buffers.
     *
     * @return  int
     */
    public function clearReadBufferStack()
    {
        $count = count($this->_readBufferStack);
        $this->_readBufferStack = array();
        return $count;
    } // public function clearReadBufferStack()

    /**
     * Returns the buffered data from the last read operation.
     *
     * @return  string
     */
    public function getReadBuffer()
    {
        return $this->_readBuffer;
    }

    /**
     * Returns an array of saved buffered data from all the read operations.
     *
     * @return  array
     */
    public function getReadBufferStack()
    {
        return $this->_readBufferStack;
    }

    /**
     * Shows if the stream is stablished and open.
     *
     * @return  bool
     */
    public function isConnected()
    {
        if ( !is_resource($this->_stream) ) {
            $this->_isConnected = false;
        } elseif ($this->isTimedOut()) {
            $this->_isConnected = false;
        } else {
            $this->_isConnected = true;
        }
        return $this->_isConnected;
    } // public function isConnected()

    /**
     * Returns true if the stream is a local data stream, false
     * if not.
     * 
     * @return  bool
     */
    public function isLocal()
    {
        return stream_is_local($this->_stream);
    }

    /**
     * Determines if the stream is openned only for reading.
     *
     * @return bool
     */
    public function isReadOnly()
    {
        $mode = strtolower($this->getMetadata(self::META_MODE));
        if ( $mode === 'r' ) {
            return true;
        }
        return false;
    } // public function isReadOnly()

    /**
     * Checks if the stream is timed out or not.
     * If the stream is not a valid stream, returns true (for the cases
     * where the timed out stream has closed the streamed).
     *
     * NOTE: If the status of the stream could not be queried, then it is NOT considered as timedout.
     *
     * @return  bool
     */
    public function isTimedOut()
    {
        $timedOut = false;
        if ( !is_resource($this->_stream) ) {
            $timedOut = true;
        } else {
            /**
             * @uses    Parsonline_Exception_StreamException
             */
            require_once('Parsonline/Exception/StreamException.php');
            try {
                $timedOut = $this->getMetadata(self::META_TIMEDOUT);
            } catch (Parsonline_Exception_StreamException $exp) {
                // failed to retreive the status of the stream
                $timedOut = false;
            }
        }
        return $timedOut;
    } // public function isTimedOut()

    /**
     * Determines if the stream is openned only for writing.
     *
     * @return bool
     */
    public function isWriteOnly()
    {
        $mode = strtolower($this->getMetadata(self::META_MODE));
        if ( strpos($mode, '+') !== false ) {
            return false;
        }
        if ( strpos($mode, 'r') !== false ) {
            return false;
        }
        return true;
    } // public function isWriteOnly()

    /**
     * Closes the data stream.
     *
     * @return  bool
     */
    public function close()
    {
        $closed = true;
        if ( is_resource($this->_stream) ) {
            fflush($this->_stream);
            $closed = fclose($this->_stream);
        }
        $this->_stream = null;
        return $closed;
    } // public function close()

    /**
     * Returns the current position of the pointer (cursor) in the stream
     *
     * @return int|null
     */
    public function currentCurserPosition()
    {
        $pos = null;
        if ( $this->isConnected() ) {
            $pos = ftell($this->_stream);
        }
        return $pos;
    } // public function currentCurserPosition()

    /**
     * Flushes data in the output buffers to the stream.
     *
     * @return  bool
     */
    public function flush()
    {
        $flushed = false;
        if ( is_resource($this->_stream) ) {
            $flushed = fflush($this->_stream);
        }
        return $flushed;
    } // public function flush()

    /**
     * Reads data from stream in fixed size packets,
     * until reached the specified number of maximum bytes or connection timesout.
     * After reading each packets, the observers are notified, thus
     * having a small packet size makes the stream I/O very verbose.
     *
     * Accepts an array of observer callable objects to be notified on each
     * packet read.
     * each observer could accept the following parameters with reasonable default
     * values:
     * 
     *      <li>string      $signature      name of the method</li>
     *      <li>int         $packetCounter  number of read packets yet</li>
     *      <li>string      $packet         data read into the buffer</li>
     *      <li>int         $totalBytes     total bytes read by now</li>
     *      <li>float       $elapsed        number of microseconds elapsed to read the last packet</li>
     *
     * @param   int     $maxLength      maximum number of bytes to read. use 0 to read all.
     * @param   array   $observers      array of callables to be notified on each packet read
     * @param   int     $packetMaxSize  number of bytes to read into the read packet buffer
     *
     * @return  string
     *
     * @throws  Parsonline_Exception_InvalidParameterException on none callable observers, or invalid length/sizes
     *          Parsonline_Exception_StreamException code CONNECTION on connection loss
     */
    public function read($maxLength=0, array $observers=array(), $packetMaxSize=1024)
    {
        $maxLength = intval($maxLength);
        if (0 > $maxLength ) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            throw new Parsonline_Exception_InvalidParameterException(
                "Maximum data length should be a none-negative integer", 0, null,
                'length >= 0', $maxLength
            );
        } elseif ($maxLength === 0) {
            /*
             * if no maximum length is specified and the stream size is determined
             * already, use the size as the default maximum length.
             */
            $streamFSStats = $this->getMetadata(self::META_FILE_SYSTEM_STATS);
            if ($streamFSStats && isset($streamFSStats[self::FILE_SYSTEM_STAT_SIZE]) ) {
                $maxLength = $streamFSStats[self::FILE_SYSTEM_STAT_SIZE];
            }
            unset($streamFSStats);
        }

        if ($observers && !$this->_validateCallables($observers)) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            throw new Parsonline_Exception_InvalidParameterException(
                "Observers should all be callable objects", 0, null,
                'array of callables', $observers
            );
        }
        /*
         * use a byte array as the buffer fot better performance than
         * the immutable string concatination
         */
        $packetMaxSize = intval($packetMaxSize);
        if ($packetMaxSize < 1) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            throw new Parsonline_Exception_InvalidParameterException(
                "Packet size should be a positive integer", 0, null,
                'size > 0', $packetMaxSize
            );
        }
        
        $buffer = array();
        $bufferSize = 0;

        $this->_preRead(__METHOD__);
        $timerStart = round(microtime(true), 6);
        $packetReadTimerEnd = $packetReadTimerStart = $timerStart;
        $packetCounter = 0;

        while (true) {
            $packet = fread($this->_stream, $packetMaxSize);
            $packetSize = strlen($packet);
            
            if ( !is_string($packet) ) { // read nothing
                break;
            }

            ++$packetCounter;
            $buffer[] = $packet;
            $bufferSize += $packetSize;
            
            $packetReadTimerEnd = round(microtime(true), 6);
            $packetReadDuration = $packetReadTimerEnd - $packetReadTimerStart;
            $packetReadTimerStart = $packetReadTimerEnd;

            // handle observers notification
            if ($observers) {
                $this->_notifyObservers(
                        $observers,
                        array(
                            __METHOD__,
                            $packetCounter,
                            $packet,
                            $bufferSize,
                            $packetReadDuration
                        )
                );
            } // if ($observers)
            
            unset($packet); // free some memory
            
            if ($packetSize < $packetMaxSize ) { // reading reached end of stream
                break;
            }
            
            if ($maxLength && $bufferSize >= $maxLength) { // reading reached maximum length
                break;
            }
        } // while()

        $end = round(microtime(true), 6);
        $duration = $end - $timerStart;

        $buffer = implode('', $buffer);
        if ($maxLength && $bufferSize > $maxLength) {
            $buffer = substr($buffer, 0, $maxLength);
        }
        
        $this->_postRead(__METHOD__, $buffer, $duration);
        return $buffer;
    } // public function read()

    /**
     * Reads all data from stream connection until stream times out.
     * If no data was read, returns false.
     *
     * NOTE: Reading is an atomic blocking process, no notification is done in between.
     * NOTE: Clears the previous read buffer.
     *
     * @see     read()
     *
     * @return  string|false
     * @throws  Parsonline_Exception_StreamException code CONNECTION on connection loss
     */
    public function readAll()
    {
        $this->_preRead(__METHOD__);
        $start = round(microtime(true), 6);
        $buff = stream_get_contents($this->_stream);
        $end = round(microtime(true), 6);
        $this->_postRead(__METHOD__, $buff, $end - $start);
        if ($buff === false || empty($buff)) {
            return false;
        }
        return $buff;
    } // public function readAll()
    
    /**
     * Reads from the stream until the buffer reached a number of bytes,
     * or reached end of stream, or connection timesout. If after the read
     * no data has been read, returns false.
     *
     * NOTE: Reading is an atomic blocking process, no notification is done in between.
     * NOTE: Clears the previous read buffer.
     *
     * @see     read()
     * 
     * @param   int     $bytes      number of bytes to read at maximum.
     * @return  string|false
     * @throws  Parsonline_Exception_InvalidParameterException on none-positive bytes
     *          Parsonline_Exception_StreamException code CONNECTION on connection loss
     */
    public function readBytes($bytes)
    {
        $bytes = intval($bytes);
        if ( 1 > $bytes ) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                'number of bytes should be positive integer', 0, null,
                'positive integer', $bytes
            );
        }
        $this->_preRead(__METHOD__);
        $start = round(microtime(true), 6);
        $buff = stream_get_contents($this->_stream, $bytes);
        $end = round(microtime(true), 6);
        $this->_postRead(__METHOD__, $buff, $end - $start);
        if ( empty($buff) ) return false;
        return $buff;
    } // public function readBytes()
    
    /**
     * Reads a single character from the stream. If connection is unavailable
     * throws exception, if failed to read the character, returns false.
     *
     * NOTE: Reading is an atomic blocking process, no notification is done in between.
     * NOTE: Clears the previous read buffer.
     *
     * @see     read()
     *
     * @return  string|false
     * @throws  Parsonline_Exception_StreamException code CONNECTION on connection loss
     */
    public function readChar()
    {
        $this->_preRead(__METHOD__);
        $start = round(microtime(true), 6);
        $buff = fgetc($this->_stream);
        $end = round(microtime(true), 6);
        $this->_postRead(__METHOD__, $buff, $end - $start);
        return $buff;
    } // public function readChar()
    
    /**
     * Reads the next line from the stream. Would return the line, or anything
     * read until reached end of stream, or connection timesout.
     * 
     * NOTE: Reading is an atomic blocking process, no notification is done in between.
     * NOTE: Clears the previous read buffer.
     *
     * @see     read()
     * 
     * @return  string|false
     * @throws  Parsonline_Exception_StreamException code CONNECTION on connection loss
     */
    public function readLine()
    {
        $this->_preRead(__METHOD__);
        $start = round(microtime(true), 6);
        $buff = fgets($this->_stream);
        $end = round(microtime(true), 6);
        $this->_postRead(__METHOD__, $buff, $end - $start);
        if ( $buff === false || empty($buff) ) return false;
        return $buff;
    } // public function readLine()

    /**
     * Reads data from stream until reached the specifed string, or the specified
     * number of maximum bytes are read, or connection timesout.
     *
     * Returns an array of the read data, and the appropriate status code to
     * specifing success, or if max bytes read yet no match found or the stream
     * had timed out.
     *
     * Accepts an array of observer callable objects to be notified on data read
     * sequences.
     * each observer could accept the following parameters with reasonable default
     * values:
     *      <li>string      $signature      name of the method</li>
     *      <li>int         $bufferCalls    counter of called buffer observers</li>
     *      <li>string      $data           data read into the buffer</li>
     *      <li>int         $totalBytes     total bytes read by now</li>
     *      <li>float       $elapsed        number of microseconds elapsed to read the buffer</li>
     * 
     * NOTE: The method DOES NOT strip the needle from the returned string.
     *
     * @see     readUntilRegex()
     *
     * @param   string  $needle
     * @param   int     $maxLength              maximum number of bytes to read. use 0 to disable
     * @param   bool    $throwException         throw an exception with code NO_MATCH_FOUND, if failed to find the needle
     * @param   bool    $observers              array of callables to be notified on data packet reads
     * @param   int     $bufferMaxSize          number of bytes to read before notifying observers
     *
     * @return  array   array(string, int)
     * 
     * @throws  Parsonline_Exception_InvalidParameterException on none callable observers, or invalid length/sizes
     *          Parsonline_Exception_StreamException code CONNECTION on connection loss
     *          Parsonline_Exception_StreamException code NO_MATCH_FOUND if failed to find a matching sting.
     */
    public function readUntil($needle, $maxLength=0, $throwException=false, array $observers=array(), $bufferMaxSize=1024)
    {
        $maxLength = intval($maxLength);
        if (0 > $maxLength ) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            throw new Parsonline_Exception_InvalidParameterException(
                "Maximum data length should be a none-negative integer", 0, null,
                'length >= 0', $maxLength
            );
        } elseif ($maxLength === 0) {
            /*
             * if no maximum length is specified and the stream size is determined
             * already, use the size as the default maximum length.
             */
            $streamFSStats = $this->getMetadata(self::META_FILE_SYSTEM_STATS);
            if ($streamFSStats && isset($streamFSStats[self::FILE_SYSTEM_STAT_SIZE]) ) {
                $maxLength = $streamFSStats[self::FILE_SYSTEM_STAT_SIZE];
            }
            unset($streamFSStats);
        }

        if ($observers && !$this->_validateCallables($observers)) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            throw new Parsonline_Exception_InvalidParameterException(
                "Observers should all be callable objects"
            );
        }
        
        $buffer = array();
        $bufferMaxSize = intval($bufferMaxSize);
        if ($bufferMaxSize < 1) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            throw new Parsonline_Exception_InvalidParameterException(
                "Buffer max size should be a positive integer", 0, null,
                'size > 0', $bufferMaxSize
            );
        }
        /**
         * number of times the observers were notified
         */
        $notifyCounter = 0;


        /*
         * use a byte array as the buffer fot better performance than
         * the immutable string concatination
         */
        $totalBuffer = array();
        $totalBufferSize = 0;
        $needleLength = strlen($needle);
        $needleLastChar = $needle[$needleLength - 1];
        
        $found = false;
        $this->_preRead(__METHOD__);
        $totalTimerStart = round(microtime(true), 6);
        $bufferTimerEnd = $bufferTimerStart = $totalTimerStart;
        
        while (true) {
            $char = $this->_readChar();
            if ( !is_string($char) && feof($this->_stream) ) { // end of the stream, or timeout
                break;
            }

            $totalBuffer[] = $char;
            ++$totalBufferSize;
            $buffer[] = $char;
            $bufferSize = count($buffer);

            // handle observers notification
            if ( $bufferSize >= $bufferMaxSize ) {
                $bufferTimerEnd = round(microtime(true), 6);
                $bufferReadDuration = $bufferTimerEnd - $bufferTimerStart;
                $bufferTimerStart = $bufferTimerEnd;
                $this->_notifyObservers(
                        $observers,
                        array(
                            __METHOD__,
                            ++$notifyCounter,
                            implode('', $buffer),
                            $totalBufferSize,
                            $bufferReadDuration
                        )
                );
                $buffer = array();
            }
            
            /**
             * if current char is not the last char of needle, and the buffer size
             * is less than the needle site, then no match is found for sure.
             * adding these conditions reduces the number of strpos() calls
             * that will fail for sure.
             */
            if ( $totalBufferSize >= $needleLength && $char == $needleLastChar ) {
                if ( substr(implode('', $totalBuffer), -1 * $needleLength) == $needle ) {
                    $found = true;
                    break;
                }
            }

            if ($maxLength && $totalBufferSize >= $maxLength) {
                break;
            }            
        } // while()

        $end = round(microtime(true), 6);
        $duration = $end - $totalTimerStart;

        if ($observers && $buffer) { // got out of the loop before notifying the observers for the last time
            $this->_notifyObservers(
                    $observers,
                    array(
                        __METHOD__,
                        ++$notifyCounter,
                        implode('', $buffer),
                        $totalBufferSize,
                        $end - $bufferTimerStart
                    )
            );
        }
        unset($observers, $bufferMaxSize, $buffer, $bufferTimerStart, $bufferTimerEnd, $notifyCounter);
        
        
        $totalBuffer = implode('', $totalBuffer);
        $this->_postRead(__METHOD__, $totalBuffer, $duration);

        if ($throwException && !$found) {
            /**
             * @uses    Parsonline_Exception
             */
            require_once('Parsonline/Exception/StreamException.php');
            throw new Parsonline_Exception_StreamException(
                sprintf("Failed to find a match for '%s'. Read '%d' bytes after '%f' microseconds from stream.", $needle, $totalBufferSize, $duration),
                Parsonline_Exception_StreamException::NO_MATCH_FOUND, null, $this->_stream
            );
        }

        if ($found) {
            $response = self::SUCCESS;
        } elseif ( empty($totalBuffer) ) {
            $response = self::TIMEOUT;
        } else {
            $response = self::NO_MATCH_FOUND;
        }
        return array($totalBuffer, $response);
    } // public function readUntil()

    /**
     * Reads data from stream until a match for a regex is found in the response data,
     * or maximum number of bytes read, or connection timesout.
     *
     * Returns an array of the read data, and the appropriate status code to
     * specifing success, or if max bytes read yet no match found or the stream
     * had timed out.
     *
     * Accepts an array of observer callable objects to be notified on data read
     * sequences.
     * each observer could accept the following parameters with reasonable default
     * values:
     *      <li>string      $signature      name of the method</li>
     *      <li>int         $notifyCounter  number of times observers are notified</li>
     *      <li>string      $data           data read into the buffer</li>
     *      <li>int         $totalBytes     total bytes read by now</li>
     *      <li>float       $elapsed        number of microseconds elapsed to read the buffer</li>
     *
     * NOTE: this method is much slower than readUntil(). if you do not need fancy regex matching, use
     * readUntil() instead for better performance.
     *
     * @see     readUntil()
     *
     * @param   string  $pattern            Perl compatible regular expression pattern
     * @param   int     $maxLength              maximum number of bytes to read. use 0 to disable
     * @param   bool    $throwException         throw an exception with code NO_MATCH_FOUND, if failed to find the needle
     * @param   bool    $observers              array of callables to be notified on data packet reads
     * @param   int     $bufferMaxSize    number of bytes to read before notifying observers
     *
     * @return  array   array(string, int)
     *
     * @throws  Parsonline_Exception_InvalidParameterException on none callable observers, or invalid size/length values
     *          Parsonline_Exception_StreamException code CONNECTION on connection loss
     *          Parsonline_Exception_StreamException with code NO_MATCH_FOUND if failed to find a matching sting.
     */
    public function readUntilRegex($pattern, $maxLength=0, $throwException=false, array $observers=array(), $bufferMaxSize=100)
    {
        $maxLength = intval($maxLength);
        if (0 > $maxLength ) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            throw new Parsonline_Exception_InvalidParameterException(
                "Maximum data length should be a none-negative integer", 0, null,
                'length >= 0', $maxLength
            );
        } elseif ($maxLength === 0) {
            /*
             * if no maximum length is specified and the stream size is determined
             * already, use the size as the default maximum length.
             */
            $streamFSStats = $this->getMetadata(self::META_FILE_SYSTEM_STATS);
            if ($streamFSStats && isset($streamFSStats[self::FILE_SYSTEM_STAT_SIZE]) ) {
                $maxLength = $streamFSStats[self::FILE_SYSTEM_STAT_SIZE];
            }
            unset($streamFSStats);
        }

        if ($observers && !$this->_validateCallables($observers)) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            throw new Parsonline_Exception_InvalidParameterException(
                "Observers should all be callable objects"
            );
        }

        $totalBuffer = '';
        $totalBufferSize = 0;
        $notifyCounter = 0;

        $buffer = array();
        $bufferSize = 0;
        $bufferMaxSize = intval($bufferMaxSize);
        if ($bufferMaxSize < 1) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            throw new Parsonline_Exception_InvalidParameterException(
                "Buffer max size should be a positive integer", 0, null,
                'positive integer', $bufferMaxSize
            );
        }

        $found = false;

        $this->_preRead(__METHOD__);
        $timerStart = round(microtime(true), 6);
        $bufferTimerEnd = $bufferTimerStart = $timerStart;
        
        while (true) {
            $char = $this->_getChar();
            if ( !is_string($char) && feof($this->_stream)) { // end of the stream, or timeout
                break;
            }
            
            $totalBuffer .= $char;
            ++$totalBufferSize;

            // handle observers notification
            $buffer[] = $char;
            ++$bufferSize;
            
            if ( $bufferSize > $bufferMaxSize ) {
                $bufferTimerEnd = round(microtime(true), 6);
                $bufferReadDuration = $bufferTimerEnd - $bufferTimerStart;
                $bufferTimerStart = $bufferTimerEnd;

                $this->_notifyObservers(
                        $observers,
                        array(
                            __METHOD__,
                            ++$notifyCounter,
                            implode('', $buffer),
                            $totalBufferSize,
                            $bufferReadDuration
                        )
                );
                $buffer = array();
            }

            $found = preg_match($pattern, $totalBuffer);
            if ($found) break;

            if ($maxLength && $totalBufferSize >= $maxLength) {
                break;
            }
        } // while()

        $end = round(microtime(true), 6);
        $duration = $end - $timerStart;

        if ($observers && $buffer) { // got out of the loop before notifying the observers for the last time
            $this->_notifyObservers(
                    $observers,
                    array(
                        __METHOD__,
                        ++$notifyCounter,
                        implode('', $buffer),
                        $totalBufferSize,
                        $end - $bufferTimerEnd
                    )
            );
        }
        unset($observers, $bufferMaxSize, $buffer, $bufferTimerEnd, $bufferTimerEnd, $notifyCounter);

        if ($throwException && !$found) {
            /**
             * @uses    Parsonline_Exception_StreamException
             */
            require_once('Parsonline/Exception/StreamException.php');
            throw new Parsonline_Exception_StreamException(
                sprintf("No match found for '%s'. Read '%d' bytes after '%f' from stream", $pattern, $totalBufferSize, $duration),
                Parsonline_Exception_StreamException::NO_MATCH_FOUND, null, $this->_stream
            );
        }

        $this->_postRead(__METHOD__, $totalBuffer, $duration);

        if ($found) {
            $response = self::SUCCESS;
        } elseif ( empty($totalBuffer) ) {
            $response = self::TIMEOUT;
        } else {
            $response = self::NO_MATCH_FOUND;
        }
        return array($totalBuffer, $response);
    } // public function readUntilRegex()

    /**
     * Sets the read/write cursor to the specified offset.
     *
     * NOTE: Applies only on seekable stream. If the steam is not seekable
     * a stream exception is thrown.
     * 
     * @param   int     $offset
     * @param   int     $whence     seek related to, use SEEK_* constants
     * @return  bool
     */
    public function seekCursor($offset, $whence=SEEK_SET)
    {
        if ( !$this->getMetadata(self::META_SEEKABLE) ) {
            /**
             * @uses    Parsonline_Exception_StreamException
             */
            require_once('Parsonline/Exception/StreamException.php');
            throw new Parsonline_Exception_StreamException(
                "Failed to seek the cursor on the stream. Stream is not seekable",
                Parsonline_Exception_StreamException::NOT_SUPPORTED, null, $this->_stream
            );
        }
        $seeked = false;
        if ($this->isConnected()) {
            /*
             * our results are inversed from the way fseek returnes its success
             * state
             */
            $seeked = true && (fseek($this->_stream, $offset, $whence) + 1);
        }
        return $seeked;
    } // public function seekCursor()

    /**
     * Writes a string buffer to the stream.
     *
     * @param   string          $buffer
     * @return  int             number of written bytes to the stream
     * @throws  Parsonline_Exception_StreamException code CONNECTION on connection loss
     *          Parsonline_Exception_StreamException code WRITE_FAILED on failed to write
     */
    public function write($buffer)
    {
        $buffer = strval($buffer);
        $this->_preWrite(__METHOD__, $buffer);
        $start = round(microtime(true), 6);
        $bytes = fwrite($this->_stream, $buffer);
        $end = round(microtime(true), 6);
        $duration = $end - $start;
        
        if ($bytes === false) {
            $this->_postWrite(__METHOD__, $buffer, 0, $duration);
            /**
             * @uses    Parsonline_Exception_StreamException
             */
            require_once('Parsonline/Exception/StreamException.php');
            throw new Parsonline_Exception_StreamException(
                "Failed to write to stream.",
                Parsonline_Exception_StreamException::WRITE_FAILED, null, $this->_stream
            );
        }
        
        $this->_postWrite(__METHOD__, $buffer, $bytes, $duration);
        return $bytes;
    } // public function write()

    /**
     * Notifies all specified observer methods with specified paramters.
     *
     * @param   array   $observers      array of callables
     * @param   array   $params         array of parameters
     */
    protected function _notifyObservers($observers, $params)
    {
        foreach($observers as $ob) {
            call_user_func_array($ob, $params);
        }
    } // protected function _notifyObservers()

    /**
     * Prepairs a stream resource by configuring it based on the
     * configuration of the stream object.
     *
     * @param   resource    &$stream    reference to the stream
     * @return  resource
     */
    protected function & _prepareStream(&$stream)
    {
        stream_set_timeout($stream, $this->getTimeout());
        stream_set_blocking($stream, $this->getBlocking());
        stream_set_write_buffer($stream, $this->getWriteBufferSize());
        if ( function_exists('stream_set_write_buffer') ) stream_set_write_buffer($stream, $this->getReadBufferSize() );
        return $stream;
    } // protected function & _prepareStream()

    /**
     * Automatically is called before all read operations.
     *
     * Checks for availablity of the stream, if the stream is readable, and
     * Clears the read buffer.
     *
     * @param   string      $signature     the name of the calling read method
     * @throws  Parsonline_Exception_StreamException code CONNECTION on connection loss
     *          Parsonline_Exception_ContextException on attempt to read from write only streams
     */
    protected function _preRead($signature)
    {
        if ( !$this->isConnected() ) {
            /**
             * @uses    Parsonline_Exception_StreamException
             */
            require_once('Parsonline/Exception/StreamException.php');
            throw new Parsonline_Exception_StreamException(
                'Failed to read from stream. Stream connection is lost',
                Parsonline_Exception_StreamException::CONNECTION, null,
                $this->_stream
            );
        }
        
        if ( $this->_strictModeChecking && $this->isWriteOnly() ) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                'Failed to read from stream. Stream is write only'
            );
        }
        
        $this->clearReadBuffer();
    } // protected function _preReadOps()

    /**
     * Automatically is called after all read operations.
     *
     * If autosave read buffer is turned on, saves the read buffer;
     *
     * @param   string          $signature  the name of the read method
     * @param   string|false    $data       the data read from stream
     * @param   float           $duration   number of microseconds used to read
     */
    protected function _postRead($signature, &$data, $duration)
    {
        $this->_readBuffer = strval($data);
        if ($this->_autoStackReadBuffer && $this->_readBuffer) {
            array_push($this->_readBufferStack, $this->_readBuffer);
        }
    } // protected function _postRead()

    /**
     * Automatically is called before all write operations.
     *
     * Checks for availablity of the stream;
     *
     * NOTE: Empties the read buffer.
     *
     * @param   string      $signature  name of the write method
     * @param   string      $data       data to be written
     * @throws  Parsonline_Exception_StreamException code CONNECTION on connection loss
     *          Parsonline_Exception_ContextException on attempt to write to read only streams
     */
    protected function _preWrite($signature, &$data)
    {
        if ( !$this->isConnected() ) {
            /**
             * @uses    Parsonline_Exception_StreamException
             */
            require_once('Parsonline/Exception/StreamException.php');
            throw new Parsonline_Exception_StreamException(
                'Failed to read from stream. Connection is lost',
                Parsonline_Exception_StreamException::CONNECTION, null,
                $this->_stream
            );
        }

        if ( $this->_strictModeChecking && $this->isReadOnly() ) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                'Failed to write to stream. Stream is read only'
            );
        }

        $this->clearReadBuffer();
    } // protected function _preWriteOps()

    /**
     * Automatically is called after all write operations.
     *
     * @param   string      $signature  name of the write method
     * @param   string      $data       data to be written
     * @param   int         $bytes      number of bytes written to stream
     * @param   float       $duration   microseconds that took to write
     */
    protected function _postWrite($signature, &$data, $bytes, $duration)
    {
    }

    /**
     * Reads a single character from the stream. If connection is unavailable
     * returns false.
     *
     * NOTE: Does not call the pre/post read operations. So does not check for
     * connection stablishment or modify the read buffer.
     * This is to reduce a lot of connection checking.
     *
     * NOTE: Just for internal use.
     *
     * @return  string|false
     * @access  protected
     */
    protected function _readChar()
    {
        if ( $this->_stream ) {
            return fgetc($this->_stream);
        }
        return false;
    } // public function _readChar()

    /**
     * Validates an array of callables.
     *
     * @param   array   $callables      array of callables
     * @return  bool
     */
    protected function _validateCallables(array $callables)
    {
        foreach($callables as $func) {
            if (!$func || !is_callable($func, false) ) {
                return false;
            }
        }
        return true;
    } // protected function _validateCallables()
}