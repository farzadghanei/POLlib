<?php
//Parsonline/IntelligentObjectAbstract.php
/**
 * Defines Parsonline_IntelligentObjectAbstract class.
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
 * @package     Parsonline
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     2.1.1 2012-09-23
 */

/**
 * @uses    Parsonline_ObjectManipulator
 */
require_once('Parsonline/ObjectManipulator.php');

/**
 * Parsonline_IntelligentObjectAbstract
 *
 * A general purpose object with a set of primitive usefull capabilities.
 * Provides feature reach batch data exchange from/to object, and a flexible
 * mechanism for string representation of the object.
 *
 * @abstract
 */
abstract class Parsonline_IntelligentObjectAbstract
{
    const METHODS_GETTERS = 'getters';
    const METHODS_SETTERS = 'setters';

    const CODE_NO_MATCH_FOUND = 404;
    
    // different formats of representing the intelligent object as a string value
    const STRING_REPRESENTATION_FORMAT_XML = 'XML';
    
    /**
     * the default format that is ued to represent the objects as string values.
     * 
     * @staticvar   string
     */
    protected static $_defaultStringRepresentationFormat;
    
    /**
     * An isntance of Parsonline_ObjectManipulator to inspect and manipulate
     * the intellgent object.
     *
     * @var Parsonline_ObjectManipulator
     */
    protected $_manipulator;
    
    /**
     * array of method names that should be ignored while iterating over
     * object methods in batch set/get operations.
     * 
     * @var array
     */
    protected $_noneAutoDiscoverableMethods = array(
                                                'getDataManipulationPairMethods',
                                                'getManipulator',
                                                'getObjectStringRepresentationFormat',
                                                'setObjectStringRepresentationFormat'
                                            );
    
    /**
     * the format that is used to represent the object as a string
     * 
     * @var string
     */
    protected $_stringRepresentaionFormat;
    
    /**
     * an array of the (object, method) pair that is used to decorate the formatted output of string
     * representation of the intelligent object.
     * 
     * @var array
     */
    protected $_stringRepresentationDecoratorInfo = array('object' => null, 'infoMethod' => null, 'toStringMethod' => null);
    
    /**
     * Sets the default format used to output string representation of intelligent objects.
     * 
     * @param   string  $format     use class constants STRING_REPRESENTATION_FORMAT_*
     * @throws  Parsonline_Exception_ValueException on not supported format
     */
    public static function setDefaultStringRepresentationFormat($format)
    {
        $formatterMethod = 'toStringIn' . strtoupper($format) . 'Format';
        if ( !method_exists(get_class($this), $formatterMethod) ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("the string representation format is not implemented yet: '{$format}'");
        }
        self::$_defaultStringRepresentationFormat = $format;
    }
    
    /**
     * Returns an array of method names that are not supposed to be auto discoverable
     * and used by the object manipulator.
     * 
     * @return array
     */
    protected function _getNoneAutoDiscoverableMethods()
    {
        return $this->_noneAutoDiscoverableMethods;
    }
    
    /**
     * Returns the format used to output string representation of intelligent object
     * 
     * @return  string
     */
    public function getObjectStringRepresentationFormat()
    {
        return $this->_stringRepresentaionFormat;
    }
    
    /**
     * Sets the format used to output string representation of intelligent object
     * 
     * @param   string  $format     use class constants STRING_REPRESENTATION_FORMAT_*
     * @return  Parsonline_IntelligentObjectAbstract        object self reference
     * @throws  Parsonline_Exception_ValueException on not supported format
     * 
     */
    public function setObjectStringRepresentationFormat($format)
    {
        $formatterMethod = 'toStringIn' . strtoupper($format) . 'Format';
        if ( !method_exists($this, $formatterMethod) ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("the string representation format is not implemented yet: '{$format}'");
        }
        $this->_stringRepresentationFormat = $format;
        return $this;
    } // public function setObjectStringRepresentationFormat()
    
