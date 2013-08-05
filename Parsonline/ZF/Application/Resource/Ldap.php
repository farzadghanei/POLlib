<?php
//Parsonline/ZF/Application/Resource/Ldap.php
/**
 * Defines Parsonline_ZF_Application_Resource_Ldap class.
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
 * @copyright  Copyright (c) 2010-2011-2012 ParsOnline, Inc. (www.parsonline.com)
 * @license    Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Parsonline_ZF_Application
 * @subpackage  Resource
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.0.1 2012-07-08
 */

/**
 * Parsonline_ZF_Application_Resource_Ldap
 * 
 * Application resource to initialize a Zend_Ldap object.
 * 
 * @uses Parsonline_ZF_Application_Resource_Abstract
 * @uses Zend_Ldap
 */
require_once('Parsonline/ZF/Application/Resource/Abstract.php');
require_once('Zend/Ldap.php');

class Parsonline_ZF_Application_Resource_Ldap extends Parsonline_ZF_Application_Resource_Abstract
{
    /**
     *
     * @var Zend_Ldap
     */
    protected $_ldap = null;

    /**
     * associative array of parameters
     * 
     * @var array
     */
    protected $_params = array();

    /**
     * Creates a Zend_Ldap obect based on the parameters
     *
     * @return  Zend_Ldap
     */
    public function getLdap()
    {
        if (!$this->_ldap) {
            $this->_ldap = new Zend_Ldap( $this->getParams() );
        }
        return $this->_ldap;
    }

    /**
     * Returns associative array of params for the resource.
     * 
     * @return  array
     * @throws  Parsonline_Exception_ContextException
     */
    public function getParams()
    {
        if ( !$this->_params ) {
            $options = $this->getOptions();
            if ( !array_key_exists('params', $options) ) {
                $err = 'ldap params is missing in application configuration';
                $this->_triggerError($err);
                /**
                 * @uses    Parsonline_Exception_ContextException
                 */
                require_once('Parsonline/Exception/ContextException.php');
                throw new Parsonline_Exception_ContextException($err);
            }
            if ( !is_array($options['params']) ) {
                $err = 'ldap params should be an array of parameters in application configuration';
                $this->_triggerError($err);
                /**
                 * @uses    Parsonline_Exception_ContextException
                 */
                require_once('Parsonline/Exception/ContextException.php');
                throw new Parsonline_Exception_ContextException($err);
            }
            $this->_params = $options['params'];
        }
        return $this->_params;
    }

    /**
     * Initializes the Zend_Ldap reference.
     * 
     * @return  Zend_Ldap
     */
    public function init()
    {
        return $this->getLdap();
    }
}