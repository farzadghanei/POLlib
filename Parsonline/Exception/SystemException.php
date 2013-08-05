<?php
//Parsonline/Exception/SystemException.php
/**
 * Defines the Parsonline_Exception_SystemException class.
 * 
 * Parsonline
 * 
 * Copyright (c) 2012 ParsOnline, Inc. (www.parsonline.com)
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
 * @copyright  Copyright (c) 2012 ParsOnline, Inc. (www.parsonline.com)
 * @license    Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Exception
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.0.1 2012-07-02
 */

/**
 * Parsonline_Exception_SystemException
 * 
 * Exception related to system call errors, like operating system restrictions.
 *
 * @uses    Parsonline_Exception
 */
require_once('Parsonline/Exception.php');
class Parsonline_Exception_SystemException extends Parsonline_Exception
{
}
