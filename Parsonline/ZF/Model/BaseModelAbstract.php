<?php
//Parsonline/ZF/Model/BaseModelAbstract.php
/**
 * Defines Parsonline_ZF_Model_BaseModelAbstract class.
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
 * @package     Parsonline_ZF_Model
 * @author      Farzad Ghanei <f.ghanei@parsonline.net>
 * @version     0.3.0 2012-07-18
 */

/**
 * @uses    Parsonline_IntelligentObjectAbstract
 */
require_once('Parsonline/IntelligentObjectAbstract.php');

/**
 * Parsonline_ZF_Model_BaseModelAbstract
 *
 * a general base data model to capsulate basic data model
 * functionalities for ZendFramework MVC applications. separates
 * the model logic and the data source by using a data mapper object as the
 * date gateway of the model. empowered by the functionality in its parent class,
 * provides a great set of data exchange features.
 * other models may extend this base model.
 *
 * @see Parsonline_IntelligentObjectAbstract
 * @abstract
 */
abstract class Parsonline_ZF_Model_BaseModelAbstract extends Parsonline_IntelligentObjectAbstract
{
    /**
     * Data Mapper of the Model object
     * 
     * @var object
     */
    protected $_mapper = null;
    
    /**
     * Returns an array of method names that are not supposed to be auto discoverable
     * and used by the object manipulator.
     * 
     * @return array
     */
    protected function _getNoneAutoDiscoverableMethods()
    {
        $methods = parent::_getNoneAutoDiscoverableMethods();
        $methods = array_merge(
                        $methods,
                        array(
                            'getMapper','setMapper',
                            'setDataFromArray', 'setDataFromOpenObject'
                        )
                    );
                    
        return array_unique($methods);
    }

    /**
     * sets data of the model object from an array. works in 2 modes: data mapper, self inspection.
     * [data mapper] if there is a data mapper object, calls the specified method of the mapper to load the data into
     * the model object.
     * [self inspection] if run in this mode, would try to find corresponding properties and setter methods
     * in the model object itself, matching the key => value pairs in the specified data array.
     * by default catches all exceptions, but this could be truned off.
     * 
     * NOTE: exceptions thrown because of invalid parameters are always thrown
     *
     * @param   array       $options                    associative array of property => values
     * @param   string      $dataMapperMethodName       the name of the method in the mapper object. use false/null to force use the self inspection mode.
     * @param   bool        $throwExceptions            if should throw exceptions
     * @return  Parsonline_ZF_Model_BaseModelAbstract   object self reference
     * @throws  Parsonline_Exception_ValueException on invalid mapper method name
     */
    public function setDataFromArray( array $options = array(), $dataMapperMethodName='loadDataFromArray', $throwExceptions=false)
    {
        if (!is_array($options)) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("options parameter should be an associative array");
        }
        
        $mapper = $this->getMapper();
        if ( $dataMapperMethodName && $mapper && is_object($mapper) ) { // use the mapper mode
            $mapperMethods = get_class_methods($mapper);
            if ( !in_array($dataMapperMethodName, $mapperMethods) ) {
                /**
                 * @uses    Parsonline_Exception_ValueException
                 */
                require_once('Parsonline/Exception/ValueException.php');
                throw new Parsonline_Exception_ValueException('specified method name is not defined in the data mapper object');
            }
            $mapper->$dataMapperMethodName($this, $options);
            
        } else { // use self inspection method
            parent::setDataFromArray($options, $throwExceptions);
        }
        return $this;
    } // public function setDataFromArray()

    /**
     * sets data of the model object from public properties of another object. if there is a data mapper object
     * for the model, and it has a loadDataFromOpenObject() method, will call that method first to load
     * the object [this method name could be changed. use false to use the property/method mode].
     * the mapper method should accept a reference to the model object, and an object that contains
     * the data.
     * if not in the mapper mode, uses all public properties/methods of the options object to read the data out of it.
     * by default catches all exceptions thrown, but this could be disabled by the $throwExceptions parameter.
     * returns the data model object.
     * 
     * NOTE: exceptions thrown because of invalid parameters are always thrown
     *
     * @param   object      $optionsObject              an object with getOptions methods related to setOptions
     * @param   string      $dataMapperMethodName       the name of the method in the data mapper object to call. use false/null to force use the property/field inspection mode.
     * @param   bool        $throwExceptions            if should throw exceptions of the setter methods being called internally.
     * @return   Parsonline_ZF_Model_BaseModelAbstract      object self reference
     * @throws  Exception, Parsonline_Exception_ValueException on invalid mapper method name
     */
    public function setDataFromOpenObject($optionsObject, $dataMapperMethodName='loadDataFromOpenObject', $throwExceptions=false)
    {
        $mapper = $this->getMapper();
        if ( $dataMapperMethodName && $mapper && is_object($mapper) ) { // use the data mapper mode
            $mapperMethods = get_class_methods($mapper);
            if ( !in_array($dataMapperMethodName, $mapperMethods) )  {
                /**
                 * @uses    Parsonline_Exception_ValueException
                 */
                require_once('Parsonline/Exception/ValueException.php');
                throw new Parsonline_Exception_ValueException('specified method name is not defined in the data mapper object');
            }
            $mapper->$dataMapperMethodName($this, $optionsObject);  
        } else { // use the property/method inspection mode
            $this->setDataFromObjectPublicFields($optionsObject, $throwExceptions);
            $this->setDataFromObjectPublicMethods($optionsObject, array(), $throwExceptions);
        }
        return $this;
    } // public function setDataFromOpenObject()  

    /**
     * Returns the mapper object that maps the model to its data source.
     * 
     * @return  object
     */
    public function getMapper()
    {
        return $this->_mapper;
    }
    
    /**
     * Sets the data mapper object of the model
     *
     * @param   object  $mapper     the mapper object to map the model to its data source, or the name of the mapper class.
     * @return  Parsonline_ZF_Model_BaseModelAbstract   object self reference
     * @throws  Parsonline_Exception_ValueException
     */
    public function setMapper($mapper)
    {
        if ( !is_object($mapper) ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            throw new Parsonline_Exception_ValueException("Data mapper should be an object");
        }
        $this->_mapper = $mapper;
        return $this;
    } // public function setMapper()
}