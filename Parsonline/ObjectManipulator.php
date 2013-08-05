<?php
//Parsonline/ObjectManipulator.php
/**
 * Defines Parsonline_ObjectManipulator class.
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
 * @copyright  Copyright (c) 2010-2011 ParsOnline, Inc. (www.parsonline.com)
 * @license    Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Parsonline
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @author      Mohammad Emami <mo.razavi@parsonline.com>
 * @version     1.0.2 2011-09-13 f.ghanei
 */

/**
 * Parsonline_ObjectManipulator
 *
 * A data extractor/manipulator for general objects.
 * provides feature reach batch data exchange from/to objects
 *
 */
class Parsonline_ObjectManipulator
{
    const METHODS_GETTERS = 'getters';
    const METHODS_SETTERS = 'setters';
    
    const CODE_NO_MATCH_FOUND = 404;
    
    /**
     * Target object to be monitored/manipulated
     * 
     * @var object
     */
    protected $_obj;
    
    /**
     * Array to cache result of inspecting for getter/setter methods.
     * 
     * @var array
     */
    protected $_dataManipulationPairMethods = array();
    
    /**
     * Array of method names to be excluded from operations.
     * 
     * @var array
     */
    protected $_methodBlackList = array();
    
    /**
     * Constructor.
     * 
     * @param   object|null     $obj            the object to be manipulated
     * @param   array|string    $ignoreMethods  names of object methods to ignore
     * @throws  Parsonline_Exception_InvalidParameterException
     */
    public function __construct($obj, array $ignoreMehtods=array())
    {
        $this->setObject($obj);
        $this->addMethodToBlackList($ignoreMehtods);
    }
    
    /**
     * Returns the target object to be monitored/manipulated
     * 
     * @return  object
     */
    public function getObject()
    {
        return $this->_obj;
    }
    
