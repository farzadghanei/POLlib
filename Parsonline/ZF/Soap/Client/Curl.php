<?php
//Parsonline/ZF/Soap/Client/Curl.php
/**
 * Defines Parsonline_ZF_Soap_Client_Curl class.
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
 * @package     Parsonline_ZF_Soap
 * @subpackage  Client
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.1.0 2012-07-08
 */

/**
 * @uses    Zend_Soap_Client
 * @uses    Zend_Soap_Client_Common
 * @uses    Parsonline_StreamWrapper_Curl
 */
require_once('Zend/Soap/Client.php');
require_once('Zend/Soap/Client/Common.php');
require_once('Parsonline/StreamWrapper/Curl.php');

/**
 * Parsonline_ZF_Soap_Client_Curl
 * 
 * a SOAP client that extends Zend_Soap_Client by transferring streams
 * through cURL.
 * this adds missing features from PHP Soap Client because the
 * lacks in PHP internal stream wrappers.
 * 
 * it fetches and caches the WSDL file first in a file using cURL, then
 * hands it to PHP SoapClient. WSDL caching uses the internal PHP SoapClient
 * caching configurations (soap.wsdl_cache*)
 * 
 * To be compatible with Zend_Soap_Client all the thrown exceptions are
 * subclasses of Zend_Soap_Client_Exception
 */
class Parsonline_ZF_Soap_Client_Curl extends Zend_Soap_Client
{
    /**
     * an array of string values for supported protocol names
     * cURL supports http, https, ftp, ftps, sftp, scp.
     * but some of these prtocols are not supported by all PHP installations
     * and need extensions to be installed.
     *
     * @staticvar array
     */
    protected static $_supportedProtocols = array('http','https','ftp', 'ftps', 'sftp', 'scp');

    /**
     * an array of protocols that are available on the PHP installation, and
     * are supported by cURl. this is populated on object construction.
     * @var array
     */
    protected $_allProtocols = array();

    /**
     * an array of cURL option => value
     * @var array
     */
    protected $_curlOptions = array();
    
    /**
     * array of string values for protocol names
     * that cURL is registered to handle
     * @var array
     */
    protected $_registeredProtocols = array();

    /**
     * if should keep the stream wrappers. if set to false,
     * will unregister/re-register the wrappers for each request.
     *
     * @var bool
     */
    protected $_keepStreamWrappersRegistered = false;

    /**
     * the protocol of the URL of the service
     * @var string
     */
    protected $_serviceProtocol = null;

    /**
     * the name of the temp file to store WSDL.
     *
     * @var string
     */
    protected $_wsdlCacheFile = null;

    /**
     * a list of protocols that are unregistered on sleep phase.
     * these will be re-registered on wake up phase
     * @var array
     */
    protected $_unregisteredProtocolsOnSleep = array();

    /**
     * an array of request headers that tha SOAP Client
     * used for the last time. because we are overriding the
     * SOAP client request method in here, we should also keep this
     * in here instead of using the PHP SOAP client getLatRequestHeader method.
     * 
     * @var array
     */
    protected $_soapClientlastRequestHeaders = array();

    /**
     * cURL handle resource from curl_init()
     * 
     * @var resource
     */
    protected $_curlHandle = null;

    /**
     * Checks if the protocol of a url is supported by
     * this client or not.
     * 
     * @param   string  $url
     * @return  bool
     * @throws  Parsonline_ZF_Soap_Client_Exception
     */
    public static function isSupported($url='')
    {
        $urlArray = parse_url($url);
        if ( !$urlArray || !array_key_exists('scheme', $urlArray)  || !array_key_exists('host', $urlArray) ) {
            /**
            *@uses Parsonline_ZF_Soap_Client_Exception 
            */
            require_once('Parsonline/ZF/Soap/Client/Exception.php');
            throw new Parsonline_ZF_Soap_Client_Exception("Failed to parse URL '$url'");
        }
        return in_array($urlArray['scheme'], self::$_supportedProtocols);
    }
    
