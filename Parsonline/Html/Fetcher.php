<?php
//Parsonline/Html/Fetcher.php
/**
 * Defines the Parsonline_Html_Fetcher.
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
 * @version     0.2.0 2012-07-22
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 */

/**
 * @uses    Parsonline_Parser_Html
 * @uses    Parsonline_URLFetcher
 */
require_once("Parsonline/Parser/Html.php");
require_once("Parsonline/URLFetcher.php");

/**
 * Parsonline_Html_Fetcher
 * 
 * Provides functionality to fetch an HTML page, and its related elements.
 */
class Parsonline_Html_Fetcher
{
    const ELEMENT_IMAGE = 'image';
    const ELEMENT_STYLESHEET = 'stylesheet';
    const ELEMENT_LINK = 'link';
    const ELEMENT_HTML = 'html';
    const ELEMENT_SCRIPT = 'script';
    
    /**
     * The URL of the HTML page to fetch
     * 
     * @var string
     */
    protected $_url = null;
    
    /**
     * array of target elements to fetch
     * 
     * @var array
     */
    protected $_targetElements = array();
    
    /**
     * level of logging, the more the value is the more log is generated
     * 
     * @var int 
     */
    protected $_logLevel = 1;
    
    /**
     * Array of callable references to be notified on each log emition
     *
     * @var array
     */
    protected $_loggers = array();
    
    /**
     * Array of observers for the progress of downloading page elements
     * 
     * @var array
     */
    protected $_elementProgressObservers = array();
    
    /**
     * The fetcher object that is used to download the HTML page and its
     * elements
     * 
     * @var Parsonline_URLFetcher
     */
    protected $_fetcher = null;
    
    
    /**
     * The URL of the HTML page to fetch.
     * 
     * @param   string  $url    [optional]
     */
    public function __construct($url=null)
    {
        if ($url) {
            $this->_url = $url;
        }
        $this->_targetElements = $this->getSupportedTargetElements();
    }
    
    /**
     * Returns the logging level of the HTML fetcher
     *
     * @return int
     */
    public function getLogLevel()
    {
        return $this->_logLevel;
    }
    
    /**
     * Sets the logging level of the HTML fetcher. the more the log level is
     * the more log is generated. use 0 to disable logging.
     * 
     * @param   int $level
     * @return  Parsonline_Html_Fetcher
     * @throws  Parsonline_Exception_ValueException 
     */
    public function setLogLevel($level)
    {
        $level = intval($level);
        if ($level < 0) {
            /**
             *@uses Parsonline_Exception_ValueException 
             */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException("Invalid log level. log level should be positive integer");
        }
        $this->_logLegvel = $level;
        return $this;
    }
    
    /**
     * Returns the fetcher object that fetches the page elements.
     * 
     * @param   bool    $refresh
     * @return  Parsonline_URLFetcher
     */
    public function getFetcher($refresh=false)
    {
        if ($refresh || !$this->_fetcher) {
            $fetcher = new Parsonline_URLFetcher();
            $this->_initFetcher($fetcher);
            $this->_fetcher = $fetcher;
        }
        return $this->_fetcher;
    }
    
    /**
     * Sets the fetcher object that fetches the page elements.
     * 
     * @param   Parsonline_URLFetcher   $fetcher
     * @return  Parsonline_Html_Fetcher 
     */
    public function setFetcher(Parsonline_URLFetcher $fetcher)
    {
        $this->_fetcher = $fetcher;
        return $this;
    }
    
    /**
     * Initializes and configures the fetcher object.
     * 
     * Registers the HTML fetcher loggers as the URL fetcher loggers if
     * the log level is more than 1.
     * 
     * This could be used to modify and configure the URL fetcher in child
     * classes.
     * 
     * @param   Parsonline_URLFetcher   $fetcher
     * @return  Parsonline_URLFetcher
     */
    protected function _initFetcher(Parsonline_URLFetcher $fetcher)
    {
        if ($this->_logLevel > 1 && $this->_loggers) {
            foreach($this->_loggers as $logger) {
                $fetcher->registerLogger($logger);
            }
        }
        return $fetcher;
    }
    
