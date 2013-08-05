<?php
//Parsonline/Network/Whois.php
/**
 * Defines Parsonline_Network_Whois class.
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
 * @package     Parsonline_Network
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.0.4 2012-03-10
 */

/**
 * Parsonline_Network_Whois
 *
 * A Whois client class to query whois databases.
 */
class Parsonline_Network_Whois
{
    const ABUSEHOST = "whois.abuse.net";
    const NICHOST = "whois.crsnic.net";
    const INICHOST = "whois.networksolutions.com";
    const DNICHOST = "whois.nic.mil";
    const GNICHOST = "whois.nic.gov";
    const ANICHOST = "whois.arin.net";
    const LNICHOST = "whois.lacnic.net";
    const RNICHOST = "whois.ripe.net";
    const PNICHOST = "whois.apnic.net";
    const MNICHOST = "whois.ra.net";
    const QNICHOST_TAIL = ".whois-servers.net";
    const SNICHOST = "whois.6bone.net";
    const BNICHOST = "whois.registro.br";
    const NORIDHOST = "whois.norid.no";
    const IANAHOST = "whois.iana.org";
    const GERMNICHOST = "de.whois-servers.net";
    
    const OPT_QUICK = 'quick';
    const OPT_SERVER = 'server';
    const OPT_COUNTRY = 'country';
    const OPT_SOCKET = 'socket';
    const OPT_PORT = 'port';
    const OPT_BLOCKING = 'blocking';
    const OPT_IO_TIMEOUT = 'io_timeout';
    const OPT_CONNECTION_TIMEOUT = 'connection_timeout';
    
    /**
     * Defautl text signature for whois redirection
     * 
     * @var array
     */
    protected static $_defaultWhoisRedirectPatterns = array("/whois(?:\sserver)?:\s+(\S+)/i");
    
    /**
     * Default text signature for whois redirection for .org whois servers
     * 
     * @var type 
     */
    protected static $_whoisOrgRedirectSignature = "Registrant Street1:Whois Server:";
    
    /**
     * List of whois servers to use as redirection whois servers for ARIN.NET
     * whois responses.
     * 
     * @var array
     */
    protected $_ipWhois = array();
    
    /**
     * Array of options
     * 
     * @var array
     */
    protected $_options = array();
    
    /**
     * Regex patterns to search for addtional whois redirections
     * @var array
     */
    protected $_whoisRedirectPatterns = array();
    
    /**
     * Returns the default values for the array whois servers to seach in ARIN.NET whois response,
     * incase no whois redirection signature is found.
     * 
     * @return array
     */
    public static function getDefaultIPWhois()
    {
        return array(self::LNICHOST, self::RNICHOST, self::PNICHOST, self::BNICHOST);
    }
    
    /**
     * Returns the default options as an array.
     * 
     * @return array
     */
    public static function getDefaultOptions()
    {
        
        return array(
                    self::OPT_PORT => 43,
                    self::OPT_BLOCKING => true,
                    self::OPT_IO_TIMEOUT => intval(ini_get('default_socket_timeout')),
                    self::OPT_CONNECTION_TIMEOUT => intval(ini_get('default_socket_timeout')),
                    self::OPT_QUICK => false
                );
    } // public static function getDefaultOptions()
    
    /**
     * Returns an array of regex patterns to search for additional whois redirect
     * hosts in a primitive whois response.
     * 
     * @return array
     */
    public static function getDefaultWhoisRedirectPatterns()
    {
        return self::$_defaultWhoisRedirectPatterns;
    }
    
    /**
     * Constructor. 
     */
    public function __construct()
    {
        $this->_ipWhois = self::getDefaultIPWhois();
        $this->_options = self::getDefaultOptions();
        $this->_whoisRedirectPatterns = self::getDefaultWhoisRedirectPatterns();
    }
    
    /**
     * Returns an array of options for the whois client.
     * 
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
        
    }
    
    /**
     * Sets the options of the whois client.
     * 
     * @param   array   $options
     * @return  Parsonline_Network_Whois 
     */
    public function setOptions(array $options)
    {
        $this->_options = $options;
        return $this;
    }
    
    /**
     * Returns a list of whois servers to use as redirection whois servers for ARIN.NET
     * whois responses, if no redirection signature is found.
     * 
     * @return array
     */
    public function getIPWhois()
    {
        return $this->_ipWhois;
    }
    
    /**
     * Sets the list of whois servers to use as redirection whois servers for ARIN.NET
     * whois responses, if no redirection signature is found.
     * 
     * @param   array   $servers
     * @return  Parsonline_Network_Whois 
     */
    public function setIPWhois(array $servers)
    {
        $this->_ipWhois = $servers;
        return $this;
    }
    
    
    /**
     * Searches a whois response data for additional whois server references,
     * to fetch contact information from regional whois servers, if any.
     * 
     * @param   string  $data       whois response data
     * @param   string  $hostname   hostname of the whois server that returned the data
     * @return  string|null
     */
    public function findRedirectWhoisServer($data, $hostname=null)
    {
        $whoisHost = null;
        /*
         * normally the response might refere to another whois server.
         * here we search for additional references to other whois servers.
         */
        foreach($this->_whoisRedirectPatterns as $pattern) {
            $matches = array();
            if ( preg_match($pattern, $data, $matches) && count($matches) > 1) {
                return trim($matches[1]);
            }
        }
        
        // did not find a Whois server reference signature. 
        foreach($this->_ipWhois as $whois) {
            if ( strpos($data, $whois) !== false ) {
                $whoisHost = $whois;
                break;
            }
        }
        return $whoisHost;
    } // public function findRedirectWhoisServer()
    
