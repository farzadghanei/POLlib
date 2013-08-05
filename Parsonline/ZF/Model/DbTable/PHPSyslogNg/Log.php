<?php
//Parsonline/ZF/Model/DbTable/PHPSyslogNg/Log.php
/**
 * Defines Parsonline_ZF_Model_DbTable_PHPSyslogNg_Log class
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
 * @copyright   Copyright (c) 2010-2011-2012 ParsOnline, Inc. (www.parsonline.com)
 * @license     Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Parsonline_ZF_Model
 * @subpackage  PHPSyslogNg
 * @author      Farzad Ghanei <f.ghanei@parsonline.net>
 * @version     1.0.0 2010-10-24 f.ghanei
 */

/**
 * @uses Zend_Db_Table_Abstract
 */
require_once('Zend/Db/Table/Abstract.php');

/**
 * Parsonline_ZF_Model_DbTable_PHPSyslogNg_Log
 * 
 * Database table gateway for logs table in a php-syslog-ng database.
 */
class Parsonline_ZF_Model_DbTable_PHPSyslogNg_Log extends Zend_Db_Table_Abstract
{
    protected $_name = 'logs';
    protected $_primary = 'seq';
}