    /**
     * Constructor
     * 
     * creates a new client, registeres Parsonline_StreamWrapper_Curl as
     * the stream wrapper for remote requests. by default the object unregisters
     * changed stream wrappers and will re-register them right when needed. this reduces
     * performance but minimizes side effects.
     * overrides the parent method.
     * accepts additional options:
     *  'wsdl_file':    absolute path to a writable file to cache the WSDL info
     *  'keep_wrappers_registered': if set to true, stream wrappers will not get unregistered after each request. improves performance, but might have side effects
     *  'curl_options': an array of option => values for cURL
     *
     * 
     * Options could be an associative array of options, or an object with a
     * toArray() method (like a Zend_Config).
     * 
     * @param   string          $wsdl
     * @param   array|object    $options
     * @throws  Parsonline_ZF_Soap_Client_Exception
     */
    public function __construct($wsdl = null, $options = null)
    {
        if ( !defined("CURLOPT_RETURNTRANSFER") || !defined("CURLOPT_USERPWD") || !function_exists('curl_init')) {
            /**
            *@uses Parsonline_ZF_Soap_Client_Exception 
            */
            require_once('Parsonline/ZF/Soap/Client/Exception.php');
            throw new Parsonline_ZF_Soap_Client_Exception("Failed to initialize cURL SOAP client. cURL extension is not available");
        }
        // load the class and set the needed options for cURL
        Parsonline_StreamWrapper_Curl::addDefaultCurlOption(CURLOPT_RETURNTRANSFER, true);
        $this->_allProtocols = array_intersect(self::$_supportedProtocols, stream_get_wrappers());

        if ( is_null($options) ) {
            $options = array();
        } elseif ( method_exists($options, 'toArray') ) {
            $options = $options->toArray();
        } elseif (!is_array($options)) {
            /**
            *@uses Parsonline_ZF_Soap_Client_Exception 
            */
            require_once('Parsonline/ZF/Soap/Client/Exception.php');
            throw new Parsonline_ZF_Soap_Client_Exception("invalid options. options should be an associative array of options");
        }

        // these options are needed to be set, before the SoapClient requests for WSDL file.
        if ( array_key_exists('wsdl_file', $options) ) $this->setWsdlFileName($options['wsdl_file']);
        if ( array_key_exists('curl_options', $options) ) $this->setCurlOptions($options['curl_options']);
        if ( array_key_exists('login', $options) ) {
            $credentials = $options['login'] . ':' . (array_key_exists('password',$options) ? $options['password'] : '');
            $this->addCurlOption(CURLOPT_USERPWD, $credentials);
            unset($credentials);
        }
        $this->_registerProtocols();
        parent::__construct($wsdl, $options);
    } // public function __construct()

    /**
     * Destructor.
     * 
     *  - unregisters all registered protocols on object destruction to reduce side effects
     *  - closes the cURL resource
     *  - if requires purges cached WSDL file
     */
    public function __destruct()
    {
        $this->_unregisterProtocols();
        if ($this->_curlHandle) curl_close($this->_curlHandle);

        switch($this->_cache_wsdl) {
            case WSDL_CACHE_BOTH:
            case WSDL_CACHE_DISK:
                    break;
            default:
                $this->purgeCachedWsdl();
        }
    }

    /**
     * when object is being serialized, unregisters all registered protocols
     * to reduce side effects. saves the list of protocols so they can be
     * re-registered on unserialize.
     */
    public function __sleep()
    {
        $protocols = $this->_unregisterProtocols();
        if ($protocols) $this->_unregisteredProtocolsOnSleep = $protocols;
        if ($this->_curlHandle) curl_close($this->_curlHandle);
    }

    /**
     * when object is being unserialized, re-registered those protocols
     * that were unregisterd during serialization.
     */
    public function __wakeup()
    {
        $this->_initCurlHanlde();
        if ($this->_unregisteredProtocolsOnSleep) $this->_registerProtocols($this->_unregisteredProtocolsOnSleep);
    }

    // ======== overridden Zend_Soap_Client methods ========

    /**
     * Returns the HTTP headers of lat request.
     * overrides the parent because the request is being handled differently
     * in here.
     * 
     * @return string
     */
    public function getLastRequestHeaders()
    {
        return implode("\n", $this->_soapClientlastRequestHeaders);
    }