    /**
     * Sets the decorator object used to format the string representation of
     * the intelligent object.
     * The decorator should register one of its methods to the intelligent object.
     * This method it used to pass intelligent object's infromation to the decorator.
     * This method should accept a string for class name, and an associative array
     * of property => values of the intelligent object.
     * 
     * A toStringMethod() is needed to be registered by the decorator object, which would be
     * called to return the string representation of the intelligent object. however if
     * this is omited, the __toString() method of the decorator would be called.
     * 
     * The decorator should recieve intelligen object's information, and then output
     * a desired formatted string value outof the toStringMethod() call.
     * This is automatically called in the string representation of the intelligent
     * object.
     * 
     * NOTE: By registering this decorator, none of internal string formattings are used and only
     * the decorator is used to convert the intelligent object to string.
     * 
     * @param   object      $decorator              an object with a setObjectInfo, and a __toString method.
     * @param   string      $receiveInfoMethod      the name of the method in decorator object to receive intelligent object info
     * @param   string      $toStringMethod         [optional] the method that would output the string representation of intelligent object
     * @return  Parsonline_IntelligentObjectAbstract    object self reference
     * @throws  Parsonline_Exception_ValueException  on invalid decorator
     */
    public function registerObjectStringRepresentationDecorator($decorator, $receiveInfoMethod, $toStringMethod='__toString')
    {
        if ( !is_object($decorator) || !method_exists($decorator, $receiveInfoMethod) || !method_exists($decorator, $toStringMethod) ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("decorator should be an object that implements the specified information and toString methods");
        }
        $this->_stringRepresentationDecoratorInfo['object'] = $decorator;
        $this->_stringRepresentationDecoratorInfo['infoMethod'] = $receiveInfoMethod;
        $this->_stringRepresentationDecoratorInfo['toStringMethod'] = $toStringMethod;
        return $this;
    }
    
    /**
     * unregistered a previously registered decorator object for string
     * representation of the intelligent object, and returns the decorator.
     * if no decorator had been registered, returns null.
     * 
     * @return  array|null      array('object' => decorator object, 'infoMmethod' => method, 'toStringMethod' => method)
     */
    public function unregisterObjectStringRepresentationDecorator()
    {
        $decoratorInfo = null;
        if ( $this->_stringRepresentationDecoratorInfo && $this->_stringRepresentationDecoratorInfo['object'] ) {
            $decoratorInfo = $this->_stringRepresentationDecoratorInfo;
            $this->_stringRepresentationDecoratorInfo = array('object' => null, 'infoMethod' => null, 'toStringMethod' => null);;
        }
        return $decoratorInfo;
    }

    /**
     * Returns an instance of Parsonline_ObjectManipulator that is configured
     * with settings from the intelligent object and internally used to
     * manipulate the intelligen object as needed.
     *
     * @return  Parsonline_ObjectManipulator
     */
    public function getManipulator()
    {
        if (!$this->_manipulator) {
            /**
             * @uses    Parsonline_ObjectManipulator
             */
            $manipulator = new Parsonline_ObjectManipulator($this);
            $manipulator->addMethodToBlackList($this->_getNoneAutoDiscoverableMethods());
            $this->_manipulator = $manipulator;
            unset($manipulator);
        }
        return $this->_manipulator;
    } // protected function getObjectManipulator()

