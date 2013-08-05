<?php
//Parsonline/ZF/Form/Common.php
/**
 * Defines Parsonline_ZF_Form_Common class.
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
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.2.4 2011-01-09
 */

/**
 * @uses    Parsonline_ZF_Form
 */
require_once('Parsonline/ZF/Form.php');

/**
 * Parsonline_ZF_Form_Common
 * 
 * A preconfigured subclass of Parsonline_ZF_Form, with methods to
 * reconfigure its behaviour.
 * Provides a generic form with data persistence, security features (using
 * a hash to identify form submission), automatically mark invalid elements
 * via element modifiers by default.
 */
class Parsonline_ZF_Form_Common extends Parsonline_ZF_Form
{
    const APPEND = 'append';
    const PREPEND = 'prepend';
    const NONE = 'none';
    
    /**
     * If should automatically add a security hash element for each form.
     * 
     * @staticvar bool
     * @access protected
     */
    protected static $_defaultAddHashEelement = false;
    
    /**
     * Array of CSS classes to add to CSS classes of invalid elements,
     * for all forms. leave empty to disable.
     * 
     * @staticvar   array
     * @access  protected
     */
    protected static $_defaultInvalidElementCSSClasses = array('invalid');
    
    
    /**
     * The default TTL for persistence session
     * 
     * NOTE: Overrides the parent by increasing this value.
     * 
     * @staticvar int
     * @access protected
     */
    protected static $_defaultPersistenceSessionTTL = 86400;
    
    /**
     * Array of CSS classes to add to CSS classes of required elements,
     * for all forms. leave empty to disable.
     * 
     * @staticvar   array
     * @access  protected
     */
    protected static $_defaultRequiredElementCSSClasses = array('required');
    
    /**
     * Placement of element errors in the titles.
     * use NONE to turn off.
     * 
     * @staticvar   string
     * @access      protected
     */
    protected static $_elementErrorPlacementInTitle = self::APPEND;
    
    /**
     * Name of the hash element created for form security.
     * 
     * @var string
     * @access protected
     */
    protected $_securityHashElementName = 'hash';
    
    /**
     * Returns the array of CSS classes that would be added to the
     * CSS class of invalid elements.
     * 
     * @return   array
     */
    public static function getDefaultInvalidElementCSSClasses()
    {
        return self::$_defaultInvalidElementCSSClasses;
    }
    
    /**
     * Set the array of CSS classes that would be added to the
     * CSS class of invalid elements.
     * 
     * @param   string  $classes
     */
    public static function setDefaultInvalidElementCSSClasses($classes)
    {
        if ( !is_array($classes) ) $classes = explode(' ', $classes);
        self::$_defaultInvalidElementCSSClasses = array();
        foreach($classes as $c) {
            $c = trim($c);
            if ($c) array_push(self::$_defaultInvalidElementCSSClasses, $c);
        }
    } // public static function setDefaultInvalidElementCSSClasses()
    
    /**
     * Returns the array of CSS classes that would be added to the
     * CSS class of required elements.
     * 
     * @return   array
     */
    public static function getDefaultRequiredElementCSSClasses()
    {
        return self::$_defaultRequiredElementCSSClasses;
    }
    
    /**
     * Set the array of CSS classes that would be added to the
     * CSS class of required elements.
     * 
     * @param   string  $classes
     */
    public static function setDefaultRequiredElementCSSClasses($classes)
    {
        if ( !is_array($classes) ) $classes = explode(' ', $classes);
        self::$_defaultRequiredElementCSSClasses = array();
        foreach($classes as $c) {
            array_push(self::$_defaultRequiredElementCSSClasses, trim($c));
        }
    } // public static function setDefaultRequiredElementCSSClasses()
    
    /**
     * Returns the placement of element errors in their titles.
     * 
     * @return   string
     */
    public static function getElementErrorPlacementInTitle()
    {
        return self::$_elementErrorPlacementInTitle;
    }
    
    /**
     * Set the placement of element errors in their titles. use NONE to turn
     * off this feature.
     * 
     * @param   string  $placement
     * @throws  Parsonline_Exception_ValueException on not supported placement
     */
    public static function setElementErrorPlacementInTitle($placement)
    {
        $placement = strtolower($placement);
        switch($placement) {
            case self::APPEND:
            case self::PREPEND:
            case self::NONE:
                self::$_elementErrorPlacementInTitle = $placement;
                return;
        }
        /**
         * @uses    Parsonline_Exception_ValueException
         */
        require_once('Parsonline/Exception/ValueException.php');
        throw new Parsonline_Exception_ValueException("placement '{$placement}' is invalid");
    } // public static function setElementErrorPlacementInTitle;
    
    /**
     * Turns off the automatically adding of a hash element to the form.
     * 
     * @param   bool    $no
     */
    public static function setNoHashElement($no=false)
    {
        self::$_defaultAddHashEelement = true && $no;
    }
    
    /**
     * Constructor
     *
     * Registers form view helper as decorator.
     * Overrides the parent by registerring the default element modifiers.
     *
     * @param   mixed       $options
     * @return  void
     */
    public function __construct($options=null)
    {
        parent::__construct($options);
        $modifierSpecs = $this->getDefaultElementModifiers();
        foreach ($modifierSpecs as $modiSpec) {
            list ($phase, $cond, $mod, $options) = $modiSpec;
            $this->addElementModifier($mod, $phase, $cond, $options);
        }
    } // public function __construct()
    