    /**
     * Set Options
     *
     * Allows setting options as an associative array of option => value pairs.
     * overrides the parent method by catching unknown option exception, and
     * tries to register object specific option values.
     * 
     * Options could be an associative array, or an object with a toArray()
     * method (like a Zend_Config)
     *
     * @param   array|object    $options
     * @return  Parsonline_ZF_Soap_Client_Curl
     * @throws  Parsonline_ZF_Soap_Client_Exception on invalid option
     * 
     */
    public function setOptions($options)
    {
        if ( is_object($options) && method_exists($options, 'toArray') ) {
            $options = $options->toArray();
        } elseif ( !is_array($options) ) {
            /**
            *@uses Parsonline_ZF_Soap_Client_Exception 
            */
            require_once('Parsonline/ZF/Soap/Client/Exception.php');
            throw new Parsonline_ZF_Soap_Client_Exception("invalid option. options should be an associative array of options");
        }

        foreach ($options as $key => $value) {
            switch ($key) {
                // added options to Zend_Soap_Client
                case 'keep_wrappers_registered':
                    $this->setKeepCurlWrapperRegistered($value);
                    break;
                case 'curl_options':
                    $this->setCurlOptions($value);
                    break;
                case 'wsdl_file':
                    $this->setWsdlFileName($value);
                    break;
                // original Zend_Soap_Client options
                case 'classmap':
                case 'classMap':
                    $this->setClassmap($value);
                    break;
                case 'encoding':
                    $this->setEncoding($value);
                    break;
                case 'soapVersion':
                case 'soap_version':
                    $this->setSoapVersion($value);
                    break;
                case 'wsdl':
                    $this->setWsdl($value);
                    break;
                case 'uri':
                    $this->setUri($value);
                    break;
                case 'location':
                    $this->setLocation($value);
                    break;
                case 'style':
                    $this->setStyle($value);
                    break;
                case 'use':
                    $this->setEncodingMethod($value);
                    break;
                case 'login':
                    $this->setHttpLogin($value);
                    break;
                case 'password':
                    $this->setHttpPassword($value);
                    break;
                case 'proxy_host':
                    $this->setProxyHost($value);
                    break;
                case 'proxy_port':
                    $this->setProxyPort($value);
                    break;
                case 'proxy_login':
                    $this->setProxyLogin($value);
                    break;
                case 'proxy_password':
                    $this->setProxyPassword($value);
                    break;
                case 'local_cert':
                    $this->setHttpsCertificate($value);
                    break;
                case 'passphrase':
                    $this->setHttpsCertPassphrase($value);
                    break;
                case 'compression':
                    $this->setCompressionOptions($value);
                    break;
                case 'stream_context':
                    $this->setStreamContext($value);
                    break;
                case 'features':
                    $this->setSoapFeatures($value);
                    break;
                case 'cache_wsdl':
                    $this->setWsdlCache($value);
                    break;
                case 'useragent':
                case 'userAgent':
                case 'user_agent':
                    $this->setUserAgent($value);
                    break;
                default:
                    /**
                     *@uses Parsonline_ZF_Soap_Client_Exception 
                     */
                    require_once('Parsonline/ZF/Soap/Client/Exception.php');
                    throw new Parsonline_ZF_Soap_Client_Exception('Unknown SOAP client option');
                    break;
            }
        }
        return $this;
    } // public function setOptions()

