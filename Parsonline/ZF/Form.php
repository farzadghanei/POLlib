<?php
//Parsonline/ZF/Form.php
/**
 * Defines Parsonline_ZF_Form class.
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
 * @copyright   Copyright (c) 2010-2011-2012 ParsOnline, Inc. (www.parsonline.com)
 * @license     Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Parsonline_ZF_Form
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     1.7.0 2012-07-08
 */

/**
 * @uses    Zend_Form
 * @uses    Zend_Session_Namespace
 * @uses    Zend_View_Interface
 */
require_once('Zend/Form.php');
require_once('Zend/Session/Namespace.php');
require_once('Zend/View/Interface.php');

/**
 * Parsonline_ZF_Form
 * 
 * A form that adds some features to Zend_Form component.
 * Addes features are:
 * 
 *  1. Persistence:
 *      stores form values into a configurable session namespace
 *  2. Invalid Elements:
 *     additional methods to get information about invalid elements, errors.
 *  3. Element Modifications:
 *      registers element modifier objects to operate on elements, for 
 *      specified conditions, in the specified phases.
 *      mostly used for invalid elements, right before rendering the form.
 *      NOTE: this feature depends on the validity of the phase/condition pair,
 *      and availablity of the feature in subclasses.
 * 
 */
class Parsonline_ZF_Form extends Zend_Form
{
    const CONDITION_ALWAYS = 'always';
    const CONDITION_INVALID_ELEMNT = 'invalid';
    const ELEMNET_MODIFIER_OPTION_AUTO_REMOVE = 'auto_remove';
    const PHASE_RENDER = 'render';
    
    /**
     * The default TTL for persistence session
     * 
     * @var int
     */
    protected static $_defaultPersistenceSessionTTL = 600;

    /**
     * Array of element names that are going to be ignored while
     * persisting form data.
     * 
     * @var array
     * @access protected
     */
    protected $_persistenceDiscardedElements = array();
    
    /**
     * The session that is used to persist form data
     *
     * @var Zend_Session_Namespace
     */
    protected $_persistenceSession;
    
    /**
     * The TTL for persistence session
     *
     * @var int
     */
    protected $_persistenceSessionTTL;
    
    /**
     * form unique identifier
     * 
     * @var string
     */
    protected $_uid;
    
    /**
     * Array of element modifier objects. keys are phase::condition.
     * each value is an associative array, the key is the ID of the modifier,
     * the value is an array of (modifier, options array).
     * 
     * $_elementModifiers = array(
     *  'render::invalid' => array(
     *                          'my_modi' => array(
     *                                          MODIFIER_OBJ,
     *                                          array(
     *                                              OPT1,
     *                                              OPT2
     *                                          )
     *                                      )
     *                       )
     * )
     * 
     * @var array
     */
    protected $_elementModifiers = array();
    
    /**
     * Returns the class default TTL (seconds) for persistence session.
     * 
     * @return  int
     */
    public static function getDefaultPeristenceSessionTTL()
    {
        return self::$_defaultPersistenceSessionTTL;
    }
    
    /**
     * Sets the class default TTL (seconds) for persistence session.
     * 
     * @param   int     $ttl
     */
    public static function setDefaultPeristenceSessionTTL($ttl)
    {
        self::$_defaultPersistenceSessionTTL = intval($ttl);
    }
    
    /**
     * Initializer.
     * 
     * Overrides parent by:
     *  1. setting the default persistenceSessionTTL value.
     */
    public function init()
    {
        parent::init();
        $this->_persistenceSessionTTL = self::$_defaultPersistenceSessionTTL;
    }
    
