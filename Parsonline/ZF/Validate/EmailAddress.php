<?php
//Parsonline/ZF/Validate/EmailAddress.php
/**
 * Defines Parsonline_ZF_Validate_EmailAddress class.
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
 * @copyright   Copyright (c) 2011-2012 ParsOnline, Inc. (www.parsonline.com)
 * @license     Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Parsonline_ZF_Validate
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.4.0 2012-08-26
 */

/**
 * @uses    Zend_Validate_EmailAddress
 * @uses    Zend_Validate_Hostname
 * @uses    Net_SMTP
 * 
 * @link    http://pear.php.net/package/Net_SMTP
 */
require_once('Zend/Validate/EmailAddress.php');
require_once('Zend/Validate/Hostname.php');
require_once('Net/SMTP.php');

/**
 * Parsonline_ZF_Validate_EmailAddress
 * 
 * Extended mail validator that validates existance of the email address account
 * on the domain mail servrs using SMTP.
 */
class Parsonline_ZF_Validate_EmailAddress extends Zend_Validate_EmailAddress
{
    const INVALID_ACCOUNT = 'emailAddressInvalidAccount';
    const INVALID_SMTP_MAIL_FROM = 'emailAddressInvalidSMTPMailFrom';
    
    /**
     * @var array
     */
    protected $_messageTemplates = array(
        self::INVALID            => "Invalid type given. String expected",
        self::INVALID_FORMAT     => "'%value%' is no valid email address in the basic format local-part@hostname",
        self::INVALID_HOSTNAME   => "'%hostname%' is no valid hostname for email address '%value%'",
        self::INVALID_MX_RECORD  => "'%hostname%' does not appear to have a valid MX record for the email address '%value%'",
        self::INVALID_SEGMENT    => "'%hostname%' is not in a routable network segment. The email address '%value%' should not be resolved from public network",
        self::DOT_ATOM           => "'%localPart%' can not be matched against dot-atom format",
        self::QUOTED_STRING      => "'%localPart%' can not be matched against quoted-string format",
        self::INVALID_LOCAL_PART => "'%localPart%' is no valid local part for email address '%value%'",
        self::LENGTH_EXCEEDED    => "'%value%' exceeds the allowed length",
        self::INVALID_ACCOUNT    => "'%value%' account does not exist on remote mail server",
        self::INVALID_SMTP_MAIL_FROM  => "'%value%' account could not be validated because remote mail server requires valid helo domain and sender email address on SMTP conversation"
    );
    
    /**
     * Internal options array
     */
    protected $_options = array(
        'mx'       => false,
        'deep'     => false,
        'domain'   => true,
        'allow'    => Zend_Validate_Hostname::ALLOW_DNS,
        'hostname' => null,
        'account'  => false,
        'maxMXHosts' => 0,
        'permissive' => false,
        'socket' => array('timeout' => 60, 'connect_timeout' => 60)
    );
    
    /**
     * Should dump data for debugging
     * 
     * @var bool
     */
    protected $_debug = false;
    
    /**
     * A stream resource to write the debugging information to
     * 
     * @var resource
     */
    protected $_debugStream = null;
    
    /**
     * The local part of the email address to use as the sender in
     * the SMTP conversation.
     * 
     * @var string
     */
    protected $_fromLocalPart = '';
    
    /**
     * The hostname to introduce in the helo conversation to remote
     * SMTP server.
     * 
     * @var string
     */
    protected $_fromHostname = '';
    
