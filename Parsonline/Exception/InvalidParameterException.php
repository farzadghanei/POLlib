<?php
//Parsonline/Exception/InvalidParameterException.php
/**
 * Defines the Parsonline_Exception_InvalidParameterException class.
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
 * @package     Exception
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.1.1 2012-07-09
 */

/**
 * Parsonline_Exception_InvalidParameterException
 * 
 * Defines an exception that is used to report invalid parameter for functions
 * or methods.
 *
 * @uses    Parsonline_Exception_ValueException
 */
require_once('Parsonline/Exception/ValueException.php');
class Parsonline_Exception_InvalidParameterException extends Parsonline_Exception_ValueException
{
    /**
     * the type of the expected value for the parameter
     *
     * @var string
     */
    protected $_expectedValue;

    /**
     * the provided value as the parameter
     * 
     * @var mixed
     */
    protected $_providedValue;

    /**
     * Constructor.
     * 
     * defines an exception that is used to report invalid parameter for functions
     * or methods.
     *
     * @param   string      $message
     * @param   int         $code
     * @param   Exception   $previous
     * @param   string      $expected
     * @param   mixed      $provided
     */
    public function __construct($message='', $code=0, Exception $previous=null, $expected=null, $provided=null)
    {
        parent::__construct($message, $code, $previous);
        if ($expected) $this->setExpectedValue($expected);
        if ($provided) $this->setProvidedValue($provided);
    }

    /**
     * returns the type of the expected value for the parameter
     *
     * @return string
     */
    public function getExpectedValue()
    {
        return $this->_expectedValue;
    }

    /**
     * sets the type of the expected value for the parameter
     *
     * @param   string
     * @return  Parsonline_Exception_InvalidParameterException  object self reference
     */
    public function setExpectedValue($value)
    {
        $this->_expectedValue = strval($value);
        return $this;
    }

    /**
     * returns the type of the invalid provided value for the parameter
     *
     * @return string
     */
    public function getProvidedValue()
    {
        return $this->_providedValue;
    }

    /**
     * sets the type of the invalid provided value as the parameter
     *
     * @param   string
     * @return  Parsonline_Exception_InvalidParameterException  object self reference
     */
    public function setProvidedValue($value)
    {
        $this->_providedValue = $value;
        return $this;
    }
}
