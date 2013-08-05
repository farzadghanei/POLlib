<?php
//Parsonline/StreamWrapper/Curl.php
/**
 * Defines Parsonline_StreamWrapper_Curl class.
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
 * @package     StreamWrapper
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.0.4 2011-11-23
 */

/**
 * @uses Parsonline_StreamWrapper_Abstract
 * @uses Parsonline_StreamWrapper_StreamWrapperInterface
 */
require_once('Parsonline/StreamWrapper/StreamWrapperInterface.php');
require_once('Parsonline/StreamWrapper/Abstract.php');

/**
 * Parsonline_StreamWrapper_Curl
 *
 * a stream wrapper class that uses curl library underneath to handle data
 * transfer.
 * 
 * @see http://www.php.net/manual/en/book.curl.php
 */
class Parsonline_StreamWrapper_Curl extends Parsonline_StreamWrapper_Abstract implements Parsonline_StreamWrapper_StreamWrapperInterface
{
    /**
     * array with keys as cUrL options, values for their values
     * @var array
     */
    protected static $_defaultCurlOptions = array();

    /**
     * sets default cURL options for the class.
     * this should help to confiugre cURL before registering this wrapper
     * for streams. because this class is not initialized by user, there is no
     * other way to set options for cURL. use this method to set cURL options
     * before registering it as the stream wrapper.
     *
     * @param   int     $option     cURL option code, use CURLOPT_* constansts
     * @param   mixed   $value      value of the option
     */
    public static function addDefaultCurlOption($option, $value)
    {
        self::$_defaultCurlOptions[$option] = $value;
    }

    /**
     * returns an array of added default cURL options
     * 
     * @return  array
     */
    public static function getDefaultCurlOptions()
    {
        return self::$_defaultCurlOptions;
    }

    /**
     * removes a previously added cURL option and returns the removed value.
     * if the option had not been set null will be returned.
     *
     * @param   int     $option     cURL option code, use CURLOPT_* constansts
     * @return  mixed   the removed option value or null if the option was not set
     */
    public static function removeDefaultCurlOption($option)
    {
        if ( !array_key_exists($option, self::$_defaultCurlOptions) ) return null;
        $value = self::$_defaultCurlOptions[$option];
        self::$_defaultCurlOptions[$option] = null;
        unset(self::$_defaultCurlOptions[$option]);
        return $value;
    }

    /**
     * removes all added cURL options and returns them as an array.
     * 
     * @return  array
     */
    public static function clearDefaultCurlOptions()
    {
        $options = self::$_defaultCurlOptions;
        self::$_defaultCurlOptions = array();
        return $options;
    }

    /**
     *
     * @var resource
     */
    protected $_curlHandle = null;

    /**
     *
     * @var string
     */
    protected $_path = null;

    /**
     * @var string
     */
    protected $_mode = null;

    /**
     * OR attached options integer values
     * @var int
     */
    protected $_options = 0;

    /**
     * an array of cURL options
     * @var array
     */
    protected $_curlOptions = array();

    /**
     * pointer to a string value
     * 
     * @var string
     */
    protected $_openedPath = null;

    /**
     *
     * @var string
     */
    protected $_buffer = null;

    /**
     *
     * @var int
     */
    protected $_position = 0;

    /**
     * last time the buffer was accessed
     * @var int
     */
    protected $_bufferAccessTime = 0;
    
    /**
     * last time the buffer was modified
     * @var int
     */
    protected $_bufferModifyTime = 0;

    /**
     * if should report errors
     * @var bool
     */
    protected $_reportErrors = false;

    /**
     * Constructor.
     * 
     * Checks to make sure of curl extension is loaded.
     * @throws  Parsonline_Exception_SystemException
     */
    public function  __construct()
    {
        if ( !function_exists('curl_init') || !function_exists('curl_exec') ) {
            /**
             *@uses Parsonline_Exception_SystemException 
             */
            require_once("Parsonline/Exception/SystemException.php");
            throw new Parsonline_Exception_SystemException(
                "curl extension is not loaded. please refer to 'http://www.php.net/manual/en/book.curl.php' for more information"
            );
        }
    }