    /**
     * Returns a unique name for the form.
     * The name is a hashed value out of the form class name, and 
     * sorted form attributes.
     * So form objects form the same form class, with the same attribute values,
     * are going to be known as idnetical.
     * 
     * NOTE: It is suggested to create subclasses of the base form for each
     * application form, so this UID would work correctly.
     * 
     * NOTE: Attributes order does not affect the UID.
     * 
     * @return  string
     */
    public function getFormUID()
    {
        if (!$this->_uid) {
            $uid = array(get_class($this));
            $props = $this->getAttribs();
            $keys = array_keys($props);
            sort($keys);
            foreach ($keys as $key) {
                array_push($uid, $key . '<=>' . $props[$key]);
            }
            $this->_uid = 'form-' . md5(implode('', $uid));
        }
        return $this->_uid;
    } // public function getFormUID()
    
    /**
     * Returns the TTL (seconds) for the persistence session of the form.
     * 
     * @return  int
     */
    public function getPersistenceSessionTTL()
    {
        return $this->_persistenceSessionTTL;
    }
    
    /**
     * Sets the TTL (seconds) for the persistence session of the form.
     * 
     * @param  int
     * @return  Parsonline_ZF_Form
     */
    public function setPersistenceSessionTTL($ttl)
    {
        $this->_persistenceSessionTTL = intval($ttl);
        $session = $this->getPersistenceSession();
        $session->setExpirationSeconds($this->_persistenceSessionTTL);
        return $this;
    }
    
    /**
     * Returns the session that is used to persist form data.
     *
     * @return  Zend_Session_Namespace
     */
    public function getPersistenceSession()
    {
        if (!$this->_persistenceSession) {
            $ttl = $this->_persistenceSessionTTL;
            if ($ttl === null) $ttl = intval(self::$_defaultPersistenceSessionTTL);
            $sess = new Zend_Session_Namespace($this->getFormUID());
            if ($ttl) $sess->setExpirationSeconds($ttl);
            $this->_persistenceSession = $sess;
        }
        return $this->_persistenceSession;
    }

    /**
     * Sets the session that is used to persist form data.
     *
     * @param  Zend_Session_Namespace       $session
     * @return  Parsonline_ZF_Form
     */
    public function setPersistenceSession(Zend_Session_Namespace $session)
    {
        $this->_persistenceSession = $session;
        return $this;
    }
    
    /**
     * Checkes the persistence session namespace for stored values for elements,
     * and returns them.
     * 
     * @param   bool        $clear       if should also clear the session. default is false
     * @return  array       associative array of field => value pairs
     */
    public function getPersistedData($clear=false)
    {
        $session = $this->getPersistenceSession();
        $results = array();
        if ( $session->formPersistedData && is_array($session->formPersistedData) ) {
            $results = $session->formPersistedData;
        }
        if ($clear) $session->unsetAll();
        return $results;
    } // public function getPersistedData()
    
    /**
     * Stores element values into the persistence session namespace.
     * Accepts an array of string values for element names to persist only their
     * data. By default stores all elements' data.
     * The elements group could be inverted by using the second parameter. By
     * default the list is considered as a whitelist, meaning only values of
     * the specified elements are persisted. Set the whitelist parameter to false
     * to persist data of all form elements, but the specified elements.
     *
     * @param   array   $elemens        specify which element to (or not to) persist
     * @param   bool    $whitelist      if the elements array is a white or black list
     * @return  array   array of name => values persisted.
     */
    public function persistFormData(array $elements=array(), $whitelist=true)
    {
        $session = $this->getPersistenceSession();
        $allElements = $this->getElements();
        $data = array();
        foreach($allElements as $el) {
            /*@var $el Zend_Form_Element*/
            $name = $el->getName();
            if ( in_array($name, $this->_persistenceDiscardedElements) ) continue;
            $inElementList = in_array($name, $elements);
            if ( ($whitelist && $inElementList) || (!$whitelist && !$inElementList) ) {
                $data[$name] = $el->getValue();
            }
        }
        $session->formPersistedData = $data;
        return $data;
    } // public function persistFormData()
    
