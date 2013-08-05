<?php
//Parsonline/URLFetcher.php
/**
 * Defines the Parsonline_URLFetcher.
 *
 * Parsonline
 * 
 * Copyright (c) 2012 ParsOnline, Inc. (www.parsonline.com)
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
 * @copyright  Copyright (c) 2012 ParsOnline, Inc. (www.parsonline.com)
 * @license    Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Html
 * @version     0.1.1 2012-07-22
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 */

/**
 *@uses Parsonline_Parser_Html 
 */
require_once("Parsonline/Parser/Html.php");

/**
 * Parsonline_URLFetcher
 * 
 * Provides functionality to fetch an array of URL references.
 */
class Parsonline_URLFetcher
{
    const MODE_SEQUENCIAL = 'sequencial';
    const MODE_PARALLEL = 'parallel';
    
    const OPT_NETWORK_INTERFACE = 'netowrk_interface';
    const OPT_USERAGENT = 'user_agent';
    const OPT_FOLLOWLOCATION = 'follow_location';
    const OPT_MAXREDIRS = 'max_redirects';
    const OPT_HTTP_VERSION = 'http_version';
    const OPT_PROXY = 'proxy';
    
    /**
     * Array of URLs to fetch
     * 
     * @var array
     */
    protected $_urls = array();
    
    /**
     * The fetching mode of the fetcher
     * 
     * @var string
     */
    protected $_mode = null;
    
    /**
     * If should use cURL library underneath.
     * 
     * @var bool
     */
    protected $_useCURL = false;
    
    /**
     * array of options to specify details of the fetching process
     * 
     * @var array
     */
    protected $_options = array();
    
    /**
     * Array of options to be used in CURL only mode.
     * 
     * @var array
     */
    protected $_cURLOptions = array();
    
    /**
     * Array of callable references to be notified on each log emition
     *
     * @var array
     */
    protected $_loggers = array();
    
    /**
     * Array of observers for the progress of fetching data
     * 
     * @var array
     */
    protected $_progressObservers = array();
    
    /**
     * Returns the default fetching mode used by all fetchers.
     * 
     * @return string
     */
    public static function getDefaultMode()
    {
        return self::MODE_SEQUENCIAL;
    }
        
    /**
     * Constructor.
     * Creates a fetcher to download all the specified URLs.
     * Accepts a strin for URL, or an array of URLs.
     * 
     * @param   string|array    $url    [optional]
     */
    public function __construct($url=null)
    {
        if ($url) {
            $this->setURLs($url);
        }
        $this->_mode = self::getDefaultMode();
    }
    
    /**
     * Returns the URLs of that are to be downloaded
     * 
     * @return array
     */
    public function getURLs()
    {
        return $this->_urls;
    }
    
    /**
     * Sets the URL of the web page to fetch.
     * 
     * @param   string  $url 
     * @return  Parsonline_URLFetcher
     */
    public function setURLs($urls)
    {
        if ( !is_array($urls) ) $urls = array(strval($urls));
        $this->_urls = $urls;
        return $this;
    }
    
    /**
     * Determines if cURL library is going to be used to fetch the files or
     * not.
     * 
     * cURL is a very powerful solution and is necessary while using the
     * fetcher in parallel mode.
     * 
     * @return bool
     */
    public function wouldUseCURL()
    {
        return $this->_useCURL;
    }
    
    /**
     * Specify to use the cURL library to fetch files.
     * 
     * Curl is mandatory for parallel fetching mode, but to reduce dependancy
     * on the cURL library, the sequencial fetching mode could omit cURL
     * functionality and use PHP internal streams.
     * 
     * @param   bool    $use
     * @return  Parsonline_URLFetcher
     * @throws  RuntimeException if set to use Curl but its not installed
     * @throws  Exception if set to not use cURL, but the fetching mode is set to parallel
     */
    public function shouldUseCURL($use)
    {
        $use = true && $use;
        if ($use && !$this->isCURLAvailable()) {
            throw new RuntimeException("cURL is not installed");
        }
        if (!$use && $this->_mode == self::MODE_PARALLEL) {
            throw new Exception("cURL is essential in parallel fetching mode.");
        }
        $this->_useCURL = $use;
        return $this;
    }
    
    /**
     * Determinds if the cURL library is available on the platform or not.
     * 
     * @return bool
     */
    public function isCURLAvailable()
    {
        return (
                    function_exists('curl_init') &&
                    function_exists('curl_exec') &&
                    defined('CURLE_OK')
                );
    }
    
