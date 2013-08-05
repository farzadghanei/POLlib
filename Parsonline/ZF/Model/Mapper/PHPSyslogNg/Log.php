<?php
//Parsonline/ZF/Model/Mapper/PHPSyslogNg/Log.php
/**
 * Defines Parsonline_ZF_Model_Mapper_PHPSyslogNg_Log class.
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
 * @version     3.2.2 2010-10-24 f.ghanei
 */

/**
 * @uses    Parsonline_ZF_Model_Mapper_IDbMapper
 * @uses    Parsonline_ZF_Model_Mapper_DbMapperAbstract
 * @uses    Parsonline_ZF_Model_DbTable_PHPSyslogNg_Log
 * @uses    Parsonline_ZF_Model_PHPSyslogNg_Log
 */
require_once('Parsonline/ZF/Model/Mapper/DbMapperAbstract.php');
require_once('Parsonline/ZF/Model/Mapper/IDbMapper.php');
require_once('Parsonline/ZF/Model/DbTable/PHPSyslogNg/Log.php');
require_once('Parsonline/ZF/Model/PHPSyslogNg/Log.php');

/**
 * Parsonline_ZF_Model_Mapper_PHPSyslogNg_Log
 * 
 * data mapper for php-syslog-ng log records model.
 * 
 * maps functionalities between a data model class of PHPSyslogNg_Log
 * to the data source which is an instance of Zend_Db_Table_Abstract.
 * 
 */
class Parsonline_ZF_Model_Mapper_PHPSyslogNg_Log extends Parsonline_ZF_Model_Mapper_DbMapperAbstract
implements Parsonline_ZF_Model_Mapper_IDbMapper
{
    /**
     * maps the name of a column of the database to a property (or getter/setter
     * pair method) of the data model. this array helps the mapper to retreive
     * or inject the proper value from the data source (DB) to the data model.
     * each key should be the name of the database column, and the value
     * should be the public property (or the % in a get% set% public getter/setter
     * method) of the data model class.
     *
     * @var array
     */
    protected $_dbColsMappedToDataModel = array(
                                                'datetime' => 'DateTime()',
                                                 'msg' => 'Message()',
                                                 'seq' => 'Sequence()'
                                            );

    /**
     * returns the DbTable associated to the data mapper
     *
     * @return  Parsonline_ZF_Model_DbTable_PHPSyslogNg_Log
     */
    public function getDbTable()
    {
        if (null === $this->_dbTable) {
            /**
             * @uses    Parsonline_ZF_Model_DbTable_PHPSyslogNg_Log
             */
            $this->setDbTable( new Parsonline_ZF_Model_DbTable_PHPSyslogNg_Log() );
        }
        return $this->_dbTable;
    }
}
