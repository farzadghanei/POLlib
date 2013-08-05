<?php
//Parsonline/Common/Entity/Host.php
/**
 * Defines the Parsonline_Common_Entity_Host class.
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
 * @package     Parsonline_Common
 * @subpackage  Entity
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.0.6 2012-02-13
 */

/**
 * @uses    Parsonline_Exception_ValueException
 * @uses    Parsonline_Common_Entity_Domain
 * @uses    Net_DNS_Resolver
 * @uses    Net_Ping
 */
require_once('Net/DNS/Resolver.php');
require_once('Net/Ping.php');

/**
 * Parsonline_Common_Entity_Host
 * 
 * represents a host machine.
 */
class Parsonline_Common_Entity_Host
{
    const SORT_HOSTNAME = 'hostname';
    const SORT_IPV4 = 'IPv4';
    const SORT_IPV6 = 'IPv6';
    const SORT_PRIORITY = 'priority';

    const LIST_ALL = 'all';
    const LIST_FIRST = 'first';
    const LIST_LAST = 'last';
    
    /**
     * the ping object assigned by default to newly created hosts
     *
     * @staticvar   Net_Ping
     */
    protected static $_defaultPingRequester = null;

    /**
     * the DNS resolver object assigned by default to newly created hosts
     * 
     * @staticvar   Net_DNS_Resolver
     */
    protected static $_defaultDNSResolver = null;
    
    /**
     * the priority of the name server
     *
     * @var int
     */
    protected $_priority = null;
    
    /**
     * the domain inwhich the server resides
     * 
     * @var Parsonline_Common_Entity_Domain
     */
    protected $_domain = null;
    
    /**
     * hostname
     * 
     * @var string
     */
    protected $_hostname = null;
    
    /**
     * list of IPv4 addresses of the Server
     *
     * @var array
     */
    protected $_ipv4 = array();
    
    /**
     * list of IPv6 address of the Server
     *
     * @var array
     */
    protected $_ipv6 = array();

    /**
     * the result of reverse DNS lookup of the IP address of the host
     * 
     * @var string
     */
    protected $_reverseDNSResult = null;
    
    /**
     * if the host is pingable or not
     * 
     * @var bool
     */
    protected $_pingable = null;

    /**
     * if the hostname can be resolved from DNS
     *
     * @var bool
     */
    protected $_dnsResolvable = null;

    /**
     * if the host name can be resolved using reverse DNS queries from its IP
     * 
     * @var bool
     */
    protected $_reverseDNSResolvable = null;
    
    /**
     * Ping Requster callable
     * 
     * @var Net_Ping
     */
    protected $_pingRequester = null;
    
    /**
     * DNS resolver callable
     * 
     * @var Net_DNS_Resolver
     */
    protected $_dnsResolver = null;
    
    /**
     * Array of options. Each host type might define its own
     * options. Use OPT_* class constants as keys.
     *  
     * @var array
     */
    protected $_options = array();
    
    /**
     * Returns the DNS resolver assigned by default to newly created hosts
     * 
     * @return  Net_DNS_Resolver|null
     */
    public static function getDefaultDNSResolver()
    {
        return self::$_defaultDNSResolver;
    }

    /**
     * Sets the DNS resolver object assigned by default to newly created hosts
     *
     * @param   Net_DNS_Resolver    $resolver
     */
    public static function setDefaultDNSResolver(Net_DNS_Resolver $resolver)
    {
        self::$_defaultDNSResolver = $resolver;
    } // public static function setDefaultDNSResolver()
    
    /**
     * Returns the ping requester assigned by default to newly created hosts
     *
     * @return  Net_Ping|null
     */
    public static function getDefaultPingRequester()
    {
        return self::$_defaultPingRequester;
    }

    /**
     * Sets the ping requester assigned by default to newly created hosts
     *
     * @param   Net_Ping    $pinger
     */
    public static function setDefaultPingRequester(Net_Ping $pinger)
    {
        self::$_defaultPingRequester = $pinger;
    }

