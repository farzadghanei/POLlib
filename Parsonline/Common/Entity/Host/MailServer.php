<?php
//Parsonline/Common/Entity/Host/Mail.php
/**
 * Defines the Parsonline_Common_Entity_Host_MailServer class.
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
 * @copyright  Copyright (c) 2011-2012 ParsOnline, Inc. (www.parsonline.com)
 * @license    Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Parsonline_Common
 * @subpackage  Entity
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.0.5 2012-02-14
 */

/**
 * @uses    Parsonline_Common_Entity_Host
 * @uses    PEAR
 * @uses    Net_SMTP
 */
require_once('Parsonline/Common/Entity/Host.php');
require_once('PEAR.php');
require_once('Net/SMTP.php');

/**
 * Parsonline_Common_Entity_Host_MailServer
 * 
 * Represents a mail server host.
 */
class Parsonline_Common_Entity_Host_MailServer extends Parsonline_Common_Entity_Host
{
    const OPT_SMTP_CONNECT_TIMEOUT = 'smtp_connect_timeout';
    const OPT_SMTP_PORT = 'smtp_port';
    const OPT_SMTP_SOCKET_OPTIONS = 'smtp_socket_options';
    
    /**
     * Default SMTP client for all newly created objects
     * 
     * @staticvar   Net_SMTP
     */
    protected static $_defaultSMTPClient = null;

    /**
     * SMTP client object
     * 
     * @var Net_SMTP
     */
    protected $_smtpClient = null;

    /**
     * if the mail server responds to SMTP requests
     * 
     * @var bool
     */
    protected $_respondsToSMTP = null;
    
    /**
     * Returns the default SMTP client of the class which is assigned to
     * newly created objects.
     *
     * @return  Net_SMTP
     */
    public static function getDefaultSMTPClient()
    {
        return self::$_defaultSMTPClient;
    }

    /**
     * Sets the default SMTP client of the class which is assigned to
     * newly created objects.
     *
     * @param   Net_SMTP    $client
     */
    public static function setDefaultSMTPClient(Net_SMTP $client)
    {
        self::$_defaultSMTPClient = $client;
    }

    /**
     * Constructor.
     * Creates a new mail server.
     * Overrides parent constructor by setting the SMTP client of the object
     * from the defualt static SMTP client.
     *
     * @param   string  $hostname
     */
    public function __construct($hostname)
    {
        parent::__construct($hostname);
        $client = self::getDefaultSMTPClient();
        if ($client) {
            $this->setSMTPClient($client);
        }
        $this->_options[self::OPT_SMTP_CONNECT_TIMEOUT] = 10;
        $this->_options[self::OPT_SMTP_PORT] = null;
        $this->_options[self::OPT_SMTP_SOCKET_OPTIONS] = null;
    }
    
    /**
     * Initializes/Insanciates a SMTP client object, makes it ready
     * to be used for the mail server host.
     * 
     * @param   Net_SMTP|null       $smtp   an SMTP client or null to create new
     * @return  Net_SMTP 
     */
    protected function _initSMTPClient($smtp=null)
    {
        if (!$smtp) $smtp = new Net_SMTP();
        
        $smtp->host = $this->getIPv4Address();
        
        if ($this->_options[self::OPT_SMTP_PORT]) {
            $smtp->port = $this->_options[self::OPT_SMTP_PORT];
        }
        
        if ($this->_options[self::OPT_SMTP_SOCKET_OPTIONS]) {
            $smtp->_socket_options = $this->_options[self::OPT_SMTP_SOCKET_OPTIONS];
        }
        
        if ($this->_options[self::OPT_SMTP_CONNECT_TIMEOUT]) {
            $smtp->_timeout = $this->_options[self::OPT_SMTP_CONNECT_TIMEOUT];
        }
        
        return $smtp;
    }
    
    /**
     * Returns the SMTP client of the mail server.
     * By default creates a new Nt_SMTP object if not disabled.
     *
     * @param   bool        $autoInstanciate
     * @return  Net_SMTP
     */
    public function getSMTPClient($autoInsanciate=true)
    {
        if (!$this->_smtpClient && $autoInsanciate) {
            $this->_smtpClient = $this->_instanciateSMTPClient();
        }
        return $this->_smtpClient;
    }

    /**
     * Sets the SMTP client of the mail server.
     * 
     * @param   Net_SMTP    $client
     * @return  Parsonline_Common_Entity_Host_MailServer   object self reference
     */
    public function setSMTPClient(Net_SMTP $client)
    {
        $this->_smtpClient = $client;
        return $this;
    }
    
    /**
     * Checks if the mail server responds to SMTP requests.
     *
     * @param   bool            $realtime       regardless of previouse on memory status, try to send a request right now
     * @param   bool            $autoset        automatically set the status of the object. default is true.
     * @return  bool
     * @throws  Parsonline_Exception_ContextException on no hostname/IPv4/SMTP client
     *          set yet for mail server
     */
    public function respondsToSMTP($realtime=false, $autoset=true)
    {
        if ( $realtime || is_null($this->_respondsToSMTP) ) {
            $hostname = $this->_hostname ? $this->_hostname : $this->getIPv4Address();
            if (!$hostname) {
                /**
                * @uses     Parsonline_Exception_ContextException
                */
                require_once('Parsonline/Exception/ContextException.php');
                throw new Parsonline_Exception_ContextException("failed to send SMTP request to host. No hostname/IPv4 is set yet", 1);
            }
            $smtp = $this->getSMTPClient();
            if (!$smtp) {
                /**
                * @uses     Parsonline_Exception_ContextException
                */
                require_once('Parsonline/Exception/ContextException.php');
                throw new Parsonline_Exception_ContextException("failed to send SMTP request to host. No SMTP cient is set yet", 2);
            }
            $smtp->host = $hostname;
            $responds = $smtp->connect(intval($this->_options[self::OPT_SMTP_CONNECT_TIMEOUT]), false);
            /**
             * @uses PEAR
             */
            $responds = (PEAR::isError($responds) || !$responds) ? false : true;
            $smtp->disconnect();
            if ($autoset) $this->setRespondToSMTPStatus($responds);
        } else {
            $responds = $this->_respondsToSMTP;
        }
        return $responds;
    }

    /**
     * Sets the status of mail server in response to SMTP requests
     *
     * @param   bool        $responds
     * @return  Parsonline_Common_Entity_Host_MailServer    object self reference
     */
    public function setRespondToSMTPStatus($responds=true)
    {
        $this->_respondsToSMTP = true && $responds;
        return $this;
    }
    
    /**
     * Returns an array representaion of the mail server.
     * Overrides parent method by adding 'responds_smtp' key that holdes a boolean
     * value to indicate if mail server responds to SMTP requests or not.
     * 
     * @return array
     */
    public function __toArray()
    {
        $result = parent::__toArray();
        $result['responds_smtp'] = $this->_respondsToSMTP;
        return $result;
    }
}