    /**
     * Returns the getter/setter method names of the intelligent object as an array.
     * If the modes is specified as getters, returns an associative array of getter method names
     * with this format info => getter method name. the 'info' is the name of the information
     * that the getter method name returns, which is the name of the method without the 'get'
     * and the first character to lower case.
     * the same concept applies to setter methods. use the setters mode to have the same
     * array of setter methods.
     * 
     * If no mode is speicifed (default), returns an array of both 'getters' and 'setters' arrays
     * as an associative array. getters => array, setters => array
     * 
     * NOTE: array keys stat with lower cased characters
     * NOTE: ONLY METHODS THAT ARE "PAIRED" AS GETTER/SETTER WOULD BE RETURNED.
     * NOTE: by conventions, method pairs like 'isFoo/setFoo' are treated as getter/setters
     * 
     * @param   string  $mode       use class constancts METHODS_GETTERS/METHODSD_SETTERS or null.
     * @return  array   associative array of strings in getter/setter modes, or associative array of associative arrays in default mode.
     */
    public function getDataManipulationPairMethods($mode=null)
    {
        $manipulator = $this->getManipulator();
        switch($mode) {
            case self::METHODS_GETTERS:
                $mode = Parsonline_IntelligentObjectAbstract::METHODS_GETTERS;
            case self::METHODS_SETTERS:
                $mode = Parsonline_IntelligentObjectAbstract::METHODS_SETTERS;
            default:
                $mode = null;
        }
        return $manipulator->getDataManipulationPairMethods($mode);
    } // public function getDataManipulationPairMethods()
    
    /**
     * Returns an array representation of the intelligent object.
     * Uses  public properties and public getter methods of object and creates an associative array
     * of property => value pairs.
     *
     * NOTE: does not throw any exceptions.
     * NOTE: array keys first letters are lower cased
     *
     * @param   bool    $properties     export object properties
     * @param   bool    $methods        export object methods
     * @return  array   associative array
     */
    public function toArray($properties=true, $methods=true)
    {
        $manipulator = $this->getManipulator();
        return $manipulator->toArray($properties, $methods);
    } // public function toArray()
    
    /**
     * Returns an XML formatted representaion of the intelligent object.
     * 
     * @return  string
     */
    public function toStringInXMLFormat()
    {
        try {
            $attribs = array(); // keep attributs as an array so merging them would be faster than using immutable strings
            $array = $this->toArray(true, true);
            
            foreach ($array as $key => $value) {
                try {
                    array_push($attribs, $key . '="' . $value . '"');
                } catch(Exception $exp) {
                    // pass incase failed to convert a value to string
                }
            }
            
            return '<' . get_class($this) . ' ' . implode(' ', $attribs) . '>';
        } catch(Exception $exp) {
            return '';
        }
    } // public function toStringInXMLFormat()

