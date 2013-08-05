<?php
//Parsonline/DataMapperAbstract.php
/**
 * Defines Parsonline_DataMapperAbstract class.
 *
 * Parsonline
 *
 * Copyright (c) 2010-2011 ParsOnline, Inc. (www.parsonline.com)
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
 * @copyright   Copyright (c) 2010-2011 ParsOnline, Inc. (www.parsonline.com)
 * @license     Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Parsonline
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.1.0 2011-05-04
 */

/**
 * @uses    Parsonline_ObjectManipulator
 */
require_once('Parsonline/ObjectManipulator.php');

/**
 * Parsonline_DataMapperAbstract
 * 
 * Maps functionalities between model (model) and the data source.
 * this will create a level of abstraction between the model logic and the real data source.
 * this is an abstract class for model mappers without specifying the
 * data source backend.
 *
 * for more information on the concept read about "Data Mapper" design pattern.
 * @see http://martinfowler.com/eaaCatalog/dataMapper.html
 * 
 * @abstract
 */
abstract class Parsonline_DataMapperAbstract
{
    /**
     * The name of the class whose objects are the data models that
     * use the data mapper to connect to their data sources.
     * This is as a helper proprety for auto generating data model
     * objects.
     * 
     * @var string
     */
    protected $_dataModelClassName;

    /**
     * Maps the name of a data key to a property (or getter/setter
     * pair method) of the data model. this array helps the mapper to retreive
     * or inject the proper value from the data source to the data model.
     * Each key should be the name of the data, and the value
     * should be the public property (or the % in a get% set% public getter/setter
     * method) of the data model class.
     * 
     * @var array
     */
    protected $_keysMappedToDataModel = array();
    
    /**
     * If the mapper should automatically handle naming convensions, by
     * converting _ delimited words for database column names, into camel cased
     * properties/methods of the data model.
     * 
     * @var bool
     */
    protected $_handleNamingConvensions = true;
    
    /**
     * Guesses the name of the data model class name appropriate for the data mapper
     * class.
     *
     * @param   string  $mapperClassName
     * @return  string
     * @abstract
     */
    abstract public function guessDataModelClassName();
    
    /**
     * Instanciates a data model object from an automatically guessed class name
     * for the data model class appropriate for the mapper object.
     * Returns the object if everything went ok.
     * 
     * If instanciations failes, PHP would throw exception.
     *
     * @return  object
     */
    public function autoInstanciateDataModel()
    {
        $modelClassName = ($this->_dataModelClassName ?
                                        $this->_dataModelClassName
                                        : $this->guessDataModelClassName());
        
        $object = new $modelClassName();
        if (!$this->_dataModelClassName) $this->_dataModelClassName = $modelClassName;
        return $object;
    } // public function autoInstanciateDataModel()
    
    /**
     * Tries to find the value of a data key from the data model object.
     * Uses the mapped data keys to data model and if failed to find a suitable
     * match, tries to find an exact match for the name of the key from the
     * public properties and getter methods of the data model object.
     * 
     * If failed to find a suitable match, throws an exception.
     * 
     * Returns an array of (requested property, requested method, value).
     * If the data is fetched out of a property, the name of the property is the
     * first index, otherwise the name of the getter method is the seconds index.
     * the value is always in the third index.
     *
     * NOTE: This method is used to relate data keys and data stored
     * in the data model object. If this method of mapping data between the
     * data source and the data model is not preferred for you, reimplementing
     * this method saves you to reimplement all other methods that depend on this.
     * 
     * NOTE: The setDataToDataModel() has the same concept.
     * 
     * NOTE: internally uses a Parsonline_ObjectManipulator object.
     * 
     * NOTE: to disable handling of different naming convensions change
     * the $_handleNamingConversions property of the mapper.
     * 
     * @see     setDataToDataModel()
     * 
     * @param   object  $model      data model object.
     * @param   string  $key        name of the key
     * @return  array(property, method, value)
     * @throws  Parsonline_Exception_ObjectInspectionException, Parsonline_Exception_InvalidParameterException
     */
    public function getDataFromDataModel($model, $key)
    {
        if (array_key_exists($key, $this->_keysMappedToDataModel)) {
            $mapped = $this->_keysMappedToDataModel[$key];
        } else {
            $mapped = $key;
        }
        
        /**
         * @uses    Parsonline_ObjectManipulator
         */
        $manipulator = new Parsonline_ObjectManipulator($model);
        return $manipulator->get($mapped, !$this->_handleNamingConvensions, true, true);
    } // public function getDataFromDataModel()
    