    /**
     * Set wsdl. overrides the parent method
     * by checking the support for the URL, fetching an caching
     * of the WSDL file to local disk.
     * to cache the WSDL, uses the same caching options that PHP SoapClient
     * does (soap.wsdl_cache* in php.ini), if found not such settings, uses
     * the value of SYSTEM_TEMP constant (if available), and if not uses
     * the system temp path by calling sys_get_temp_dir()
     *
     * @param   string  $wsdl
     * @return  Parsonline_ZF_Soap_Client_Curl  object self reference
     * @throws  Parsonline_ZF_Soap_Client_Exception
     */
    public function setWsdl($wsdl)
    {
        if (!self::isSupported($wsdl)) {
            /**
            *@uses Parsonline_ZF_Soap_Client_Exception 
            */
            require_once('Parsonline/ZF/Soap/Client/Exception.php');
            throw new Parsonline_ZF_Soap_Client_Exception('invalid WSDL. the URL is not supported by this client');
        }
        $urlArray = parse_url($wsdl);
        $this->_serviceProtocol = strtolower($urlArray['scheme']);
        unset($urlArray);
        
        if ($this->_wsdlCacheFile) {
            $wsdlCacheFileName = $this->_wsdlCacheFile;
        } else {
            $wsdlCacheFileName = ini_get('soap.wsdl_cache_dir');
            if (!$wsdlCacheFileName) {
                trigger_error('failed to retreive SOAP WSDL cache path settings from soap.wsdl_cache_dir', E_USER_NOTICE);
                $wsdlCacheFileName = (defined('SYSTEM_TEMP') ? SYSTEM_TEMP : sys_get_temp_dir());
            }
            $wsdlCacheFileName = $wsdlCacheFileName . DIRECTORY_SEPARATOR . 'soap_curl_cache_' .md5( strtolower($wsdl) ) . '_wsdl.xml';
        }
        if (!$wsdlCacheFileName) {
            /**
            *@uses Parsonline_ZF_Soap_Client_Exception 
            */
            require_once('Parsonline/ZF/Soap/Client/Exception.php');
            throw new Parsonline_ZF_Soap_Client_Exception('failed to determine the name of the temp file for WSDL cache');
        }
        $this->_wsdlCacheFile = $wsdlCacheFileName;

        $wsdlContent = '';
        $requestForWSDL = true; // if should run a request for WSDL right now

        /*
         * if file exists, then it might have been cached.
         * PHP might have been configured not to cache WSDL. so check for validity of the cache
         */
        if ( ini_get('soap.wsdl_cache_enabled') && file_exists($wsdlCacheFileName) ) {
            if ( !is_file($wsdlCacheFileName) ) {
                /**
                *@uses Parsonline_ZF_Soap_Client_Exception 
                */
                require_once('Parsonline/ZF/Soap/Client/Exception.php');
                throw new Parsonline_ZF_Soap_Client_Exception("temp file name for WSDL cache '{$wsdlCacheFileName}' exists but is not a regular file");
            }
            $tempFileModifytime = filemtime($wsdlCacheFileName);
            $tempFileCacheTTL = intval(ini_get('soap.wsdl_cache_ttl'));
            if (!$tempFileCacheTTL) {
                trigger_error('failed to retreive SOAP WSDL cache ttl settings from soap.wsdl_cache_ttl', E_USER_NOTICE);
                $tempFileCacheTTL = 3600; // hardcoded cache life time is 1 hour
            }
            if (!$tempFileModifytime || ( time() > $tempFileModifytime + $tempFileCacheTTL) ) {
                $requestForWSDL = true;
            } else {
                $requestForWSDL = false;
                $wsdlContent = file_get_contents($wsdlCacheFileName);
            }
            unset($tempFileModifytime, $tempFileCacheTTL);
        }

        if ($requestForWSDL || !$wsdlContent) {
            $wsdlContent = $this->_curlRequest($wsdl);
            if (!$wsdlContent) {
                /**
                *@uses Parsonline_ZF_Soap_Client_Exception 
                */
                require_once('Parsonline/ZF/Soap/Client/Exception.php');
                throw new Parsonline_ZF_Soap_Client_Exception("failed to retreive the WSDL file from '{$wsdl}'");
            }
            if ( !file_put_contents($wsdlCacheFileName, $wsdlContent, FILE_TEXT) ) {
                /**
                *@uses Parsonline_ZF_Soap_Client_Exception 
                */
                require_once('Parsonline/ZF/Soap/Client/Exception.php');
                throw new Parsonline_ZF_Soap_Client_Exception("failed to save contents of WSDL to temporary file on disk");
            }
        }
        parent::setWsdl($wsdlCacheFileName);
        return $this;
    } // public function setWsdl()

