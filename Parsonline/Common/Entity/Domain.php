<?php
//Parsonline/Common/Entity/Domain.php
/**
 * Defines the Parsonline_Common_Entity_Domain class.
 *
 * Parsonline
 *
 * Copyright (c) 2011-2012 ParsOnline, Inc. (www.parsonline.com)
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
 * @copyright  Copyright (c) 2011-2012 ParsOnline, Inc. (www.parsonline.com)
 * @license    Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Parsonline_Common
 * @subpackage  Entity
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.0.5 2012-03-11
 */

/**
 * @uses    Parsonline_Common_Entity_Host
 * @uses    Parsonline_Common_Entity_Host_MailServer
 * @uses    Parsonline_Common_Entity_Host_NameServer
 * @uses    Parsonline_Network_Whois
 * @uses    Parsonline_Parser_Whois
 * @uses    Net_DNS_Resolver
 */
require_once('Parsonline/Common/Entity/Host.php');
require_once('Parsonline/Common/Entity/Host/MailServer.php');
require_once('Parsonline/Common/Entity/Host/NameServer.php');
require_once('Parsonline/Network/Whois.php');
require_once('Parsonline/Parser/Whois.php');
require_once('Net/DNS/Resolver.php');

/**
 * Parsonline_Common_Entity_Domain
 * 
 * Represents a domain name.
 */
class Parsonline_Common_Entity_Domain
{
    const SORT_NAME = 'name';

    const LIST_ALL = 'all';
    const LIST_FIRST = 'first';
    const LIST_LAST = 'last';

    /**
     * the DNS resolver object assigned by default to newly created domains
     *
     * @staticvar   Net_DNS_Resolver
     */
    protected static $_defaultDNSResolver = null;

    /**
     * the Whois client object assigned to newly created domains by default
     *
     * @staticvar   Parsonline_Network_Whois
     */
    protected static $_defaultWhoisClient = null;

    /**
     * the Whois parser object assigned to newly created domains by default
     * 
     * @staticvar   Parsonline_Parser_Whois
     */
    protected static $_defaultWhoisParser = null;
    
    /**
     * name of the domain
     * 
     * @var string
     */
    protected $_name = null;
    
    /**
     * extracted information from the whois server output
     * 
     * @var array
     */
    protected $_whoisInfo = array();
    
    /**
     * the list of status strings of the domain.
     * 
     * @var array
     */
    protected $_statuses = array();
    
    /**
     * name servers of the domain.
     * 
     * @var array   array of Parsonline_Common_Entity_Host_NameServer
     */
    protected $_nameServers = array();
    
    /**
     * mail servers of the domain.
     * 
     * @var array   array of Parsonline_Common_Entity_Host_MailServer
     */
    protected $_mailServers = array();
    
    /**
     * Root record host of the domain.
     *
     * @var Parsonline_Common_Entity_Host
     */
    protected $_rootHost = null;
    
    /**
     * SPF Policy string of the domain
     * 
     * @var string
     */
    protected $_spf = null;
    
    /**
     * timestamp of the time that the domain will expire
     *
     * @var int
     */
    protected $_expirationTime = null;
    
    /**
     * timestamp of the time when the domain was updated for the last time
     * 
     * @var int
     */
    protected $_lastUpdateTime = null;
    
    /**
     * if the root record of the domain is DNS resolvable
     *
     * @var bool
     */
    protected $_dnsResolvable = null;
    
    /**
     * the DNS resolver object used to resolve the domain hosts
     * 
     * @var Net_Dns_Resolver
     */
    protected $_dnsResolver = null;
    
    /**
     * the whois client that queries the whois information
     * 
     * @var Parsonline_Network_Whois
     */
    protected $_whoisClient = null;
    
    /**
     * the whos parser that parses the output of whois servers to extract information
     * 
     * @var Parsonline_Parser_Whois
     */
    protected $_whoisParser = null;

    /**
     * returns the DNS resolver object assigned by default to newly created domains
     *
     * @return  Net_DNS_Resolver
     */
    public static function getDefaultDNSResolver()
    {
        return self::$_defaultDNSResolver;
    }

    /**
     * sets the DNS resolver object assigned by default to newly created domains
     *
     * @param   Net_DNS_Resolver    $resolver
     */
    public static function setDefaultDNSResolver(Net_DNS_Resolver $resolver)
    {
        self::$_defaultDNSResolver = $resolver;
    }