    /**
     * Add a new element
     *
     * $element may be either a string element type, or an object of type
     * Zend_Form_Element. If a string element type is provided, $name must be
     * provided, and $options may be optionally provided for configuring the
     * element.
     *
     * If a Zend_Form_Element is provided, $name may be optionally provided,
     * and any provided $options will be ignored.
     * 
     * NOTE: Overrides the parent class, by adding the password and hash
     * elements to the list of persistence discarded elements.
     *
     * @param  string|Zend_Form_Element $element
     * @param  string                   $name
     * @param  array|Zend_Config        $options
     * @return  Parsonline_ZF_Form_Common
     */
    public function addElement($element, $name=null, $options=null)
    {
        parent::addElement($element, $name, $options);
        $elementName = ($element instanceof Zend_Form_Element) ? $element->getName() : $name;
        $elementClass = get_class($this->getElement($elementName));
        switch ($elementClass) {
            case 'Zend_Form_Element_Password':
            case 'Zend_Form_Element_Hash':
                $this->addPersistenceDiscardedElements($elementName);
            default:
                // pass
        }
        return $this;
    } // public function addElement()
    
    /**
     * Setups a Zend_Form_Element_Hash object.
     * 
     * @return  Zend_Form_Element_Hash
     */
    public function createSecurityHashElement()
    {
        /**
        * @uses Zend_Form_Element_Hash
        */
       $hashName = $this->getSecurityHashElementName();
       $hashElement = new Zend_Form_Element_Hash($hashName);
       $hashElement->setSalt($this->getFormUID());
       $hashElement->setSession($this->getPersistenceSession());
       return $hashElement;
    } // public function createSecurityHashElement()
    
    /**
     * Returns an indexed array of arrays which describe the default element
     * modifiers. each subarray is in (phase, condition, modifier, options) format.
     * 
     * Default Element modifiers are:
     *      1. on render phase for invalid elements, add an ErrorsInTitle modifier
     *         with auto remove option.
     *      2. on render phase for invalid elements, add an InvalidElementCSSClass
     *          modifier, with auto remove option.
     *      3. on render phase for all elements, add a RequiredElementCSSClass
     *          modifier to elements, with auto remove option.
     * 
     * @return  array
     */
    public function getDefaultElementModifiers()
    {
        $modifierSpecs = array();
        if (self::$_elementErrorPlacementInTitle !== self::NONE) {
            /**
             * @uses    Parsonline_ZF_Form_ElementModifier_ErrorsInTitle
             */
            require_once('Parsonline/ZF/Form/ElementModifier/ErrorsInTitle.php');
            $modifier = new Parsonline_ZF_Form_ElementModifier_ErrorsInTitle();
            $modifier->setPlacement(self::$_elementErrorPlacementInTitle);
            $spec = array(
                        self::PHASE_RENDER,
                        self::CONDITION_INVALID_ELEMNT,
                        $modifier,
                        array(self::ELEMNET_MODIFIER_OPTION_AUTO_REMOVE)
                    );
            array_push($modifierSpecs, $spec);
            unset($spec, $modifier);
        }
        
        if (self::$_defaultInvalidElementCSSClasses) {
            /**
             * @uses    Parsonline_ZF_Form_ElementModifier_InvalidElementCSSClass
             */
            require_once('Parsonline/ZF/Form/ElementModifier/InvalidElementCSSClass.php');
            $modifier = new Parsonline_ZF_Form_ElementModifier_InvalidElementCSSClass();
            $modifier->setClasses(self::$_defaultInvalidElementCSSClasses);
            $spec = array(
                        self::PHASE_RENDER,
                        self::CONDITION_INVALID_ELEMNT,
                        $modifier,
                        array(self::ELEMNET_MODIFIER_OPTION_AUTO_REMOVE)
                    );
            array_push($modifierSpecs, $spec);
            unset($spec, $modifier);
        }
        
        if (self::$_defaultRequiredElementCSSClasses) {
            /**
             * @uses    Parsonline_ZF_Form_ElementModifier_RequiredElementCSSClass
             */
            require_once('Parsonline/ZF/Form/ElementModifier/RequiredElementCSSClass.php');
            $modifier = new Parsonline_ZF_Form_ElementModifier_RequiredElementCSSClass();
            $modifier->setClasses(self::$_defaultRequiredElementCSSClasses);
            $spec = array(
                        self::PHASE_RENDER,
                        self::CONDITION_ALWAYS,
                        $modifier,
                        array(self::ELEMNET_MODIFIER_OPTION_AUTO_REMOVE)
                    );
            array_push($modifierSpecs, $spec);
            unset($spec, $modifier);
        }
        
        return $modifierSpecs;
    } // public function getDefaultElementModifiers()
    
    /**
     * Returns the name of the hash element used for form security.
     * 
     * @return  string
     */
    public function getSecurityHashElementName()
    {
        return $this->_securityHashElementName;
    }
    
    /**
     * Sets the name of the hash element used for form security.
     * 
     * @param  string       $name
     * @return  Parsonline_ZF_Form_Common
     */
    public function setSecurityHashElementName($name)
    {
        $this->_securityHashElementName = $name;
        return $this;
    }
    
    /**
     * Initialize the Form.
     * 
     * Overrides the parent by:
     *  1. clearing all elements, and element modifiers.
     *  2. Adding the security hash element to the form elements.
     */
    public function init()
    {
       parent::init();
       $this->clearElements();
       $this->clearAllElementModifiers();
       /**
        * Since the '$_defaultAddHashEelement' protected property and this method are both defined in
        * this class (Parsonline_ZF_Form_Common), using the PHP construct
        * 'self' would not check if the property is overriden in child classes.
        * So I use this trick below, to get the self reference on runtime, as
        * the reference to the same class that is actually overriding the
        * property.
        */
       $self = get_class($this);
       if ($self::$_defaultAddHashEelement) {
           $hashElement = $this->createSecurityHashElement();
           $this->addElement($hashElement);
       }
    } // public function init()
}