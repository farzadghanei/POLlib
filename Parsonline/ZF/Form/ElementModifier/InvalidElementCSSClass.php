<?php
//Parsonline/ZF/Form/ElementModifier/InvalidElementCSSClass.php
/**
 * Defines Parsonline_ZF_Form_ElementModifier_InvalidElementCSSClass class.
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
 * @version     1.0.0 2010-12-29
 */

/**
 * @uses    Parsonline_ZF_Form_ElementModifier_AppendClass
 * @uses    Parsonline_ZF_Form_IElementModifier
 */
require_once('Parsonline/ZF/Form/ElementModifier/AppendClass.php');
require_once('Parsonline/ZF/Form/IElementModifier.php');

/**
 * Parsonline_ZF_Form_ElementModifier_InvalidElementCSSClass
 * 
 * Modifies a form element by adding CSS class names to the element current
 * class stack, If the element has errors.
 */
class Parsonline_ZF_Form_ElementModifier_InvalidElementCSSClass extends Parsonline_ZF_Form_ElementModifier_AppendClass
implements Parsonline_ZF_Form_IElementModifier
{
    /**
     * The default CSS class to add to invalid elements.
     * 
     * @staticvar   string
     * @access      protected
     */
    protected static $_defaultInvalidCSSClass = 'invalid';
    
    /**
     * Returns the default CSS class to mark invalid elements with.
     * 
     * @return  string
     */
    public static function getDefaultInvalidCSSClass()
    {
        return self::$_defaultInvalidCSSClass;
    }
    
    /**
     * Sets the default CSS class to mark invalid elements with.
     * Use and empty string '' to disable the default CSS class.
     * 
     * @param   string  $class
     */
    public static function setDefaultInvalidCSSClass($class)
    {
        self::$_defaultInvalidCSSClass = trim($class);
    }
    
    /**
     * Constructor.
     * 
     * @param   array|Zend_Config   $options 
     */
    public function __construct($options=null)
    {
        parent::__construct($options);
        if (self::$_defaultInvalidCSSClass !== '') {
            $this->addClasses(self::$_defaultInvalidCSSClass);
        }
    }
    
    /**
     * Modifies the form element object by appending the CSS classes to it.
     * If the element has no CSS class attribute, it would be created for it.
     * 
     * @return  Parsonline_ZF_Form_ElementModifier_InvalidElementCSSClass
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
        $classes = $this->getClasses();
        if (!$el->hasErrors() || !$classes) return $this;
        $additionalClasses = trim(implode(' ', $classes));
        $class = $el->getAttrib('class');
        if ($class) {
            $class .= ' ' . $additionalClasses;
        } else {
            $class = $additionalClasses;
        }
        $el->setAttrib('class', trim($class));
        return $this;
    } // public function modifyFormElement()
}