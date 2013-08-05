<?php
//Parsonline/Exception/Context/MethodCallException.php
/**
 * Defines the Parsonline_Exception_Context_MethodCallException class.
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
 * @version     0.1.1 2011-01-21
 */

/**
 * Parsonline_Exception_Context_MethodCallException
 * 
 * An exception that is used to report when a method is called when
 * it should not have been.
 *
 * @uses    Parsonline_Exception_ContextException
 */
require_once('Parsonline/Exception/ContextException.php');
class Parsonline_Exception_Context_MethodCallException extends Parsonline_Exception_ContextException
{
    /**
     * a callable. a string for a function name, an array as (object objec reference, string method name).
     * 
     * @var mixed
     */
    protected $_method = null;

    /**
     * Constructor.
     * 
     * an exception that is used to report when a method is called when
     * it should not have been.
     *
     * @param   string          $message
     * @param   int             $code
     * @param   Exception       $previous
     * @param   array|string    $method     a callable. a string for a function name, an array as (object objec reference, string method name).
     */
    public function __construct($message='', $code=0, Exception $previous=null, $method=null)
    {
        parent::__construct($message, $code, $previous);
        if ($method) $this->setMethod($method);
    }

    /**
     * Returns a callable, a string for a function name, an array as
     * (object objec reference, string method name).
     *
     * @return  array|string|null
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * sets the method that was used in a bad context. should be a callable. a string for a function name, an array as (object objec reference, string method name).
     * if the specified method was not a callable, triggers an E_USER_WARNING error.
     *
     * @param   string|array    $method     callable
     * @return  bool
     */
    public function setMethod($method)
    {
        if ( !is_callable($method) ) {
            trigger_error('user method specified for the exception class should be a callable object', E_USER_WARNING);
            return false;
        }
        $this->_method = $method;
        return true;
    }
}
