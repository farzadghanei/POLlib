<?php
//Parsonline/Common/Entity/Host/NameServer.php
/**
 * Defines the Parsonline_Common_Entity_Host_NameServer class.
 * 
 * Parsonline
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
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
 * @copyright   Copyright (c) 2011-2012 ParsOnline, Inc. (www.parsonline.com)
 * @license     Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Parsonline_Common
 * @subpackage  Entity
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.0.4 2012-02-13
 */

/**
 * @uses    Parsonline_Common_Entity_Host
 */
require_once('Parsonline/Common/Entity/Host.php');

/**
 * Parsonline_Common_Entity_Host_NameServer
 * 
 * Represents a name server host.
 * 
 */
class Parsonline_Common_Entity_Host_NameServer extends Parsonline_Common_Entity_Host
{
    /**
     * if the name server resolves the root record of its DNS zone
     *
     * @var bool
     */
    protected $_resolvesDNSZoneRootRecord = null;
    
    /**
     * Resolve IP address of a hostname or a host object.
     * returns an array of resolved IP addresses, or null.
     *
     * @param   Parsonline_Common_Entity_Host|string    $host
     * @return  array|null
     * @throws  Parsonline_Exception_ValueException on invalid host param
     *          Parsonline_Exception_ContextException on no DNS resolver set yet
     */
    public function resolveHost($host)
    {
        if (is_string($host)) {
            $host = new Parsonline_Common_Entity_Host($host);
        }
        if (!$host || !($host instanceof Parsonline_Common_Entity_Host) || !$host->getHostname()) {
            /**
            * @uses    Parsonline_Exception_ValueException 
            */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException(
                "host should be a hostname string, or a Parsonline_Common_Entity_Host object with a hostname"
            );
        }
        
        $nameServers = $this->getIPAddress();
        
        if (!$nameServers) {
            $nameServers = $this->resolveIPv4Addresseses(array(), true, true);
        } else {
            $nameServers = array($nameServers);
        }

        if (!$nameServers) return null;
        
        $hostname = $host->getHostname();
        $dnsResolver = $this->getDNSResolver();
        
        if (!$dnsResolver) {
            /**
            * @uses     Parsonline_Exception_ContextException
            */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException("no DNS resolver is provided for the host object");
        }
        
        $dnsResolver->nameservers = $nameServers;
        
        $response = $dnsResolver->query($hostname);
        $resolvable = ( !$response || !isset($response->answer) || !is_array($response->answer)  ) ? false : true;
        
        if (!$resolvable) return null;
        $ipList = array();
        foreach ($response->answer as $record) {
            if ($record && isset($record->address)) array_push($ipList, strval($record->address));
        }
        $ipList = array_unique($ipList);
        return $ipList;
    } // public function resolveHost()

    /**
     * Tries to resolve the root record of the DNS zone.
     * 
     * @param   bool    $autoset                    if should automatically update the resolveRootRecord status of the name server object
     * @param   bool    $autoAddIPAddresses         if should automatically add the resolved IPv4 addresses to root record of the DNS zone
     * @return  array|null      array of IP addresses or null
     * @throws  Parsonline_Exception_ContextException on no DNS zone set yet
     */
    public function resolveDNSZoneRootRecord($autoset=true, $autoAddIPAddresses=true)
    {
        $zone = $this->getDNSZone();
        if (!$zone) {
            /**
            * @uses     Parsonline_Exception_ContextException
            */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException("no DNS zone is specified for the name server yet");
        }
        $rootRecord = $zone->getRootHost();
        $ipList = $this->resolveHost($rootRecord);
        if ($autoset) $this->setResolvesDNSZoneRootRecord($ipList);
        if ($ipList && $autoAddIPAddresses) $rootRecord->addIPv4Addresses($ipList);
        return $ipList;
    }

    /**
     * Returns true if the name server resolves the root record of the DNS zone
     * or false if not.
     *
     * @param   bool    $realtime   if should ignore on memory state and try to resolve the record now
     * @param   bool    $autoset    if should automatically set the on memory resolve status
     * @return  bool
     */
    public function doesResolveDNSZoneRootRecord($realtime=false, $autoset=true)
    {
        if ($realtime || $this->_resolvesDNSZoneRootRecord === null) {
            $this->resolveDNSZoneRootRecord($autoset, true);
        }
        return $this->_resolvesDNSZoneRootRecord;
    }

    /**
     * Set if the name server resolves the root record of the DNS zone or not.
     * 
     * @param   bool    $resolves
     * @return  Parsonline_Common_Entity_Host_NameServer
     */
    public function setResolvesDNSZoneRootRecord($resolves)
    {
        $this->_resolvesDNSZoneRootRecord = true && $resolves;
        return $this;
    }
}