    /**
     * Loads the form default values from the persisted data.
     * Accepts an array of string values for element names to load only their
     * data. By default loads all elements' data.
     * The elements group could be inverted by using the second parameter. By
     * default the list is considered as a whitelist, meaning only values of
     * the specified elements are loaded. Set the whitelist parameter to false
     * to load data of all form elements, but the specified elements.
     * Could be configured to clear the persisted data automatically after the
     * loading.
     * Returns an array of element name => value that were successfully
     * loaded from the persisted data.
     * 
     * @param   array       $elements
     * @param   bool        $whitelist
     * @param   bool        $clear          should automatically clear the persisted data
     * @return  array       array of field => values
     */
    public function loadDefaultsFromPersistedData(array $elements=array(), $whitelist=true, $clear=false)
    {
        $loaded = array();
        $allElements = $this->getElements();
        $data = $this->getPersistedData($clear);
        if ($data && is_array($data) ) {
            foreach($allElements as $el) {
                $name = $el->getName();
                $inElementList = in_array($name, $elements);
                if ( ($whitelist && $inElementList) || (!$whitelist && !$inElementList) ) {
                    if ( array_key_exists($name, $data) ) {
                        $value = $data[$name];
                        $this->setDefault($name, $value);
                        $loaded[$name] = $value;
                    }
                }
            } // foreach
        }
        return $loaded;
    } // public function loadDefaultsFromPersistedData()
    
    /**
     * Clears all elements marked to be discarded from persistence.
     * 
     * @return Parsonline_ZF_Form 
     */
    public function clearPersistenceDiscardedElements()
    {
        $this->_persistenceDiscardedElements = array();
        return $this;
    }
    
    /**
     * Adds an element (or an array of them) to the list of the elements
     * that are going to be discarded while persisting form data.
     * 
     * @param   array|string|Zend_Form_Element  $elements
     * @return  Parsonline_ZF_Form
     */
    public function addPersistenceDiscardedElements($elements)
    {
        if (!is_array($elements)) $elements = array($elements);
        foreach ($elements as $el) {
            $name = ($el instanceof Zend_Form_Element) ? $el->getName() : strval($el);
            if ( !in_array($name, $this->_persistenceDiscardedElements) ) {
                array_push($this->_persistenceDiscardedElements, $name);
            }
        }
        return $this;
    }
    
    /**
     * Returns array of element names marked to be discarded from persistence.
     * 
     * @return  array
     */
    public function getPersistenceDiscardedElements()
    {
        return $this->_persistenceDiscardedElements;
    }
    
    /**
     * Returns an array of form elements that had an invalid value after
     * validation is done.
     *
     * @return  array       associative array of name => Zend_Form_Element objects
     */
    public function getInvalidElements()
    {
        $errors = $this->getErrors();
        $elements = array();  
        foreach ($errors as $elName => $elErrors) {
            $elements[$elName] = $this->getElement($elName);
        }
        return $elements;
    } // public function getInvalidElements()
    
    /**
     * Retrieve error messages from invalid elements.
     * Returns messages without element names, just the description of the error.
     * 
     * @param   array   [optional]$validationMessages   array of messeges to perepend to the returned messages
     * @return  array
     */
    public function getAllMessages(array $validationMessages=array())
    {
        $errMessages = $this->getMessages();
        foreach ($errMessages as $el => $elErrors) {
            $validationMessages = array_merge_recursive($validationMessages, $elErrors);
        }
        return $validationMessages;
    } // public function getAllMessages()
    
        
    /**
     * Returns the key for the element modifier list of the specified
     * phase and condition.
     * 
     * @param   string  $phase
     * @param   string  $cond
     * @return  string
     */
    protected function _getElementModifierKey($phase, $cond)
    {
        $key = strtolower(trim($phase)) . '::' . strtolower(trim($cond));
        return $key;
    }
    
    /**
     * Instantiate an element modifier object based on class name.
     * Could pass the instanciation options array to the construtor.
     *
     * @param  string       $class
     * @param  null|array   $options
     * @return  Parsonline_ZF_Form_IElementModifier
     */
    protected function _instanciateElementModifier($class, $options)
    {
        if (null === $options) {
            $modifier = new $class();
        } else {
            $modifier = new $class($options);
        }
        return $modifier;
    } // protected function _getElementModifier()
    