    /**
     * Sets the target object to be monitored/manipulated
     * 
     * @param   object  $obj
     * @return  Parsonline_ObjectManipulator 
     * @throws  Parsonline_Exception_InvalidParameterException on none object param
     */
    public function setObject($obj)
    {
        if ( !is_object($obj) ) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                "param should be an object", 0, null, 'object', $obj);
        }
        $this->_obj = $obj;
        // reset the cached data manipulation pair methods
        $this->_dataManipulationPairMethods = array();
        return $this;
    } // public function setObject()
    
    /**
     * Returns an array of method names that are ignored in operations.
     * 
     * @return  array
     */
    public function getMethodBlackList()
    {
        return $this->_methodBlackList;
    }
    
    /**
     * Adds a method (or an array of method) names that are going to be
     * ignored in operations.
     * 
     * @param    string|array        $method
     * @return  Parsonline_ObjectManipulator
     */
    public function addMethodToBlackList($method)
    {
       if ( is_array($method) ) {
           foreach($method as $m) {
               $this->addMethodToBlackList($m);
           }   
       } else {
            array_push($this->_methodBlackList, $method);
       }
       // reset cached DMPM results
       $this->_dataManipulationPairMethods = array();
       return $this;
    } // public function addMethodToBlackList()
    
    /**
     * Sets the array of method names that are ignored in operations.
     * 
     * NOTE: use an empty array to clear the list.
     * 
     * @param array $methods
     * @return Parsonline_ObjectManipulator
     */
    public function setMethodBlackList(array $methods)
    {
        $this->_methodBlackList = $methods;
        // reset cached DMPM results
        $this->_dataManipulationPairMethods = array();
        return $this;
    }
    
    /**
     * Returns the getter/setter method names of the object as an array.
     * If the mode is specified as getters, returns an associative array of getter method names
     * with this format info() => getter method name. the 'info' is the name of the information
     * that the getter method name returns, which is the name of the method without the 'get'
     * and the first character to lower case.
     * The same concept applies to setter methods. use the setters mode to have the same
     * array of setter methods.
     * 
     * If no mode is speicifed (default), returns an array of both 'getters' and 'setters' arrays
     * as an associative array. getters => array, setters => array
     * 
     * NOTE: array keys start with lower cased characters
     * NOTE: ONLY METHODS THAT ARE "PAIRED" AS GETTER/SETTER WOULD BE RETURNED.
     * NOTE: by conventions, method pairs like 'isFoo/setFoo' are treated as getter/setters
     * 
     * @param   string  $mode       use class constancts METHODS_GETTERS/METHODSD_SETTERS or null.
     * @return   array   associative array of strings in getter/setter modes, or associative array of associative arrays in default mode.
     * @throws  Parsonline_Exception_InvalidParameterException on invalid mode.
     */
    public function getDataManipulationPairMethods($mode=null)
    {
        if (!$this->_dataManipulationPairMethods) {
            $getters = array();
            $setters = array();
            $objectMethodList = get_class_methods($this->_obj);

            if ($objectMethodList) {
                $patternGet = "/^get(\S+)/i";
                $patternIs = "/^is(\S+)/i";

                foreach ($objectMethodList as $method) {
                    if ( in_array($method, $this->_methodBlackList) ) continue;
                    $isValidGetter = false;
                    $matches = array();

                    if ( preg_match($patternGet, $method, $matches)
                        && 1 < count($matches) ) {
                        $isValidGetter = true;
                    } else {
                        $matches = array();
                        if ( preg_match($patternIs, $method, $matches)
                            && 1 < count($matches) ) {
                            $isValidGetter = true;
                        }
                    }

                    if ($isValidGetter) {
                        $property = $matches[1];
                        $property = strtolower($property[0]) . substr($property, 1);
                        $pairedSetterMethodCamelCase = 'set' . ucfirst($property);
                        $pairedSetterMethod = 'set' . $property;
                        if ( in_array($pairedSetterMethodCamelCase, $objectMethodList) ) {
                            $getters[$property] = $method;
                            $setters[$property] = $pairedSetterMethodCamelCase;
                        } elseif( in_array($pairedSetterMethod, $objectMethodList) ) {
                            $getters[$property] = $method;
                            $setters[$property] = $pairedSetterMethod;
                        }
                        unset($property, $pairedSetterMethod,
                                $pairedSetterMethodCamelCase, $matches, $isValidGetter);
                    }

                } // foreach()

                unset($objectMethodList, $patternGet, $patternIs);
            } // if ($objectMethodList)

            $this->_dataManipulationPairMethods[self::METHODS_GETTERS] =& $getters;
            $this->_dataManipulationPairMethods[self::METHODS_SETTERS] =& $setters;
            unset($getters, $setters);
        } // if (!$this->_dataManipulationPairMethods)
        
        if ($mode) {
            if (array_key_exists($mode, $this->_dataManipulationPairMethods)) {
                return $this->_dataManipulationPairMethods[$mode];
            }
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                "mode '{$mode}' is not valid", 0, null,
                implode(',', array_keys($this->_dataManipulationPairMethods)),
                $mode
            );
        }
        return $this->_dataManipulationPairMethods;        
    } // public function getDataManipulationPairMethods()
    
    /**
     * Returns an array representation of the target object.
     * Uses  public properties and public getter methods of object and
     * creates an associative array of key => value pairs.
     * keys for properties are propety names. keys for methods are
     * the property the getter methods represent, with their first character
     * in lower case, ending with () (to distinquish them from real properties).
     * so the getProperty() method is returned as property() => value.
     *
     * NOTE: might throw exceptions thrown from the getter methods.
     * 
     * @param   bool    $properties     export object public properties
     * @param   bool    $methods        use object getter methods
     * @return  array   associative array
     * @throws  Exception from getter methods of the object
     */
    public function toArray($properties=true, $methods=true)
    {
        $result = array();
        if ($properties) $result = get_object_vars($this->_obj);
        
        if ($methods) {
            $getterMethods = $this->getDataManipulationPairMethods(self::METHODS_GETTERS);
            foreach ($getterMethods as $property => $method) {
                if ( !array_key_exists($property, $result) ) {
                    $result[$property . '()'] = $this->_obj->$method();
                }
            }
        }
        
        return $result;
    } // public function toArray()
    
    /**
     * Find the value of the requested data from object.
     * Tries to find an exact match for the name of the data from the
     * public properties and getter methods of the object.
     * 
     * If failed to find a suitable match, throws an exception.
     * 
     * Returns an array of (value, requested resource).
     * The value is always in the first index.
     * If the data is fetched out of a property, the name of the property is the
     * first index, otherwise it is the name of the getter method ending with ().
     *
     * NOTE: The set() method has the same concept.
     * NOTE: By default tries to find different variants of getter method names
     * for the requested data in different character case convensions.
     * If you are sure you just want the exact match for the data (with
     * no character convension detection), specify the "exact" param.
     *
     * NOTE: since get appends the '()' at the end of the returned properties,
     * the set() method would automatically remove '()' at the end of the
     * specified keys.
     *
     * @see     set()
     * 
     * @param   string  $key        name of the data
     * @param   bool    $exact      if should seek for an exact match only
     * @param   bool    $properties use object public properties
     * @param   bool    $methods    use object public methods
     * @return  array(value, resource)
     * @throws  Parsonline_Exception_ObjectInspectionException on failure to find a match
     */
    public function get($key, $exact=false, $properties=true, $methods=true)
    {
        $key = trim($key);

        //this is not supported in php earlier 5.2.6
        //$isMarkedAsMethod = (substr($key, -2) == '()');
        $keyLen = strlen($key);
        $isMarkedAsMethod = (substr($key, $keyLen-2) == '()');
        
        if ($properties && !$isMarkedAsMethod && property_exists($this->_obj, $key) ) {
            return array($this->_obj->$key, $key);
        }
        
        if ($methods) {
            //this is not supported in php earlier 5.3
            //if ($isMarkedAsMethod) $key = strstr($key, '()', true);
            $pos = strpos($key, '()');
            if($pos !== false) {
            	$key = substr($key, 0, $pos); 
            }
            
            /*
             * data value is a method name. try all possiblities.
             */
            $getterMethods = array('get' . ucfirst($key), 'get' . $key, $key);

            if (!$exact) {
                // convert _ delimited names to camel case as well
                $convertedToCamelCase = str_replace(
                                            ' ', '',
                                            ucwords(str_replace('_', ' ', $key))
                                        );
                $getterMethods[] = 'get' . ucfirst($convertedToCamelCase);
                $getterMethods[] = 'get' .
                                    strtolower($convertedToCamelCase[0]) .
                                    substr($convertedToCamelCase, 1);
                $getterMethods[] = $convertedToCamelCase;
                unset($convertedToCamelCase);
            }

            foreach($getterMethods as $method) {
                if (method_exists($this->_obj, $method)
                    && !in_array($method, $this->_methodBlackList) ) {
                    return array($this->_obj->$method(), $method . '()');
                }
            }
        }
        
        /**
         * @uses    Parsonline_Exception_ObjectInspectionException
         */
        require_once('Parsonline/Exception/ObjectInspectionException.php');
        throw new Parsonline_Exception_ObjectInspectionException(
            "failed to find a match for '{$key}' in target object",
            self::CODE_NO_MATCH_FOUND, null, $key, $this->_obj
        );
    } // public function get()
    
    /**
     * Extracts the specified data out of the object, and returns as an array.
     * Tries to find corresponding properties and getter methods
     * in the object, matching the key => value pairs in the specified data array.
     * calls the internal get() method for each array member.
     * 
     * Returns an indexed array. the first member is an associative array of key
     * and value pairs whose data were extracted from the object successfully.
     * the second, is an associative array of keys whose data were failed to be
     * extracted, and values are Excpetion objects describing what went wrong.
     * 
     * NOTE: does not throw exceptions. check the second memeber of returned
     * array for errors.
     * 
     * NOTE: there is a corresponding setDataFromArray() method.
     * 
     * @see get()
     * @see setDataFromArray()
     * @see toArray()
     *
     * @param   array       $keys           associative array of requested keys
     * @param   bool        $exact          search for exact key match.
     * @param   bool        $noMatches      include not matching values
     * @return  array(array key => value, array key => Exception)
     */
    public function extractDataAsArray(array $keys, $exact=false, $noMatches=false)
    {   
        $successes = array();
        $errors = array();
        
        foreach($keys as $key => $value) {
            try {
                $successes[$key] = current($this->get($key, $exact));
            } catch(Exception $exp) {
                $errors[$key] = $exp;
            }
        }
        return array($successes, $errors);
    } // public function extractDataAsArray()
    
    /**
     * Sets the value of the specified data to the object.
     * Tries to find an exact match for the name of the data from the
     * public properties and setter methods of the object.
     * 
     * If failed to find a suitable match, throws an exception.
     * 
     * Returns an array of (returned value, requested resource).
     * 
     * If the data is fetched set to a property, the name of the property is the
     * seconds index, otherwise it is the name of the setter method ending with ().
     * If the setter method returned a value, it is stored in the first index,
     * otherwise it is always null.
     *
     * NOTE: The get() method has the same concept.
     *
     * NOTE: since get appends the '()' at the end of the returned properties,
     * the set() method would automatically remove '()' at the end of the
     * specified keys.
     * 
     * NOTE: by default tries to find different variants of setter method names
     * for the requested data in different character case convensions.
     * If you are sure you just want the exact match for the data (with
     * no character convension detection), specify the "exact" param.
     * 
     * @see     get()
     * 
     * @param   string  $key   name of the data
     * @param   mixed   $value  the value to set
     * @param   bool    $exact  if should seek for an exact match only
     * @param   bool    $properties use object public properties
     * @param   bool    $methods    use object public methods
     * @return  array(property, method, returned value)
     * @throws  Parsonline_Exception_ObjectInspectionException on failure to find a match
     */
    public function set($key, $value, $exact=false, $properties=true, $methods=true)
    {
        $key = trim($key);
        $isMarkedAsMethod = (substr($key, -2) == '()');
        if ($properties && !$isMarkedAsMethod && property_exists($this->_obj, $key) ) {
            $this->_obj->$key = $value;
            return array(null, $key);
        }
        
        if ($methods) {
            //this is not supported in php earlier 5.3
            //if ($isMarkedAsMethod) $key = strstr($key, '()', true);
            $pos = strpos($key, '()');
            if($pos !== false) {
            	$key = substr($key, 0, $pos); 
            }
            /*
             * data value is a method name. try all possiblities.
             */
            $setterMethods = array('set' . ucfirst($key), 'set' . $key, $key);

            if (!$exact) {
                // convert _ delimited names to camel case as well
                $convertedToCamelCase = str_replace(
                                            ' ', '',
                                            ucwords(str_replace('_', ' ', $key))
                                        );
                $setterMethods[] = 'set' . ucfirst($convertedToCamelCase);
                $setterMethods[] = 'set' .
                                    strtolower($convertedToCamelCase[0]) .
                                    substr($convertedToCamelCase, 1);
                $setterMethods[] = $convertedToCamelCase;
                unset($convertedToCamelCase);
            }

            foreach($setterMethods as $method) {
                if (method_exists($this->_obj, $method)
                    && !in_array($method, $this->_methodBlackList) ) {
                    return array($this->_obj->$method($value), $method . '()');
                }
            }            
        }
        
        /**
         * @uses    Parsonline_Exception_ObjectInspectionException
         */
        require_once('Parsonline/Exception/ObjectInspectionException.php');
        throw new Parsonline_Exception_ObjectInspectionException(
            "failed to find a match for '{$key}' in target object",
            self::CODE_NO_MATCH_FOUND, null, $key, $this->_obj
        );
    } // public function set()
    
    /**
     * Sets data of the object from an array.
     * Tries to find corresponding properties and setter methods
     * in the object, matching the key => value pairs in the specified data array.
     * calls the internal set() method for each array member.
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
     * NOTE: there is a corresponding extractDataAsArray() method.
     *
     * NOTE: since toArray() appends the '()' at the end of the returned values,
     * out of the getter methods, this method would automatically remove '()' at
     * the end of the specified keys.
     * 
     * @see set()
     * @see extractDataAsArray()
     * @see toArray()
     * 
     * @param   array       $data               associative array of property => values
     * @param   bool        $exact              search for exact key match.
     * @param   bool        $skipMisMatches  
     * @param   bool        $properties         use object public properties
     * @param   bool        $methods            use object public methods
     * @return  array(array key => value, array key => Exception)
     */
    public function setDataFromArray(array $data, $exact=false, $skipMisMatches=false, $properties=true, $methods=true)
    {   
        $successes = array();
        $errors = array();
        foreach($data as $key => $value) {
            try {
                $successes[$key] = current($this->set($key, $value, $exact, $properties, $methods));
            } catch(Exception $exp) {
                /**
                 * @uses    Parsonline_Exception_ObjectInspectionException
                 */
                require_once('Parsonline/Exception/ObjectInspectionException.php');
                if ($skipMisMatches &&
                    $exp instanceof Parsonline_Exception_ObjectInspectionException
                    && $exp->getCode() == self::CODE_NO_MATCH_FOUND
                ) {
                    continue;
                }
                $errors[$key] = $exp;
            }
        }
        return array($successes, $errors);
    } // public function setDataFromArray()
    
    /**
     * Sets data of the object from public properties of another object.
     * Tries to convert the remote object to an array, and then
     * use the internal setDataFromArray() method.
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
     * @see set()
     * 
     * @param   object      $dataObject
     * @param   bool        $exact          search for exact property match
     * @param   bool        $skipMisMatches
     * @return   array(array key => value, array key => Exception)
     * @throws  Parsonline_Exception_InvalidParameterException on none object data param
     */
    public function setDataFromRemoteObject($dataObject, $exact=false, $skipMisMatches=false)
    {
        if (!is_object($dataObject)) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                "data parameter should be an object", 0, null, 'object',
                $dataObject);
        }
        
        /*
         * if the remote object had an obvious way to turn into an array,
         * use it. otherwise force convert it into an array using our
         * own powers. 
         */
        $dataObjectAsArray = array();
        $toArrayMethods = array('toArray', 'asArray');
        foreach ($toArrayMethods as $method) {
            if ( method_exists($dataObject, $method) ) {
                $dataObjectAsArray = $dataObject->$method();
                break;
            }
        }
        unset($toArrayMethods);
        
        if (!$dataObjectAsArray || !is_array($dataObjectAsArray)) {
            $manipulator = new self($dataObject);
            $dataObjectAsArray = $manipulator->toArray();
            unset($manipulator);
        }
        
        if ($dataObjectAsArray) {
            return $this->setDataFromArray($dataObjectAsArray, $exact, $skipMisMatches);
        } else {
            return array(array(), array());
        }
    } // public function setDataFromRemoteObject()
    
    /**
     * Sets data of the object from public properties of another object.
     * iterates over the remote object public properties, and calls
     * the internall set() method over all the public properties of the
     * data object.
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
     * @see set()
     * 
     * @param   object      $dataObject
     * @param   bool        $exact          search for exact property match
     * @param   bool        $skipMisMatches
     * @param   bool        $properties         use target object public properties
     * @param   bool        $methods            use target object setter methods
     * @return   array(array key => value, array key => Exception)
     * @throws  Parsonline_Exception_InvalidParameterException on none object data param
     */
    public function setDataFromRemoteObjectPublicFields($dataObject, $exact=false, $skipMisMatches=false, $properties=true, $methods=true)
    {
        if (!is_object($dataObject)) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                "data parameter should be an object", 0, null, 'object',
                $dataObject);
        }
        $dataObjectAsArray = get_object_vars($dataObject);
        
        // // some objects implement iteratable interface. use that too.
        if (!$dataObjectAsArray) {
            $dataObjectAsArray = array();
            foreach($dataObject as $key => $value) {
                $dataObjectAsArray[$key] = $value;
            }
        }
        if ($dataObjectAsArray) {
            return $this->setDataFromArray($dataObjectAsArray, $exact, $skipMisMatches, $properties, $methods);
        } else {
            return array(array(), array());
        }
    } // public function setDataFromRemoteObjectPublicFields()

    /**
     * Sets data of the object from a remote object by calling getter methods of
     * the remote object that correspond to the setter methods, or public
     * properties of current target object.
     * 
     * An array of remote method names could be used to ignore them.
     * 
     * Returns an indexed array. the first member is an associative array of remote
     * method names whose value were set into the object successfully, mapped to the return
     * value of the setter method or null if method returned nothing.
     * the second, is an associative array of remote method names whose data
     * were failed to be set, and values are Excpetion objects describing what
     * went wrong.
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
     * @param   object      $dataObject      
     * @param   array       $ignoreMethods      an array of method names to ignore
     * @param   bool        $skipMisMatches
     * @param   bool        $properties         use target object public properties
     * @param   bool        $methods            use target object setter methods
     * @return   array(array key => value, array key => Exception)
     * @throws  Parsonline_Exception_InvalidParameterException on none object data param
     */
    public function setDataFromRemoteObjectPublicMethods($dataObject, array $ignoreMethods=array(), $skipMisMatches=false, $properties=true, $methods=true)
    {   
        if ( !is_object($dataObject) ) {
            /**
             * @uses    Parsonline_Exception_InvalidParameterException
             */
            require_once('Parsonline/Exception/InvalidParameterException.php');
            throw new Parsonline_Exception_InvalidParameterException(
                "data parameter should be a valid object", 0, null, 'object',
                $dataObject
            );
        }
        
        $successes = array();
        $errors = array();
        
        $manipulator = new self($dataObject);
        $manipulator->setMethodBlackList($ignoreMethods);
        $remoteGetterMethods = $manipulator->getDataManipulationPairMethods(self::METHODS_GETTERS);
        unset($manipulator);
        
        foreach ($remoteGetterMethods as $key => $method) {
            try {
                $successes[$method] = current($this->set($key, $dataObject->$method(), true, $properties, $methods));
            } catch(Exception $exp) {
                if ($skipMisMatches &&
                    $exp instanceof Parsonline_Exception_ObjectInspectionException
                    && $exp->getCode() == self::CODE_NO_MATCH_FOUND
                ) {
                    continue;
                }
                $errors[$key] = $exp;
            }
        }
        return array($successes, $errors);
    } // public function setDataFromRemoteObjectPublicMethods()    
}