    /**
     * Constructor.
     *
     * @param   string          $hostname
     */
    public function __construct($hostname=null)
    {
        if (!is_null($hostname)) $this->_hostname = $hostname;
        
        $res = self::getDefaultDNSResolver();
        if ($res) {
            $this->setDNSResolver($res);
        }
        $req = self::getDefaultPingRequester();
        if ($req) {
            $this->setPingRequester($req);
        }
    } // public function __construct()
    
    /**
     * Returns the priority of the host. This is mostly used by
     * name/mail servers.
     * 
     * @return  int
     */
    public function getPriority()
    {
        return $this->_priority;
    }

    /**
     * sets the priority of the host.
     * This is mostly used by name/mail servers.
     * 
     * @param   int $priority
     * @return  Parsonline_Common_Entity_Host_NameServer    object self reference
     */
    public function setPriority($priority)
    {
        $this->_priority = intval($priority);
        return $this;
    }

    /**
     * Returns the DNS zone object that this host is assigend to.
     * By default if no zone is assigned to the host, a new instance of
     * Parsonline_Common_Entity_Domain is created and assigned automatically.
     * This causes dependance on Parsonline_Common_Entity_Domain.
     *
     * By default auto instanciates a Parsonline_Common_Entity_Domain object.
     * @see Parsonline_Common_Entity_Domain
     * 
     * @param   bool    $auto       if should create a domain object if none exists
     * @return  mixed
     */
    public function getDomain($autoInstanciate=true)
    {
        if (!$this->_domain && $autoInstanciate) {
            /**
             * @uses    Parsonline_Common_Entity_Domain 
             */
            require_once('Parsonline/Common/Entity/Domain.php');
            $this->_domain = new Parsonline_Common_Entity_Domain($this->_hostname);
        }
        return $this->_domain;
    }

    /**
     * Sets the DNS zone object that this host is assigend to
     *
     * @param   Parsonline_Common_Entity_Domain
     * @return  Parsonline_Common_Entity_Host    object self reference
     * @throws  Exception
     */
    public function setDomain($domain)
    {
        if ($domain !== null) {
            $this->_domain = $domain;
        } else {
            $this->_domain = null;
        }
        return $this;
    }

    /**
     * Returns the hostname
     * 
     * @return  string
     */
    public function getHostname()
    {
        return $this->_hostname;
    }

    /**
     * Sets the hostname.
     * 
     * @param   string                  $hostname
     * @return  Parsonline_Common_Entity_Host    object self reference
     */
    public function setHostname($hostname)
    {
        $this->_hostname = strval($hostname);
        return $this;
    }
    
    /**
     * Returns the specific options of the host object.
     * 
     * @return  array
     */
    public function getOptions()
    {
        return $this->_options;
    }
    
    /**
     * Returns a specific option set for the host object. Specify the target
     * option by using OPT_* class constants. If no such option is available
     * for the host object, throw exception.
     * 
     * @param   string  $key
     * @return  mixed
     * @throws  Parsonline_Exception_ValueException on invalid option key
     */
    public function getOption($key)
    {
        if (!isset($this->_options[$key])) {
            /**
            * @uses    Parsonline_Exception_ValueException 
            */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("invalid option name '{$key}'");
        }
        return $this->_options[$key];
    }
    
    /**
     * Sets the host specific options. each option has a specific key that
     * should be referenced by OPT_* class constants.
     * Setting invalid keys raises exceptions.
     * 
     * @param   string  $key
     * @param   mixed   $value
     * @return  Parsonline_Common_Entity_Host
     */
    public function setOption($key, $value)
    {
        if (!isset($this->_options[$key])) {
            /**
            * @uses    Parsonline_Exception_ValueException 
            */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("invalid option name '{$key}'");
        }
        $this->_options[$key] = $value;
        return $this;
    }