    /**
     * Registers an element modifiers for the specified phase and condition.
     * The modifier could be specified as a pre configured object that implements the
     * Parsonline_ZF_Form_IElementModifier, a string for the
     * class name of a modifier, or an array with a key for the class name,
     * and value as an array of constructor options for the modifier.
     * 
     * NOTE: If the modifier parameter is not an object (string|array), the class
     * of the modifier should already be loaded, since there is no implementation
     * of pulugin loading for element modifiers (unlike decorators).
     * 
     * A list of options could be specified for the element modifier.
     * 
     * NOTE: These options are not related to the element modifier itselt, but for
     * the way the form interacts with the modifier.
     * 
     * currently these options are available:
     *      1. OPTION_AUTO_REMOVE
     *          Most modifiers are supposed to be used just once in the lifetime of the form.
     *          The auto remove paramter makes sure after the modifiers are called, they
     *          are automatically removed from the registered list so they would not affect
     *          the elements any furthor. By not using this feature, You are responsible
     *          to control the existence (and possible side effects of the modifiers being
     *          called multiple times) of the modifiers registered in the form.
     * 
     * NOTE: It is not possible to register multiple modifiers for the same phase/condition
     * with the same class.
     * 
     * @see     removeElementModifier()
     * 
     * @param   string|Parsonline_ZF_Form_IElementModifier|array   $mod
     * @param   string                                                              $phase
     * @param   string                                                              $cond
     * @param   array|string                                                        $options
     * @return  Parsonline_ZF_Form
     * @throws  Parsonline_Exception_ValueException
     */
    public function addElementModifier($mod, $phase, $cond, array $options=array())
    {
        /**
         * @uses    Parsonline_ZF_Form_IElementModifier
         */
        require_once('Parsonline/ZF/Form/IElementModifier.php');
        if ( is_string($mod) ) {
            $className = $mod;
            $mod = $this->_instanciateElementModifier($className);
        } elseif (is_array($mod)) {
            $className = key($mod);
            $modiOptions = current($mod);
            if ( !is_array($modiOptions) ) $modiOptions = null;
            $mod = $this->_instanciateElementModifier($className, $modiOptions);
            unset($modiOptions);
        } elseif ($mod instanceof Parsonline_ZF_Form_IElementModifier) {
            $className = get_class($mod);
        } else {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException(
                "element modifier is not a Parsonline_ZF_Form_IElementModifier"
            );
        }
        $key = $this->_getElementModifierKey($phase, $cond);
        if (!array_key_exists($key, $this->_elementModifiers)) {
            $this->_elementModifiers[$key] = array();
        }
        
        if ($options) {
            if (!is_array($options)) {
                $options = explode(',',$options);
            }
            $cleanedOptions = array();
            foreach($options as $opt) {
                array_push($cleanedOptions, strtolower(trim($opt)));
            }
            $options = $cleanedOptions;
            unset($cleanedOptions);
        }
        $this->_elementModifiers[$key][$className] = array($mod, $options);
        return $this;
    } // public function addElementModifier()
    
    /**
     * Returns an associative array of key => values.
     * keys are class name of the modifier, and values are indexed arrays
     * with 2 indexes, the first is the modifier object, and the second
     * is an array of options.
     * 
     * @param   string  $phase
     * @param   string  $cond
     * @return  array   array(className => array(modifier, options), ...)
     */
    public function getElementModifiers($phase, $cond)
    {
        $key = $this->_getElementModifierKey($phase, $cond);
        if ( !array_key_exists($key, $this->_elementModifiers) ) {
            return array();
        }
        return $this->_elementModifiers[$key];
    } // public function getElementModifiers()
    