    /**
     * actual "do request" method.
     * overrides the parent method.
     * based on the configuration calls the appropriate object
     * method to perform the SOAP request. by default calls
     * the direct cURL method and avoids calling of PHP SoapClient _doRequest()
     * method.
     *
     * @internal
     * @param   Zend_Soap_Client_Common $client
     * @param   string                  $request
     * @param   string                  $location
     * @param   string                  $action
     * @param   int                     $version
     * @param   int                     $one_way
     * @return  mixed
     * @throws  Exception
     */
    public function _doRequest(Zend_Soap_Client_Common $client, $request, $location, $action, $version, $one_way = null)
    {
        if ($this->_proxy_host) {
            $this->addCurlOption(CURLOPT_PROXY, $this->_proxy_host);
            if ($this->_proxy_login) {
                $credentials = $this->_proxy_login . ':' . ($this->_proxy_password ? $this->_proxy_password : '');
                $this->addCurlOption(CURLOPT_PROXYUSERPWD, $credentials);
                unset($credentials);
            }
            if ($this->_proxy_port) $this->addCurlOption(CURLOPT_PROXYPORT, $this->_proxy_port);
        }
        
        if ( $this->_login ) {
            $credentials = $this->_login . ':' . ($this->_password ? $this->_password : '');
            $this->addCurlOption(CURLOPT_USERPWD, $credentials);
            unset($credentials);
        }
        
        $this->addCurlOption(CURLOPT_USERAGENT, ($this->_user_agent ? $this->_user_agent : 'ParsOnline SOAP Client cURL'));
        $this->_unregisterProtocols();
        $this->_registerProtocols();
        return $this->_soapRequestOverCurl($client, $request, $location, $action, $version, $one_way);
    } // public function _doRequest()

    // ======= added object sepcific methods ========

    /**
     * initializes the cURL handle, and set some hadcoded default settings on it.
     * if it had been initialized, will not touch it unless the force option is set.
     * 
     * @param   bool        $force  if should force the handle
     * @return  resource    cURL resource handle
     * @throws  Parsonline_ZF_Soap_Client_Exception
     */
    protected function _initCurlHanlde($force=false)
    {
        if ($force || !$this->_curlHandle) {
            if ($this->_curlHandle) curl_close($this->_curlHandle);
            $this->_curlHandle = curl_init();
            if (!$this->_curlHandle) {
                /**
                *@uses Parsonline_ZF_Soap_Client_Exception 
                */
                require_once('Parsonline/ZF/Soap/Client/Exception.php');
                throw new Parsonline_ZF_Soap_Client_Exception("failed to initialize a cURL handler");
            }
            if ( !curl_setopt($this->_curlHandle, CURLOPT_RETURNTRANSFER, true) ) {
                /**
                *@uses Parsonline_ZF_Soap_Client_Exception 
                */
                require_once('Parsonline/ZF/Soap/Client/Exception.php');
                throw new Parsonline_ZF_Soap_Client_Exception("failed to set options for cURL handler to return transfer results");
            }
            if ( !curl_setopt($this->_curlHandle, CURLOPT_UNRESTRICTED_AUTH, true) ) {
                /**
                *@uses Parsonline_ZF_Soap_Client_Exception 
                */
                require_once('Parsonline/ZF/Soap/Client/Exception.php');
                throw new Parsonline_ZF_Soap_Client_Exception("failed to set options for cURL handler to keep sending of credentials on follow");
            }
        }
        return $this->_curlHandle;
    }

    /**
     * unregisteres the pre registered stream wrappers.
     * returns an array of unregistred protocols.
     * if failed to unregister a protocol, triggers an E_WARNING error. if a list of essential
     * protocols is passed and any of them failed to be unregistered, an exception
     * will be thrown.
     *
     * @return  array
     * @throws  Parsonline_ZF_Soap_Client_Exception
     */
    protected function _unregisterProtocols($essential=array())
    {
        if (!is_array($essential)) $essential = array($essential);
        $unregistered = array();
        foreach($this->_registeredProtocols as $protocol) {
            if ( stream_wrapper_unregister($protocol) && stream_wrapper_restore($protocol) ) {
                array_push($unregistered, $protocol);
                continue;
            }
            trigger_error( sprintf("%s > failed to unregister 'Parsonline_StreamWrapper_Curl' as stream wrapper for '%s'", __METHOD__, $protocol), E_USER_WARNING);
            if ( in_array($protocol, $essential) ) {
                /**
                *@uses Parsonline_ZF_Soap_Client_Exception 
                */
                require_once('Parsonline/ZF/Soap/Client/Exception.php');
                throw new Parsonline_ZF_Soap_Client_Exception("failed to unregister 'Parsonline_StreamWrapper_Curl' as stream wrapper for '{$protocol}'");
            }
        }

        $stillRegistered = array();
        foreach($this->_registeredProtocols as $protocol) {
            if (!in_array($protocol, $unregistered)) array_push($stillRegistered, $protocol);
        }
        $this->_registeredProtocols = $stillRegistered;
        return $unregistered;
    } // protected function _unregisterProtocols()

