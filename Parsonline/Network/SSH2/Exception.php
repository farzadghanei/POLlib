<?php
//Parsonline/Network/SSH2/Exception.php
/**
 * Defines Parsonline_Network_SSH2_Exception class.
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
 * @package     Parsonline_Network_SSH2
 * @subpackage  Exception
 * @author      Farzad Ghanei <f.ghanei@parsoline.com>
 * @version     0.0.1 2011-01-24
*/


/**
 * @uses    Parsonline_Exception
 */
require_once('Parsonline/Exception.php');

/**
 * Parsonline_Network_SSH2_Exception
 *
 * SSH2 specific exception class
 */
class Parsonline_Network_SSH2_Exception extends Parsonline_Exception
{
    const CONNECTION = 1;
    const TIMEOUT = 2;
    const NO_MATCH_FOUND = 3;
    const REACHED_EOF = 4;
    const STREAM_NOT_AVAILABLE = 4;
}