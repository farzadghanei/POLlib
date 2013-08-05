<?php
//Parsonline/Converter/Abstract.php
/**
 * Defines Parsonline_Converter_Abstract class.
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
 * @package     Converter
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.1.0 2010-12-02
 */

/**
 * Parsonline_Converter_Abstract
 * 
 * Capsulates shared functionality between all converters.
 */
abstract class Parsonline_Converter_Abstract
{
    /**
     * keeps an array of method names that might be used to convert a target
     * to a string value.
     *
     * @staticvar   array
     */
    protected static $_convertToStringMethods = array('toString','convertToString','string');
    
    /**
     * the original data to convert
     * 
     * @var mixed
     */
    protected $_data;
    
    /**
     * Constructor.
     *
     * @param   mixed   $data  [optional]
     */
    public function __construct($data=null)
    {
        if ($data) {
            $this->setData($data);
        }        
    }

    /**
     * returns the original data to be converted
     *
     * @return  mixed
     */
    public function getData()
    {
       return $this->_data;
    }

    /**
     * sets the origial data to be converted.
     * 
     * @param   mixed   $data
     * @return  Parsonline_Converter_Abstract
     */
    public function setData($data)
    {
        $this->_data = $data;
        return $this;
    }

    /**
     * tries to call a string conversion method.
     * the name of the strin conversion method should be specified
     * in the $_possibleStringConverterMethods class property.
     *
     * @return  string
     * @throws  Parsonline_Exception_ContextException on no string converter method specified
     */
    public function __toString()
    {
        $allMethods = get_class_methods($this);
        foreach (self::$_convertToStringMethods as $method) {
            if ( in_array($method, $allMethods) ) {
                return $this->$method();
            }
        }
        /**
         * @uses    Parsonline_Exception_ContextException
         */
        require_once('Parsonline/Exception/ContextException.php');
        throw new Parsonline_Exception_ContextException(
            "no string conversion or representation is available"
        );
    } // public function __toString()

    /**
     * Returns the PHP serialized value of the data.
     *
     * @return  string
     */
    public function toPHPSerialized()
    {
        return serialize($this->_data);
    }
}