    /**
     * Returns the Whois client object assigned to newly created domains by default
     *
     * @return  Parsonline_Network_Whois
     */
    public static function getDefaultWhoisClient()
    {
        return self::$_defaultWhoisClient;
    }

    /**
     * sets the Whois client object assigned to newly created domains by default
     *
     * @param   Parsonline_Network_Whois    $whois
     */
    public static function setDefaultWhoisClient(Parsonline_Network_Whois $whois)
    {
        self::$_defaultWhoisClient = $whois;
    }
    
    /**
     * returns the Whois parser object assigned to newly created domains by default
     *
     * @return  Parsonline_Parser_Whois
     */
    public static function getDefaultWhoisParser()
    {
        return self::$_defaultWhoisParser;
    }

    /**
     * sets the Whois parser object assigned to newly created domains by default
     *
     * @param   Parsonline_Parser_Whois    $parser
     */
    public static function setDefaultWhoisParser(Parsonline_Parser_Whois $parser)
    {
        self::$_defaultWhoisParser = $parser;
    }

    /**
     * defines a domain object to represent an internet domain.
     *
     * @param   string  $name
     */
    public function __construct($name)
    {
        $this->setName($name);
        if (self::getDefaultDNSResolver()) $this->setDNSResolver(self::getDefaultDNSResolver());
        if (self::getDefaultWhoisClient()) $this->setWhoisClient(self::getDefaultWhoisClient());
        if (self::getDefaultWhoisParser()) $this->setWhoisParser(self::getDefaultWhoisParser());
    }

    /**
     * returns the name of the domain
     * @return  string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * sets the name of the doamin. domain name is validated and then set.
     * 
     * @param   string  $name
     * @return  Parsonline_Common_Entity_Domain    object self reference
     */
    public function setName($name)
    {
        $name = strval($name);
        $this->_name = $name;
        return $this;
    }

    /**
     * returns the list of status codes (or description) of the domain.
     * by default returns all statuses assigned as an array.
     * this could be changed by passing the mode argument. use LIST_* constants.
     * returns null if no status is available.
     *
     * @param   string  $mode
     * @return  array|string|null
     * @throws  Parsonline_Exception_ValueException on invalid mode
     */
    public function getStatuses($mode=self::LIST_ALL)
    {
        switch($mode) {
            case self::LIST_ALL:
                return $this->_statuses;
            case self::LIST_LAST:
                return empty($this->_statuses) ? null : $this->_statuses[ count($this->_statuses) - 1 ];
            case self::LIST_FIRST:
                return empty($this->_statuses) ? null : $this->_statuses[0];
            default:
                /**
                 * @uses    Parsonline_Exception_ValueException 
                 */
                require_once('Parsonline/Exception/ValueException.php');
                throw new Parsonline_Exception_ValueException("invalid mode '{$mode}'");
        }
    }

    /**
     * Adds a status the list of statuses of the domain
     *
     * @param   array|string    $stats
     * @return  Parsonline_Common_Entity_Domain
     */
    public function addStatuses($stats)
    {
        if (!is_array($stats)) $stats = array($stats);
        foreach ($stats as $s) {
            array_push($this->_statuses, strval($s));
        }
        return $this;
    }

    /**
     * Sets the statuses codes of the domain
     *
     * @param   array|int               $statuses
     * @return  Parsonline_Common_Entity_Domain
     */
    public function setStatuses($statuses=array())
    {
        if (!is_array($statuses)) $statuses = array(strval($statuses));
        $this->_statuses = $statuses;
        return $this;
    }

    /**
     * Assignes an array of hosts to some server list.
     *
     * @param   array                                   &$taget     array to set the hosts to
     * @param   array|Parsonline_Common_Entity_Host     $hosts      array of Parsonline_Common_Entity_Host objects, or a single instance of Parsonline_Common_Entity_Host
     * @param   bool                                    $setDomain  update the domain of the hosts with current object
     * @param   string                                  $class      class name that all objects should extend
     * @return  array       new list of updated servers
     * @throws  Parsonline_Exception_ValueException on invalid host values
     */
    protected function _setHosts(&$target, $hosts, $setDomain=true, $class='Parsonline_Common_Entity_Host')
    {
        if (!is_array($target)) $target = array($target);
        if ($hosts !== null) {
            if (!is_array($hosts)) $hosts = array($hosts);
            foreach($hosts as $server) {
                if (!is_object($server) || !is_a($server,$class) ) {
                    /**
                    * @uses    Parsonline_Exception_ValueException 
                    */
                    require_once('Parsonline/Exception/ValueException.php');
                    throw new Parsonline_Exception_ValueException("Invalid hosts list. hosts should contain only '{$class}' objects");
                }
            }
            $target = $hosts;
        } else {
            $target = array();
        }
        
        if ($setDomain) {
            foreach($target as $t) {
                $t->setDomain($this);
            }
            
        }
        
        return $target;
    } // protected function _setHosts()