    /**
     * Returns the IPv4 of the host.
     * by default returns all IPs assigned as an array.
     * This could be changed by passing the mode argument. use LIST_* constants.
     * 
     * @see getIpv6Addresses()
     * 
     * @param   string  $mode   the return mode
     * @return  array|string|null
     * @throws  Parsonline_Exception_ValueException on invalid mode
     */
    public function getIPv4Addresses($mode=self::LIST_ALL)
    {
        switch ($mode) {
            case self::LIST_ALL:
                return $this->_ipv4;
            case self::LIST_LAST:
                return empty($this->_ipv4) ? null : $this->_ipv4[count($this->_ipv4) - 1];
            case self::LIST_FIRST:
                return empty($this->_ipv4) ? null : $this->_ipv4[0];
            default:
                /**
                 * @uses    Parsonline_Exception_ValueException 
                 */
                require_once('Parsonline/Exception/ValueException.php');
                throw new Parsonline_Exception_ValueException("invalid mode '{$mode}'");
        }
    }

    /**
     * Returns the first IPv4 of the host, if any exists.
     * 
     * @return  string|null
     */
    public function getIPv4Address()
    {
        return empty($this->_ipv4) ? null : $this->_ipv4[0];
    }

    /**
     * Returns the IP address of the host (or a specified IP address)
     * as reversed addresses ready to query reverse DNS lookups.
     * the returned IP addresses are:
     * a.b.c.d => d.c.b.a.in-addr.arpa
     *
     * @param   bool            $addInAddr  if should add in-addr.arpa section to the end of the reversed IP address
     * @param   null|string     $ip 
     * @return  string|null
     */
    public function getReverseIPv4Address($addInAddr=true, $ip=null)
    {
        $ip = $ip ? strval($ip) : $this->getIPv4Address();
        if (!$ip) return null;
        return implode( '.', array_reverse(explode('.', $ip)) ) . ($addInAddr ? 'in-addr.arpa' : '');
    } // public function getReverseIPv4Address()

    /**
     * Returns the IP addresses assigned to the host as reversed addresses,
     * ready to query reverse DNS lookups. the returned IP addresses are:
     * a.b.c.d => d.c.b.a.in-addr.arpa
     * 
     * @param   bool    $addInAddr      if should add  in-addr.arpa section to the end of the revered IP address
     * @param   string  $mode
     * @return  string
     * @throws  Parsonline_Exception_ValueException
     */
    public function getReverseIPv4Addresses($addInAddr=true, $mode=self::LIST_ALL)
    {
        switch ($mode) {
            case self::LIST_ALL:
                $reverse = array();
                foreach($this->_ipv4 as $ip) {
                    array_push($reverse, $this->getReverseIPv4Address($addInAddr, $ip));
                }
                return $reverse;
            case self::LIST_LAST:
                return empty($this->_ipv4) ? null : $this->getReverseIPv4Address($addInAddr, $this->_ipv4[count($this->_ipv4) - 1]);
            case self::LIST_FIRST:
                return empty($this->_ipv4) ? null : $this->getReverseIPv4Address($addInAddr, $this->_ipv4[0]);
            default:
                /**
                 * @uses    Parsonline_Exception_ValueException 
                 */
                require_once('Parsonline/Exception/ValueException.php');
                throw new Parsonline_Exception_ValueException("invalid mode '{$mode}'");
        }
    } // public function getReverseIPAddresses()

    /**
     * adds an IPv4 address to the list of IPv4 addresses.
     * IP data is not validated.
     *
     * @param   array|string    $ipList
     * @param   bool            $ignoreReduntants   if should check available IPs and ignore redundant records. default is true.
     * @return  Parsonline_Common_Entity_Host
     */
    public function addIPv4Addresses($ipList, $ignoreReduntants=true)
    {
        if (!is_array($ipList)) $ipList = array($ipList);
        foreach ($ipList as $ip) {
            $ip = strval($ip);
            if ($ignoreReduntants && in_array($ip, $this->_ipv4)) continue;
            array_push($this->_ipv4, $ip);
        }
        return $this;
    }

    /**
     * Sets the IPv4 of the host. IP data is not validated.
     *
     * @param   array   $ipList
     * @return  Parsonline_Common_Entity_Host
     */
    public function setIPv4Addresses(array $ipList)
    {
        $this->_ipv4 = $ipList;
        return $this;
    }

    /**
     * Returns the first IPv6 of the host, if any exists.
     *
     * @return  string|null
     */
    public function getIPv6Address()
    {
        return (empty($this->_ipv6) ? null : $this->_ipv6[0]);
        
    }
    
