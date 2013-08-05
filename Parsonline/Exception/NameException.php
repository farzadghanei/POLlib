<?php
//Parsonline/Exception/NameException.php
/**
 * Defines the Parsonline_Exception_NameException class.
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
 * @version     0.2.0 2012-07-09
 */

/**
 * Parsonline_Exception_NameException
 * 
 * Defines an exception that is used to report an invalid name call
 * exception. could be used for invalid method name or variable/property
 * calls.
 *
 * @uses    Parsonline_Exception
 */
require_once('Parsonline/Exception.php');
class Parsonline_Exception_NameException extends Parsonline_Exception
{
    /**
     * the name that was called
     * 
     * @var string
     */
    protected $_invalidName = '';
    
    /**
     * Constructor.
     * 
     * defines an exception that is used to report invalid parameter for functions
     * or methods.
     *
     * @param   string      $message
     * @param   int         $code
     * @param   Exception   $previous
     * @param   string      $name
     */
    public function __construct($message='', $code=0, Exception $previous=null, $name=null)
    {
        parent::__construct($message, $code, $previous);
        if ($name) $this->setInvalidName($name);
    }
    
    /**
     * returns the invalid name that was called
     * 
     * @return  string
     */
    public function getInvalidName()
    {
        return $this->_invalidName;
    }
    
    /**
     * sets the invalid name that was called
     * 
     * @param   string  $name
     * @return  Parsonline_Exception_NameException
     */
    public function setInvalidName($name)
    {
        $this->_invalidName = (string) $name;
        return $this;
    }
}