    /**
     * Chooses an appropriate whois server to query the whois information
     * of the domain.
     * Returns the string for whois server address, or null if failed to
     * detect a valid whois server.
     * 
     * @param   string  $domain
     * @return  string|null
     */
    public function chooseServer($domain)
    {
        $_domain = strtoupper($domain);
        if ( strlen($_domain) > 6 && substr($_domain, -6) == '-NORID' ) {
            return self::NORIDHOST;
        }
        $parts = explode('.', $_domain);
        if (count($parts) < 2 ) {
            return null;
        }
        $tld = array_pop($parts);
        unset($parts);
        if ( is_numeric($tld) ) {
            return self::ANICHOST;
        }
        return $tld . self::QNICHOST_TAIL;
    } // public function chooseServer()
    
    /**
     * Queries the specified whois server and returns the result.
     * Accepts an associative array of options to configure the operation.
     * 
     * Options:
     *      socket (array): Associative array of options for TCP socket
     *          timeout (int): general timeout
     *          connection_timeout (int): timeout of connection stablishment
     *          io_timeout (int): timeout of IO on socket
     *          blocking (bool): set socket on blocking mode or not
     *          port (int): overwrite object whois port
     * 
     * @param   string  $query      whois query string
     * @param   string  $host       whois server host
     * @param   array   $options    array of options
     * @return  string
     */
    public function queryWhoisServer($query, $host, array $options=array())
    {
        $_options = array_merge_recursive($this->_options, $options);
        $socketOptions = array();
        if ( isset($_options[self::OPT_SOCKET]) && is_array($_options[self::OPT_SOCKET]) ) {
            $socketOptions = $_options[self::OPT_SOCKET];
        }
        $_host = strtolower($host);
        $sock = $this->_initSocket($_host, $socketOptions);
        unset($socketOptions);
        
        if ( $_host == self::GERMNICHOST ) {
            fwrite($sock, "-T dn.ace -C US-ASCII {$query}\r\n");
        } else {
            fwrite($sock, $query . "\r\n");
        }
        $buff = array();
        $packet = false;
        while ( !feof($sock) ) {
            $packet = fread($sock, 8192);
            if ($packet === false) {
                break;
            }
            $buff[] = $packet;
        }
        fclose($sock);
        $response = implode('', $buff);
        unset($buff, $sock, $packet);
        
        $nextWhoisServer = null;
        if ( !isset($_options[self::OPT_QUICK]) || !$_options[self::OPT_QUICK] ) {
            $nextWhoisServer = $this->findRedirectWhoisServer($response, $host);
        }
        
        if ($nextWhoisServer) {
            $response .= $this->queryWhoisServer($query, $nextWhoisServer, $_options);
        }
        return $response;
    } // public function queryWhoisServer()
    
    /**
     * Creates and returns a socket resource ready to communicate with
     * the specified host.
     * 
     * @param   string  $hostname
     * @param   array   $options        socket options
     * @return  resource
     * @throws  Parsonline_Exception_IOException on failure to stablish connection
     */
    protected function _initSocket($host, array $options=array())
    {
        $host = trim($host);
        $errCode = null;
        $errStr = '';
        
        $_defaultOptions = $this->getOptions();
        $options = array_merge($_defaultOptions, $options);
        
        
        $connectionTimeout = $options[self::OPT_CONNECTION_TIMEOUT];
        $ioTimeout = $options[self::OPT_CONNECTION_TIMEOUT];
        $port = $options[self::OPT_PORT];
        $sock = fsockopen($host, $port, $errCode, $errStr, $connectionTimeout);
        if (!$sock) {
            /**
             * @uses     Parsonline_Exception_IOException
             */
            require_once('Parsonline/Exception/IOException.php');
            throw new Parsonline_Exception_IOException("failed to open TCP connection to '{$host}:{$port}'. Error '$errCode': '$errStr'", $errCode);
        }
        
        socket_set_timeout($sock, $ioTimeout);
        
        $blocking = (isset($options[self::OPT_BLOCKING]) ? ((bool) $options[self::OPT_BLOCKING]) : true);
        stream_set_blocking($sock, intval($blocking));
        
        return $sock;
    } // protected function _initSocket()
    
    /**
     * Lookup a whois query. The query would be modified by the specified options
     * as an associative array. Options are
     *      
     *      quick (bool):       if should not lookup regional whois server for more info
     *      server (string):    specify the whois server
     * 
     * @param   string  $query
     * @param   array   $options
     * @return  string
     */
    public function whois($query, array $options=array())
    {
        $_options = array_merge($this->_options, $options);
        
        $server = (isset($_options[self::OPT_SERVER]) && $_options[self::OPT_SERVER]) ? strval($_options[self::OPT_SERVER]) : null;
        $country = (isset($_options[self::OPT_COUNTRY]) && $_options[self::OPT_COUNTRY]) ? strval($_options[self::OPT_COUNTRY]) : null;
        
        if (!$server && !$country) {
            $server = $this->chooseServer($query);
        } elseif ($country) {
            $server = $country . self::QNICHOST_TAIL;
        }
        
        return $this->queryWhoisServer($query, $server, $options);
    } // public function whois()
}