<?php
//Parsonline/ZF/Application/Resource/Abstract.php
/**
 * Defines Parsonline_ZF_Application_Resource_Abstract class.
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
 * @author      Mohammad Emami<mo.razavi@parsonline.com>
 * @version     2.0.0 2011-12-20
 */

/**
 * Parsonline_ZF_Application_Resource_Abstract
 *
 * Abstract class for all zend framework application resources defined
 * in Parsonline library.
 * 
 * @uses    Zend_Application_Resource_ResourceAbstract
 */
require_once('Zend/Application/Resource/ResourceAbstract.php');
abstract class Parsonline_ZF_Application_Resource_Abstract extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * If by default resources should trigger an error before they throw
     * exceptions.
     * 
     * @var bool
     */
    protected static $_triggerErrorsByDefault = true;
    
    /**
     * The default error level that the trigger_error would use.
     * 
     * @var int
     */
    protected static $_defaultTriggerErrorLevel = E_USER_WARNING;
    
    /**
     * If resource should trigger an error before they throw
     * exceptions
     * 
     * @var bool
     */
    protected $_triggerErrors = null;
        
    /**
     * The error level that the trigger_error would use for this resource.
     * 
     * @var int
     */
    protected $_triggerErrorLevel = null;

    /**
     * If by default all resources should trigger PHP errors on errors.
     * 
     * @return  bool
     */
    public static function doesTriggerErrorsByDefault()
    {
        return self::$_triggerErrorsByDefault;
    }

    /**
     * Sets if by default all resources should trigger PHP errors on errors.
     *
     * @param   bool    $do
     */
    public static function shouldTriggerErrorsByDefault($do=true)
    {
        self::$_triggerErrorsByDefault = true && $do;
    }
    
    /**
     * Returns the default error level used to trigger errors by all
     * resource of this class.
     * 
     * @return  int
     */
    public static function getDefaultTriggerErrorLevel()
    {
        return self::$_defaultTriggerErrorLevel;
    }
    
    /**
     * Returns the default error level used to trigger errors by all
     * resource of this class.
     * 
     * @param  int  $level      error type
     */
    public static function setDefaultTriggerErrorLevel($level)
    {
        self::$_defaultTriggerErrorLevel = intval($level);
    }
    
    /**
     * If resources should trigger an error before they throw
     * exceptions.
     * 
     * @see doesTriggerErrorsByDefault()
     * 
     * @return  bool
     */
    public function doesTriggerErrors()
    {
        if ($this->_triggerErrors === null) {
            $this->_triggerErrors = self::doesTriggerErrorsByDefault();
        }
        return $this->_triggerErrors;
    }

    /**
     * Sets if resources should trigger an error before they throw
     * exceptions.
     * 
     * @see shouldTriggerErrorsByDefault()
     *
     * @param   bool    $do
     */
    public function shouldTriggerErrors($do=true)
    {
        $this->_triggerErrors = true && $do;
    }
    
    /**
     * If resources should trigger an error before they throw
     * exceptions.
     * 
     * @see getDefaultTriggerErrorLevel()
     * 
     * @return  Int
     */
    public function getTriggerErrorLevel()
    {
        if ($this->_triggerErrorLevel === null) {
            $this->_triggerErrorLevel = self::getDefaultTriggerErrorLevel();
        }
        return $this->_triggerErrorLevel;
    }

    /**
     * Sets if resources should trigger an error before they throw
     * exceptions.
     * 
     * @see shouldTriggerErrorsByDefault()
     *
     * @param   bool    $do
     */
    public function setTriggerErrorLevel($level)
    {
        $this->_triggerErrorLevel = intval($level);
    }

    /**
     * Triggers PHP errors, only if the class option to trigger
     * errors is true. uses class error level if no level is specified.
     *
     * @param   string  $message    error message
     * @param   int     $level      error level integer. null to use class default
     */
    protected function _triggerError($message, $level=null)
    {
        if ($this->doesTriggerErrors()) {
            trigger_error(strval($message), is_null($level) ? $this->getTriggerErrorLevel() : $level);
        }
    }
}