    /**
     * Returns the fetching mode of the fetcher.
     * It would be one of the MODE_* class constants.
     * 
     * @return string
     */
    public function getMode()
    {
        return $this->_mode;
    }
    
    /**
     * Sets the mode of fetching process.
     * use MODE_* class constants.
     * 
     * If using the parallel fetching mode, automatically the cURL library
     * is going to be used.
     * 
     * @param   string  $mode
     * @return  Parsonline_URLFetcher
     * @throws  Parsonline_Exception_ValueException on invalid/not supported mode value
     */
    public function setMode($mode)
    {
        if (!in_array($mode, $this->getSupportedModes())) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/ExceptionValueException.php');
            throw new Parsonline_Exception_ValueException("fetching mode '{$mode}' is not supported");
        }
        
        $this->_mode = $mode;
        if ($mode == self::MODE_PARALLEL) {
            $this->shouldUseCURL(true);
        }
        return $this;
    }
    
    /**
     * Returns an array of fetching mode names (accoding to MODE_* class
     * constants) that are supported on the platform.
     * 
     * @return  array
     */
    public function getSupportedModes()
    {
        $supported = array(self::MODE_SEQUENCIAL);
        if ( $this->isCURLAvailable() && function_exists('curl_multi_init') && function_exists('curl_multi_exec')) {
            $supported[] = self::MODE_PARALLEL;
        }
        return $supported;
    }
    
    /**
     * Returns the value of the specified option on the object.
     * If the option is not set yet, returns null.
     * 
     * @see     setOption()
     * 
     * @return  mixed
     */
    public function getOption($key)
    {
        if (!isset($this->_options[$key])) {
            return null;
        }
        return $this->_options[$key];
    }
    
    /**
     * Sets an specific option for the URL requests.
     * Use OPT_* class constants for convinence.
     * 
     * These options are passed to the underlying method of fetching files. so
     * regardless of using cURL or not, these options would be applied to the
     * fetching process.
     * 
     * Since cURL provides more detailed options, you could use setCURLOption()
     * method if you are forcing to use cURL.
     * 
     * @see setCurlOption()
     * 
     * @param   string  $key        the option key
     * @param   mixed   $value      the value of the option
     * @return  Parsonline_URLFetcher
     */
    public function setOption($key, $value)
    {
        $this->_options[$key] = $value;
        return $this;
    }
    
    /**
     * Returns the value of the specified option on the object.
     * If the option is not set yet, returns null.
     * 
     * @see     getOption()
     * @see     setCurlOption()
     * 
     * @return  mixed
     */
    public function getCURLOption($key)
    {
        if (!isset($this->_cURLOptions[$key])) {
            return null;
        }
        return $this->_cURLOptions[$key];
    }
    
    /**
     * Sets an specific option for the URL requests.
     * All the cURL options that could be specified using the 
     * curl_setopt() could be used in this method.
     * 
     * Note: Use CURLOPT_* constants provided by the CURL library.
     * Note: These options override any equivalent options that were set
     * using setOption().
     * Note: These options are used only when the cURL is going to be used.
     * 
     * @see     setOption()
     * 
     * @param   string  $key        the option key
     * @param   mixed   $value      the value of the option
     * @return  Parsonline_URLFetcher
     */
    public function setCURLOption($key, $value)
    {
        $this->_cURLOptions[$key] = $value;
        return $this;
    }
    
    /**
     * Removes all registered callable loggers.
     *
     * @return  Parsonline_URLFetcher
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
     * @param   string|array    $logger   a string for function name, or an array of object, method name.
     * @return  Parsonline_URLFetcher
     * @throws  Parsonline_Exception_ValueException on none callable parameter
     */
    public function registerLogger($logger)
    {
        if (!$logger || !is_callable($logger, false)) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/ExceptionValueException.php');
            throw new Parsonline_Exception_ValueException(
                "Logger for URL fetcher should be a string for function name, or an array of object, method name",
                0, null, 'callable', $logger
            );
        }
        array_push($this->_loggers, $logger);
        return $this;
    }
    
    /**
     * Removes all registered callable observers on progress of fetching
     * files.
     *
     * @return  Parsonline_URLFetcher
     */
    public function clearProgressObservers()
    {
        $this->_progressObservers = array();
        return $this;
    }

    /**
     * Returns an array of callable references (function, or object method) that
     * are called on each file fetched.
     *
     * @return  array
     */
    public function getProgressObservers()
    {
        return $this->_progressObservers;
    }

    /**
     * Registers a callable reference (function, or object method) so on
     * each file fetched, the observers are notified.
     *
     * Each observer should accept these paramters (with appropriate default values
     * incase some paramters are not provided):
     *
     *  <li>string url: the URL of the element</li>
     *  <li>int index: the number of downloaded elements</li>
     *  <li>int total: the number of total elements</li>
     *  <li>int size: size of the element downloaded in bytes</li>
     *  <li>float seconds: number of seconds took to fetch the media</li>
     *
     * NOTE: no validation is applied on the registered callable. any valid callable
     * reference could be registered, but they should be able to handle the
     * specified parameters.
     *
     * @param   string|array    $observer   a string for function name, or an array of object, method name.
     * @return  Parsonline_URLFetcher
     * @throws  Parsonline_Exception_ValueException on none callable parameter
     */
    public function registerProgressObserver($observer)
    {
        if (!$observer || !is_callable($observer, false)) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/ExceptionValueException.php');
            throw new Parsonline_Exception_ValueException(
                "Progress observer should be a string for function name, or an array of object, method name",
                0, null, 'callable', $observer
            );
        }
        array_push($this->_progressObservers, $observer);
        return $this;
    }
    
    /**
     * Notifies all registered loggers.
     *
     * @param   string  $signature      name of the method sending the log
     * @param   string  $message        message string being logged
     * @param   int     $priority       the standard priority of the log. 7 for DEBUG 0 for EMERG. default is 6 INFO
     */
    protected function _log($signature, $message, $priority=LOG_INFO)
    {
        if ($signature) $message = $signature . '> ' . $message;
        foreach($this->_loggers as $log) {
            call_user_func($log, $message, $priority);
        }
    }
    
    /**
     * Fetches the contents of the specified URL, and returns a string,
     * of its contents.
     * 
     * Throws exceptions if failed to read the contents of the URL.
     * 
     * Note: This method is used to fetch a single URL. It is not related
     * to the registered, progress observers, or URLs.
     * 
     * @param   string  $url
     * @return  string
     * @throws  Parsonline_Exception on failure to read data
     */
    public function fetchURL($url)
    {
        if ($this->_useCURL) {
            return $this->_fetchURLByCURL($url);
        } else {
            return $this->_fetchURLbyPHPStream($url);
        }
    }
    
    /**
     * Fetches the contents of the specified URL using PHP stream functions,
     * applying object options to the context.
     * 
     * @param   string  $url
     * @return  string
     * @throws  Parsonline_Exception on failure to fetch
     */
    protected function _fetchURLByPHPStream($url)
    {
        $contextOptions = $this->_createPHPStreamContextOptions($this->_options);
        $context = stream_context_create($contextOptions);
        $this->_log(__METHOD__, "fetching contents of '{$url}' using PHP stream", LOG_DEBUG);
        $data = file_get_contents($url, false, $context);
        if ($data == false) {
            /**
             * throw simple Parsonline_Exception instead of Parsonline_Exception_IOException
             * to be compatible with _fetchURLByCURL() method.
             * 
             * @uses Parsonline_Exception
             */
            require_once('Parsonline/Exception.php');
            throw new Parsonline_Exception("Failed to fetch URL '{$url}'");
        }
        $this->_log(__METHOD__, sprintf("fetched %d bytes from '{$url}' using PHP stream", strlen($data)), LOG_DEBUG);
        return $data;
    }
    
    /**
     * Creates an array approprate to create a PHP stream context, based
     * on the array of options from the object option description.
     * 
     * @param   array   $options
     * @return  array
     */
    protected function _createPHPStreamContextOptions(array $options)
    {
        $contextOptions = array();
        $socketOptions = array();
        $httpOptions = array();
        foreach($options as $key => $val) {
            if ($key == self::OPT_NETWORK_INTERFACE) {
                $socketOptions['bindto'] = $val;
            } else {
                $httpOptions[$key] = $val;
            }
        }
        $contextOptions['http'] = $httpOptions;
        $contextOptions['https'] = $contextOptions['http'];
        $contextOptions['socket'] = $socketOptions;
        return $contextOptions;
    }
    
    /**
     * Creates an array of cURL options that are equivalent of the options used
     * by the fetcher object.
     * 
     * @param   array   $options
     * @return  array
     */
    protected function _createCURLContextOptions(array $options)
    {
        $curlOptions = array();
        
        foreach($options as $key => $val) {
            switch($key) {
                case self::OPT_FOLLOWLOCATION:
                    $curlOptions[CURLOPT_FOLLOWLOCATION] = (bool) $val;
                case self::OPT_HTTP_VERSION:
                    $curlOptions[CURLOPT_HTTP_VERSION] = (int) $val;
                case self::OPT_MAXREDIRS:
                    $curlOptions[CURLOPT_MAXREDIRS] = (int) $val;
                case self::OPT_NETWORK_INTERFACE:
                    $curlOptions[CURLOPT_INTERFACE] = (string) $val;
                    break;
                case self::OPT_USERAGENT:
                    $curlOptions[CURLOPT_USERAGENT] = (string) $val;
                case self::OPT_PROXY:
                    if ( strpos(':', $val) !== false) {
                        list($ip, $port) = explode(':', $val, 2);
                    } else {
                        $ip = (string) $val;
                        $port = null;
                    }
                    $curlOptions[CURLOPT_PROXY] = $ip;
                    if ($port) $curlOptions[CURLOPT_PROXYPORT] = (int) $port;
                    unset($ip, $port);
                    break;
            }
        }
        return $curlOptions;
    }
    
    /**
     * Fetches the contents of the specified URL using cURL functions,
     * applying object options to the context.
     * 
     * @param   string  $url
     * @return  string
     * @throws  Parsonline_Exception on failure to fetch the URL
     */
    protected function _fetchURLByCURL($url)
    {
        $ch = curl_init($url);
        if (!$ch) {
            /**
             *@uses  Parsonline_Exception
             */
            require_once('Parsonline/Exception');
            throw new Parsonline_Exception("Failed to initialize a cURL session to URL '{$url}'");
        }
        $ch = $this->_configureCURLHandle($ch);
        $this->_log(__METHOD__, "fetching contents of '{$url}' using cURL", LOG_DEBUG);
        $result = curl_exec($ch);
        if ($result === false) {
            if ($ch) {
                $code = curl_errno($ch);
                $message = curl_error($ch);
                curl_close($ch);
            }
            /**
             * @uses  Parsonline_Exception
             */
            require_once('Parsonline/Exception.php');
            throw new Parsonline_Exception(
                sprintf("Failed to fetch URL '%s'. cURL error %d: %s", $url, $code, $message),
                $code
            );
        }
        
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($ch) curl_close($ch);
        // cURL returns true on a response from server without a body
        if ( !is_string($result) ) $result = '';
        
        if ($code > 400) {
            /**
             * @uses  Parsonline_Exception
             */
            require_once('Parsonline/Exception.php');
            throw new Parsonline_Exception(
                sprintf("Failed to fetch URL '%s'. HTTP status code %d: %s", $url, $code, $result),
                $code
            );
        }
        
        $this->_log(__METHOD__, sprintf("fetched %d bytes from '{$url}' using cURL", strlen($result)), LOG_DEBUG);
        return $result;
    }
    
    /**
     * Configures a cURL session handle by the specified options of the fetcher.
     * 
     * Note: the resource is modified in place, nothing is copied.
     * Note: forces the resuorce to return transfer.
     * 
     * @param   &resource   $ch
     * @return  &resource
     * @throws  Parsonline_Exception_ValueException on none resource
     */
    protected function & _configureCURLHandle(&$ch)
    {
        if (!is_resource($ch)) {
            /**
             * @uses Parsonline_Exception_ValueException
             */
            require_once('Parsonline/ExceptionValueException.php');
            throw new Parsonline_Exception_ValueException("Failed to initialize the cURL handle. Parameter is not a valid cURL resource");
        }
        $curlOptions = $this->_createCURLContextOptions($this->_options);
        foreach($this->_cURLOptions as $key => $value) {
            $curlOptions[$key] = $value;
        }
        foreach($curlOptions as $key => $value) {
            curl_setopt($ch, $key, $value);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return $ch;
    }
    
    /**
     * Notifies all registered observers on progress of fetching files.
     * 
     * @param   string              $url        the URL of the element
     * @param   int                 $index      the number of downloaded elements
     * @param   int                 $total      the number of total elements
     * @param   string|Exception    $data       the contents of the URL or exception on failure
     * @param   float               $seconds    number of seconds took to fetch the media
     */
    protected function _notifyProgressObservers($url, $index, $total, $data, $seconds)
    {
        foreach($this->_progressObservers as $observer) {
            call_user_func($observer, $url, $index, $total, $data, $seconds);
        }
    }
    
    /**
     * Fetches all the registered URLs.
     * Returns an associative array of whose keys are URLs and values
     * are string values of the resource file.
     * 
     * If an exception happens while fetching a resource, the value for that
     * URL is going to be an exception object instead of string of contents.
     * 
     * @return  array
     * @throws  Parsonline_Exception_ContextException on no URL set yet
     * @throws  Parsonline_Exception on internal failure
     */
    public function fetch()
    {
        if (!$this->_urls) {
            /**
             * @uses Parsonline_Exception_ContextException
             */
            require_once("Parsonline/Exception/ContextException.php");
            throw new Parsonline_Exception_ContextException("Failed to fetch files. No URL is specified");
        }
        if ($this->_mode == self::MODE_SEQUENCIAL) {
            return $this->_fetchSequencial($this->_urls);
        } else {
            return $this->_fetchParallel($this->_urls);
        }
    }
    
    /**
     * Fetches the specified URLs in sequencial mode, fetching each file
     * after the previous file is fetched.
     * 
     * Returns an associative array of whose keys are URLs and values
     * are string values of the resource file.
     * 
     * If an exception happens while fetching a resource, the value for that
     * URL is going to be an exception object instead of string of contents.
     * 
     * @param   array   $urls
     * @return  array
     */
    protected function _fetchSequencial($urls)
    {
        $result = array();
        $total = count($urls);
        $this->_log(__METHOD__, "fetching $total URLs in sequencial mode", LOG_DEBUG);
        $index = 1;
        
        foreach($urls as $url) {
            $start = microtime(true);
            try {
                $data = $this->fetchURL($url);
                $end = microtime(true);
                $this->_notifyProgressObservers($url, $index, $total, $data, $end - $start);
                $result[$url] = $data;
                unset($data);
            } catch(Exception $exp) {
                $result[$url] = $exp;
                $this->_log(
                    __METHOD__,
                    sprintf("Exception code %d occurred while fetching '%s'. message: %s", $exp->getCode(), $url, $exp->getMessage()),
                    LOG_ERR
                );
                $end = microtime(true);
                $this->_notifyProgressObservers($url, $index, $total, $exp, $end - $start);
            }
            ++$index;
        }
        return $result;
    }
    
    /**
     * Fetches the specified URLs in parallel mode.
     * 
     * Returns an associative array of whose keys are URLs and values
     * are string values of the resource file.
     * 
     * If an exception happens while fetching a resource, the value for that
     * URL is going to be an exception object instead of string of contents.
     * 
     * @param   array   $urls
     * @return  array
     * @throws  Parsonline_Exception on failure to register cURL handle to a URL
     */
    protected function _fetchParallel($urls)
    {        
        $curlMultiHandle = curl_multi_init();
        if (!$curlMultiHandle) {
            /**
             *@uses Parsonline_Exception
             */
            require_once('Parsonline/Exception.php');
            throw new Parsonline_Exception("Failed to initialize a cURL multi handle resource");
        }
        $result = array();
        $total = count($elements);
        $this->_log(__METHOD__, "fetching $total URLs in parallel mode", LOG_DEBUG);
        
        // associative array of url => cURL handles that are going to be
        // registered on the multi handle
        $curlSubHandles = array();
        
        foreach ($urls as $url) {
            $ch = curl_init($url);
            $curlSubHandles[$url] = $this->_configureCURLHandle($ch);
            $code = curl_multi_add_handle($curlMultiHandle, $curlSubHandles[$url]);
            if ($code) {
                /*
                 * failed to add cURL handle for the URL, cleanup the resources
                 * and throw exceptions
                 */
                foreach($curlSubHandles as $urls => $ch) {
                    if ($ch && is_resource($ch)) {
                        curl_multi_remove_handle($curlMultiHandle, $ch);
                        curl_close($ch);
                    }
                }
                curl_close($curlMultiHandle);
                /**
                * @uses Parsonline_Exception
                */
                require_once('Parsonline/Exception.php');
                throw new Parsonline_Exception("Failed to register a cURL handle to URL '{$url}'");
            }
        } // foreach ($urls ...
        
        $this->_log(
            __METHOD__,
            sprintf("fetching %d URLs in parallel mode using cURL", count($curlSubHandles)),
            LOG_DEBUG
        );

        // keeps track of which URLs we have already exposed to observer callbacks
        $exposedURLsToObservers = array();
        
        // execute the handles
        $active = null;
        do {
            $cmx = curl_multi_exec($curlMultiHandle, $active);
        } while ($cmx == CURLM_CALL_MULTI_PERFORM);
        
        /*
         * check if there are still active subhandles running.
         * using curl_multi_select() we could waite for events, and then again
         * keep executing until all downloads are finished.
         */
        while ($active && $cmx == CURLM_OK) {
            $selectStatus = curl_multi_select($curlMultiHandle);

            if ($selectStatus == 0) {
                $this->_log(__METHOD__, "reached cURL multi select timeout", LOG_WARN);                    
            } else if ($selectStatus < 0) {
                $this->_log(__METHOD__, "error ocurred in cURL multi select", LOG_WARN);
            }
            
            // since an event has occurred, check to see if any download is compelete
            // to notify observers.
            $messagesInQueue = 0;
            do {
                $subHandleInfo = curl_multi_info_read($curlMultiHandle, $messagesInQueue);
                if ($subHandleInfo && isset($subHandleInfo['result']) && $subHandleInfo['result'] == CURLE_OK) {
                    $ch = $subHandleInfo['handle'];
                    $subHandleEffectiveURL = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                    $subHandleURL = array_search($ch, $curlSubHandles, true);
                    if ($subHandleURL === false) $subHandleURL = $subHandleEffectiveURL;
                    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    if ( $code < 400) {
                        $subHandleContents = curl_multi_getcontent($ch);
                        if ( !is_string($subHandleContents) ) $subHandleContents = '';
                    } else {
                        $msg = "Failed to fetch URL '{$subHandleEffectiveURL}'. HTTP response code '{$code}' returned. Response body: {$subHandleContents}";
                        $this->_log(__METHOD__, $msg, LOG_ERR);
                        /**
                        * @uses Parsonline_Exception
                        */
                        require_once('Parsonline/Exception.php');
                        $subHandleContents = new Parsonline_Exception($msg, $code);
                        unset($msg);
                    }
                    
                    $exposedURLsToObservers[] = $subHandleURL;
                    
                    $this->_notifyProgressObservers(
                            $subHandleURL,
                            count($exposedURLsToObservers), $total,
                            $subHandleContents,
                            curl_getinfo($ch, CURLINFO_TOTAL_TIME)
                    );
                    
                    unset($ch, $subHandleEffectiveURL, $subHandleURL, $code, $subHandleContents);
                }
            } while($subHandleInfo && $messagesInQueue > 0);
            unset($subHandleInfo, $messagesInQueue);

            // keep downloading so the $cmx and $active values are updated again for the start of the loop above
            do {
                $cmx = curl_multi_exec($curlMultiHandle, $active);
            } while ($cmx == CURLM_CALL_MULTI_PERFORM);
        } // while ($active ...

        /**
         *@TODO implement finishing, by calling the rest of the observers,
         * and get the contents of all the sub handles and preparing the
         * result array.
         */
        $index = count($exposedURLsToObservers); // the last index passed to the observers
        $effectiveURL = null;
        foreach($curlSubHandles as $url => $ch) {
            $effectiveURL = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ( $code < 400) {
                $subHandleContents = curl_multi_getcontent($ch);
                if ( !is_string($subHandleContents) ) $subHandleContents = '';
            } else {
                $msg = "Failed to fetch URL '{$urls}'. HTTP response code '{$code}' returned. Response body: {$subHandleContents}";
                $this->_log(__METHOD__, $msg, LOG_ERR);
                /**
                * @uses Parsonline_Exception
                */
                require_once('Parsonline/Exception.php');
                $subHandleContents = new Parsonline_Exception($msg, $code);
                unset($msg);
            }
            
            // check if we have already notified observers of this download or not
            // comparing index and total could help us to know that we already have
            // notified all observers, so there is no need for array searches in exposedURLs array
            if ( ($index < $total) && !in_array($effectiveURL, $exposedURLsToObservers) && !in_array($url, $exposedURLsToObservers) ) {
                $this->_notifyProgressObservers(
                        $url,
                        ++$index, $total,
                        $subHandleContents,
                        curl_getinfo($ch, CURLINFO_TOTAL_TIME)
                );
            }
            
            $result[$url] = $subHandleContents;
            
            // free resources
            unset($effectiveURL, $code, $subHandleContents);
            curl_multi_remove_handle($curlMultiHandle, $ch);
            curl_close($ch);
        } // foreach ($curlSubHandles ...
        
        curl_multi_close($curlMultiHandle);
        return $result;
    } // protected function _fetchParallel()
}