    /**
     * registers the cURL stream wrapper for the specified supported protocols. if failed
     * to register a protocol, triggers an E_WARNING error. if a list of essential
     * protocols is passed and any of them failed to be registered, an exception
     * will be thrown.
     * if a list of protocols is provided, that list will be registered, otherwise
     * the default supported list will be registered.
     *
     * @param   array   $protcolList        list of protocols to register
     * @param   array   $essential
     * @throws  Parsonline_ZF_Soap_Client_Exception
     */
    protected function _registerProtocols($protocolList=null, $essential=array())
    {
        if (!$protocolList) {
            $protocolList = $this->_allProtocols;
        } elseif (!is_array($protocolList)) {
            $protocolList = array($protocolList);
        }
        if (!is_array($essential)) $essential = array($essential);

        foreach($this->_curlOptions as $option => $value) {
            Parsonline_StreamWrapper_Curl::addDefaultCurlOption($option, $value);
        }

        $systemWrappers = stream_get_wrappers();
        foreach( $protocolList as $protocol) {
            if ( in_array($protocol, $this->_registeredProtocols) ) continue;
            if ( in_array($protocol, $systemWrappers)) {
                stream_wrapper_unregister($protocol);
            } else {
                trigger_error( sprintf("%s > failed to register stream wrapper for '%s'. this protocol is not supported by current PHP installation", __METHOD__, $protocol), E_USER_WARNING);
                if (in_array($protocol, $essential)) {
                    /**
                    *@uses Parsonline_ZF_Soap_Client_Exception 
                    */
                    require_once('Parsonline/ZF/Soap/Client/Exception.php');
                    throw new Parsonline_ZF_Soap_Client_Exception("the '{$protocol}' is not supported by current PHP installation");
                }
            }
            if ( stream_wrapper_register($protocol, 'Parsonline_StreamWrapper_Curl', STREAM_IS_URL) ) {
                array_push($this->_registeredProtocols, $protocol);
                continue;
            }
            trigger_error( sprintf("%s > failed to register 'Parsonline_StreamWrapper_Curl' as stream wrapper for '%s'", __METHOD__, $protocol), E_USER_WARNING);
            if (in_array($protocol, $essential)) {
                /**
                *@uses Parsonline_ZF_Soap_Client_Exception 
                */
                require_once('Parsonline/ZF/Soap/Client/Exception.php');
                throw new Parsonline_ZF_Soap_Client_Exception("failed to register 'Parsonline_StreamWrapper_Curl' as stream wrapper for '{$protocol}'");
            }
        }
    } // protected function _registerProtocols()