    /**
     * Adds an array of hosts to a host list.
     * 
     * Note: this method does not update the current host list, but returns a
     * new list with the new hosts added to it.
     * 
     * @param   array   $target                         array of hosts that the new hosts will be added to
     * @param   array|Parsonline_Common_Entity_Host     $hosts      array of Parsonline_Common_Entity_Host objects, or a single instance of Parsonline_Common_Entity_Host
     * @param   bool                                    $setDomain  update the new hosts by setting their domain to current domain object
     * @return  array   new list of updated servers
     * @throws  Parsonline_Exception_ValueException on invalid server values on the list
     */
    protected function _addHosts($target, $hosts, $setDomain=true, $class='Parsonline_Common_Entity_Host')
    {
        if (!is_array($target)) $target = array($target);
        if ($hosts !== null) {
            if (!is_array($hosts)) $hosts = array($hosts);
            foreach($hosts as $server) {
                if (!is_object($server) || !is_a($server, $class) ) {
                    /**
                    * @uses    Parsonline_Exception_ValueException 
                    */
                    require_once('Parsonline/Exception/ValueException.php');
                    throw new Parsonline_Exception_ValueException("Invalid host list. hosts should contain only '{$class}' objects");
                }
            }
            
            if ($setDomain) {
                foreach($hosts as $host) {
                    $host->setDomain($this);
                }
            }
            $target = array_merge($target, $hosts);
        }
        return $target;
    }
    
    /**
     * Returns an array of NS servers of the domain.
     * each NS server is a host object.
     *
     * @param   string  $mode   use class constants LIST_*
     * @return  array|Parsonline_Common_Entity_Host_NameServer|null   indexed array of Parsonline_Common_Entity_Host_NameServer objects
     */
    public function getNameServers($mode=self::LIST_ALL)
    {
        switch ($mode) {
            case self::LIST_ALL:
                return $this->_nameServers;
            case self::LIST_LAST:
                return empty($this->_nameServers) ? null : $this->_nameServers[ count($this->_nameServers) - 1 ];
            case self::LIST_FIRST:
                return empty($this->_nameServers) ? null : $this->_nameServers[0];
            default:
                /**
                * @uses    Parsonline_Exception_ValueException 
                */
                require_once('Parsonline/Exception/ValueException.php');
                throw new Parsonline_Exception_ValueException("Invalid mode '{$mode}'");
        }
    }

    /**
     * Sets the array of name servers of the domain. each NS is a host object.
     * Accepts indexed array of Parsonline_Common_Entity_Host objects,
     * or a single instance of Parsonline_Common_Entity_Host.
     * 
     * @param   array|Parsonline_Common_Entity_Host_NameServer  $nServers
     * @return  Parsonline_Common_Entity_Domain
     * @throws  Parsonline_Exception_ValueException
     */
    public function setNameServers($nServers=null)
    {
        $this->_nameServers = $this->_setHosts($this->_nameServers, $nServers, true, 'Parsonline_Common_Entity_Host_NameServer');
        return $this;
    }

    /**
     * Adds an array of hosts to the list of name servers of the domain.
     * Each NS is a Parsonline_Common_Entity_Host_NameServevr object.
     * Accepts indexed array of Parsonline_Common_Entity_Host_NameServer objects,
     * or a single instance of Parsonline_Common_Entity_Host_NameServer.
     * 
     * @param   array|Parsonline_Common_Entity_Host_NameServer      $nServers
     * @return  Parsonline_Common_Entity_Domain
     * @throws  Parsonline_Exception_ValueException
     */
    public function addNameServers($nServers)
    {
        $this->_nameServers = $this->_addHosts($this->_nameServers, $nServers, true, 'Parsonline_Common_Entity_Host_NameServer');
        return $this;
    }