    /**
     * Constructor.
     * 
     * Instantiates Email validator.
     *
     * The following option keys are supported:
     * 'hostname' => A hostname validator, see Zend_Validate_Hostname
     * 'allow'    => Options for the hostname validator, see Zend_Validate_Hostname::ALLOW_*
     * 'mx'       => If MX check should be enabled, boolean
     * 'deep'     => If a deep MX check should be done, boolean
     * 'validate_account' => if should use SMTP to validate the account on mail server
     * 'debug'  => enabled debugging
     * 'maxMXhosts' => maximum number of MX hosts to check for the domain
     * 'from_localpart' => local part of the fake sender on SMTP connection
     * 'from_hostname' => hostname of the fake sender on SMTP connection
     * 'permissive' => use permissive mode for SMTP authentication
     * 'socket' => associative array of options for SMTP socket
     *         'timeout' => I/O timeout of SMTP socket
     *         'connect_timeout' => timeout of SMPT connection
     *
     * @param   array|Zend_Config $options OPTIONAL
     * @return  void
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        $this->_options['socket'] = $this->getDefaultSocketOptions();
        parent::__construct($options);
    }
    
    /**
     * Returns an associative array of default options to use for the
     * SMTP socket.
     * 
     * @return array
     */
    public function getDefaultSocketOptions()
    {
        $dst = intval(ini_get('default_socket_timeout'));
        $opts = array('timeout' => $dst, 'connect_timeout' => $dst);
        return $opts;
    }
    
    /**
     * Returns if the validator is running in debug mode.
     * 
     * @return bool
     */
    public function isDebugMode()
    {
        return $this->_debug;
    }
    
    /**
     * Sets the validator to work in debug mode.
     * Debug mode dumps the operations of validation to the
     * standard output.
     * 
     * @param   bool    $debug
     * @return  Parsonline_ZF_Validate_EmailAddress 
     */
    public function setDebugMode($debug)
    {
        $this->_debug = (bool) $debug;
        return $this;
    }
    
    /**
     * Returns the stream that the debugging information would be written to.
     * 
     * @return stream
     */
    public function &getDebugStream()
    {
        if (!$this->_debugStream) {
            $this->_debugStream = fopen("php://stdout", "a");
        }
        return $this->_debugStream;
    }
    
    /**
     * Sets the stream that the debugging information would be written to.
     * 
     * @param   stream  &$stream
     * @return  Parsonline_ZF_Validate_EmailAddress 
     */
    public function setDebugStream(&$stream)
    {
        $this->_debugStream = &$stream;
        return $this;
    }
    
    /**
     * Returns the local part of the email address to use as the sender in
     * the SMTP conversation.
     * 
     * @return  string
     */
    public function getFromLocalPart()
    {
        return $this->_fromLocalPart;
    }
    
    /**
     * Sets the local part of the email address to use as the sender in
     * the SMTP conversation.
     * 
     * @param   string      $from
     * @return  Parsonline_ZF_Validate_EmailAddress
     */
    public function setFromLocalPart($from)
    {
        $this->_fromLocalPart = (string) $from;
        return $this;
    }
    
    /**
     * Returns the hostname of the email address to use as the sender in
     * the SMTP conversation.
     * 
     * @return  string
     */
    public function getFromHostname()
    {
        return $this->_fromHostname;
    }
    
    /**
     * Sets the hostname of the email address to use as the sender in
     * the SMTP conversation.
     * 
     * @param   string      $host
     * @return  Parsonline_ZF_Validate_EmailAddress
     */
    public function setFromHostname($host)
    {
        $this->_fromHostname = (string) $host;
        return $this;
    }
    
    /**
     * Returns if the email account would be validated on remote mail server
     *
     * @return boolean
     */
    public function getValidateAccount()
    {
        return $this->_options['account'];
    }

    /**
     * Set if the email account would be validated on remote mail server.
     * This forces to check for domain and mx records as well.
     * 
     * Setting this option, would automatically enable checking of domain and
     * MX records as well.
     * 
     * @param   boolean     $account    Set allowed to true to validate for MX records, and false to not validate them
     * @return  Parsonline_ZF_Validate_EmailAddress
     */
    public function setValidateAccount($account)
    {
        $this->_options['account'] = (bool) $account;
        
        if (!$this->_options['domain']) {
            $this->setDomainCheck(true);
        }
        
        if (!$this->_options['mx']) {
            $this->setValidateMx(true);
        }
        
        return $this;
    } // public function setValidateAccount()
    
    /**
     * Returns the maximum number of MX hosts that are going to be checked
     * to validate the email. 0 means no max is forced an all MX records
     * of the domain are going to be ckecked.
     * 
     * @return  int
     */
    public function getMaxMXHosts()
    {
        return $this->_options['maxMXHosts'];
    }
    