    /**
     * initializes the stream buffer by using cURL methods to fetch
     * data from the specified path.
     * reports errors during the process, but this may be turned off.
     *
     * @param   string  $path           the target path
     * @param   bool    $reportErrors   if should report errors
     * @return  bool
     */
    protected function initBuffer($path, $reportErrors=null)
    {
        $reportErrors = is_null($reportErrors) ? $this->_reportErrors : !!$reportErrors;
        $pathArray = parse_url($path);
        if (!$pathArray || !$pathArray['host']) {
            if ($reportErrors) trigger_error('invalid path specified', E_USER_WARNING);
            return false;
        }
        $this->_curlHandle = curl_init($path);
        if ( !curl_setopt($this->_curlHandle, CURLOPT_RETURNTRANSFER, true) ) {
            if ($reportErrors) trigger_error("failed to set option 'CURLOPT_RETURNTRANSFER' on cURL handle", E_USER_WARNING);
            return false;
        }
        
        curl_setopt_array($this->_curlHandle, self::$_defaultCurlOptions);        
        if ($this->_curlOptions) curl_setopt_array($this->_curlHandle, $this->_curlOptions);
        $buffer = curl_exec($this->_curlHandle);
        if ($buffer === false) {
            if ($reportErrors) trigger_error( sprintf("curl_exec failed with code %d. message: %s", curl_errno($this->_curlHandle), curl_error($this->_curlHandle)), E_USER_ERROR);
            return false;
        }
        $info = curl_getinfo($this->_curlHandle);
        $this->_buffer = $buffer;
        $this->_bufferModifyTime = time();
        $this->_position = 0;
        return true;
    }

    /**
     * parses the OR attached integer value of options and returns an array of
     * options.
     * 
     * @param   int     $options    an OR attached integer value of options
     * @return  array
     */
    protected function _parseOptions($options)
    {
        return array($options);
    }

    /**
     * open the stream, called by fopen() like functions.
     * path should be avalid URL with at least the host value.
     * options is an OR attached list of integer values, can be 'STREAM_REPORT_ERRORS' to report errors,
     * any of CURL_OPT_* values
     *
     *
     *
     * @param   string  $path
     * @param   string  $mode
     * @param   int     $options
     * @param   string  &$openedPath
     * @return  bool
     */
    public function stream_open($path, $mode, $options, &$openedPath)
    {
        if ($options) {
            $this->_options = $options;
            $optionsArray = $this->_parseOptions($options);
            if ( in_array(self::STREAM_REPORT_ERRORS, $optionsArray) ) $this->_reportErrors = true;
        }

        $pathArray = parse_url($path);
        if (!$pathArray || !$pathArray['host']) {
            if ($this->_reportErrors) trigger_error("invalid path. the path should be a valid URL", E_USER_WARNING);
            return false;
        }

        if ( strpos($mode, 'w') !== false || strpos($mode, 'a') !== false ) {
            if ( $this->_reportErrors ) trigger_error("invalid mode. the cURL stream wrapper does not support writing of streams", E_USER_WARNING);
            return false;
        }
        
        $this->_path = $path;
        $this->_mode = $mode;
        
        try {
            $this->initBuffer($path);
            $openedPath = $this->_openedPath = $path;
        } catch (Exception $exp) {
            return false;
        }
        return true;
    } // public function stream_open()

    /**
     * close the stream, frees the resources. called by fclose()
     */
    public function stream_close()
    {
        curl_close($this->_curlHandle);
        $this->_curlHandle = null;
    }

    /**
     * Read the stream. called by fread()
     *
     * @param   int     $count      number of bytes to read
     * @return  string|false    string from contents, or false on error. if buffer is empty, returns empty string.
     */
    public function stream_read($count=null)
    {
        if( strlen($this->_buffer) === 0) return '';
        $read = substr($this->_buffer, $this->_position, $count);
        if ($read === false && $this->_reportErrors ) {
            trigger_error('could not read from the buffer. buffer dump: ' . var_export($this->_buffer, true), E_USER_ERROR);
            return false;
        }
        $this->_bufferAccessTime = time();
        $this->_position += strlen($read);
        return $read;
    }
    
