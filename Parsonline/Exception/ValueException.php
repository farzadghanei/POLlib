<?php
//Parsonline/Exception/ValueException.php
/**
 * Defines the Parsonline_Exception_ValueException class.
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
 * @version     0.1.0 2012-07-09
 */

/**
 * Parsonline_Exception_ValueException
 * 
 * Defines an exception that is used to report invalid parameter for functions
 * or methods.
 *
 * @uses    Parsonline_Exception
 */
require_once('Parsonline/Exception.php');
class Parsonline_Exception_ValueException extends Parsonline_Exception
{
    /**
     * the type of the expected value for the parameter
     *
     * @var string
     */
    protected $_expectedValue = null;

    /**
     * the provided value as the parameter
     * 
     * @var mixed
     */
    protected $_providedValue = null;

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
     * Returns the type of the expected value for the parameter
     *
     * @return mixed
     */
    public function getExpectedValue()
    {
        return $this->_expectedValue;
    }

    /**
     * Sets the type of the expected value for the parameter
     *
     * @param   mixed   $value
     * @return  Parsonline_Exception_ValueException  object self reference
     */
    public function setExpectedValue($value)
    {
        $this->_expectedValue = $value;
        return $this;
    }

    /**
     * Returns the type of the invalid provided value for the parameter
     *
     * @return mixed
     */
    public function getProvidedValue()
    {
        return $this->_providedValue;
    }

    /**
     * sets the type of the invalid provided value as the parameter
     *
     * @param   mixed
     * @return  Parsonline_Exception_ValueException
     */
    public function setProvidedValue($value)
    {
        $this->_providedValue = $value;
        return $this;
    }
}
