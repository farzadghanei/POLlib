<?php
//Parsonline/Parser/Whois.php
/**
 * Defines the Parsonline_Parser_Whois class.
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
 * @package     Parsonlne_Parser
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.0.6 2012-01-07
 */

/**
 * Parsonline_Parser_Whois
 * 
 * A parser to extract information out of whois data.
 */
class Parsonline_Parser_Whois
{
    /**
     * the raw text output from whois response
     * 
     * @var string
     */
    protected $_rawData = '';

    /**
     *
     * @param string $data
     */
    public function __construct($data='')
    {
        $this->setRawData($data);
    }

    /**
     * gets the raw data of whois output
     *
     * @return   string  $data
     */
    public function getRawData()
    {
        return $this->_rawData;
    }

    /**
     * sets the raw data of whois output
     *
     * @param   string  $data
     * @return  Parsonline_Parser_Whois     object self reference
     */
    public function setRawData($data='')
    {
        $this->_rawData = strval($data);
        return $this;
    }

    /**
     * parses a text of whois info and extracts last updated time from it.
     *
     * @param   string      $whois   the whois response text
     * @return  int|null    timestamp or null if extraction failed
     * @throws  Parsonline_Exception_ParseException on regex engine failure
     */
    public function parseLastUpdateTime($whois=null)
    {
        $whois = is_null($whois) ? $this->_rawData : strval($whois);
        $lastUpdate = null;
        $pattern = "/updated(?:\s+on)*(?:\s*Date)*(?::)*\s+([a-zA-Z0-9 -:]+)/im";
        $matches = array();
        $regexResult = preg_match($pattern, $whois, $matches);
        if ($regexResult === false) {
            /**
             * @uses    Parsonline_Exception_ParseException
             */
            require_once('Parsonline/Exception/ParseException.php');
            throw new Parsonline_Exception_ParseException("regex engine failed while extracting domain last update from whois data", preg_last_error(), null, $whois, $pattern);
        }
        if ($regexResult) $lastUpdate = strtotime($matches[1]);
        return $lastUpdate;
    }

    /**
     * parses a text of whois info and extracts expiration time from it.
     *
     * @param   string      $whois   the whois response text
     * @return  int|null    timestamp or null if extraction failed
     * @throws  Parsonline_Exception_ParseException
     */
    public function parseExpirationTime($whois=null)
    {
        $whois = is_null($whois) ? $this->_rawData : strval($whois);
        $expires = null;
        $pattern = "/(?:expiration|expires)(?:\s+on)*(?:\s*Date)*(?::)*\s+([a-zA-Z0-9 -:]+)/im";
        $matches = array();
        $regexResult = preg_match($pattern, $whois, $matches);
        if ($regexResult === false) {
            /**
             * @uses    Parsonline_Exception_ParseException
             */
            require_once('Parsonline/Exception/ParseException.php');
            throw new Parsonline_Exception_ParseException("regex engine failed while extracting domain expiration date from whois data", preg_last_error(), null, $whois, $pattern);
        }
        if ($regexResult) $expires = strtotime($matches[1]);
        return $expires;
    }

    /**
     * parses a text of whois info and extracts status text from it.
     *
     * @param   string      $whois   the whois response text
     * @return  array       array of status texts
     * @throws  Parsonline_Exception_ParseException
     */
    public function parseStatuses($whois=null)
    {
        $whois = is_null($whois) ? $this->_rawData : strval($whois);
        $statuses = array();
        $pattern = "/status(?::)*\s*(\S+)/im";
        $matches = array();
        $regexResult = preg_match_all($pattern, $whois, $matches);
        if ($regexResult === false) {
            /**
             * @uses    Parsonline_Exception_ParseException
             */
            require_once('Parsonline/Exception/ParseException.php');
            throw new Parsonline_Exception_ParseException(
                "regex engine failed while extracting domain statuses from whois data",
                preg_last_error(),null, $whois, $pattern
            );
        }
        if ($regexResult) {
            foreach($matches[1] as $m) {
                if (!in_array($m, $statuses)) array_push($statuses, $m);
            }
        }
        return $statuses;
    }

