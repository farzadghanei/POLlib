<?php
//Parsonline/ZF/Form/IElementModifier.php
/**
 * Defines Parsonline_ZF_Form_IElementModifier interface.
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
 * @version     0.2.1 2010-12-26
 */

/**
 * @uses    Zend_Form_Element
 * @uses    Zend_Config
 */
require_once('Zend/Form/Element.php');
require_once('Zend/Config.php');

interface Parsonline_ZF_Form_IElementModifier
{
    /**
     * Constructor
     *
     * Accept options during initialization.
     *
     * @param  array|Zend_Config    $options
     * @return  void
     */
    public function __construct($options=null);

    /**
     * Set an element to element modifier.
     *
     * @param   Zend_Form_Element       $element
     * @return  Parsonline_ZF_Form_IElementModifier
     */
    public function setElement(Zend_Form_Element $element);

    /**
     * Retrieve current element
     *
     * @return  Zend_Form_Element
     */
    public function getElement();

    /**
     * Set options from an array
     *
     * @param   array   $options
     * @return  Parsonline_ZF_Form_IElementModifier
     */
    public function setOptions(array $options);

    /**
     * Set options from a config object
     *
     * @param   Zend_Config     $config
     * @return  Parsonline_ZF_Form_IElementModifier
     */
    public function setConfig(Zend_Config $config);

    /**
     * Set a single option
     *
     * @param   string  $key
     * @param   mixed   $value
     * @return  Parsonline_ZF_Form_IElementModifier
     */
    public function setOption($key, $value);

    /**
     * Retrieve a single option
     *
     * @param  string   $key
     * @return  mixed
     */
    public function getOption($key);

    /**
     * Retrieve all options
     *
     * @return array
     */
    public function getOptions();

    /**
     * Delete a single option
     *
     * @param  string $key
     * @return bool
     */
    public function removeOption($key);

    /**
     * Clear all options
     *
     * @return  Parsonline_ZF_Form_IElementModifier
     */
    public function clearOptions();
    
    /**
     * Modifies the form element object.
     * 
     * @return  Parsonline_ZF_Form_IElementModifier
     */
    public function modifyFormElement();
}