    /**
     * Returns a string representaion of the intelligent object, in the defined format
     * of string representation.
     *
     * @return  string
     * @throws  Parsonline_Exception_ContextException on invalid string representation format setup
     */
    public function __toString()
    {
        if ( $this->_stringRepresentationDecoratorInfo['object'] ) {
            $decorator = $this->_stringRepresentationDecoratorInfo['object'];
            $infoMethod = $this->_stringRepresentationDecoratorInfo['infoMethod'];
            $toStringMethod = $this->_stringRepresentationDecoratorInfo['toStringMehtod'];            
            $decorator->$infoMethod( get_class($this), $this->toArray() );
            return $decorator->$toStringMethod;
        }
        
        $format = $this->_stringRepresentaionFormat ? $this->_stringRepresentaionFormat : self::$_defaultStringRepresentationFormat;
        if (!$format) $format = 'XML';
        $formatterMethod = 'toStringIn' . strtoupper($format) . 'Format';
        if ( method_exists($this, $formatterMethod) ) {
            return $this->$formatterMethod();
        } else {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                        sprintf('invalid string representation format is specified for the object "%s"', get_class($this))
                    );
        }
    } // public function __toString()

    /**
     * Returns a property of the intelligent object by calling the
     * corresponding getter method if any existed.
     * 
     * @param   string  $property       the name of property
     * @return  mixed
     * @throws  Parsonline_Exception_NameException on failed to find a suitable getter
     */
    public function __get($property)
    {
        $manipulator = $this->getManipulator();
        /**
         * @uses    Parsonline_Exception_ObjectInspectionException
         * @uses    Parsonline_Exception_NameException
         */
        require_once('Parsonline/Exception/ObjectInspectionException.php');
        require_once('Parsonline/Exception/NameException.php');
        try {
            return current($manipulator->get($property));
        } catch(Parsonline_Exception_ObjectInspectionException $exp) {
            throw new Parsonline_Exception_NameException(
                "attempt to get invalid property [{$property}]", 0, $exp, $property
            );
        }
    } // public function __get()
    
    /**
     * Sets a property of the intelligent object by calling the 
     * corresponding setter method if any existed.
     *
     * @param   string  $property       name of the property
     * @param   mixed   $value          value of the property
     * @return  mixed   returned value of the setter method (if any)
     * @throws  Parsonline_Exception_NameException on failed to find a suitable setter
     */
    public function __set($property, $value)
    {
        $manipulator = $this->getManipulator();
        /**
         * @uses    Parsonline_Exception_ObjectInspectionException
         * @uses    Parsonline_Exception_NameException
         */
        require_once('Parsonline/Exception/ObjectInspectionException.php');
        require_once('Parsonline/Exception/NameException.php');
        try {
            return $manipulator->set($property, $value);
        } catch(Parsonline_Exception_ObjectInspectionException $exp) {            
            throw new Parsonline_Exception_NameException(
                "attempt to set invalid property [{$property}]", 0, $exp, $property
            );
        }
    } // public function __set()

    /**
     * Sets data of the object from an array.
     * Tries to fill corresponding public properties and setter methods
     * in the intelligent object, matching the key => value pairs in the
     * specified data array.
     * By default catches all exceptions, but this could be truned off.
     *
     * Returns an indexed array. The first member is an associative array of property
     * names whose value were set into the object successfully, mapped to the return
     * value of the setter method or null if the setting was done on a property
     * or the method returned nothing.
     * The second, is an associative array of property names whose data were
     * failed to be set, and values are Excpetion objects describing what went
     * wrong.
     *
     * NOTE: Does not throw exceptions. check the second memeber of returned
     * array for errors. This could be turned off by the second parameter.
     *
     * NOTE: exceptions thrown because of invalid parameters are always thrown
     *
     * NOTE: If there were data in the array that did not have a corresponding
     * data in the object, they are included in the returned Exceptions array
     * as Parsonline_Exception_ObjectInspectionExceptions with code
     * CODE_NO_MATCH_FOUND. Inclusion of these values to the returning results
     * could be turned off, by setting the 'skip mismatches' parameter.
     * This way the returned Exception array only contains
     * technical problems occurred while batch setting the data.
     *
     * NOTE: exceptions thrown because of invalid parameters are always thrown
     *
     * @param   array       $options            associative array of property => values
     * @param   bool        $throwExceptions    if should throw exceptions
     * @param   bool        $skipMismatches     if should skip mismatches from the returned exception array
     * @return  array(array key => value, array key => Exception)
     * @throws  Parsonline_Exception_ValueException on none array option
     * @throws  Exception from internall setter methods
     */
    public function setDataFromArray(array $options, $throwExceptions=false, $skipMismatches=false)
    {
        if (!is_array($options)) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("options parameter should be an associative array");
        }
        
        $manipulator = $this->getManipulator();
        $setResult = $manipulator->setDataFromArray($options, true, $skipMismatches);
        $exceptions = $setResult[1];
        if ($throwExceptions && count($exceptions) > 1) {
            throw current($exceptions);
        }
        return $setResult;
    } // public function setDataFromArray()
    
    /**
     * Sets data of the object from public properties of another object.
     * Uses local public properties and setter methods corresponding
     * to the remote object public properties.
     *
     * Returns an indexed array. the first member is an associative array of property
     * names whose value were set into the object successfully, mapped to the return
     * value of the setter method or null if the setting was done on a property
     * or the method returned nothing.
     * The second, is an associative array of property names whose data were
     * failed to be set, and values are Excpetion objects describing what went
     * wrong.     
     * 
     * NOTE: Does not throw exceptions. check the second memeber of returned
     * array for errors.
     *
     * NOTE: Exceptions thrown because of invalid parameters are always thrown
     *
     * NOTE: If there were data in the remote object that did not have a corresponding
     * data in the object, they are included in the returned Exceptions array
     * as Parsonline_Exception_ObjectInspectionExceptions with code
     * CODE_NO_MATCH_FOUND. Inclusion of these values to the returning results
     * could be turned off, by setting the 'skip mismatches' parameter.
     * This way the returned Exception array only contains
     * technical problems occurred while batch setting the data.
     *
     * @param   object      $optionsObject      an object with getOptions methods related to setOptions
     * @param   bool        $throwExceptions    if should throw exceptions of the setter methods being called internally.
     * @param   bool        $skipMissmatches    skip mismatching data exceptions
     * @return  array(array key => value, array key => Exception)
     * @throws  Parsonline_Exception_ValueException  on none object param
     * @throws  Exception from internall setter methods
     */
    public function setDataFromObjectPublicFields($optionsObject, $throwExceptions=false, $skipMismatches=false)
    {
        if (!is_object($optionsObject)) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("options parameter should be an object");
        }

        $manipulator = $this->getManipulator();
        $setResult = $manipulator->setDataFromRemoteObjectPublicFields(
                                                    $optionsObject,
                                                    true,
                                                    $skipMismatches
                                                );
        $exceptions = $setResult[1];
        if ($throwExceptions && count($exceptions) > 1) {
            throw current($exceptions);
        }
        return $setResult;
    } // public function setDataFromObjectPublicFields()

    /**
     * Sets data of the object from public getter methods of another object.
     * Uses local public properties and setter methods corresponding
     * to the remote object public getter methods.
     *
     * Returns an indexed array. the first member is an associative array of property
     * names whose value were set into the object successfully, mapped to the return
     * value of the setter method or null if the setting was done on a property
     * or the method returned nothing.
     * The second, is an associative array of property names whose data were
     * failed to be set, and values are Excpetion objects describing what went
     * wrong.
     *
     * NOTE: does not throw exceptions. check the second memeber of returned
     * array for errors.
     *
     * NOTE: exceptions thrown because of invalid parameters are always thrown
     *
     * NOTE: If there were data in the remote object that did not have a corresponding
     * data in the object, they are included in the returned Exceptions array
     * as Parsonline_Exception_ObjectInspectionExceptions with code
     * CODE_NO_MATCH_FOUND. Inclusion of these values to the returning results
     * could be turned off, by setting the 'skip mismatches' parameter.
     * This way the returned Exception array only contains
     * technical problems occurred while batch setting the data.
     *
     * @param   object      $optionsObject      an object with getOptions methods related to setOptions
     * @param   array       $ignoreMethods      array of method names in remote object to ignore
     * @param   bool        $throwExceptions    if should throw exceptions of the setter methods being called internally.
     * @param   bool        $skipMissmatches    skip mismatching data exceptions
     * @return  array(array key => value, array key => Exception)
     * @throws  Parsonline_Exception_ValueException  on none objet data param
     * @throws  Exception from internall setters
     */
    public function setDataFromObjectPublicMethods($optionsObject, array $ignoreMethods=array(), $throwExceptions=false, $skipMismatches=false)
    {   
        if (!is_object($optionsObject)) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("options parameter should be a valid object");
        }
        
        if (!is_array($ignoreMethods)) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("ignore methods should be a valid array of method names to ignore");
        }

        $manipulator = $this->getManipulator();
        $setResult = $manipulator->setDataFromRemoteObjectPublicMethods(
                                                    $optionsObject,
                                                    $ignoreMethods,
                                                    $skipMismatches,
                                                    true,
                                                    true
                                                );
        $exceptions = $setResult[1];
        if ($throwExceptions && count($exceptions) > 1) {
            throw current($exceptions);
        }
        return $setResult;
    } // public function setDataFromObjectPublicMethods()    
}