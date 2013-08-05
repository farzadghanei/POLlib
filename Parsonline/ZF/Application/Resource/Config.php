<?php
//Parsonline/ZF/Application/Resource/Config.php
/**
 * Defines Parsonline_ZF_Application_Resource_Config class.
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
 * @copyright  Copyright (c) 2010-2012 ParsOnline, Inc. (www.parsonline.com)
 * @license    Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Parsonline_ZF_Application
 * @subpackage  Resource
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     1.2.0 2012-08-04
 */

/**
 * Parsonline_ZF_Application_Resource_Config
 *
 * Application resource to capsulate application configurations. generates
 * a Zend_Config instance.
 *
 * @uses    Zend_Config_Ini
 * @uses    Zend_Config_Xml
 * @uses    Parsonline_ZF_Application_Resource_Abstract
 */

require_once('Parsonline/ZF/Application/Resource/Abstract.php');
class Parsonline_ZF_Application_Resource_Config extends Parsonline_ZF_Application_Resource_Abstract
{
    const TYPE_INI = 'ini';
    const TYPE_XML = 'xml';
    const TYPE_YAML = 'yaml';
    const TYPE_ARRAY = 'array';
    const TYPE_JSON = 'json';

    /**
     * The referene to Zend_Config
     *
     * @var Zend_Config
     */
    protected $_config = null;

    /**
     * The absolute path to config file
     * 
     * @var string
     */
    protected $_filename = null;

    /**
     * The type of the config.
     * use class constants for convinience.
     *
     * @var string
     */
    protected $_configType = null;
    
    /**
     * Associative array of data to be used as the initial value of the
     * configuration.
     * 
     * @var array
     */
    protected $_initialData = array();
    
    /**
     * Returns the absolutep path to config file
     *
     * @return  string  full path to file
     * @throws  Parsonline_Exception_ContextException on missinge configurations
     *          Parsonline_Exception_IOException on not readable file
     */
    public function getFilename()
    {
        if (!$this->_filename) {
            $options = $this->getOptions();
            if ( !array_key_exists('file', $options) ) {
                $err = 'file option is missing for config resource';
                $this->_triggerError($err);
                /**
                 * @uses    Parsonline_Exception_ContextException
                 */
                require_once('Parsonline/Exception/ContextException.php');
                throw new Parsonline_Exception_ContextException($err);
            }
            $this->setFilename($options['file']);
        }
        return $this->_filename;
    } // public function getFilename()

    /**
     * Sets the path to the config file
     * 
     * @param   string  $file   full file path
     * @return  Parsonline_ZF_Application_Resource_Config
     * @throws  Parsonline_Exception_IOException on not readable file
     */
    public function setFilename($file)
    {
        $file = realpath($file);
        if ( !$file || !is_file($file) || !is_readable($file) ) {
            $err = "invalid path for config resource. path '{$file}' is not a readable file.";
            $this->_triggerError($err);
            /**
             * @uses    Parsonline_Exception_IOException
             */
            require_once('Parsonline/Exception/IOException.php');
            throw new Parsonline_Exception_IOException($err);
        }
        $this->_filename = $file;
    } // public function setFilename()

    /**
     * Returns the config type of the config object.
     * use class constants for convinience.
     *
     * @return  string
     * @throws  Parsonline_Exception_ContextException on no type option specified
     *          Parsonline_Exception_ValueException on invalid type value
     */
    public function getConfigType()
    {
        if ( !$this->_configType ) {
            $options = $this->getOptions();
            if ( !array_key_exists('type', $options) ) {
                $err = 'type option is missing for config resource';
                $this->_triggerError($err);
                /**
                 * @uses    Parsonline_Exception_ContextException
                 */
                require_once('Parsonline/Exception/ContextException.php.php');
                throw new Parsonline_Exception_ContextException($err);
                
            }
            $this->setConfigType($options['type']);
        }
        return $this->_configType;
    } // public function getConfigType()

