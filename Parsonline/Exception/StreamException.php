<?php
//Parsonline/Exception/StreamException.php
/**
 * Defines the Parsonline_Exception_StreamException class.
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
 * @package     Parsonline_Exception
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.2.0 2012-07-09
 */

/**
 * Parsonline_Exception_StreamException
 * 
 * Defines an exception that is used to report failures over streams.
 *
 * @uses    Parsonline_Exception_IOException
 */
require_once('Parsonline/Exception/IOException.php');
class Parsonline_Exception_StreamException extends Parsonline_Exception_IOException
{
    const CONNECTION = 1;
    const TIMEOUT = 2;
    const REACHED_EOF = 3;
    const NO_MATCH_FOUND = 4;
    const READ_FAILED = 5;
    const WRITE_FAILED = 6;
    const DATA_NOT_AVAILABLE = 7;
    const NOT_SUPPORTED = 7;

    /**
     * The stream resource
     * 
     * @var resource
     */
    protected $_stream;

    /**
     * Constructor.
     *
     * @param   string      $message
     * @param   int         $code
     * @param   Exception   $previous
     * @param   resource    $stream
     */
    public function __construct($message='', $code=0, Exception $previous=null, $stream=null)
    {
        parent::__construct($message, $code, $previous);
        if ( is_resource($stream) ) $this->_stream = $stream;
    }

    /**
     * Returns the stream that the excpetion occurred on.
     *
     * @return  resource|null
     */
    public function getStream()
    {
        return $this->_stream;
    }
}