    /**
     * Returns an array that contains the element modifier registered for the
     * phase and condition with the specified ID, and the options of the modifier.
     * If no such modifiere were registered, returns null.
     * 
     * @param   string  $phase
     * @param   string  $cond
     * @param   string  $className
     * @return  array|null   array(modifier, options)
     */
    public function getElementModifier($phase, $cond, $className)
    {
        $modifiers = $this->getElementModifiers($phase, $cond);
        if (!$modifiers) return null;
        foreach($modifiers as $modiKey => $modiArray) {
            if ($modiKey == $className) return $modiArray;
        }
        return null;
    } // public function getElementModifier()
    
    /**
     * Removes all the registered group of element modifiers for the specified
     * phase and condition.
     * 
     * @param   string  $phase
     * @param   string  $cond
     * @return  Parsonline_ZF_Form       object self reference
     */
    public function clearElementModifiers($phase, $cond)
    {
        $key = $this->_getElementModifierKey($phase, $cond);
        if (array_key_exists($key, $this->_elementModifiers)) {
            $this->_elementModifiers[$key] = array();
        }
        return $this;
    } // public function clearElementModifiers()

    /**
     * Removes all the registered element modifiers.
     *
     * @return  Parsonline_ZF_Form       object self reference
     */
    public function clearAllElementModifiers()
    {
        $this->_elementModifiers = array();
        return $this;
    } // public function clearAllElementModifiers()
    
    /**
     * Removes the registered element modifier for the specified phase and condition,
     * specified with the class name.
     * 
     * @param   string  $phase
     * @param   string  $cond
     * @param   string  $className
     * @return  bool     if removed the registered, or no such modifier was registered
     */
    public function removeElementModifier($phase, $cond, $className)
    {
        $key = $this->_getElementModifierKey($phase, $cond);
        if (array_key_exists($key, $this->_elementModifiers)) {
            $modiKeys = array_keys($this->_elementModifiers[$key]);
            foreach($modiKeys as $modiKey) {
                if ($modiKey == $className) {
                    unset($this->_elementModifiers[$key][$modiKey]);
                    return true;
                }
            }
        }
        return false;
    } // public function removeElementModifier()
    
    /**
     * Calls all registered element modifiers for the given phase/condition.
     * passes them the element.
     * Returned number of called modifiers.
     * Automatically removes those modifiers that were set to have the auto
     * remove option.
     * 
     * @param   string              $phase
     * @param   string              $cond
     * @param   Zend_Form_Element   $el
     * @return  int
     */
    protected function _callElementModifiers($phase, $cond, $el)
    {
        $modies = $this->getElementModifiers($phase, $cond);
        $counter = 0;
        $toRemove = array();
        
        foreach($modies as $className => $modiArray) {
            $modi = $modiArray[0];
            $options = $modiArray[1];
            /*@var $modi Parsonline_ZF_Form_IElementModifier */
            $modi->setElement($el);
            $modi->modifyFormElement();
            $counter++;
            if (in_array(self::ELEMNET_MODIFIER_OPTION_AUTO_REMOVE, $options)) {
                array_push($toRemove, $className);
            }
        }
        
        foreach($toRemove as $className) {
            $this->removeElementModifier($phase, $cond, $className);
        }
        
        return $counter;
    } // protected function _callElementModifier()
    
    /**
     * Render form.
     * 
     * NOTE: Overrides the Zend_Form::render() by calling registered
     * element modifiers on render phase.
     *
     * @param  Zend_View_Interface $view
     * @return string
     */
    public function render(Zend_View_Interface $view=null)
    {
        $elements = $this->getElements();
        foreach($elements as $el) {
            $this->_callElementModifiers(self::PHASE_RENDER, self::CONDITION_ALWAYS, $el);
        }
        if ($this->isErrors()) {
            $invalidElements = $this->getInvalidElements();
            foreach ($invalidElements as $el) {
                $this->_callElementModifiers(self::PHASE_RENDER, self::CONDITION_INVALID_ELEMNT, $el);
            }
        }
        return parent::render();
    } // public function render()
}