    /**
     * Returns an array of MX records (Mail servers) of the domain.
     * each server is a Parsonline_Common_Entity_Host object.
     * Returns indexed array of Parsonline_Common_Entity_Host_MailServer objects,
     * null or a Parsonline_Common_Entity_Host_MailServer.
     * 
     * @param   string  $mode
     * @return  array|Parsonline_Common_Entity_Host_MailServer|null
     * @throws  Parsonline_Exception_ValueException
     */
    public function getMailServers($mode=self::LIST_ALL)
    {
        switch ($mode) {
            case self::LIST_ALL:
                return $this->_mailServers;
            case self::LIST_LAST:
                return empty($this->_mailServers) ? null : $this->_mailServers[ count($this->_mailServers) - 1 ];
            case self::LIST_FIRST:
                return empty($this->_mailServers) ? null : $this->_mailServers[0];
            default:
                /**
                * @uses    Parsonline_Exception_ValueException 
                */
                require_once('Parsonline/Exception/ValueException.php');
                throw new Parsonline_Exception_ValueException("Invalid mode '{$mode}'");
        }
    }

    /**
     * Sets the array of MX records (Mail servers) of the domain.
     * each server is a Parsonline_Common_Entity_Host object.
     * Accepts indexed array of Parsonline_Common_Entity_Host objects, or a single instance of Parsonline_Common_Entity_Host.
     * 
     * @param   array|Parsonline_Common_Entity_Host_MailServer  $hosts
     * @return  Parsonline_Common_Entity_Domain
     * @throws  Parsonline_Exception_ValueException
     */
    public function setMailServers($hosts)
    {
        $mailServers = $this->_setHosts($this->_mailServers, $hosts, true, 'Parsonline_Common_Entity_Host_MailServer');
        $mailServer = new Parsonline_Common_Entity_Host_MailServer('localhost');
        $this->_mailServers = $mailServer->sort($mailServers, Parsonline_Common_Entity_Host_MailServer::SORT_PRIORITY);
        return $this;
    }

    /**
     * Adds an array of hosts to the list of mail servers of the domain.
     * Each mail server is a Parsonline_Common_Entity_Host_MailServer object.
     *
     * @param   array|Parsonline_Common_Entity_Host      $hosts     array of Parsonline_Common_Entity_Host objects, or a single instance of Parsonline_Common_Entity_Host
     * @param   bool                                    $autoSort   sort domain mail servers based on their priority
     * @return  Parsonline_Common_Entity_Domain
     * @throws  Exception
     */
    public function addMailServers($hosts, $autoSort=true)
    {
        $hosts = $this->_addHosts($this->_mailServers, $hosts, true, 'Parsonline_Common_Entity_Host_MailServer');
        if ($autoSort) {
            /**
             * @uses    Parsonline_Common_Entity_Host_MailServer
             */
            $mailServer = new Parsonline_Common_Entity_Host_MailServer();
            $hosts = $mailServer->sort($hosts, Parsonline_Common_Entity_Host_MailServer::SORT_PRIORITY);
            unset($mailServer);
        }
        $this->_mailServers = $hosts;
        return $this;
    }

    /**
     * Returns the root host of the domain that usually serves the web
     * 
     * @return  Parsonline_Common_Entity_Host
     */
    public function getRootHost($autoInsanciate=true)
    {
        if (!$this->_rootHost && $autoInsanciate) {
            $rootServer = new Parsonline_Common_Entity_Host();
            if ($this->getName()) $rootServer->setHostname($this->getName());
            $rootServer->setDomain($this);
            $this->_rootHost = $rootServer;
        }
        return $this->_rootHost;
    }

    /**
     * Sets the root host of the domain that usually serves the web
     * 
     * @param   Parsonline_Common_Entity_Host|null  $host
     * @return  Parsonline_Common_Entity_Domain    object self reference
     */
    public function setRootRecord(Parsonline_Common_Entity_Host $host)
    {
        if ($host && !$host->getHostname()) {
            /**
            * @uses    Parsonline_Exception_ValueException 
            */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("domain root host should have a hostname");
        }
        $this->_rootHost = $host;
        return $this;
    }