    /**
     * Sets the config type of the config object.
     * use class constants for convinience.
     *
     * @param   string          $type
     * @return  Parsonline_ZF_Application_Resource_Config
     * @throws  Parsonline_Exception_ValueException on invalid type
     */
    public function setConfigType($type)
    {
        switch($type)
        {
            case self::TYPE_INI:
            case self::TYPE_XML:
            case self::TYPE_YAML:
            case self::TYPE_ARRAY:
                $this->_configType = $type;
                break;
            default:
                /**
                * @uses    Parsonline_Exception_ValueException
                */
                require_once('Parsonline/Exception/ValueException.php');
                throw new Parsonline_Exception_ValueException("config type '{$type}' is not supported");
        }
        return $this;
    } // public function setConfigType()

    /**
     * Instanciates a Zend_Config_Ini object based on the option.
     *
     * @return  Zend_Config
     * @throws  Parsonline_Exception_ValueException, Parsonline_Exception on invalid options
     *          Zend_Exception from underlying Zend_Config_* objects. for invalid actual data of options.
     */
    public function getConfig()
    {
        if ( !$this->_config ) {
            $configFile = $this->getFilename();
            $configType = $this->getConfigType();
            $environment = (string)$this->getBootstrap()->getEnvironment();
            /**
            * @uses    Zend_Config
            */
            require_once('Zend/Config.php');
            
            switch ($configType) {
                case self::TYPE_INI:
                    /**
                     * @uses    Zend_Config_Ini
                     */
                    require_once('Zend/Config/Ini.php');
                    $config = new Zend_Config_Ini($configFile, $environment);
                    break;
                case self::TYPE_XML:
                    /**
                     * @uses    Zend_Config_Xml
                     */
                    require_once('Zend/Config/Xml.php');
                    $config = new Zend_Config_Xml($configFile, $environment);
                    break;
                case self::TYPE_YAML:
                    /**
                     * @uses    Zend_Config_Yaml
                     */
                    require_once('Zend/Config/Yaml.php');
                    $config = new Zend_Config_Yaml($configFile, $environment);
                    break;
                case self::TYPE_ARRAY:
                    $config = new Zend_Config(require($configFile));
                    break;
                case self::TYPE_JSON:
                    /**
                     * @uses    Zend_Config_Json
                     */
                    require_once('Zend/Config/Json.php');
                    $config = new Zend_Config_Json($configFile);
                    break;
            }
            
            $initData = $this->getInitialData();
            $initConfig = new Zend_Config($initData, true);
            unset($initData);
            // by merging config back to init config, we make sure configurations could overwrite init values
            $initConfig->merge($config);
            unset($config);
            
            $initConfig->setReadOnly();
            $this->_config = $initConfig;
        }
        return $this->_config;
    } // public function getConfig()
    
    /**
     * Returns the data that is used as the initial data vlues of the confiugrations
     * when they are created.
     * 
     * @return  array
     * @throws  Parsonline_Exception_ValueException 
     */
    public function getInitialData()
    {
        if (!$this->_initialData) {
            $options = $this->getOptions();
            if ( isset($options['data']) ) {
                if (!is_array($options['data'])) {
                    /**
                     *@uses  Parsonline_Exception_ValueException
                     */
                    require_once("Parsonline/Exception/ValueException.php");
                    throw new Parsonline_Exception_ValueException("configuration resource initial data should be an array");
                }
                $this->setInitialData($options['data']);
            }
        }
        return $this->_initialData;
    }
    
    /**
     * Sets the data to be used as the initial values of the configurations.
     * 
     * @param   array   $data       associative array
     * @return  Parsonline_ZF_Application_Resource_Config 
     */
    public function setInitialData(array $data)
    {
        $this->_initialData = $data;
        return $this;
    }

    /**
     * Creates an instance of Zend_Config based on the specified type and
     * returns the object.
     * 
     * @return  Zend_Config
     */
    public function init()
    {
        return $this->getConfig();
    }
}