    /**
     * write contents to the stream.
     * NOTE: NOT SUPPORTED
     * 
     * @param   string  content
     * @return  int
     */
    public function stream_write($data)
    {
        if ($this->_reportErrors) trigger_error('cURL stream does not support writing of data', E_USER_WARNING);
        return 0;
    }

    /**
     * if the stream has reached its end
     * 
     * @return  bool
     */
    public function stream_eof()
    {
        return ( $this->_position > strlen($this->_buffer) );
    }

    /**
     * returns current position in the stream
     *
     * @return  int the position of the current read pointer
     */
    public function stream_tell()
    {
        return $this->_position;
    }

    /**
     * flush stream data, releasing the memory occupied by the stream.
     * @return  bool
     */
    public function stream_flush()
    {
        $this->_buffer = null;
        $this->_position = 0;
        $this->_bufferModifyTime = time();
        return true;
    }

    /**
     * called by stream_set_* functions.
     *
     * option can be one of:
     * STREAM_OPTION_BLOCKING (The method was called in response to stream_set_blocking())
     * STREAM_OPTION_READ_TIMEOUT (The method was called in response to stream_set_timeout())
     * STREAM_OPTION_WRITE_BUFFER
     *
     * arg1: If option  is
     * STREAM_OPTION_BLOCKING: requested blocking mode (1 meaning block 0 not blocking).
     * STREAM_OPTION_READ_TIMEOUT: the timeout in seconds.
     * STREAM_OPTION_WRITE_BUFFER: buffer mode (STREAM_BUFFER_NONE or STREAM_BUFFER_FULL).
     *
     * arg2:  If option  is
     * STREAM_OPTION_BLOCKING: This option is not set.
     * STREAM_OPTION_READ_TIMEOUT: the timeout in microseconds.
     * STREAM_OPTION_WRITE_BUFFER: the requested buffer size.
     *
     * @param   int $option
     * @param   int $arg1
     * @param   int $arg2
     * @return  bool
     */
    public function stream_set_option($option, $arg1, $arg2)
    {
        switch($option) {
            case self::STREAM_OPTION_READ_TIMEOUT:
                        $this->_curlOptions[ CURLOPT_TIMEOUT ] = $arg1;
                        break;
            default:
                if ($this->_reportErrors) trigger_error('the cURL stream does not support this option', E_USER_WARNING);
                return false;
        }
        return true;
    }
    
    /**
     * gives stats about the stream. called by fstat().
     * not supported stat values would have null value.
     *
     * @return  array   associative and indexed array
     */
    public function stream_stat()
    {
        $this->initBuffer($this->_path);
        $stat = array();
        $stat[0] = $stat['dev'] = is_resource($this->_curlHandle) ? intval($this->_curlHandle) : 0;
        $stat[1] = $stat['ino'] = null;
        $stat[2] = $stat['mode'] = $this->_mode;
        $stat[3] = $stat['nlink'] = 1;
        $stat[4] = $stat['uid'] = getmyuid();
        $stat[5] = $stat['gid'] = getmygid();
        $stat[6] = $stat['rdev'] = null;
        $stat[7] = $stat['size'] = strlen($this->_buffer);
        $stat[8] = $stat['atime'] = $this->_bufferAccessTime;
        $stat[9] = $stat['mtime'] = $this->_bufferModifyTime;
        $stat[10] = $stat['ctime'] = $this->_bufferModifyTime; // the cURL buffer is modified/created at the same operations because it is immutable
        $stat[11] = $stat['blksize'] = null; // not supported for this data
        $stat[12] = $stat['blocks'] = null; // not supported for this data
        return $stat;
    }

    /**
     * stat the url. buffers the Path and returns the stat
     * data.
     *
     * @return  array   associative and indexed array
     */
    public function url_stat($path, $flags)
    {
        $reportStatErrors = true;
        if ($flags && $flags = self::STREAM_URL_STAT_QUIET) $reportStatErrors = false;
        $this->initBuffer($path, ($this->_reportErrors && $reportStatErrors) );
        return $this->stream_stat();
    }
}