    /**
     * Returns the IPv6 of the host. by default returns all IPs assigned as an array.
     * this could be changed by passing the mode argument. use LIST_* constants.
     *
     * @param   string  $mode   the return mode
     * @return  array|string|null
     * @throws  Parsonline_Exception_ValueException on invalid mode
     */
    public function getIPv6Addresses($mode=self::LIST_ALL)
    {
        switch ($mode) {
            case LIST_ALL:
                return $this->_ipv6;
            case LIST_LAST:
                return empty($this->_ipv6) ? null : $this->_ipv6[count($this->_ipv6) - 1];
            case LIST_FIRST:
                return empty($this->_ipv6) ? null : $this->_ipv6[0];
            default:
                /**
                 * @uses    Parsonline_Exception_ValueException 
                 */
                require_once('Parsonline/Exception/ValueException.php');
                throw new Parsonline_Exception_ValueException("invalid mode '{$mode}'");
        }
    }
    
    /**
     * Adds an IPv6 address to the list of IPv6 addresses.
     * IP addresses are not validated.
     *
     * @param   string|array    $ipv6List
     * @param   bool            $ignoreReduntants if should check available IPs and ignore redundant records. default is true.
     * @return  Parsonline_Common_Entity_Host
     * @throws  Exception
     */
    public function addIPv6Addresses($ipv6List, $ignoreReduntants=true)
    {
        if (!is_array($ipv6List)) $ipv6List = array($ipv6List);
        foreach ($ipv6List as $ip) {
            $ip = strval($ip);
            if ($ignoreReduntants && in_array($ip, $this->_ipv6)) continue;
            array_push($this->_ipv6, $ip);
        }
        return $this;
    }

    /**
     * Sets the IPv6 of the host.
     * IP addresses are not validated.
     *
     * @param   array   $ipv6List
     * @return  Parsonline_Common_Entity_Host
     */
    public function setIPv6Addresses(array $ipv6List)
    {
        $this->_ipv6 = $ipv6List;
        return $this;
    }

    /**
     * Compares 2 host objects based on their names. returns 1 if the first
     * param is sorted after the second, -1 if the first is sorted before the
     * second param, and 0 if there is no difference while sorting.
     * 
     * Note: this is used for sorting arrays of hosts.
     *
     * @param   Parsonline_Common_Entity_Host    $h1
     * @param   Parsonline_Common_Entity_Host    $h2
     * @return  int
     */
    public function _compareHostsByHostname($h1, $h2)
    {
        return strnatcasecmp($h1->getHostname(), $h2->getHostname());
    }
    
    /**
     * Compares 2 host objects based on their IPv4. returns 1 if the first
     * param is sorted after the second, -1 if the first is sorted before the
     * second param, and 0 if there is no difference while sorting.
     * 
     * Note: this is used for sorting arrays of hosts.
     *
     * @param   Parsonline_Common_Entity_Host    $h1
     * @param   Parsonline_Common_Entity_Host    $h2
     * @return  int
     */
    public function _compareHostsByIPv4($h1, $h2)
    {
        return strnatcasecmp($h1->getIPv4Address(), $h2->getIPv4Address());
    }
    
    /**
     * Compares 2 host objects based on their IPv6. returns 1 if the first
     * param is sorted after the second, -1 if the first is sorted before the
     * second param, and 0 if there is no difference while sorting.
     * 
     * Note: this is used for sorting arrays of hosts.
     *
     * @param   Parsonline_Common_Entity_Host    $h1
     * @param   Parsonline_Common_Entity_Host    $h2
     * @return  int
     */
    public function _compareHostsByIPv6($h1, $h2)
    {
        return strnatcasecmp($h1->getIPv6Address(), $h2->getIPv6Address());
    }
    
    /**
     * Compares 2 host objects based on their priority. returns 1 if the first
     * param is sorted after the second, -1 if the first is sorted before the
     * second param, and 0 if there is no difference while sorting.
     * 
     * Note: this is used for sorting arrays of hosts.
     * 
     * @param   Parsonline_Common_Entity_Host    $h1
     * @param   Parsonline_Common_Entity_Host    $h2
     * @return  int
     */
    public function _compareHostsByPriority($h1, $h2)
    {
        $result = $h1->getPriority() - $h2->getPriority();
        if ($result === 0) return strnatcasecmp($h1->getHostname(), $h2->getHostname());
        return ($result < 0) ? -1 : 1;
    }
    