    /**
     * Returns the SPF policy of the domain if available. if no SPF is found,
     * fetches it by querying a DNS request of type 'TXT'.
     *
     * @param   bool    $realtime   ignore on memory state and query a DNS request now. default is false
     * @return  string
     * @throws  Parsonline_Exception_ContextException on no domain name/resolver set yet
     */
    public function getSPF($realtime=false)
    {
        if ( $realtime || $this->_spf === null ) {
            $name = $this->getName();
            if (!$name) {
                /**
                * @uses Parsonline_Exception_ContextException
                */
                require_once('Parsonline/Exception/ContextException.php');
                throw new Parsonline_Exception_ContextException("failed to query for domain SPF. the domain has no name yet");
            }
            /**
             * @uses    Net_DNS_Resolver
             */
            $dns = $this->getDNSResolver();
            if (!$dns) {
                /**
                * @uses Parsonline_Exception_ContextException
                */
                require_once('Parsonline/Exception/ContextException.php');
                throw new Parsonline_Exception_ContextException("no DNS resolver object is provided to the domain yet");
            }
            $results = $dns->query($name, 'TXT');
            $this->_spf = '';
            if ( $results && isset($results->answer) && is_array($results->answer) ) {
                $answer = array_pop($results->answer);
                if ( isset($answer->text) ) {
                    $this->_spf = is_array($answer->text) ? implode(', ', $answer->text) : strval($answer->text);
                }
            }
        }
        return $this->_spf;
    }

    /**
     * Sets the SPF of the domain.
     *
     * @param   string  $spf
     * @return  Parsonline_Common_Entity_Domain    object self reference
     */
    public function setSPF($spf='')
    {
        $this->_spf = strval($spf);
        return $this;
    }

    /**
     * Returns the expiration time of the domain
     *
     * @return  int
     */
    public function getExpirationTime()
    {
        return $this->_expirationTime;
    }

    /**
     * Sets the expiration time of the domain. If null is passed,
     * uses current time.
     * 
     * @param   int     $time
     * @return  Parsonline_Common_Entity_Domain    object self reference
     */
    public function setExpirationTime($time=null)
    {
        $this->_expirationTime = is_null($time) ? time() : intval($time);
        return $this;
    }

    /**
     * Returns the last update time of the domain
     *
     * @return  int
     */
    public function getLastUpdateTime()
    {
        return $this->_lastUpdateTime;
    }

    /**
     * Sets the last update time of the domain. If null is passed,
     * uses current time.
     *
     * @param   int     $time
     * @return  Parsonline_Common_Entity_Domain    object self reference
     */
    public function setLastUpdateTime($time=null)
    {
        $this->_lastUpdateTime = is_null($time) ? time() : intval($time);
        return $this;
    }
    
    /**
     * Compares 2 domain objects based on their names.returns 1 if the first
     * param is sorted after the second, -1 if the first is sorted before the
     * second param, and 0 if there is no difference while sorting.
     * 
     * Note: this is used for sorting arrays of domains.
     *
     * @param   Parsonline_Common_Entity_Domain     $d1
     * @param   Parsonline_Common_Entity_Domain     $d2
     * @return  int
     */
    public function _compareDomainsByName($d1, $d2)
    {
        return strnatcasecmp($d1->getName(), $d2->getName());
    }
    
    /**
     * Sorts an array of Parsonline_Common_Entity_Domain objects and returns the sorted array.
     * 
     * @param   array   $domains    array of Parsonline_Common_Entity_Domain objects
     * @param   string  $by       the property to compare entities by. use SORT_* constants.
     * @return  array   array of sorted objects
     * @throws  Parsonline_Exception on failed to sort the array
     *          Parsonline_Exception_ValueException on invalid property
     */
    public function sort(array $domains, $by=self::SORT_NAME)
    {
        $compareMethod = '_compareHostsBy' . ucfirst($by);
        if ( !method_exists($this, $compareMethod) ) {
            /**
                * @uses  Parsonline_Exception_ValueException
                */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("Invalid sort property '{$by}'");
        }
        
        if (!usort($domains, array($this, $compareMethod))) {
            /**
             * @uses  Parsonline_Exception
             */
            require_once('Parsonline/Exception.php');
            throw new Parsonline_Exception("failed to sort the domain array");
        }
        return $domains;
    } // public function sort()