    /**
     * parses a text of whois info and extracts name servers from it. returns
     * an array of nameserver info, each of them is an associative array with keys
     * 'name' for name and 'address' for IP address of the machine. if any of these
     * info could not be parsed, they are returned as null values.
     * supports IRNIC (for .ir domains) and whois server v2.
     * 
     * @param   string      $whois  the whois response text
     * @return  array       array of associative arrays.
     * @throws  Parsonline_Exception_ParseException
     */
    public function parseNameServers($whois=null)
    {
        $whois = strtolower( is_null($whois) ? $this->_rawData : strval($whois) );
        $nameServers = array();
        if ( strpos($whois,'irnic') !== false ) {
            /*
             * IRNIC returns name servers like:
             * nserver:	ns.somehost.com
             */
            $pattern = '/nserver:\s*(\S+)/im';
        } else {
            /*
             * who is server version 2 returns detailed name servers at the end
             * of the reponse with IP addresses after the string "Domain servers in listed order:".
             * i'm using a second plan to extract just the name from the string
             * like "Name Server: ns.shomehost.com" from the first paragraph of the
             * response, if the detailed section could not be found.
             */
            $splittedWhois = explode('domain servers in listed order:', $whois, 2);
            if ( $splittedWhois && is_array($splittedWhois) && (count($splittedWhois) > 1) ) {
                $pattern = '/([A-Za-z0-9\.]+\.[A-Za-z0-9\.]+\.[A-Za-z0-9]+)(?:\s+(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})?)/im';
                $whois = $splittedWhois[1];
            } else {
                //$pattern = '/name\s+server\s*:?\s*(\S+)/im';
                $pattern = '/name +server *: *(\S+)/im';
            }
        }
        $matches = array();
        $regexResult = preg_match_all($pattern, $whois, $matches, PREG_PATTERN_ORDER);
        if ($regexResult === false) {
            /**
             * @uses    Parsonline_Exception_ParseException
             */
            require_once('Parsonline/Exception/ParseException.php');
            throw new Parsonline_Exception_ParseException(
                "regex engine failed while extracting name servers from whois data.",
                preg_last_error(), null, $whois, $pattern
            );
        }
        if ($regexResult) {
            /*
             * first matches are names, seconds matches are IP addresses which could be empty.
             * so walk on names, if a matching IP address exists, then collect the IP address
             * and add both to the server array.
             */
            $counter = 0;
            foreach($matches[1] as $m) {
                $m = trim($m);
                if (!$m) continue;
                $server = array('name'=> $m);
                $server['address'] = ( isset($matches[2]) && isset($matches[2][$counter]) && $matches[2][$counter] ) ? $matches[2][$counter] : null;
                array_push($nameServers, $server);
                $counter++;
            }
        }
        return $nameServers;
    }
    
    /**
     * parses a text of whois info and extracts admin contact text from it.
     * If failed to detect the admin, returns null.
     *
     * @param   string      $whois   the whois response text
     * @return  string|null
     * @throws  Parsonline_Exception_ParseException on regex engine failure
     */
    public function parseAdminContact($whois=null)
    {
        $whois = is_null($whois) ? $this->_rawData : strval($whois);
        $contact = null;
        
        $patterns = array("/admin\S*\s+contact:\s+(\S.+)/im", "/admin-c:\s+(\S.+)/im");
        $matches = array();
        foreach ($patterns as $pattern) {
            $regexResult = preg_match($pattern, $whois, $matches);
            if ($regexResult === false) {
                /**
                * @uses    Parsonline_Exception_ParseException
                */
                require_once('Parsonline/Exception/ParseException.php');
                throw new Parsonline_Exception_ParseException(
                    "regex engine failed while extracting domain admin contact from whois data",
                    preg_last_error(), null, $whois, $pattern
                );
            }
            if ($regexResult) {
                $contact = $matches[1];
                break;
            }
        }
        return $contact;
    } // public function parseAdminContact()
    
    /**
     * parses a text of whois info and extracts technical contact text from it.
     * If failed to detect the admin, returns null.
     *
     * @param   string      $whois   the whois response text
     * @return  string|null
     * @throws  Parsonline_Exception_ParseException on regex engine failure
     */
    public function parseTechnicalContact($whois=null)
    {
        $whois = is_null($whois) ? $this->_rawData : strval($whois);
        $contact = null;
        
        $patterns = array("/tech\S*\s+contact:\s+(\S.+)/im", "/tech-c:\s+(\S.+)/im");
        $matches = array();
        foreach ($patterns as $pattern) {
            $regexResult = preg_match($pattern, $whois, $matches);
            if ($regexResult === false) {
                /**
                * @uses    Parsonline_Exception_ParseException
                */
                require_once('Parsonline/Exception/ParseException.php');
                throw new Parsonline_Exception_ParseException(
                    "regex engine failed while extracting domain admin contact from whois data",
                    preg_last_error(), null, $whois, $pattern
                );
            }
            if ($regexResult) {
                $contact = $matches[1];
                break;
            }
        }
        return $contact;
    } // public function parseTechnicalContact()
    
    /**
     * parses a text of whois info and extracts information from it. returns the
     * extracted data as an associative array. keys are: 'lastUpdateTime',
     * 'expirationTime', 'statuses', 'nameServers'.
     *
     * @param   string  $whois   the whois response text
     * @return  array
     * @thorws  Parsonline_Exception_ParseException
     */
    public function parseAll($whois=null)
    {
        $whois = is_null($whois) ? $this->_rawData : strval($whois);
        $info = array();
        $lastUpdate = $this->parseLastUpdateTime($whois);
        if ($lastUpdate) $info['lastUpdateTime'] = $lastUpdate;
        $expire = $this->parseExpirationTime($whois);
        if ($expire) $info['expirationTime'] = $expire;
        $statuses = $this->parseStatuses($whois);
        if ($statuses) $info['statuses'] = $statuses;
        $nameServers = $this->parseNameServers($whois);
        if ($nameServers) $info['nameServers'] = $nameServers;
        $adminContact = $this->parseAdminContact($whois);
        if ($adminContact) $info['adminContact'] = $adminContact;
        $technicalContact = $this->parseTechnicalContact($whois);
        if ($technicalContact) $info['technicalContact'] = $technicalContact;
        return $info;
    }
}