    /**
     * Sorts an array of Parsonline_Common_Entity_Host objects and returns the sorted array.
     * 
     * @param   array   $hosts    array of Parsonline_Common_Entity_Host objects
     * @param   string  $by       the property to compare entities by. use SORT_* constants.
     * @return  array   array of sorted objects
     * @throws  Parsonline_Exception on failed to sort the array
     *          Parsonline_Exception_ValueException on invalid property
     */
    public function sort(array $hosts, $by=self::SORT_HOSTNAME)
    {
        $compareMethod = '_compareHostsBy' . ucfirst($by);
        if ( !method_exists($this, $compareMethod) ) {
            /**
                * @uses  Parsonline_Exception_ValueException
                */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("Invalid sort property '{$by}'");
        }
        
        if (!usort($hosts, array($this, $compareMethod))) {
            /**
             * @uses  Parsonline_Exception
             */
            require_once('Parsonline/Exception.php');
            throw new Parsonline_Exception("Failed to sort the host array");
        }
        return $hosts;
    } // public function sort()

    /**
     * Returns an array representaion of the host object
     * keys are:
     * 'hostname', 'name', 'domain' (the same as name), ipv4, ipv6, pingable (bool)
     * resolvable (bool)
     * 
     * @return array
     */
    public function __toArray()
    {
        $result = array();
        $result['hostname'] = $this->_hostname;
        $domain = $this->getDomain();
        
        if (is_object($domain)) {
            if ( method_exists($domain, 'getName') ) {
                $domain = $domain->getName();
            } else {
                $domain = (string) $domain;
            }
        }
        $result['name'] = $result['domain'] = $domain;
        $result['ipv4'] = implode(',', $this->_ipv4);
        $result['ipv6'] = implode(',', $this->_ipv6);
        $result['pingable'] = $this->_pingable;
        $result['resolvable'] = $this->_dnsResolvable;
        $result['reverse_hostname'] = $this->_reverseDNSResult;
        $result['priority'] = $this->_priority;
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
        foreach($baseArray as $key => $data) {
            if (is_array($data)) {
                $data = implode(':', $data);
            } elseif (is_bool($data) ) {
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
     * @return  Net_DNS_Resolver|null
     */
    public function getDNSResolver()
    {
        return $this->_dnsResolver;
    }

    /**
     * Sets the object that is used to resolve DNS queries.
     * 
     * @see  http://pear.php.net/manual/en/package.networking.net-dns.php
     *
     * @param   Net_DNS_Resolver    $resolver
     * @return  Parsonline_Common_Entity_Host    object self reference
     */
    public function setDNSResolver(Net_DNS_Resolver $resolver)
    {
        $this->_dnsResolver = $resolver;
        return $this;
    }

    /**
     * Returns the ping requester callable.
     * 
     * @return  Net_Ping
     */
    public function getPingRequester()
    {
        return $this->_pingRequester;
    }

    /**
     * Sets the ping requester object.
     * 
     * 
     * @see     http://pear.php.net/package/Net_Ping
     * 
     * @param   Net_Ping    $pinger
     * @return  Parsonline_Common_Entity_Host    object self reference
     */
    public function setPingRequester(Net_Ping $pinger)
    {
        $this->_pingRequester = $pinger;
        return $this;
    }

    /**
     * Checks if the host is pingable.
     *
     * @param   bool    $realtime               regardless of previouse on memory status, try to ping the host
     * @param   bool    $autoset                automatically set the pingable status of the object. default is true.
     * @return  bool
     * @throws  Parsonline_Exception_ContextException on no ping requester
     */
    public function isPingable($realtime=false, $autoset=true)
    {
        $hostname = $this->_hostname ? $this->_hostname : $this->getIPv4Address();
        if ( $realtime || is_null($this->_pingable) ) {
            $pinger = $this->getPingRequester();
            if (!$pinger) {
                /**
                * @uses     Parsonline_Exception_ContextException
                */
                require_once('Parsonline/Exception/ContextException.php');
                throw new Parsonline_Exception_ContextException("Failed to ping the host. No ping requester is provided for the host object");
            }
            
            // use output bufferring to make sure no data from PING command output
            // is sent to PHP output
            $obContents = ob_get_contents();
            $obEnabled = ($obContents !== false);
            if ($obContents) ob_clean();
            ob_start();
            
            $response = @$pinger->ping($hostname);
            
            ob_clean();
            if ($obEnabled) {
                // re-send the previous output buffer data
                echo $obContents;
            }
            unset($obContents, $obEnabled);
            
            if ( PEAR::isError($response) || !$response ) {
                $response = false;
            } else {
                $response = true;
            }
            $pingable = $response;
            if ($autoset) $this->setPingable($pingable);
        } else {
            $pingable = $this->_pingable;
        }
        return $pingable;
    }

    /**
     * sets the object pingable status
     *
     * @param   bool                    $pingable
     * @return  Parsonline_Common_Entity_Host    object self reference
     */
    public function setPingable($pingable=true)
    {
        $this->_pingable = true && $pingable;
        return $this;
    }
    
    /**
     * checks if the Server object (or a hostname) is DNS resolvable. if the object
     * is being queried, and query returned results, by default updates the list of
     * IP addresses of the object.
     *
     * @param   bool            $realtime                   regardless of previouse on memory status, try to resolve the host
     * @param   array           $nameServers                an array of name servers to query from
     * @param   bool            $autset                     automatically update the DNS resolvable status of the server. default is true
     * @param   bool            $autoUpdateIpAddresses      automatically update the IPv4 addresses of the Server from the DNS. default is true
     * @return  bool
     * @throws  Exception
     */
    public function isDNSResolvable($realtime=false, $nameServers=array(), $autoset=true, $autoUpdateIpAddresses=true)
    {
        if ( $realtime || is_null($this->_dnsResolvable) ) {
            $resolvable = false;
            $ipList = $this->resolveIPv4AddressesByDNSRequest($nameServers);
            if ($ipList) {
                $resolvable = true;
                if ($autoUpdateIpAddresses) $this->setIPv4Addresses($ipList);
            }
            if ($autoset) $this->setDNSResolvable($resolvable);
        } else {
            $resolvable = $this->_dnsResolvable;
        }
        return $resolvable;
    }

    /**
     * sets the object DNS resolvable status
     *
     * @param   bool                    $resolveable
     * @return  Parsonline_Common_Entity_Host    object self reference
     */
    public function setDNSResolvable($resolveable=true)
    {
        $this->_dnsResolvable = true && $resolveable;
        return $this;
    }
    
    /**
     * resolves the IP address of the object by sending a DNS request to nameservers.
     * returns an array of IP addresses, otherwise returns false. automatically
     * updates the DNS resolvable status of the object if not disabled.
     *
     * @param   array|string        $nameServers                additional name servers
     * @param   bool                $autoset                    automatically set the DNSresolvable status of the object
     * @param   bool                $autoUpdateIpAddresses      automatically add the resolved IP addresses to the object
     * @return  array|false         array of resolved IP addresses
     * @throws  Parsonline_Exception_ContextException on no hostname/resolver set yet
     */
    public function resolveIPv4AddressesByDNSRequest($nameServers=array(), $autoset=true, $autoUpdateIpAddresses=true)
    {
        $hostname = $this->_hostname;
        if(!$hostname) {
            /**
            * @uses     Parsonline_Exception_ContextException
            */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException("failed to resolve the IPv4 address. no hostname is specified",1);
        }
        
        if ($nameServers && !is_array($nameServers)) $nameServers = array( strval($nameServers) );
        
        $dnsResolver = $this->getDNSResolver();
        if (!$dnsResolver) {
            /**
            * @uses     Parsonline_Exception_ContextException
            */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException("no DNS resolver object is provided for the host object",2);
        }
        
        if ($nameServers) {
            $nservers = $dnsResolver->nameservers;
            if (!$nservers) $nservers = array();
            $dnsResolver->nameservers = array_unique(array_merge($nameServers, $nservers));
        }
        
        $response = $dnsResolver->query($hostname);
        $resolvable = ( !$response || !isset($response->answer) || !is_array($response->answer)  ) ? false : true;
        if ($autoset) $this->setDNSResolvable($resolvable);
        if (!$resolvable) return false;
        $ipList = array();
        foreach ($response->answer as $record) {
            if ($record && isset($record->address)) array_push($ipList, strval($record->address));
        }
        $ipList = array_unique($ipList);
        if ($autoUpdateIpAddresses && $ipList) $this->addIPv4Addresses($ipList);
        return $ipList;
    }

    /**
     * Resolves the IP address of the object by trying to ping the host.
     * automatically updates the pingable status of the object if not disabled.
     *
     * @param   bool            $autoset                automatically set the pingable stat of the object. default is true
     * @param   bool            $autoUpdateIpAddresses  automatically add the resolved IPv4 addresses to the object
     * @return  string|false
     * @throws  Parsonline_Exception_ContextException on no hostname/ping requester object
     *          Parsonline_Exception on ping failure
     */
    public function resolveIPv4AddressByPing($autoset=true, $autoUpdateIpAddresses=true)
    {
        $hostname = $this->_hostname;
        if(!$hostname) {
            /**
            * @uses     Parsonline_Exception_ContextException
            */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException("failed to resolve the IPv4 address. no hostname is specified",1);
        }
        $ip = false;
        $pinger = $this->getPingRequester();
        if (!$pinger) {
            /**
            * @uses     Parsonline_Exception_ContextException
            */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException("failed to resolve IPv4 by ping. no ping requester is set for the host object",2);
        }
        
        // use output bufferring to make sure no data from PING command output
        // is sent to PHP output
        $obContents = ob_get_contents();
        $obEnabled = ($obContents !== false);
        if ($obContents) ob_clean();
        ob_start();

        $response = @$pinger->ping($hostname);

        ob_clean();
        if ($obEnabled) {
            // re-send the previous output buffer data
            echo $obContents;
        }
        unset($obContents, $obEnabled);
        
        $pingable = false;
        
        if ( PEAR::isError($response) || !$response ) {
            $pingable = false;
        } elseif (is_object($response) && ($response instanceof Net_Ping_Result)) {
            $ip = $response->getTargetIp();
            $pingable = true;
        } else {
            /**
            * @uses     Parsonline_Exception
            */
            require_once('Parsonline_Exception.php');
            throw new Parsonline_Exception( sprintf("failed to reslove the IPv4 address of '%s' by ping. Net_Ping::ping returned: $%s"), $hostname, var_export($response, bool));
        }
        if ($autoset) $this->setPingable($pingable);
        if ($autoUpdateIpAddresses && $ip) $this->addIPv4Addresses($ip);
        return $ip;
    } // public function resolveIPv4AddressByPing()

    /**
     * Resolves the IPv4 address of a given hostname or the object itself. tries
     * to resovle using DNS requests, if failed, tries to ping the host and get
     * the IP address from there. returns an array of IP addresses, otherwise returns false.
     *
     * @param   array|string    $nameServers                additional name servers
     * @param   bool            $autset                     if should automatically set the pingable/DNS resolvable statuses of the object
     * @param   bool            $autoUpdateIpAddresses      automatically add the resolved IPv4 addresses to the object
     * @return  array|false
     * @throws  Exception
     */
    public function resolveIPv4Addresseses($nameServers=array(), $autoset=true, $autoUpdateIpAddresses=true)
    {
        if(!$this->_hostname) {
            /**
            * @uses     Parsonline_Exception_ContextException
            */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException("failed to resolve IPv4. no hostname is specified");
        }
        $ipList = $this->resolveIPv4AddressesByDNSRequest($nameServers, $autoset, $autoUpdateIpAddresses);
        if (!$ipList) {
            $ipList = $this->resolveIPv4AddressByPing($autoset, $autoUpdateIpAddresses);
            if ($ipList !== false) $ipList = array($ipList);
        }
        return $ipList;
    }

    /**
     * resolves the IP address of the object by sending a DNS request to nameservers.
     * returns an array of IP addresses, otherwise returns false. automatically
     * updates the DNS resolvable status of the object if not disabled.
     *
     * @param   bool                $realtime       if should ignore onmemory resutls and execute a realtime query
     * @return  string
     * @throws  Parsonline_Exception_ContextException on no IPv4 address set yet
     */
    public function reverseDNSResolve($realtime=false)
    {
        if ($realtime || $this->_reverseDNSResult === null) {
            $ip = $this->getIPv4Address();
            if (!$ip) {
                /**
                * @uses     Parsonline_Exception_ContextException
                */
                require_once('Parsonline/Exception/ContextException.php');
                throw new Parsonline_Exception_ContextException("failed to lookup reverse DNS results. the host has no IPv4 address");
            }
            $this->_reverseDNSResult = gethostbyaddr($ip);
        }
        return $this->_reverseDNSResult;
    } // public function reverseDNSResolve()

    /**
     * indicates if a reverse DNS lookup returns resutls for the host.
     *
     * @param   bool    $realtime   if should ignore on memory status and query DNS right now
     * @return  bool
     * @throws  Exception
     */
    public function isRevereseDNSResolvable($realtime=false)
    {
        if ($realtime || $this->_reverseDNSResolvable === null) {
            $resolvedHostname = $this->reverseDNSResolve($realtime);
            $this->_reverseDNSResolvable = true && $resolvedHostname;
        }
        return $this->_reverseDNSResolvable;
    } // public function isRevereseDNSResolvable()

    /**
     * set the DNS reverse lookup resolvable state of the host object
     * @param   bool $resolvable
     * @return  Parsonline_Common_Entity_Host  object self reference
     */
    public function setReverseDNSResolvable($resolvable=true)
    {
        $this->_reverseDNSResolvable = true && $resolvable;
        return $this;
    }
    
    /**
     * Reads an array of host objects, and finds their hostnames as string
     * values. If a host object did not have a hostname, tries to find their host
     * name based on their addresses.
     * Returns an array, whose first index is an array of hostnames, and the
     * second is an array of host objects that failed to find a suitable 
     * hostname for.
     * 
     * @param   array   $hosts
     * @return  array(array, array)
     */
    public function getHostsNames(array $hosts)
    {
        $hostnames = array();
        $failed = array();
        foreach($hosts as $host) {
            /*@var $host Parsonline_Common_Entity_Host*/
            if (!$host || !($host instanceof self)) {
                array_push($failed, $host);
                continue;
            }
            $name = $host->getHostname();
            if (!$name) {
                $ip = $host->getIPv4Address();
                if ($ip) {
                    $name = gethostbyaddr($ip);
                    if ($name == $ip) $name = false;
                }
                unset($ip);
            }
            if ($name) {
                array_push($hostnames, $name);
            } else {
                array_push($failed, $host);
            }
        }
        return array($hostnames, $failed);
    } // public function getHostsNames()
    
    /**
     * Reads an array of host objects, and finds their IP addresses as string
     * values. If a host object did not have an IP, tries to find their IP
     * based on their hostnames.
     * Returns an array, whose first index is an array of IP addresses, and the
     * second is an array of host objects that failed to find a suitable 
     * IP for.
     * 
     * @param   array   $hosts
     * @return  array(array, array)
     */
    public function getHostsIPv4Addresses(array $hosts)
    {
        $ipAddresses = array();
        $failed = array();
        foreach($hosts as $host) {
            /*@var $host Parsonline_Common_Entity_Host*/
            if (!$host || !($host instanceof self)) {
                array_push($failed, $host);
                continue;
            }
            $ip = $host->getIPv4Address();
            if (!$ip) {
                $name = $host->getHostname();
                if ($name) {
                    $ip = gethostbyname($name);
                    if ($ip == $name) $ip = false;
                }
                unset($name);
            }
            if ($ip) {
                array_push($ipAddresses, $ip);
            } else {
                array_push($failed, $host);
            }
        }
        return array($ipAddresses, $failed);
    } // public function getHostsNames()
}