    /**
     * Returns an array representaion of the object. each index is a string by default.
     *
     * keys are:
     * name, statuses (array), spf, expirationTime (int), lastUpdateTime (int),
     * nameServers (string <hostname [ip]> if name server failed to resolve domain string <hostname [ip](!)>
     * mailServers (string <hostname [ip]> if mail server failed to respond to SMTP string <hostname [ip](!)>
     * rootRecord (string [ hostname ip], ip, pingable (bool), resolvable (bool)
     * 
     * @return array
     */
    public function __toArray()
    {
        $result = array();
        $result['name'] = $this->_name;
        $result['statuses'] = $this->_statuses;
        $result['spf'] = $this->_spf;
        $result['expiration_time'] = $this->_expirationTime;
        $result['last_update_time'] = $this->_lastUpdateTime;
        if ($this->_nameServers) {
            $nameServers = array();
            $counter = 1;
            foreach($this->_nameServers as $server) {
                $hostname = $server->getHostname();
                $ip = $server->getIPAddress();
                $resolvesDomain = $server->doesResolveDomainRootRecord();
                $result["ns{$counter}"] = $hostname;
                $result["ns{$counter}_ip"] = $ip;
                $result["ns{$counter}_resolvable"] = $resolvesDomain;
                $result["ns{$counter}_reverse"] = $server->reverseDNSResolve(false);
                $ns = sprintf('<%s [%s]%s>', $hostname, $ip, ($resolvesDomain ? '' : '(!)'));
                array_push($nameServers, $ns);
                $counter++;
            }
            $result['name_servers'] = implode(':', $nameServers);
            unset($nameServers, $ns, $counter, $ip, $hostname, $resolvesDomain);
        } else {
            $result['names_servers'] = null;
        }
        if ($this->_mailServers) {
            $mailServers = array();
            $counter = 1;
            foreach($this->_mailServers as $server) {
                $hostname = $server->getHostname();
                $ip = $server->getIPAddress();
                $smtpConnectable = $server->respondsToSMTP();
                $result["mx{$counter}"] = $hostname;
                $result["mx{$counter}_ip"] = $ip;
                $result["mx{$counter}_smtp"] = $smtpConnectable;
                $result["mx{$counter}_reverse"] = $server->reverseDNSResolve(false);
                $ms = sprintf('<%s [%s]%s>', $hostname, $ip, ($smtpConnectable ? '' : '(!)'));
                array_push($mailServers, $ms);
                $counter++;
            }
            $result['mail_servers'] = implode(':', $mailServers);
            unset($mailServers, $ms, $counter, $hostname, $ip, $smtpConnectable);
        } else {
            $result['mail_servers'] = null;
        }
        $result['root_host'] = ($this->_rootHost ?
                                        sprintf('%s [%s]', $this->_rootHost->getHostname(), $this->_rootHost->getIPAddress()) :
                                        null
                                );
        $result['resolvable'] = $this->isDNSResolvable();
        if ($this->_rootHost) {
            $result['ip'] = $this->_rootHost->getIPAddress();
            $result['pingable'] = $this->_rootHost->isPingable();
            $result['reverse_hostname'] = $this->_rootHost->reverseDNSResolve(false);
        } else {
            $result['ip'] = null;
            $result['pingable'] = null;
            $result['reverse_hostname'] = '';
        }
        
        return $result;
    }

    /**
     * Returns a string representation of the object in a comma separated values
     * format.
     *
     * @return  string
     */
    public function __toString()
    {
        $baseArray = $this->__toArray();
        $resultArray = array();
        foreach($baseArray as $data) {
            if (is_array($data)) {
                $data = implode(':', $data);
            } elseif (is_bool($data)) {
                $data = $data ? 'yes' : 'no';
            } else {
                $data = strval($data);
            }
            array_push($resultArray, $data);
        }
        return implode(',', $resultArray);
    }

    /**
     * Returns the object that is used to resolve DNS queries.
     *
     * @return  Net_DNS_Resolver
     * @uses    Net_DNS_Resolver
     * @see     http://pear.php.net/manual/en/package.networking.net-dns.php
     * @throws  Exception
     */
    public function getDNSResolver()
    {
        return $this->_dnsResolver;
    }

    /**
     * Sets the object that is used to resolve DNS queries.
     *
     * @param   Net_DNS_Resolver        $resolver   a DNS resolver object with a query method
     * @param   bool                    $addNameServers add domain name servers to list of NS of the resolver
     * @return  Parsonline_Common_Entity_Domain    object self reference
     * @throws  Exception
     */
    public function setDNSResolver(Net_DNS_Resolver $resolver, $addNameServers=true)
    {
        if ($addNameServers) {
            $nameServers = $this->getNameServers();
            if ($nameServers) {
                $host = current($nameServers);
                /*@var $host Parsonline_Common_Entity_Host_NameServer */
                $nameServers = current($host->getHostsIPv4Addresses($nameServers));
                
                if ($nameServers) {
                    $nservers = $resolver->nameservers;
                    if (!$nservers) $nservers = array();
                    $resolver->nameservers = array_unique(array_merge($nameServers, $nservers));
                }
            }
        }
        
        $this->_dnsResolver = $resolver;
        return $this;
    } // public function setDNSResolver()