    /**
     * Tries to set the value of a data key into the data model object.
     * Uses the mapped data keys to data model and if failed to find a suitable
     * match, tries to find an exact match for the name of the data from the
     * public properties and setter methods of the data model object.
     * If failed to find a suitable match, throws an exception.
     * 
     * Returns an array of (returned value, requested resource).
     * 
     * If the data is fetched set to a property, the name of the property is the
     * seconds index, otherwise it is the name of the setter method ending with ().
     * If the setter method returned a value, it is stored in the first index,
     * otherwise it is always null.
     *
     * NOTE: This method is used to relate data keys and data stored
     * in the data model object. If this method of mapping data between the
     * data keys and the data model is not preferred for you, reimplementing
     * this method saves you to reimplement all methods that depend on this.
     * 
     * NOTE: The getDataToDataModel() has the same concept.
     * 
     * NOTE: internally uses a Parsonline_ObjectManipulator object.
     * 
     * NOTE: to disable handling of different naming convensions change
     * the $_handleNamingConversions property of the mapper.
     * 
     * @see     getDataToDataModel();
     * 
     * @param   object  $model  data model object.
     * @param   string  $key    name of the key
     * @param   mixed   $value  the value of the key
     * @return  array(property, method, value)
     * @throws  Parsonline_Exception_ObjectInspectionException
     */
    public function setDataToDataModel($model, $key, $value)
    {
        if (array_key_exists($key, $this->_keysMappedToDataModel)) {
            $mapped = $this->_keysMappedToDataModel[$key];
        } else {
            $mapped = $key;
        }
        
        /**
         * @uses    Parsonline_ObjectManipulator
         */
        $manipulator = new Parsonline_ObjectManipulator($model);
        return $manipulator->set($mapped, $value, !$this->_handleNamingConvensions, true, true);
    } // public function setDataToDataModel()
    
    /**
     * Loads the data in the array into the model object.
     * loads those array keys that relate to the data source, by calling the
     * setDataToDataModel() internally.
     * 
     * catches exceptions incase of failures, but keeps track of failures and
     * successes.
     * 
     * Returns an indexed array. the first member is an associative array of keys
     * whose data were set into the object successfully, mapped to the return
     * value of the setter method or null if the setting was done
     * on a property or the method returned nothing.
     * the second, is an associative array of keys whose data were failed to be set,
     * and values are Excpetion objects describing what went wrong.
     * 
     * NOTE: does not throw exceptions. check the second memeber of returned
     * array for errors.
     * 
     * NOTE: If there were members in the array that did not have a corresponding
     * data in the object, they are included in the returned Exceptions array
     * as Parsonline_Exception_ObjectInspectionExceptions with code
     * CODE_NO_MATCH_FOUND. Inclusion of these values to the returning results
     * could be turned off, by setting the third parameter.
     * This way the returned Exception array only contains
     * technical problems occurred while batch setting the data.
     *
     * @see     setDataToDataModel()
     * 
     * @param   object  $model
     * @param   array   $data
     * @param   bool    $skipMisMatches
     * @return  array(array key => returned value, array key => Exception)
     */
    public function loadDataFromArray($model, array $data, $skipMisMatches=false)
    {
        $successes = array();
        $errors = array();

        foreach($data as $key => $value) {
            try {
                $successes[$key] = current($this->setDataToDataModel($model, $key, $value));
            } catch(Exception $exp) {
                /**
                 * @uses    Parsonline_ObjectManipulator
                 * @uses    Parsonline_Exception_ObjectInspectionException
                 */
                require_once('Parsonline/Exception/ObjectInspectionException.php');
                if ($skipMisMatches &&
                    $exp instanceof Parsonline_Exception_ObjectInspectionException
                    && $exp->getCode() == Parsonline_ObjectManipulator::CODE_NO_MATCH_FOUND
                ) {
                    continue;
                }
                $errors[$key] = $exp;
            }
        }
        return array($successes, $errors);
    } // public function loadDataFromArray()
    
