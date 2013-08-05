<?php
//Parsonline/ZF/Db/Adapter/Pdo/Dblib.php
/**
 * Defines Parsonline_ZF_Db_Adapter_Pdo_Dblib class.
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
 * @package     Parsonline_ZF_Db
 * @subpackage  Adapter
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.1.0 2011-02-13
 */

/**
 * @uses    Zend_Db_Adapter_Pdo_Mssql
 */
require_once('Zend/Db/Adapter/Pdo/Mssql.php');


/**
 * Parsonline_ZF_Db_Adapter_Pdo_Dblib
 * 
 * A DB adapter to connect to Microsoft SQL server
 * on unix base machines, using the dblib and freeTDS libraries.
 */
class Parsonline_ZF_Db_Adapter_Pdo_Dblib extends Zend_Db_Adapter_Pdo_Mssql
{
    protected $_pdoType = 'dblib';
}