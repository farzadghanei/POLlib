<?php
//Parsonline/ZF/Form/ElementModifier/AppendClass.php
/**
 * Defines Parsonline_ZF_Form_ElementModifier_AppendClass class.
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
 * @version     2.2.1 2010-12-29
 */

/**
 * @uses    Parsonline_ZF_Form_ElementModifier_Abstract
 * @uses    Parsonline_ZF_Form_IElementModifier
 */
require_once('Parsonline/ZF/Form/ElementModifier/Abstract.php');
require_once('Parsonline/ZF/Form/IElementModifier.php');

/**
 * Parsonline_ZF_Form_ElementModifier_AppendClass
 * 
 * Modifies a form element by adding CSS class names to the element current
 * class stack.
 */
class Parsonline_ZF_Form_ElementModifier_AppendClass extends Parsonline_ZF_Form_ElementModifier_Abstract
implements Parsonline_ZF_Form_IElementModifier
{
    /**
     * Constructor.
     * 
     * @param   array|Zend_Config   $options 
     */
    public function __construct($options=null)
    {
        parent::__construct($options);
        if ( !array_key_exists('classes', $this->_options) ) {
            $this->_options['classes'] = array();
        }
    }
    
    /**
     * Returns an array of CSS classes.
     * 
     * @return  array
     */
    public function getClasses()
    {
        return $this->getOption('classes');
    }
    
    /**
     * Adds a group of CSS classes to the list of target CSS classes.
     * Accepts a space separated string, or an array.
     * 
     * @param array|string  $classes
     * @return Parsonline_ZF_Form_ElementModifier_InvalidElementCSSClass 
     */
    public function addClasses($classes)
    {
        if ( !is_array($classes) ) $classes = explode(' ', $classes);
        if ( !array_key_exists('classes', $this->_options) ) {
            $this->_options['classes'] = array();
        }
        foreach($classes as $c) {
            $c = trim($c);
            if ($c !== '') array_push($this->_options['classes'], $c);
        }
        return $this;
    }
    
    /**
     * Removes a group of CSS classes from the list of target CSS classes.
     * Accepts a space separated string, or an array.
     * 
     * @param array|string  $classes
     * @return Parsonline_ZF_Form_ElementModifier_InvalidElementCSSClass 
     */
    public function removeClasses($classes)
    {
        if (!is_array($classes)) $classes = explode(' ', $classes);
        foreach ($classes as $c) {
            $c = trim($c);
            $pos = array_search($c, $this->_options['classes']);
            if ($pos === false) continue;
            unset($this->_options['classes'][$pos]);
        }
        return $this;
    }
    
    /**
     * Sets the group of CSS classes as the list of target CSS classes.
     * 
     * @param array|string  $classes
     * @return Parsonline_ZF_Form_ElementModifier_AppendClass 
     */
    public function setClasses($classes)
    {
        $this->removeOption('classes');
        $this->addClasses($classes);
        return $this;
    }
    
    /**
     * Modifies the form element object by appending the CSS classes to it.
     * If the element has no CSS class attribute, it would be created for it.
     * 
     * @return  Parsonline_ZF_Form_ElementModifier_AppendClass
     * @throws  Parsonline_Exception_ContextException on no element presence
     */
    public function modifyFormElement()
    {
        $el = $this->getElement();
        if (!$el) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "No form element is assigned to the element modifier yet"
            );
        }
        $additionalClasses = trim(implode(' ', $this->getClasses()));
        if (!$additionalClasses) return $this;
        
        $class = trim($el->getAttrib('class'));
        if ($class) {
            $class .= ' ' . $additionalClasses;
        } else {
            $class = $additionalClasses;
        }
        
        $el->setAttrib('class', $class);
        return $this;
    } // public function modifyFormElement()
}