<?php
//Parsonline/ZF/Auth.php
/**
 * Defines Parsonline_ZF_Auth class.
 * 
 * Parsonline
 *
 * Copyright (c) 2010-2012 ParsOnline, Inc. (www.parsonline.com)
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
 * @copyright   Copyright (c) 2010-2012 ParsOnline, Inc. (www.parsonline.com)
 * @license     Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     ZF
 * @version     0.0.8 2012-10-14
 * @author      Mohammad Emami <mo.razavi@parsonline.com>
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 */

/**
 * @uses    Zend_Auth
 * @uses    Zend_Auth_Adapter_Interface
 * @uses    Zend_Auth_Result
 */
require_once("Zend/Auth.php");
require_once("Zend/Auth/Adapter/Interface.php");
require_once("Zend/Auth/Result.php");

/**
 * Parsonline_ZF_Auth
 * 
 * Extends Zend_Auth to check user existance in white or black lists before authenticating.
 * 
 * @uses    Zend_Auth
 */
class Parsonline_ZF_Auth extends Zend_Auth
{
    /**
     * username is neccesary because of checking it with whitelist
     * therefore we can not rely on username and password passed to authenticate method
     * exist in predefined Auth_Adapter
     * 
     * @var string
     */
    protected $_username;

    /**
     * An array of users listed in whitelist
     * 
     * @var
     */
    protected $_whiteList = array();

    /**
     * An array of users listed in blacklist
     * 
     * @var array
     */
    protected $_blackList = array();

    /**
     * Constructor.
     * 
     * set white list, black list, username
     * 
     * @param   string  $username
     * @param   array   $config
     */
    public function __construct(array $config=array(), $username=null)
    {
        if(array_key_exists('whitelist', $config) && $config['whitelist'])
            $this->setWhiteList($config['whitelist']);

        if(array_key_exists('blacklist', $config) && $config['blacklist'])
            $this->setBlackList($config['blacklist']);

        if($username) {
            $this->setUsername($username);
        }
        parent::__construct();
    }

    /**
     * set username to authenticate
     * 
     * @param   string  $username
     * @return  Parsonline_ZF_Auth
     */
    public function setUsername($username)
    {
        $this->_username = $username;
        return $this;
    }

    /**
     * Set usernames listed in whitelist
     * 
     * @param   array   $usernames
     * @return  Parsonline_ZF_Auth
     */
    public function setWhiteList(array $usernames = array())
    {
        $this->_whiteList = $usernames;
        return $this;
    }

    /**
     * Set usernames listed in blacklist
     * 
     * @param   array   $usernames
     * @return  Parsonline_ZF_Auth
     */
    public function setBlackList(array $usernames = array())
    {
        $this->_blackList = $usernames;
        return $this;
    }

    /**
     * Authenticate the user on passed Zend_Auth_Adapter after passing authentication on black
     * and white list
     *
     * @param   Zend_Auth_Adapter_Interface    $adapter
     * @return  Zend_Auth_Result
     */
    public function authenticate(Zend_Auth_Adapter_Interface $adapter)
    {
        $username = $this->_username;
 
        $messages = array();
        $code = '';

        if ($username) {
            if($this->_whiteList) {
                if (!in_array($username, $this->_whiteList)) {
                    $code = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
                    $messages[0] = "Username is not in white list";
                }
            } elseif ($this->_blackList && in_array($username, $this->_blackList)) {
                $code = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
                $messages[0] = 'Username is in black list';
            }
        } else {
            $code = Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND;
            $messages[0] = 'A username is required';
        }

        if (isset($messages[0])) {
            return new Zend_Auth_Result(Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, $username, $messages);
        }

        return parent::authenticate($adapter);
    }
}