    /**
     * Returns the whois client object. the object has a query method
     * to query the whois info.
     * 
     * @param   bool        $autoInstanciate
     * @return  Parsonline_Network_Whois
     * @uses    Parsonline_Network_Whois
     */
    public function getWhoisClient($autoInstanciate=true)
    {
        if (!$this->_whoisClient && $autoInstanciate) {
            $this->_whoisClient = new Parsonline_Network_Whois();
        }
        return $this->_whoisClient;
    }

    /**
     * sets the object that is used to perform whois queries
     *
     * @param   Parsonline_Network_Whois               $client     a whois client object with a query method
     * @return  Parsonline_Common_Entity_Domain    object self reference
     * @throws  Exception
     */
    public function setWhoisClient(Parsonline_Network_Whois $client)
    {
        $this->_whoisClient = $client;
        return $this;
    }

    /**
     * Returns the whois parser. the object hast parse* methods to parse
     * different information from output of a whois response.
     * 
     * @param   bool        $autoInstanciate    if should create a new whois parser if none exists
     * @return  Parsonline_Parser_Whois
     */
    public function getWhoisParser($autoInstanciate=true)
    {
        if (!$this->_whoisParser && $autoInstanciate) {
            $this->_whoisParser = new Parsonline_Parser_Whois();
        }
        return $this->_whoisParser;
    }

    /**
     * sets the whois parser object. the object hast parse* methods to parse
     * different information from output of a whois server.
     *
     * @param   Parsonline_Parser_Whois     $parser     a whois parser object
     * @return  Parsonline_Common_Entity_Domain        object self reference
     * @throws  Exception
     */
    public function setWhoisParser(Parsonline_Parser_Whois $parser)
    {
        $this->_whoisParser = $parser;
        return $this;
    }

    /**
     * Returns an array of keys to access whois info.
     * 
     * @return  array
     */
    public function getWhoisInfoKeys()
    {
        return array('lastUpdateTime', 'expirationTime','statuses','nameServers','adminContact','technicalContact', '_raw');
    }

    /**
     * Retrieves whois information for the domain.
     * Returns the extracted data as an associative array.
     * 
     * keys are:
     *  * lastUpdateTime
     *  * expirationTime
     *  * statuses
     *  * nameServers
     *  * _raw
     * 
     * if a value could not be parsed, returns as null.
     * 
     * @param   bool        $realtime               ignore on memory results and fetch new whois info
     * @return  array
     * @throws  Parsonline_Exception_ContextException on no domain name/whois client/parser available
     */
    public function getWhoisInfo($realtime=false)
    {
        $domainName = $this->_name;
        if (!$domainName) {
            /**
            * @uses Parsonline_Exception_ContextException
            */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException("failed to perform whois query. domain has no name");
        }
        
        if ( $realtime || !$this->_whoisInfo ) {
            $results = array();
            $whoisClient = $this->getWhoisClient();
            if (!$whoisClient) {
                /**
                * @uses Parsonline_Exception_ContextException
                */
                require_once('Parsonline/Exception/ContextException.php');
                throw new Parsonline_Exception_ContextException("no whois client object is provided for the domain object");
            }
            
            $whoisParser = $this->getWhoisParser();
            if (!$whoisParser) {
                /**
                * @uses Parsonline_Exception_ContextException
                */
                require_once('Parsonline/Exception/ContextException.php');
                throw new Parsonline_Exception_ContextException("no whois parser object is provided for the domain object");
            }
            
            $response = $whoisClient->whois($domainName);
            $whoisInfo = $whoisParser->parseAll($response);
            $whoisInfo;
            $keys = $this->getWhoisInfoKeys();
            foreach($keys as $key) {
                $results[$key] = array_key_exists($key, $whoisInfo) ? $whoisInfo[$key] : null;
            }
            $results['_raw'] = $response;
            $this->_whoisInfo = $results;
        } else {
            $results = $this->_whoisInfo;
        }
        
        return $results;
    } // public function getWhoisInfo()