    /**
     * Sets the maximum number of MX hosts that are going to be checked
     * to validate the email. 0 means no max is forced an all MX records
     * of the domain are going to be ckecked.
     * 
     * @param   int $max
     * @throws  Parsonline_Exception_ValueException on negative value
     * @return  Parsonline_ZF_Validate_EmailAddress
     */
    public function setMaxMXHosts($max)
    {
        $max = intval($max);
        if ($max < 0) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException("Invalid maximum number of MX hosts.");
        }
        $this->_options['maxMXHosts'] = $max;
        return $this;
    }
    
    /**
     * Returns an associative array of options for the SMTP socket
     * 
     * @return array
     */
    public function getSocketOptions()
    {
        return $this->_options['socket'];
    }
    
    /**
     * Returns true if SMTP validation is in permissive mode or false if not.
     * 
     * @return bool
     */
    public function isPermissive()
    {
        return $this->_options['permissive'];
    }
    
    /**
     * Sets the SMTP validation to permissive mode.
     * 
     * @param   bool    $perm
     * @return  Parsonline_ZF_Validate_EmailAddress
     */
    public function setPermissive($perm)
    {
        $this->_options['permissive'] = (bool) $perm;
        return $this;
    }
    
    /**
     * Set an option for the SMTP socket.
     * 
     * @see getSocketOptions()
     * 
     * @param   string  $key        option key
     * @para    mixed   $value
     * @return  Parsonline_ZF_Validate_EmailAddress
     * @throws  Parsonline_Exception_ValueException on invalid key
     */
    public function setSocketOption($key, $value)
    {
        if (!isset($this->_options['socket'][$key])) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException("Invalid socket option '{$key}'");
        }
        $this->_options['socket'][$key] = $value;
        return $this;
    }
    
    /**
     * Set options for the email validator
     *
     * @param   array   $options
     * @return  Parsonline_ZF_Validate_EmailAddress fluid interface
     */
    public function setOptions(array $options = array())
    {
        parent::setOptions($options);
        if (isset($options['socket']) && is_array($options['socket'])) {
            foreach($options['socket'] as $key => $value) {
                if ( isset($this->_options['socket'][$key]) ) {
                    $this->_options['socket'][$key] = $value;
                }
            }
        }
        if (isset($options['maxMXHosts'])) {
            $this->setMaxMXHosts($options['maxMXHosts']);
        }
        if (isset($options['debug'])) {
            $this->setDebugMode(true);
        }
        if (isset($options['validate_account'])) {
            $this->setValidateAccount(true);
        }
        if (isset($options['from_localpart'])) {
            $this->setFromLocalPart($options['from_localpart']);
        }
        if (isset($options['from_hostname'])) {
            $this->setFromHostname($options['from_hostname']);
        }
        if (isset($options['permissive'])) {
            $this->setPermissive($options['permissive']);
        }
        
        return $this;
    }
    
    /**
     * Prints out the message to the debugging stream (standard output by default)
     * in the debug mode only.
     * 
     * Returns number of bytes written to debugging stream
     * 
     * @param   string  $msg
     * @return  int|false
     */
    protected function _debug($msg)
    {
        $wrote = 0;
        if ($this->_debug) {
            $stream = $this->getDebugStream();
            if ($stream) {
                $wrote = fwrite($stream, $msg . PHP_EOL);
            }
        }
        return $wrote;
    }
    
    /**
     * Validates the localpart of the email address to check
     * if the account exists on the mail servers of the domain or not.
     * 
     * @return  bool
     * @throws  Parsonline_Exception_ContextException on no from local part and hostname specified
     */
    protected function _validateAccount()
    {
        $mxHosts = array();
        getmxrr($this->_hostname, $mxHosts);
        
        if (!$mxHosts) {
            $this->_error(self::INVALID_MX_RECORD);
            return false;
        }
        
        if (!$this->_fromLocalPart || !$this->_fromHostname) {
            /**
             *@uses Parsonline_Exception_ContextException 
             */
            require_once("Parsonline/Exception/ContextException.php");
            throw new Parsonline_Exception_ContextException(
                "Failed to validate account via SMTP. No from email address and hostname is specified"
            );
        }
        
        $from = $this->_fromLocalPart . '@' . $this->_fromHostname;
        $mailAddress = $this->_localPart . '@' . $this->_hostname;
        
        $this->_debug("validating account availabilty for '{$mailAddress}'");
        
        
        if ( isset($this->_options['maxMXHosts']) && $this->_options['maxMXHosts']) {
            $maxMXHosts = intval($this->_options['maxMXHosts']);
        } else {
            $maxMXHosts = 0;
        }
        
        $mxCounter = 0;
        $sockOpts = $this->_options['socket'];
        
        /**
         * iterate over all MX hosts, check the account on each one.
         * 
         * If least one of the MX servers validate the account, the account
         * is valid.
         * 
         * In permissive mode, the user is supposed to valid,
         * unless we get an error on one of the SMTP servers.
         * 
         */
        
        $permissive = (bool) $this->_options['permissive'];
        $isValid = $permissive;
        $mxServersResults = array();
        
        foreach ($mxHosts as $host) {
            if ( $maxMXHosts > 0 && ++$mxCounter > $maxMXHosts ) {
                break;
            }
            
            $mxServersResults[$host] = null;
            $this->_debug("checking mail server {$host}");
            
            /**
             * @uses    Net_SMTP
             */
            $smtp = new Net_SMTP($host);
            $smtp->setTimeout($sockOpts['timeout']);
            if ($this->_debug) $smtp->setDebug(true);            

            $res = $smtp->connect($sockOpts['connect_timeout']);
            if ( PEAR::isError($res) ) {
                unset($smtp);
                continue; // check the next mail server
            }

            $res = $smtp->helo($this->_fromHostname);
            if (PEAR::isError($res)) {
                if (!$permissive) {
                    $this->_error(self::INVALID_SMTP_MAIL_FROM);
                    $mxServersResults[$host] = false;
                }
            } else {
                $res = $smtp->mailFrom($from);
                if (PEAR::isError($res)) {
                    if (!$permissive) {
                        $this->_error(self::INVALID_SMTP_MAIL_FROM);
                        $mxServersResults[$host] = false;
                    }
                } else {
                    $res = $smtp->rcptTo($mailAddress);
                    if (PEAR::isError($res)) {
                        $this->_error(self::INVALID_ACCOUNT);
                        $mxServersResults[$host] = false;
                    } else {
                        $mxServersResults[$host] = true; // the SMTP server has validated the account
                    }
                }
            }

            // force close the SMTP socket
            $res = $smtp->disconnect();
            if (PEAR::isError($res) && is_object($smtp->_socket)) {
                $smtp->_socket->disconnect();
            }
            unset($smtp);
            
            // to help mass mail validators make sure system resources are freed
            if ( function_exists('gc_enabled') && function_exists('gc_collect_cycles') ) {
                if (gc_enabled()) gc_collect_cycles();
            }
            
            // if user is validated, then stop checking other MX servers
            if ($mxServersResults[$host] === true) break;
        } // foreach()
        
        foreach($mxServersResults as $host => $mxValidated) {
            if ($mxValidated) {
                $isValid = true;
                break;
            } elseif ($mxValidated === false) {
                $isValid = false;
            }
        }
        
        return $isValid;
    } // protected function _validateAccount()
    
    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if and only if $value is a valid email address
     * according to RFC2822
     *
     * @link   http://www.ietf.org/rfc/rfc2822.txt RFC2822
     * @link   http://www.columbia.edu/kermit/ascii.html US-ASCII characters
     * @param  string $value
     * @return boolean
     * @throws  Exception on no from local part and hostname specified if account check is enabled
     */
    public function isValid($value)
    {
        $valid = parent::isValid($value);
        if ($valid && $this->_options['account']) {
            $valid = $this->_validateAccount();
        }
        return $valid;
    }
}