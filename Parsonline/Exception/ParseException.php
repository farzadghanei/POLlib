<?php
//Parsonline/Exception/ParseException.php
/**
 * Defines the Parsonline_Exception_ParseException class.
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
 * @author      Farzad Ghanei <f.ghanei@parsonline.net>
 * @version     0.1.0 2010-09-25
 */

/**
 * Parsonline_Exception_ParseException
 * 
 * Defines an exception that is used for failures in text parsing.
 *
 * @uses    Parsonline_Exception
 */
require_once('Parsonline/Exception.php');

class Parsonline_Exception_ParseException extends Parsonline_Exception
{
    /**
     * the original string that was being parsed
     *
     * @var string
     */
    protected $_targetString = '';

    /**
     * the string that the search was supposed to find
     * 
     * @var string
     */
    protected $_searchPattern = '';

    /**
     * Constructor.
     * 
     * defines an exception that is used for failures in text parsing.
     * or methods.
     *
     * @param   string      $message
     * @param   int         $code
     * @param   Exception   $previous
     * @param   string      $targetString       the string that was being searched through (known as haystack)
     * @param   string      $search
     */
    public function __construct($message='', $code=0, Exception $previous=null, $targetString=null, $search=null)
    {
        parent::__construct($message, $code, $previous);
        if ($targetString) $this->setTargetString($targetString);
        if ($search) $this->setSearchPattern($search);
    }

    /**
     * Returns the original string that was being parsed
     *
     * @return  string
     */
    public function getTargetString()
    {
        return $this->_targetString;
    }

    /**
     * Sets the original string that was being parsed
     *
     * @param   string      $string
     * @return   Parsonline_Exception_ParseException        object self reference
     */
    public function setTargetString($string)
    {
        $this->_targetString = (string) $string;
        return $this;
    }

    /**
     * Returns the string that the search was supposed to find
     *
     * @return  string
     */
    public function getSearchPattern()
    {
        return $this->_searchPattern;
    }

    /**
     * Sets the string that the search was supposed to find
     *
     * @param   string      $pattern
     * @return  Parsonline_Exception_ParseException     object self reference
     */
    public function setSearchPattern($pattern)
    {
        $this->_searchPattern = (string) $pattern;
        return $this;
    }
    
    /**
     * Return the string representation of the exception.
     * appends the search target and pattern to the string.
     * 
     * @return string
     */
    public function __toString()
    {
        return parent::__toString() . sprintf(' searching for "%s" in "%s"', $this->_searchPattern, $this->_targetString);
    }
}
