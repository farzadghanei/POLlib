<?php
//Parsonline/Exception.php
/**
 * Defines Parsonline_Exception class.
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
 * @package     Exception
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     1.2.0 2010-09-04 f.ghanei
 */

/**
 * Parsonline_Exception
 * 
 * General purpose Exception class for Parsonline Library.
 *
 * @uses    Exception
 */
class Parsonline_Exception extends Exception
{
    /**
     * the timestamp of when the exception had been thrown
     * 
     * @var float
     */
    protected $_timestamp;

    /**
     * Constructor.
     *
     * general purpose Exception class for Parsonline Library.
     *
     * @param   string      $message
     * @param   int         $code
     * @param   Exception   $previous
     */
    public function __construct($message='', $code=0, Exception $previous=null)
    {
        /*
         * Exception chaining is only available since PHP v5.3. make sure
         * if our running PHP version is higher than 5.3
         * otherwise ignore the Exception chaining parameter
         */
        if ( defined('PHP_VERSION_ID') && PHP_VERSION_ID > 50300 ) {
            parent::__construct($message, $code, $previous);
        } else {
            parent::__construct($message, $code);
        }
        $this->_timestamp = microtime(true);
    }

    /**
     * Returns The timestamp of when the exception had been thrown
     * 
     * @return  float
     */
    public function getTimestamp()
    {
        return $this->_timestamp;
    }
}