    /**
     * sends a request to the URL using cURL functions. uses currently set
     * cURL options on the object.
     * accepts additional cURL options and HTTP headers for this specific request.
     *
     * @param   string  $url
     * @param   array   $curlOptions    additionl cURL options to currently set options
     * @param   array   $headers        additional HTTP headers
     * @return  string|array  response  an array of false, 'error code,'error message' incase of error
     * @throws  Parsonline_ZF_Soap_Client_Exception
     */
    protected function _curlRequest($url, $curlOptions=array(), $headers=array())
    {
        $this->_initCurlHanlde();
        if ( !curl_setopt($this->_curlHandle, CURLOPT_URL, $url) ) {
            /**
            *@uses Parsonline_ZF_Soap_Client_Exception 
            */
            require_once('Parsonline/ZF/Soap/Client/Exception.php');
            throw new Parsonline_ZF_Soap_Client_Exception("failed to set URL for cURL handler");
        }

        if ( $this->_login ) {
            $credentials = $this->_login . ':' . ($this->_password ? $this->_password : '');
            $this->addCurlOption(CURLOPT_USERPWD, $credentials);
            unset($credentials);
        }

        if ($this->_proxy_host) {
            $this->addCurlOption(CURLOPT_PROXY, $this->_proxy_host);
            if ($this->_proxy_login) {
                $credentials = $this->_proxy_login . ':' . ($this->_proxy_password ? $this->_proxy_password : '');
                $this->addCurlOption(CURLOPT_PROXYUSERPWD, $credentials);
                unset($credentials);
            }
            if ($this->_proxy_port) $this->addCurlOption(CURLOPT_PROXYPORT, $this->_proxy_port);
        }
        
        if ( $this->_curlOptions && !curl_setopt_array($this->_curlHandle, $this->_curlOptions) ) trigger_error(sprintf("%s > failed to set object cURL options on cURL handler", __METHOD__), E_USER_WARNING);
        if ( $curlOptions && !curl_setopt_array($this->_curlHandle, $curlOptions) ) trigger_error(sprintf("%s > failed to set additional cURL options on cURL handler", __METHOD__), E_USER_WARNING);
        if ( !curl_setopt($this->_curlHandle, CURLOPT_HTTPHEADER, $headers) ) trigger_error(sprintf("%s > failed to set HTTP headers on cURL handler", __METHOD__), E_USER_WARNING);
        
        $response = curl_exec($this->_curlHandle);
        if ($response === false) $response = array(false, curl_errno($this->_curlHandle), curl_error($this->_curlHandle));
        return $response;
    } // protected function _curlRequest
    
    /**
     * Requests a SOAP call by calling cURL requests.
     * generates a SOAP/HTTP requests and submits the request using cURL, retreives
     * the response and returns it. this is to avoid calling PHP SoapClient request
     * method.
     *
     * @internal
     * @param   Zend_Soap_Client_Common $client
     * @param   string                  $request
     * @param   string                  $location
     * @param   string                  $action
     * @param   int                     $version
     * @param   int                     $one_way
     * @return  mixed
     * @throws  Parsonline_ZF_Soap_Client_Exception
     */
    protected function _soapRequestOverCurl(Zend_Soap_Client_Common $client, $request, $location, $action, $version, $one_way = null)
    {
        if (!$this->_serviceProtocol && !self::isSupported($location) ) {
            /**
            *@uses Parsonline_ZF_Soap_Client_Exception 
            */
            require_once('Parsonline/ZF/Soap/Client/Exception.php');
            throw new Parsonline_ZF_Soap_Client_Exception("invalid service location. the URL '{$location}' is not supported by this client");
        }
        $response = '';       
        $httpHeaders = array();

        $contentType = "text/xml;charset=UTF-8";
        switch( $this->_soapVersion ) {
            case SOAP_1_2:
                $contentType = "application/soap+xml;charset=UTF-8;action=\"{$action}\"";
                break;
            case SOAP_1_1:
            default:
        }
        
        $httpHeaders[] = 'Connection: keep-alive'; // keep the connection to server
        $httpHeaders[] = "Content-Type: {$contentType}";
        $httpHeaders[] = sprintf('Content-Length: %d', strlen($request));
        $httpHeaders[] = "SOAPAction: \"{$action}\"";
        $httpHeaders[] = "Cache-Control: no-cache";
        $httpHeaders[] = "Pragma: no-cache";
        
        $curlOptions = array(
                            CURLOPT_POST => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_POSTFIELDS => $request
                        );
        
        $this->_soapClientlastRequestHeaders = $httpHeaders;
        $response = $this->_curlRequest($location, $curlOptions, $httpHeaders);
        if ( is_array($response) ) {
            /**
            *@uses Parsonline_ZF_Soap_Client_Exception 
            */
            require_once('Parsonline/ZF/Soap/Client/Exception.php');
            throw new Parsonline_ZF_Soap_Client_Exception($response[2], $response[1]);
        }
        return $response;
    } // protected function _soapRequestOverCurl()