    /**
     * Checks if the root host of the domain resolvable using DNS.
     *
     * @param   bool            $realtime                   regardless of previouse on memory status, try to resolve the host
     * @param   array           $nameServers                an array of name servers to query from
     * @param   bool            $autoset                    automatically set the DNSresolvable status of the domain and its root record
     * @param   bool            $autoUpdateIpAddresses      automatically add the resolved IP addresses to the root record of the domain
     * @return  bool
     * @throws  Exception
     */
    public function isDNSResolvable($realtime=false, $nameServers=array(), $autoset=true, $autoUpdateIPAddresses=true)
    {
        if ( $realtime || is_null($this->_dnsResolvable) ) {
            $resolvable = false;
            $rootHost = $this->getRootHost();
            $ipList = $rootHost->resolveIPv4AddressesByDNSRequest($nameServers, $autoset, $autoUpdateIPAddresses);
            if ($ipList) {
                $resolvable = true;
            }
            if ($autoset) $this->setDNSResolvable($resolvable);
        } else {
            $resolvable = $this->_dnsResolvable;
        }
        return $resolvable;
    }

    /**
     * sets the root record of the domain object resolvable.
     *
     * @param   bool    $resolveable
     * @param   bool    $updateRootHost     automatically update the DNS resolvable status of the root record of domain
     * @return  Parsonline_Common_Entity_Domain    object self reference
     */
    public function setDNSResolvable($resolveable=true, $updateRootHost=true)
    {
        if ($updateRootHost) $this->getRootHost()->setDNSResolvable($resolveable);
        $this->_dnsResolvable = true && $resolveable;
        return $this;
    }
    
    /**
     * Resolves the IPv4 address of the root host of the domain by sending a DNS
     * request to nameservers.
     * Returns an array of IP addresses, otherwise returns false. automatically
     * updates the DNS resolvable status of the domain root host if not disabled.
     *
     * @param   array|string        $nameServers        additional name servers
     * @param   bool                $autoset            automatically set the DNSresolvable status of the root record of the domain
     * @param   bool                $autoAddIps         automatically add the resolved IP addresses to the root record of the domain
     * @return  array|false
     * @throws  Parsonline_Exception_ContextException on no DNS resolver available
     */
    public function resolveIPv4AddressesByDNSRequest($nameServers=array(), $autoset=true, $autoAddIps=true)
    {
        $rootHost = $this->getRootHost();
        
        $dnsResolver = $this->getDNSResolver();
        if (!$dnsResolver) {
            /**
            * @uses Parsonline_Exception_ContextException
            */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException("no DNS resolver is provided for the domain");
        }
        $rootHost->setDNSResolver($dnsResolver);
        $ipList = $rootHost->resolveIPv4AddressesByDNSRequest($nameServers, $autoset, $autoAddIps);
        if ($autoset) $this->setDNSResolvable( $rootHost->isDNSResolvable() );
        return $ipList;
    } // public function resolveIPv4AddressesByDNSRequest()

    /**
     * Resolves the IP address of the root host of domain by pinging the host.
     * Automatically updates the pingable status of the root server if not disabled.
     * Returns the IP address or false if failed to detect.
     *
     * @param   bool            $autoset        automatically set the pingable state of the root server. default is true
     * @param   bool            $autoAddIPs     automatically add the resolved IP addresses to the root server
     * @return  string|false
     * @throws  Exception
     */
    public function resolveIPv4AddressByPing($autoset=true, $autoAddIPs=true)
    {
        $rootHost = $this->getRootHost();
        $ipList = $rootHost->resolveIPv4AddressByPing($autoset, $autoAddIPs);
        if ($autoset) $this->setDNSResolvable( $rootHost->isDNSResolvable() );
        return $ipList;
    }

    /**
     * Resolves the IP address of the root host of the domain.
     * Resovle using DNS requests, if failed tries to ping the host and get
     * the IP address from there.
     * Returns an array of IP addresses, otherwise returns false.
     *
     * @param   array|string    $nameServers        additional name servers
     * @param   bool            $autset             if should automatically set the pingable/DNS resolvable statuses of the root record
     * @param   bool            $autoAddIPs         automatically add the resolved IP addresses to the object
     * @return  array|false
     */
    public function resolveIPv4Addresseses($nameServers=array(), $autoset=true, $autoAddIPs=true)
    {
        $rootHost = $this->getRootHost();
        
        $dnsResolver = $this->getDNSResolver();
        if (!$dnsResolver) {
            /**
            * @uses Parsonline_Exception_ContextException
            */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException("no DNS resolver is provided for the domain");
        }
        
        $rootHost->setDNSResolver($dnsResolver);
        $ipList = $rootHost->resolveIPv4Addresseses($nameServers, $autoset, $autoAddIPs);
        if ($autoset) $this->setDNSResolvable( $rootHost->isDNSResolvable() );
        return $ipList;
    }
}