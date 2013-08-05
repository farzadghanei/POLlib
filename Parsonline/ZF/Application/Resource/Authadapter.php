<?php
//Parsonline/ZF/Applicatoin/Resource/Authadapter.php
/**
 *  
 * Defines Parsonline_ZF_Application_Resource_Authadapter class.
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
 * @author      Farzad Ghanei <f.ghanei@parsonline.cm>
 * @version     0.0.2 2010-05-18
 */


/**
 * @uses    Parsonline_ZF_Application_Resource_Abstract
 * @uses    Zend_Loader
 */
require_once('Parsonline/ZF/Application/Resource/Abstract.php');
require_once('Zend/Loader.php');

/**
 * Parsonline_ZF_Application_Resource_Authadapter
 * 
 * Application resource to provide an authentication adapter.
 * Creates and initializes an instance of Zend_Auth_Adapter_Abstract based on
 * the resource parameters.
 */
class Parsonline_ZF_Application_Resource_Authadapter extends Parsonline_ZF_Application_Resource_Abstract
{
    /**
     *
     * @var Zend_Auth_Adapter_Abstract
     */
    protected $_adapter = null;

    /**
     * associative array of parameters
     * 
     * @var array
     */
    protected $_params = array();

    /**
     * Returns the configued auth adapter
     * 
     * @return  Zend_Auth_Adapter_Abstract
     * @throws  Parsonline_Exception_ContextException
     */
    public function getAdapter()
    {
        if ( !$this->_adapter) {
            $options = $this->getOptions();
            if ( !array_key_exists('adapter',$options) ) {
                $err = "adapter name for authadapter resource is not specified";
                $this->_triggerError($err, E_USER_WARNING);
                /**
                 *@uses Parsonline_Exception_ContextException 
                 */
                require_once("Parsonline/Exception/ContextException.php");
                throw new Parsonline_Exception_ContextException($err);
            }
            $adapterName = ucfirst($options['adapter']);
            /**
            * @uses    Zend_Loader
            */
            require_once('Zend/Loader.php');
            $adapterName = "Zend_Auth_Adapter_{$adapterName}";
            Zend_Loader::loadClass($adapterName);
            $params = array();
            $optionResourceStack = array();
            $this->getParams();

            foreach ( $this->_params as $key => $value ) {
                if ( strpos($key,'__') === 0 ) { // special parameters starting with __ are used to link resources. this is my own convention.
                    $optionResourceStack[ substr($key,2)] = $this->getBootstrap()->getResource($value);
                } else {
                    $params[$key] = $value;
                }
            } // forach

            $adapter = new $adapterName($params);
            foreach($optionResourceStack as $option => $resource) {
                $method = null;
                if ( method_exists($adapter, "set{$option}")  ) {
                    $method = "set{$option}";
                } elseif ( method_exists($adapter, "set" . ucfirst($option) )  ) {
                    $method = "set" . ucfirst($option);
                }
                if ($method) $adapter->$method($resource);
            }
            $this->_adapter = $adapter;
        }
        return $this->_adapter;
    } // public function getAdapter()

    /**
     * Returns an associative array of params for the resource object.
     * 
     * @return  array
     * @throws  Parsonline_Exception_ContextException
     */
    public function getParams()
    {
        if ( !$this->_params ) {
            $options = $this->getOptions();
            if ( !array_key_exists('params', $options) ) {
                $err = 'authadapter params is missing';
                $this->_triggerError($err, E_USER_WARNING);
                /**
                 *@uses Parsonline_Exception_ContextException 
                 */
                require_once("Parsonline/Exception/ContextException.php");
                throw new Parsonline_Exception_ContextException($err);
            }
            if ( !is_array($options['params']) ) {
                $err = 'authadapter params should be an array of parameters in configuration';
                $this->_triggerError($err, E_USER_WARNING);
                /**
                 *@uses Parsonline_Exception_ContextException 
                 */
                require_once("Parsonline/Exception/ContextException.php");
                throw new Parsonline_Exception_ContextException($err);
            }
            $this->_params = $options['params'];
        }
        return $this->_params;
    }

    /**
     * returns a configured Zend_Auth
     *
     * @return  Zend_Auth_Adapter_Abstract
     */
    public function init()
    {
        return $this->getAdapter();
    }
}