    /**
     * Returns the URL of the web page to fetch.
     * 
     * @return string|null
     */
    public function getURL()
    {
        return $this->_url;
    }
    
    /**
     * Sets the URL of the web page to fetch.
     * 
     * @param   string  $url 
     * @return  Parsonline_Html_Fetcher
     */
    public function setURL($url)
    {
       $this->_url = (string) $url;
       return $this;
    }
    
    /**
     * Returns an array of target elements that are going to be fetched from the web page.
     * 
     * @return array(string,...)
     */
    public function getTargetElements()
    {
        return $this->_targetElements;
    }
    
    /**
     * Returns an array of elements that are supported by the HTML fetcher.
     * 
     * @return array
     */
    public function getSupportedTargetElements()
    {
        return array(
                    self::ELEMENT_IMAGE,
                    self::ELEMENT_STYLESHEET,
                    self::ELEMENT_SCRIPT,
                    self::ELEMENT_LINK
                );
    }
    
    /**
     * Adds an element to the list of elements that are going to be fetched from
     * the web page.
     * use ELEMENT_* class constants.
     * 
     * @see     setTargetElements()
     * @see     getSupportedTargetelements()
     * 
     * @param   string  $element
     * @return  Parsonline_Html_Fetcher
     * @throws  Parsonline_Exception_ValueException on not supported element
     */
    public function addTargetElement($element)
    {
        if ( !in_array($element, $this->getSupportedTargetElements()) ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/ExceptionValueException.php');
            throw new Parsonline_Exception_ValueException("element '$element' is not supported");
        }
        $this->_targetElements[] = $element;
        return $this;
    }
    
    /**
     * Sets the list of elements that are going to be fetched from the web page.
     * use ELEMENT_* class constants.
     * 
     * @see addTargetElement()
     * @see getSupportedTargetelements()
     * 
     * @param   array $elements
     * @return  Parsonline_Html_Fetcher
     * @throws  Parsonline_Exception_ValueException on not supported elements
     */
    public function setTargetElements(array $elements)
    {
        $supported = $this->getSupportedTargetElements();
        foreach($elements as $el) {
            if ( !in_array($el, $supported) ) {
                /**
                * @uses    Parsonline_Exception_ValueException
                */
                require_once('Parsonline/ExceptionValueException.php');
                throw new Parsonline_Exception_ValueException("element '$el' is not supported");
            }
        }
        $this->_targetElements = $elements;
        return $this;
    }
    
    /**
     * Removes all registered callable loggers.
     *
     * @return  Parsonline_Html_Fetcher
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
     * @return  Parsonline_Html_Fetcher
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
                "Logger for HTML fetcher should be a string for function name, or an array of object, method name",
                0, null, 'callable', $logger
            );
        }
        array_push($this->_loggers, $logger);
        return $this;
    }
    
    /**
     * Removes all registered callable observers on progress of fetching
     * elements.
     *
     * @return  Parsonline_Html_Fetcher
     */
    public function clearElementProgressObservers()
    {
        $this->_elementProgressObservers = array();
        return $this;
    }

    /**
     * Returns an array of callable references (function, or object method) that
     * are called on each element file fetch.
     *
     * @return  array
     */
    public function getElementProgressObservers()
    {
        return $this->_elementProgressObservers;
    }

