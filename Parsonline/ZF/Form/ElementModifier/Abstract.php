<?php
//Parsonline/ZF/Form/ElementModifier/Abstract.php
/**
 * Defines Parsonline_ZF_Form_ElementModifier_Abstract class.
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
 * @package     Parsonline_ZF_Form
 * @subpackage  ElementModifier
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.1.0 2010-12-26
 */

/**
 * @uses    Zend_Config
 * @uses    Zend_Form_Element
 */
require_once('Zend/Config.php');
require_once('Zend/Form/Element.php');

/**
 * Parsonline_ZF_Form_ElementModifier_Abstract
 * 
 * Abstract class to implement general functionality defined in the
 * Parsonline_ZF_Form_ElementModifier_Interface.
 */
abstract class Parsonline_ZF_Form_ElementModifier_Abstract
{
    /**
     * Modifier options
     * 
     * @var array
     */
    protected $_options = array();
    
    /**
     * Constructor
     *
     * @param  array|Zend_Config $options
     * @return void
     */
    public function __construct($options=null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        } elseif ($options instanceof Zend_Config) {
            $this->setConfig($options);
        }
    }

    /**
     * Set options
     *
     * @param  array    $options
     * @return Parsonline_ZF_Form_ElementModifier_Abstract
     */
    public function setOptions(array $options)
    {
        $this->_options = $options;
        return $this;
    }

    /**
     * Set options from config object
     *
     * @param  Zend_Config  $config
     * @return Parsonline_ZF_Form_ElementModifier_Abstract
     */
    public function setConfig(Zend_Config $config)
    {
        return $this->setOptions($config->toArray());
    }

    /**
     * Set option
     *
     * @param  string   $key
     * @param  mixed    $value
     * @return Parsonline_ZF_Form_ElementModifier_Abstract
     */
    public function setOption($key, $value)
    {
        $this->_options[(string) $key] = $value;
        return $this;
    }

    /**
     * Get option
     *
     * @param  string $key
     * @return mixed
     */
    public function getOption($key)
    {
        $key = (string) $key;
        if (isset($this->_options[$key])) {
            return $this->_options[$key];
        }
        return null;
    }

    /**
     * Retrieve options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Remove single option.
     * Returns true of element exited, or false if no such element
     * existed.
     *
     * @param mixed $key
     * @return bool
     */
    public function removeOption($key)
    {
        if (null !== $this->getOption($key)) {
            unset($this->_options[$key]);
            return true;
        }
        return false;
    }

    /**
     * Clear all options
     *
     * @return Parsonline_ZF_Form_ElementModifier_Abstract
     */
    public function clearOptions()
    {
        $this->_options = array();
        return $this;
    }

    /**
     * Set current form element
     *
     * @param  Zend_Form_Element    $element
     * @return Parsonline_ZF_Form_ElementModifier_Abstract
     */
    public function setElement(Zend_Form_Element $element)
    {
        $this->_element = $element;
        return $this;
    }

    /**
     * Retrieve current element
     *
     * @return Zend_Form_Element
     */
    public function getElement()
    {
        return $this->_element;
    }
}