    /**
     * Deletes the cached WSDL file from disk
     * Returns the contents of the file, or null if cache is empty.
     *
     * @return string|null
     */
    public function purgeCachedWsdl()
    {
        $filename = $this->_wsdlCacheFile;
        $content = null;
        if ( file_exists($filename) ) {
            $content = file_get_contents($filename);
            if ( !unlink($filename) ) trigger_error("failed to remove the cached WSDL file '{$filename}'", E_USER_WARNING);
        }
        return $content;
    }

    /**
     * Adds a cURL option to cURL option list. accepts an array of option => values
     * or an option for the first argument and the value for the second.
     *
     * @param   array|int       $option     use CURLOPT_* constants
     * @param   mixed           $value
     * @return  Parsonline_ZF_Soap_Client_Curl  object self reference
     */
    public function addCurlOption($option, $value=null)
    {
        if ( is_array($option) ) {
            foreach ($option as $optKey => $optVaue ) {
                $this->_curlOptions[$optKey] = $optValue;
            }
        } else {
            $this->_curlOptions[$option] = $value;
        }
        return $this;
    }

    /**
     * Sets cURL options
     *
     * @param   array   $options        array of cURL option => values
     * @return  Parsonline_ZF_Soap_Client_Curl  object self reference
     * @throws  Parsonline_ZF_Soap_Client_Exception
     */
    public function setCurlOptions($options)
    {
        if (!is_array($options)) {
            /**
            *@uses Parsonline_ZF_Soap_Client_Exception 
            */
            require_once('Parsonline/ZF/Soap/Client/Exception.php');
            throw new Parsonline_ZF_Soap_Client_Exception("invalid options. curl options should be an associative array of cURL options");
        }
        $this->_curlOptions = $options;
        return $this;
    }

    /**
     * returns an array of cURL option => values
     *
     * @return  array
     */
    public function getCurlOptions()
    {
        return $this->_curlOptions;
    }

    /**
     * if should keep the cURL as default stream wrapper
     * on the system.
     * 
     * @param   bool    $keep
     * @return  Parsonline_ZF_Soap_Client_Curl  object self reference
     */
    public function setKeepCurlWrapperRegistered($keep=true)
    {
        $this->_keepStreamWrappersRegistered = !!$keep;
        return $this;
    }

    /**
     * Sets the file name that will store the local copy for the WSDL.
     *
     * @param   string      $filename
     * @return  Parsonline_ZF_Soap_Client_Curl
     * @throws  Parsonline_ZF_Soap_Client_Exception
     */
    public function setWsdlFileName($filename)
    {
        $dir = dirname($filename);
        if ( !file_exists($dir) ) {
            /**
            *@uses Parsonline_ZF_Soap_Client_Exception 
            */
            require_once('Parsonline/ZF/Soap/Client/Exception.php');
            throw new Parsonline_ZF_Soap_Client_Exception("invalid file path '{$filename}'. no such directory as '{$dir}' exists");
        } elseif ( !is_dir($dir) ) {
            /**
            *@uses Parsonline_ZF_Soap_Client_Exception 
            */
            require_once('Parsonline/ZF/Soap/Client/Exception.php');
            throw new Parsonline_ZF_Soap_Client_Exception("invalid file path '{$filename}'. specified directory '{$dir}' is not a directory");
        } elseif ( !is_readable($dir) ) {
            /**
            *@uses Parsonline_ZF_Soap_Client_Exception 
            */
            require_once('Parsonline/ZF/Soap/Client/Exception.php');
            throw new Parsonline_ZF_Soap_Client_Exception("invalid file path '{$filename}'. directory '{$dir}' is not readable");
        } elseif ( !is_writable($dir) ) {
            /**
            *@uses Parsonline_ZF_Soap_Client_Exception 
            */
            require_once('Parsonline/ZF/Soap/Client/Exception.php');
            throw new Parsonline_ZF_Soap_Client_Exception("invalid file path '{$filename}'. directory '{$dir}' is not writable");
        }
        $this->_wsdlCacheFile = $filename;
        return $this;
    }

    /**
     * returns array of names of all protocols that are supported and available
     * on the system. these protocols will be registered
     * or unregistered on the system, the SOAP client is being used
     * in none-WSDL mode.
     *
     * @return  array
     */
    public function getAllProtocols()
    {
        return $this->_allProtocols;
    }
}