    /**
     * Registers a callable reference (function, or object method) so on
     * each element fetched, the observers are notified.
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
     * @return  Parsonline_Html_Fetcher
     * @throws  Parsonline_Exception_ValueException on none callable parameter
     */
    public function registerElementProgressObserver($observer)
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
        array_push($this->_elementProgressObservers, $observer);
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
        if ($this->_loggers && $this->_logLevel > 0) {
            if ($signature) $message = $signature . '> ' . $message;
            foreach($this->_loggers as $log) {
                call_user_func($log, $message, $priority);
            }
        }
    }
    
    /**
     * Notifies all registered observers on progress of fetching
     * page elements.
     * 
     * 
     * @param   string              $url        the URL of the element
     * @param   int                 $index      the number of downloaded elements
     * @param   int                 $total      the number of total elements
     * @param   string|Exception    $data       the contents of the URL or exception on failure
     * @param   float               $seconds    number of seconds took to fetch the media
     * 
     */
    protected function _notifyElementProgressObservers($url, $index, $total, $data, $seconds)
    {
        foreach($this->_elementProgressObservers as $observer) {
            call_user_func($observer, $url, $index, $total, $data, $seconds);
        }
    }
    
    /**
     * Fetches the whole HTML web page, and its relatd elements.
     * Returns an array of resource categories where keys are the name of the
     * category, and values are associative arrays of URLs => data.
     * category names are identical to the ELEMENT_* class constants.
     * 
     * Each value of the categories, are associative arrays whose keys are the
     * URL of the resource, and values are data arrays with 2 vlaues.
     * the first value is the reference name for that resource in the HTML docuemnt,
     * the seconds index is a string value of the resource file (or an Exception
     * object describing what went wrong while fetching resource).
     * 
     * Sample:
     *      array(
     *          ELEMENT_HTML => array(
     *                              [pageURL] => array(pageURL, pagedata)
     *                          )
     *          ELEMENT_IMAGE => array(
     *                              [image1_URL] => array(image1_refrence, image1_data),
     *                              [image2_URL] => array(image2_refrence, image2_exception),
     *                          )
     *              
     *      )
     * 
     * If the specified URL of the web page was not reachable or not a value
     * HTML web page, throws exceptions.
     * 
     * Note: the returning array would contain only values that were actually
     * references on the original HTML web page.
     * 
     * @return  array
     * @throws  Parsonline_Exception_ContextException on no URL set yet
     * @throws  Parsonline_Exception_IOException on failure to read HTML page
     */
    public function fetchWebPageWithElements()
    {
        if (!$this->_url) {
            /**
             * @uses Parsonline_Exception_ContextException
             */
            require_once("Parsonline/Exception/ContextException.php");
            throw new Parsonline_Exception_ContextException("Failed to fetch web page. No URL is specified");
        }
        $pageURL = $this->_url;
        $result = array(self::ELEMENT_HTML => array());
        $fetcher = $this->getFetcher();
        $htmlPage = $fetcher->fetchURL($pageURL);
        $this->_log(__METHOD__, "fetched " . strlen($htmlPage) . " bytes from the web page '{$pageURL}'", LOG_DEBUG);
        
        /**
         *@uses  Parsonline_Parser_Html
         */
        $parser = new Parsonline_Parser_Html();
        $baseURL = $parser->getDocumentBaseURL($pageURL, $htmlPage);
        if (!$baseURL) {
            /**
             *@uses Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException(
                "Failed to detect the base of the web page from URL. Invalid URL specified: '{$pageURL}'"
            );
        }
        
        $parsedElementReferences = $this->parseTargetElements($htmlPage);
        
        $result[self::ELEMENT_HTML][$pageURL] = array($pageURL, $htmlPage);
        unset($htmlPage);
        
        $total = count($parsedElementReferences, COUNT_RECURSIVE) - count($parsedElementReferences);
        $this->_log(__METHOD__, "parsed $total resources from the web page '{$pageURL}'", LOG_DEBUG);
        $total++; // include the HTML page itself. total is going to be used to notify observers manually
        
        /**
         *@TODO add a public method to notify observers manually, using this total
         * in here. register that method as an observer in the fetcher object 
         */
        
        // first download stylesheets, then scripts, then links and images at last
        $elementOrder = array(self::ELEMENT_STYLESHEET, self::ELEMENT_SCRIPT, self::ELEMENT_LINK, self::ELEMENT_IMAGE);
        
        // keep track of downloaded URLs to avoid downloading a URL twice to handle redundant references
        $downloadedURLs = array();
        
        foreach($elementOrder as $element) {
            if ( !isset($parsedElementReferences[$element]) ) {
                continue;
            }
            
            $result[$element] = array();
            $references = $parsedElementReferences[$element];
            $urlsToFetch = array();
            
            foreach($references as $ref) {
                // convert to absolute URL
                if (parse_url($ref, PHP_URL_SCHEME)) {
                    $url = $ref;
                } else {
                    $url = $parser->convertReferenceToURL($ref, $pageURL, $baseURL);
                }
                
                 // handle multiple redundant references
                if ( !isset($result[$element][$url]) ) {
                    $result[$element][$url] = array($ref, null);
                }
                
                if ( in_array($url, $downloadedURLs) ) {
                    // if we have already downloaded the URL, do not include it
                    // int the $urlsToFetch and use previously fetched data
                    $result[$element][$url] = array($ref, $downloadedURLs[$url]);
                } else {
                    $urlsToFetch[] = $url;
                }
            } // foreach($references ...

            if ($urlsToFetch) {
                $this->_log(
                        __METHOD__,
                        sprintf("fetching '%d' resource of '%s' from the web page '%s'", count($urlsToFetch), $element, $pageURL)
                        ,LOG_DEBUG
                        );

                $fetcher->setURLs($urlsToFetch);
                unset($urlsToFetch);

                $fetched = $fetcher->fetch();

                // now push the fetched data back to the result strucutre
                foreach($fetched as $fetchedURL => $resourceData) {
                    if ( isset($result[$element][$fetchedURL]) ) {
                        // fetched URL is the same as the elementURL
                        $result[$element][$fetchedURL][1] = $resourceData;
                    } else {
                        // fetched URL differes from the elementURLs, so we do not
                        // know what is the actual element reference
                        $result[$element][$fetchedURL] = array(null, $resourceData);
                    }
                    $downloadedURLs[$fetchedURL] = &$result[$element][$fetchedURL][1];
                }
            }
            
        } // foreach($elementOrder ...
        
        return $result;
    }
    
    /**
     * Parses target elements out of the contents of a web page.
     * Returns an associative array whose keys are the element names as used in
     * ELEMENT_* class constants, and values are arrays of element references
     * parsed from the web page contents.
     * 
     * Note: the references might be relative or absolute URLs.
     * Note: throws exceptions if the data is not HTML.
     * 
     * @param   string  &$data
     * @return  array
     * @throws  Parsonline_Exception_ValueException on non HTML data
     * @TODO    Exception on trying to parse object elements (not implemented yet!)
     */
    public function parseTargetElements(&$data)
    {
        $result = array();
        /**
         *@uses  Parsonline_Parser_Html
         */
        $parser = new Parsonline_Parser_Html();
        $parser->setContents($data);
        unset($data);
        
        if ( in_array(self::ELEMENT_STYLESHEET, $this->_targetElements) ) {
            $result[self::ELEMENT_STYLESHEET] = $parser->parseStyleSheetReferences();
        }
        
        if ( in_array(self::ELEMENT_LINK, $this->_targetElements) ) {
            $links = $parser->parseLinkReferences();
            // ignore previous style sheet link objects in the link elements
            if ( isset($result[self::ELEMENT_STYLESHEET]) ) {
                $result[self::ELEMENT_LINK] = array();
                foreach($links as $link) {
                    if ( !in_array($link, $result[self::ELEMENT_STYLESHEET]) ) {
                        $result[self::ELEMENT_LINK][] = $link;
                    }
                }
            } else {
                $result[self::ELEMENT_LINK] = $links;
            }
            unset($links);
        }
        
        if ( in_array(self::ELEMENT_SCRIPT, $this->_targetElements) ) {
            $result[self::ELEMENT_SCRIPT] = $parser->parseScriptReferences();
        }
        
        if ( in_array(self::ELEMENT_IMAGE, $this->_targetElements) ) {
            $result[self::ELEMENT_IMAGE] = $parser->parseImageReferences();
        }
        
        return $result;
    } // public function parseTargetElements()
}