    /**
     * Loads the data in the specified object into the model object.
     * 
     * internally uses an instance of Parsonline_ObjectManipulator.
     * 
     * Returns an indexed array. the first member is an associative array of property
     * names whose value were set into the object successfully, mapped to the return
     * value of the setter method or null if the setting was done
     * on a property or the method returned nothing.
     * the second, is an associative array of property names whose data were failed to be set,
     * and values are Excpetion objects describing what went wrong.
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
     * could be turned off, by setting the third parameter.
     * This way the returned Exception array only contains
     * technical problems occurred while batch setting the data.
     *
     * @see     Parsonline_ObjectManipulator
     * @see     loadDataFromArray()
     * 
     * @param   object  $model
     * @param   object  $data
     * @param   bool    $skipMisMatches
     * @return  array(array key => returned value, array key => Exception)
     * @throws  Parsonline_Exception_InvalidParameterException
     */
    public function loadDataFromOpenObject($model, $data, $skipMisMatches=false)
    {
        if (!is_object($data)) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                "Data parameter should be an object", 0, null, 'object', $data
            );
        }

        /**
         * @uses    Parsonline_ObjectManipulator
         */
        $manipulator = new Parsonline_ObjectManipulator($model);
        return $manipulator->setDataFromRemoteObject(
                                            $data,
                                            !$this->_handleNamingConvensions,
                                            $skipMisMatches
                );
    } // public function loadDataFromOpenObject()
    
    /**
     * Iterates over an array of data arrays. creates an instance of the data model
     * class for each data array and loads the data model with that array.
     * 
     * By default catches all exceptions and returns them at the end, but this
     * could be disabled by setting the "stopOnFailure" parameter.
     * 
     * Returns an indexed array with 2 members.
     * The first is an indexed array of successfully loaded data model objects.
     * The socond is an associative array of index => exceptions to report what
     * went wrong while loading that array (specified by index) to the data model.
     * 
     * NOTE: if the mapper fails to autho instanciate the data model calss,
     * the method always throws a Parsonline_Context_Exception.
     * 
     * @param   array   $dataArrays
     * @param   bool    $stopOnFailure
     * @return  array (array model objects, array index => exception)
     * @throws  Parsonline_Exception_ContextException
     */
    public function convertDataArrayToDataModelArray(array $dataArrays, $stopOnFailure=false)
    {
        $entities = array();
        $errors = array();
        
        foreach($dataArrays as $index => $dataArray) {
            $entity = $this->autoInstanciateDataModel();
            $loadResults = $this->loadDataFromArray($entity, $dataArray, false);
            $loadExceptions = $loadResults[1];
            if (count($loadExceptions) > 0) {
                $property = key($loadExceptions);
                $exp = $loadExceptions[$property];
                
                if ($stopOnFailure) {
                    /**
                     * @uses    Parsonline_Exception_ContextException
                     */
                    require_once('Parsonline/Exception/ContextException.php');
                    throw new Parsonline_Exception_ContextException(
                        sprintf(
                            "Failed to load '%s' from data array into the new generated data model. exception <%s> code %d: %s",
                            $property,
                            get_class($exp),
                            $exp->getCode(),
                            $exp->getMessage()
                        ),
                        500, $exp
                    );
                }
                $errors[$index] = $exp;
            }
            array_push($entities, $entity);
        }
        
        return array($entities, $errors);
    } // public function convertDataArrayToDataModelArray()
}