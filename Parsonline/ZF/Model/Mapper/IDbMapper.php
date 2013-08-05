<?php
//Parsonline/ZF/Model/Mapper/IDbMapper.php
/**
 * Defines Parsonline_ZF_Model_Mapper_IDbMapper interface.
 *
 * * Parsonline
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
 * @subpackage  Mapper
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     1.1.5 2010-12-14 f.ghanei
 */

/**
 * Parsonline_ZF_Model_Mapper_IDbMapper
 * 
 * an interface for data mappers for application models that use a DB backend
 * as data source.
 *
 * @see Parsonline_ZF_Model_DbModelAbstract
 */
interface Parsonline_ZF_Model_Mapper_IDbMapper
{
    /**
     * Saves the model object to database
     * 
     * @param   object  $object
     * @return  int    number of added units to the data source (0 for inline update)
     * @throws  Parsonline_Exception
     * @throws  Zend_Db_Exception
     */
    public function save($object);
    
    /**
     * Loads the information of the specified row, into the object
     * 
     * @param   mixed       $id
     * @param   object      reference to the target object
     */
    public function load($id, $object);
    
    /**
     * fetches all records in the database as an array of objects
     * 
     * @param   string              $where      SQL where clause
     * @param   string|array        $order      SQL order by clause
     * @param   int                 $limit      SQL limit clause
     * @param   int                 $offset     SQL offset clause
     * @return  array
     */
    public function fetchAll($where=null, $order=null, $limit=null, $offset=null);
    
    /**
     * Counts records in the database
     * 
     * @return  int
     */
    public function count();
    
    /**
     * Finds an object regarding to a unique identifier (like a primary key)
     * 
     * @param   mixed       $id
     * @return  object
     */
    public function find($id);
    
    /**
     * Delets an object from the database
     * 
     * @param   object  $object
     * @param   int     number of deleted units from the data source
     * @throws  Parsonline_Exception
     */
    public function delete($object);
}