<?php
//Parsonline/ZF/Model/DbModelAbstract.php
/**
 * Defines Parsonline_ZF_Model_DbModelAbstract class.
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
 * @author      Farzad Ghanei <f.ghanei@parsonline.net>
 * @version     0.3.0 2012-07-08
 */

/**
 * @uses    Parsonline_ZF_Model_BaseModelAbstract
 * @uses    Parsonline_ZF_Model_Mapper_IDbMapper
 */
require_once('Parsonline/ZF/Model/BaseModelAbstract.php');
require_once('Parsonline/ZF/Model/Mapper/IDbMapper.php');

/**
 * Parsonline_ZF_Model_DbModelAbstract
 *
 * a specific data model class with a DB backed end data mapper as the data gateway.
 * this class requires its data mapper object to provide a DB like interface.
 * 
 * @see Parsonline_ZF_Model_BaseModelAbstract
 * @abstract
 */
abstract class Parsonline_ZF_Model_DbModelAbstract extends Parsonline_ZF_Model_BaseModelAbstract
{
    /**
     * Data mapper of the model object.
     * should implement the Parsonline_ZF_Model_Mapper_IDbMapper
     * 
     * @var Parsonline_ZF_Model_Mapper_IDbMapper
     */
    protected $_mapper = null;

    /**
     * Returns an array of method names that are not supposed to be auto discoverable
     * and used by the object manipulator.
     * 
     * @return array
     */
    protected function _getNoneAutoDiscoverableMethods()
    {
        $methods = parent::_getNoneAutoDiscoverableMethods();
        $methods = array_merge(
                        $methods,
                        array('load','delete','save', 'find', 'remove', 'count', 'fetchAll')
                    );
                    
        return array_unique($methods);
    }
    
    /**
     * Sets the data mapper object of the model
     *
     * @param   Parsonline_ZF_Model_Mapper_IDbMapper    $mapper
     * @return  Parsonline_ZF_Model_DbModelAbstract
     * @throws  Parsonline_Exception_ValueException
     */
    public function setMapper($mapper)
    {
        /**
         * @uses    Parsonline_ZF_Model_Mapper_IDbMapper
         */
        if ( !is_object($mapper) || !!($mapper instanceof Parsonline_ZF_Model_Mapper_IDbMapper) ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException(
                "Data mapper should be an object that implements Parsonline_ZF_Model_Mapper_IDbMapper"
            );
        }
        $this->_mapper = $mapper;
        return $this;
    } // public function setMapper()

    /**
     * Returns the mapper object that maps the model to its data source.
     * 
     * @return  Parsonline_ZF_Model_Mapper_IDbMapper
     */
    public function getMapper()
    {
        return $this->_mapper;
    }

    /**
     * saves model's data to the data source.
     * 
     * @return  Parsonline_ZF_Model_DbModelAbstract   object self reference
     * @throws  Parsonline_Exception_ContextException
     */
    public function save()
    {
        $mapper = $this->getMapper();
        if (!$mapper) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once("Parsonline/Exception/ContextException.php");
            throw new Parsonline_Exception_ContextException("Failed to save. no data mapper is set for the model object");
        }
        $mapper->save($this);
        return $this;
    }

    /**
     * Searches for a model identified by the unique identifier, then loads
     * data from that record into the model.
     *
     * @param   mixed   $id
     * @return  Parsonline_ZF_Model_DbModelAbstract   object self reference
     * @throws  Parsonline_Exception_ContextException
     */
    public function load($id)
    {
        $mapper = $this->getMapper();
        if (!$mapper) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once("Parsonline/Exception/ContextException.php");
            throw new Parsonline_Exception_ContextException("Failed to load data to model. no data mapper is set for the model object");
        }
        $mapper->load($id, $this);
        return $this;
    }

    /**
     * Deletes the data associated to the model object.
     *
     * @return  Parsonline_ZF_Model_DbModelAbstract   object self reference
     * @throws  Parsonline_Exception_ContextException
     */
    public function delete()
    {
        $mapper = $this->getMapper();
        if (!$mapper) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once("Parsonline/Exception/ContextException.php");
            throw new Parsonline_Exception_ContextException("Failed to delete model. No data mapper is set for the model object");
        }
        $mapper->delete($this);
        return $this;
    }

    /**
     * Removes an instance or a list of instances from the system storage. instances can be passed as objects,
     * or integer values for identifiers.
     * 
     * @param   mixed|Parsonline_ZF_Model_DbModelAbstract|array       $target       identifier value of, or an instance of a Parsonline_ZF_Model_DbModelAbstract, or array of these
     * @return  int     number of removed objects
     * @throws  Parsonline_Exception_ContextException
     */
    public function remove($target)
    {
        $mapper = $this->getMapper();
        if (!$mapper) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once("Parsonline/Exception/ContextException.php");
            throw new Parsonline_Exception_ContextException("Failed to remove target model. No data mapper is set for the model object");
        }
        return $mapper->delete($target);
    }

    /**
     * Returns an object related to a given id in the records, or null if no
     * record exists with the specified ID
     *
     * @param   mixed     $id
     * @return  Parsonline_ZF_Model_DbModelAbstract|null
     * @throws  Parsonline_Exception_ContextException
     */
    public function find($id)
    {
        $mapper = $this->getMapper();
        if (!$mapper) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once("Parsonline/Exception/ContextException.php");
            throw new Parsonline_Exception_ContextException("Failed to find. No data mapper is set for the model object");
        }
        return $mapper->find($id);
    }

    /**
     * Fetches all models.
     * 
     * @param   string|array|Zend_Db_Table_Select     $where  OPTIONAL An SQL WHERE clause or Zend_Db_Table_Select object.
     * @param   string|array                          $order  OPTIONAL An SQL ORDER clause.
     * @param   int                                   $count  OPTIONAL An SQL LIMIT count.
     * @param   int                                   $offset OPTIONAL An SQL LIMIT offset.
     * 
     * @return  array
     * @throws  Parsonline_Exception_ContextException
     */
    public function fetchAll($where = null, $order = null, $count = null, $offset = null)
    {
        $mapper = $this->getMapper();
        if (!$mapper) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once("Parsonline/Exception/ContextException.php");
            throw new Parsonline_Exception_ContextException("Failed to fetch all data. No data mapper is set for the model object");
        }
        return $mapper->fetchAll($where, $order , $count, $offset);
    }

    /**
     * Returns the number of available records
     * 
     * @return  int
     * @throws  Parsonline_Exception_ContextException
     */
    public function count()
    {
        $mapper = $this->getMapper();
        if (!$mapper) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once("Parsonline/Exception/ContextException.php");
            throw new Parsonline_Exception_ContextException("Failed to count available data. No data mapper is set for the model object");
        }
        return $mapper->count();
    }
}