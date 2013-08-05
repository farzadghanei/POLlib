<?php
//Parsonline/ZF/Model/Mapper/DbMapperAbstract.php
/**
 * Defines Parsonline_ZF_Model_Mapper_DbMapperAbstract class.
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
 * @subpackage  Mapper
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @author      Mohammad Emami Razavi <mo.razavi@parsonline.com>
 * @version     5.2.2 2012-07-15
 */

/**
 * @uses    Parsonline_Exception_ObjectInspectionException
 * @uses    Parsonline_ObjectManipulator
 * @uses    Zend_Db
 * @uses    Zend_Db_Table
 * @uses    Zend_Db_Table_Abstract
 * @uses    Zend_Db_Table_Select
 * @uses    Zend_Db_Table_Rowset_Abstract
 * @uses    Zend_Db_Table_Row_Abstract
 * @uses    Zend_Db_Adapter_Abstract
 */
require_once('Parsonline/ObjectManipulator.php');
require_once('Parsonline/Exception/ObjectInspectionException.php');
require_once('Zend/Db.php');
require_once('Zend/Db/Table.php');
require_once('Zend/Db/Table/Abstract.php');
require_once('Zend/Db/Table/Select.php');
require_once('Zend/Db/Table/Rowset/Abstract.php');
require_once('Zend/Db/Table/Row/Abstract.php');
require_once('Zend/Db/Adapter/Abstract.php');

/**
 * Parsonline_ZF_Model_Mapper_DbMapperAbstract
 * 
 * maps functionalities between model (model) and model data table (data source).
 * this will create a level of abstraction between the model logic and the real data source.
 * this is an abstract class for model mappers with database backend.
 *
 * for more information on the concept read about "Data Mapper" design pattern.
 * @see http://martinfowler.com/eaaCatalog/dataMapper.html
 * 
 * @abstract
 * 
 */
abstract class Parsonline_ZF_Model_Mapper_DbMapperAbstract
{
    /**
     * the DbTable object
     * 
     * @var Zend_Db_Table_Abstract
     */
    protected $_dbTable;
    
    /**
     * cached list of DB table columns
     * 
     * @var array
     */
    protected $_dbTableColumns;

    /**
     * cached list of DB table primary key columns
     * 
     * @var array
     */
    protected $_dbTablePrimaryKeyColumns = array();
    
    /**
     * The name of the class whose objects are the data models that
     * use the data mapper to connect to their data sources.
     * This is as a helper proprety for auto generating data model
     * objects.
     * 
     * @var string
     */
    protected $_dataModelClassName;

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
    protected $_dbColsMappedToDataModel = array();
    
    /**
     * If the mapper should automatically handle naming convensions, by
     * converting _ delimited words for database column names, into camel cased
     * properties/methods of the data model.
     * 
     * @var bool
     */
    protected $_handleNamingConvensions = true;
    
    /**
     * returns the actual DbTable object that is the database access gateway
     * of the data storage.
     * overrides the parent, by automatically creating the dbtable object if not exists,
     * using the application naming conventions.
     *
     * @return  Zend_Db_Table_Abstract
     */
    public function getDbTable()
    {
        if (!$this->_dbTable) {
            $guessedDbTable = $this->autoInstanciateDbTable();
            if ($guessedDbTable) $this->setDbTable($guessedDbTable);
        }
        return $this->_dbTable;
    } // public function getDbTable()
    
    /**
     * sets the DbTable object of the Mapper.
     *
     * @param   Zend_Db_Table_Abstract      $dbTable
     * @return  Parsonline_ZF_Model_Mapper_DbMapperAbstract
     * @throws  Parsonline_Exception_ValueException
     */
    public function setDbTable($dbTable)
    {
        if (is_string($dbTable)) {
            $dbTable = new $dbTable();
        }
        if (!$dbTable instanceof Zend_Db_Table_Abstract) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException('table data gateway should be a Zend_Db_Table_Abstract object');
        }
        $this->_dbTable = $dbTable;
        return $this;
    } // public function setDbTable()

    /**
     * returns an array of column names from the database.
     * if no data table is assigned to the mapper yet throws an exception.
     * if failed to detect database columns, returns empty array.
     *
     * @return  array
     * @throws  Parsonline_Exception_ContextException
     */
    public function getDbTableColumns()
    {
        if ( !$this->_dbTableColumns ) {
            $cols = null;
            /**
             * @uses    Zend_Db_Table_Abstract
             */
            $dbTable = $this->getDbTable();
            if (!$dbTable) {
                /**
                 * @uses    Parsonline_Exception_ContextException
                 */
                require_once('Parsonline/Exception/ContextException.php');
                throw new Parsonline_Exception_ContextException(
                    "failed to fetch table columns. no DbTable data source has been assigned to the mapper"
                );
            }
            $cols = $dbTable->info(Zend_Db_Table_Abstract::COLS);
            if (!$cols) $cols = array();
            $this->_dbTableColumns = $cols;
        }
        return $this->_dbTableColumns;
    } // public function getDbTableColumns()

    /**
     * returns an array of primary key column names from the database table.
     * if no data table is assigned to the mapper yet throws an exception.
     * if failed to detect database columns, returns empty array.
     * 
     * @return  array
     * @throws  Parsonline_Exception_ContextException
     */
    public function getDbTablePrimaryKeyColumns()
    {
        if (!$this->_dbTablePrimaryKeyColumns) {
            /**
             * @uses    Zend_Db_Table_Abstract
             */
            $dbTable = $this->getDbTable();
            if (!$dbTable) {
                /**
                 * @uses    Parsonline_Exception_ContextException
                 */
                require_once('Parsonline/Exception/ContextException.php');
                throw new Parsonline_Exception_ContextException(
                    "failed to fetch table primary keys. no DbTable data source has been assigned to the mapper"
                );
            }
            $pks = $dbTable->info(Zend_Db_Table_Abstract::PRIMARY);
            if ( is_array($pks) ) {
                $this->_dbTablePrimaryKeyColumns = $pks;
            } elseif (!$pks) {
                $this->_dbTablePrimaryKeyColumns = array();
            } else { // single string value
                $this->_dbTablePrimaryKeyColumns = array($pks);
            }
        }
        return $this->_dbTablePrimaryKeyColumns;
    } // public function getDbTablePrimaryKeyColumns()

    /**
     * Guesses the name of the DbTable class name appropriate for the data mapper
     * class (either specified, or the objects own class if no param is specified).
     * The guess is done by ZF suggested naming convensions.
     * 
     * @param   string  $mapperClassName
     * @return  string
     */
    public function guessDbTableClassName($mapperClassName=null)
    {
        $mapperClassName = $mapperClassName ? strval($mapperClassName) : get_class($this);
        return str_replace('_Mapper_','_DbTable_', $mapperClassName);
    } // public function guessDbTableClassName()

    /**
     * Guesses the name of the data model class name appropriate for the data mapper
     * class (either specified, or the objects own class if no param is specified).
     * The guess is done by the ZF suggested naming convensions.
     *
     * @param   string  $mapperClassName
     * @return  string
     */
    public function guessDataModelClassName($mapperClassName=null)
    {
        $mapperClassName = $mapperClassName ? strval($mapperClassName) : get_class($this);
        return str_replace('_Mapper','', $mapperClassName);
    }

    /**
     * Instanciates a DbTable object from an automatically guessed class name
     * for the DbTable appropriate for the mapper object. returns the object if
     * everything went ok, or null if failed to instanciate the class (no such class
     * existed). in that case, a PHP warning would be triggered.
     *
     * @return  Zend_Db_Table_Abstract|null
     */
    public function autoInstanciateDbTable()
    {
        $dbTableClassName = $this->guessDbTableClassName();
        try {
            $object = new $dbTableClassName();
            return $object;
        } catch(Exception $exp) {
            // failed to instanciate the class.
            trigger_error(
                sprintf(
                    '%s:%d> failed to instanciate the auto guessed DbTable class "%s". message: "%s"',
                    __METHOD__,
                    $exp->getLine(),
                    $dbTableClassName,
                    $exp->getMessage()
                ),
                E_USER_WARNING
            );
        }
        return null;
    } // public function autoInstanciateDbTable()

    /**
     * Instanciates a data model object from an automatically guessed class name
     * for the data model class appropriate for the mapper object. returns the object if
     * everything went ok, or null if failed to instanciate the class (no such class
     * existed). in that case, a PHP warning would be triggered.
     *
     * @return  object|null
     */
    public function autoInstanciateDataModel()
    {
        $modelClassName = ($this->_dataModelClassName ?
                                        $this->_dataModelClassName
                                        : $this->guessDataModelClassName());
        try {
            $object = new $modelClassName();
            if (!$this->_dataModelClassName) $this->_dataModelClassName = $modelClassName;
            return $object;
        } catch (Exception $exp) {
            // failed to instanciate the class.
            trigger_error(
                sprintf(
                    '%s:%d> failed to auto instanciate the data model class "%s". message: "%s"',
                    __METHOD__,
                    $exp->getLine(),
                    $modelClassName,
                    $exp->getMessage()
                ),
                E_USER_WARNING
            );
        }
        return null;
    } // public function autoInstanciateDataModel()
    
    /**
     * returns an array of database column names.
     *
     * @return array
     * @throws  Parsonline_Exception_ContextException
     */
    public function toArray()
    {
        return $this->getDbTableColumns();
    }

    /**
     * returns a string of database column names.
     * returned string is in XML format with attributes about the database schema,
     * table name, and the columns in the database.
     *
     * @return  string
     * @throws  Parsonline_Exception_ContextException
     */
    public function __toString()
    {
        $dbTable = $this->getDbTable();
        /*@var $dbTable    Zend_Db_Table_Abstract */
        $cols = $this->toArray();
        $info = $dbTable->info();
        $schema = $info[Zend_Db_Table::SCHEMA];
        $table = $info[Zend_Db_Table::NAME];
        return  sprintf(
                        '<%s description="data mapper with database backend" schema="%s" table="%s" columns="%s" />',
                        get_class($this),
                        $schema, $table,
                        implode(',', $cols)
                );
    } // public function __toString()
    
    /**
     * Tries to find the value of a database column from the data model object.
     * Uses the mapped column names to data model from the internal
     * property $_dbColsMappedToDataModel, and if failed to find a suitable
     * match, tries to find an exact match for the name of the column from the
     * public properties and getter methods of the data model object.
     * 
     * If failed to find a suitable match, throws an exception.
     * 
     * Returns an array of (requested property, requested method, value).
     * If the data is fetched out of a property, the name of the property is the
     * first index, otherwise the name of the getter method is the seconds index.
     * the value is always in the third index.
     *
     * NOTE: This method is used to relate database columns and data stored
     * in the data model object. If this method of mapping data between the
     * database and the data model is not preferred for you, reimplementing
     * this method saves you to reimplement all the save/load/delete methods.
     * 
     * NOTE: The setDbColumnDataToDataModel() has the same concept.
     * 
     * NOTE: internally uses a Parsonline_ObjectManipulator object.
     * 
     * NOTE: to disable handling of different naming convensions change
     * the $_handleNamingConversions property of the mapper.
     * 
     * @see     $_dbColsMappedToDataModel
     * @see     $_handleNamingConvensions
     * @see     setDbColumnDataToDataModel()
     * @see     save()
     * @see     load()
     * @see     delete()
     * @see     Parsonline_ObjectManipulator
     * 
     * @param   object  $model      data model object.
     * @param   string  $col            name of the column
     * @return  array(value, requested resource)
     * @throws  Parsonline_Exception_ObjectInspectionException, Parsonline_Exception_ValueException
     */
    public function getDbColumnDataFromDataModel($model, $col)
    {
        if (array_key_exists($col, $this->_dbColsMappedToDataModel)) {
            $mapped = $this->_dbColsMappedToDataModel[$col];
        } else {
            $mapped = $col;
        }
        
        /**
         * @uses    Parsonline_ObjectManipulator
         */
        $manipulator = new Parsonline_ObjectManipulator($model);
        return $manipulator->get($mapped, !$this->_handleNamingConvensions, true, true);
    } // public function getDbColumnDataFromDataModel()
    
    /**
     * Tries to set the value of a database column into the data model object.
     * Uses the mapped column names to data model from the internal
     * property $_dbColsMappedToDataModel, and if failed to find a suitable
     * match, tries to find an exact match for the name of the column from the
     * public properties and setter methods of the data model object.
     * If failed to find a suitable match, throws an exception.
     * 
     * Returns an array of (returned value, requested resource).
     * 
     * If the data is fetched set to a property, the name of the property is the
     * seconds index, otherwise it is the name of the setter method ending with ().
     * If the setter method returned a value, it is stored in the first index,
     * otherwise it is always null.
     *
     * NOTE: This method is used to relate database columns and data stored
     * in the data model object. If this method of mapping data between the
     * database and the data model is not preferred for you, reimplementing
     * this method saves you to reimplement all the save/load/delete methods.
     * 
     * NOTE: The getDbColumnDataToDataModel() has the same concept.
     * 
     * NOTE: internally uses a Parsonline_ObjectManipulator object.
     * 
     * NOTE: to disable handling of different naming convensions change
     * the $_handleNamingConversions property of the mapper.
     * 
     * @see     $_dbColsMappedToDataModel
     * @see     $_handleNamingConvensions
     * @see     save
     * @see     load
     * @see     delete
     * @see     Parsonline_ObjectManipulator
     * 
     * @param   object  $model  data model object.
     * @param   string  $col    name of the column
     * @param   mixed   $value  the value of the column
     * @return  array(value, requested resource)
     * @throws  Parsonline_Exception_ObjectInspectionException
     */
    public function setDbColumnDataToDataModel($model, $col, $value)
    {
        if (array_key_exists($col, $this->_dbColsMappedToDataModel)) {
            $mapped = $this->_dbColsMappedToDataModel[$col];
        } else {
            $mapped = $col;
        }
        
        /**
         * @uses    Parsonline_ObjectManipulator
         */
        $manipulator = new Parsonline_ObjectManipulator($model);
        return $manipulator->set($mapped, $value, !$this->_handleNamingConvensions, true, true);
    } // public function setDbColumnDataFromDataModel()
    
    /**
     * Tries to find the unique identifier of the data model.
     *
     * Searches for the a public property, or getter method corresponding
     * to the pirmary key field of the underlying DbTable class.
     * if DbTable had no primary key, would throw a Parsonline_Exception_ContextException.
     * If failed to find the unique ID, would throw a Parsonline_Exception_ObjectInspectionException.
     *
     * NOTE: internally uses getDbColumnDataFromDataModel() to get PK value out of model.
     * 
     * NOTE: If the unique Identifier of the data model is not in the
     * name of the primary key of the database table,
     * please reimplement this method in the specific
     * data mapper of the data model class.
     * 
     * NOTE: methods that need to identify a data model, internally use this method.
     * 
     * @see     getDbColumnDataFromDataModel()
     * @see     setDataModelUniqueIdentifier
     * @see     save()
     * @see     delete()
     * 
     * @param   object  $model
     * @return  mixed   the unique ID value
     * @throws  Parsonline_Exception_ObjectInspectionException, Parsonline_Exception_ContextException
     */
    public function getDataModelUniqueIdentifier($model)
    {
        $pk = current($this->getDbTablePrimaryKeyColumns());
        if (!$pk) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "failed to detect data model unique identifier. the DbTable has no primary key fields"
                );
        }
        return $this->getDbColumnDataFromDataModel($model, $pk);
    } // public function getDataModelUniqueIdentifier()
    
    /**
     * Tries to set the unique identifier of the data model.
     *
     * Searches for the a public property, or getter method corresponding
     * to the pirmary key field of the underlying DbTable class.
     * if DbTable had no primary key, would throw a Parsonline_Exception_ContextException.
     * If failed to find the unique ID, would throw a Parsonline_Exception_ObjectInspectionException.
     *
     * NOTE: internally uses setDbColumnDataFromDataModel() to get PK value out of model.
     * 
     * NOTE: If the unique Identifier of the data model is not in the
     * name of the primary key of the database table,
     * please reimplement this method in the specific
     * data mapper of the data model class.
     * 
     * NOTE: methods that need to set the model identity, internally use this method.
     * 
     * @see     setDbColumnDataToDataModel()
     * @see     getDataModelUniqueIdentifier()
     * @see     save()
     * 
     * @param   object  $model
     * @return  mixed   the unique ID value
     * @throws  Parsonline_Exception_ObjectInspectionException, Parsonline_Exception_ContextException
     */
    public function setDataModelUniqueIdentifier($model, $id)
    {
        $pk = current($this->getDbTablePrimaryKeyColumns());
        if (!$pk) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "failed to detect data model unique identifier. the DbTable has no primary key fields"
                );
        }
        return $this->setDbColumnDataToDataModel($model, $pk, $id);
    } // public function setDataModelUniqueIdentifier()
    
    /**
     * Loads the data in the array into the model object.
     * loads those array keys that relate to a database column, by calling the
     * setDbColumnDataToDataModel() internally.
     * catches exceptions incase of failures, but keeps track of failures and
     * successes.
     * 
     * Returns an indexed array. the first member is an associative array of keys
     * whose data were set into the object successfully, mapped to the return
     * value of the setter method or null if the setting was done
     * on a property or the method returned nothing.
     * the second, is an associative array of keys whose data were failed to be set,
     * and values are Excpetion objects describing what went wrong.
     * 
     * NOTE: does not throw exceptions. check the second memeber of returned
     * array for errors.
     * 
     * NOTE: If there were members in the array that did not have a corresponding
     * data in the object, they are included in the returned Exceptions array
     * as Parsonline_Exception_ObjectInspectionExceptions with code
     * CODE_NO_MATCH_FOUND. Inclusion of these values to the returning results
     * could be turned off, by setting the third parameter.
     * This way the returned Exception array only contains
     * technical problems occurred while batch setting the data.
     *
     * @see     setDbColumnDataToDataModel()
     * 
     * @param   object  $model
     * @param   array   $data
     * @param   bool    $skipMisMatches
     * @return  array(array key => returned value, array key => Exception)
     */
    public function loadDataFromArray($model, array $data, $skipMisMatches=false)
    {
        $successes = array();
        $errors = array();

        foreach($data as $key => $value) {
            try {
                $successes[$key] = current($this->setDbColumnDataToDataModel($model, $key, $value));
            } catch(Exception $exp) {
                /**
                 * @uses    Parsonline_ObjectManipulator
                 * @uses    Parsonline_Exception_ObjectInspectionException
                 */
                if ($skipMisMatches &&
                    $exp instanceof Parsonline_Exception_ObjectInspectionException
                    && $exp->getCode() == Parsonline_ObjectManipulator::CODE_NO_MATCH_FOUND
                ) {
                    continue;
                }
                $errors[$key] = $exp;
            }
        }
        return array($successes, $errors);
    } // public function loadDataFromArray()
    
    /**
     * Loads the data in the specified object into the model object.
     * 
     * internally uses an instance of Parsonline_ObjectManipulator.
     * 
     * Returns an indexed array. the first member is an associative array of property
     * names whose value were set into the object successfully, mapped to the return
     * value of the setter method or null if the setting was done
     * on a property or the method returned nothing.
     * the second, is an associative array of property names whose data were failed to be set,
     * and values are Excpetion objects describing what went wrong.
     * 
     * NOTE: does not throw exceptions. check the second memeber of returned
     * array for errors.
     * 
     * NOTE: exceptions thrown because of invalid parameters are always thrown
     * 
     * NOTE: If there were data in the remote object that did not have a corresponding
     * data in the object, they are included in the returned Exceptions array
     * as Parsonline_Exception_ObjectInspectionExceptions with code
     * CODE_NO_MATCH_FOUND. Inclusion of these values to the returning results
     * could be turned off, by setting the third parameter.
     * This way the returned Exception array only contains
     * technical problems occurred while batch setting the data.
     *
     * @see     Parsonline_ObjectManipulator
     * @see     loadDataFromArray()
     * 
     * @param   object  $model
     * @param   object  $data
     * @param   bool    $skipMisMatches
     * @return  array(array key => returned value, array key => Exception)
     * @throws  Parsonline_Exception_ValueException
     */
    public function loadDataFromOpenObject($model, $data, $skipMisMatches=false)
    {
        if (!is_object($data)) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("data parameter should be an object");
        }

        /**
         * @uses    Parsonline_ObjectManipulator
         */
        $manipulator = new Parsonline_ObjectManipulator($model);
        return $manipulator->setDataFromRemoteObject(
                                            $data,
                                            !$this->_handleNamingConvensions,
                                            $skipMisMatches
                );
    } // public function loadDataFromOpenObject()
    
    /**
     * Iterates over an array of data arrays. creates an instance of the data model
     * class for each data array and loads the data model with that array.
     * 
     * By default catches all exceptions and returns them at the end, but this
     * could be disabled by setting the "stopOnFailure" parameter.
     * 
     * Returns an indexed array with 2 members.
     * The first is an indexed array of successfully loaded data model objects.
     * The socond is an associative array of index => exceptions to report what
     * went wrong while loading that array (specified by index) to the data model.
     * 
     * NOTE: if the mapper fails to autho instanciate the data model calss,
     * the method always throws a Parsonline_Context_Exception.
     * 
     * @param   array   $dataArrays
     * @param   bool    $stopOnFailure
     * @return  array (array model objects, array index => exception)
     * @throws  Parsonline_Exception_ContextException
     */
    public function convertDataArrayToDataModelArray(array $dataArrays, $stopOnFailure=false)
    {
        $entity = $this->autoInstanciateDataModel();
        if (!is_object($entity)) {
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "failed to auto instanciate the data model class"
            );
        }
        unset($entity);
        
        $entities = array();
        $errors = array();
        
        foreach($dataArrays as $index => $dataArray) {
            $entity = $this->autoInstanciateDataModel();
            $loadResults = $this->loadDataFromArray($entity, $dataArray, false);
            $loadExceptions = $loadResults[1];
            if (count($loadExceptions) > 0) {
                $property = key($loadExceptions);
                $exp = $loadExceptions[$property];
                
                if ($stopOnFailure) {
                    /**
                     * @uses    Parsonline_Exception_ContextException
                     */
                    require_once('Parsonline/Exception/ContextException.php');
                    throw new Parsonline_Exception_ContextException(
                        sprintf(
                            "failed to load '%s' from data array into the new generated data model. exception <%s> code %d: %s",
                            $property,
                            get_class($exp),
                            $exp->getCode(),
                            $exp->getMessage()
                        ),
                        500, $exp
                    );
                }
                $errors[$index] = $exp;
            }
            array_push($entities, $entity);
        }
        
        return array($entities, $errors);
    } // public function convertDataArrayToDataModelArray()
    
    /**
     * counts the number of records
     * 
     * @return  int
     * @throws  Parsonline_Exception_ContextException
     */
    public function count()
    {
        $dbTable = $this->getDbTable();
        if (!$dbTable) {
            /**
            * @uses    Parsonline_Exception_ContextException
            */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException("failed to count records. no DBTable data source has been assigned to the mapper");
        }
        /*@var $dbTable    Zend_Db_Table_Abstract */
        
        $dbAdapter = $dbTable->getAdapter();
        $tableName = $dbAdapter->quoteIdentifier( $dbTable->info(Zend_Db_Table_Abstract::NAME) );
        /* @var $dbAdapter Zend_Db_Adapter_Abstract */
        
        $count = $dbAdapter->fetchOne("SELECT COUNT(*) FROM {$tableName}");
        if (!$count) return 0;
        return intval($count);
    } // public function count()
    
    /**
     * returns a row object related to a given id in the database
     *
     * @param   mixed     $id       a database primary key, or an array of them
     * @return  Zend_Db_Table_Row_Abstract|null
     * @throws  Parsonline_Exception_ContextException, Zend_Db_Table_Exception
     */
    public function findRow($id)
    {
        $dbTable = $this->getDbTable();
        if (!$dbTable) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException("failed to search the database. no DbTable object is assigned as data source to the data mapper");
        }
        /*@var $dbTable Zend_Db_Table_Abstract*/
        
        $rowSet = $dbTable->find($id);
        /*@var  $rowSet Zend_Db_Table_Rowset_Abstract */
        
        if (0 == count($rowSet)) {
            return null;
        }
        return $rowSet->current();
    } // public function findRow()
    
    /**
     * Based on the specified unique ID, finds and returns the populated
     * data model object.
     * If no such object exists, returns null.
     * If failed to autoinstanciate the data model, or failed
     * to load the data from the found record into the data model, throws an
     * exception.
     *
     * @param   mixed       $id
     * @return  object|null
     * @throws  Parsonline_Exception_ContextException, Zend_Db_Exception
     */
    public function find($id)
    {
        $model = $this->autoInstanciateDataModel();
        if (!$model) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "failed to auto instanciate the data model class"
            );
        }
        $row = $this->findRow($id);
        if ( !$row ) return null;
        $row = $row->toArray();
        $loadResults = $this->loadDataFromArray($model, $row, false);
        $loadExceptions = $loadResults[1];
        unset($loadResults);
        if (count($loadExceptions) > 0) {
            $property = key($loadExceptions);
            $exp = $loadExceptions[$property];
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                sprintf(
                    "failed to load '%s' from data array into the new generated data model. exception <%s> code %d: %s",
                    $property,
                    get_class($exp),
                    $exp->getCode(),
                    $exp->getMessage()
                ),
                500, $exp
            );
        }
        return $model;
    } // public function find()

    /**
     * Finds an previousely stored data for the unique ID speceified,
     * and loads that data into the data model object.
     * 
     * @param   mixed     $id
     * @param   object    $model
     * @return  object
     * @throws  Parsonline_Exception_ContextException, Zend_Db_Exception
     * @throws  Parsonline_Exception_InvalidParamterException
     */
    public function load($id, $model)
    {
        $row = $this->findRow($id);
        if ( !$row ) return null;
        $row = $row->toArray();
        $loadResults = $this->loadDataFromArray($model, $row, false);
        $loadExceptions = $loadResults[1];
        unset($loadResults);
        if (count($loadExceptions) > 0) {
            $property = key($loadExceptions);
            $exp = $loadExceptions[$property];
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                sprintf(
                    "failed to load '%s' from data array into the new generated data model. exception <%s> code %d: %s",
                    $property,
                    get_class($exp),
                    $exp->getCode(),
                    $exp->getMessage()
                ),
                500, $exp
            );
        }
        return $model;
    } // public function load()
    
    /**
     * Returns all records (or could be configured to return a subset of them)
     * in the data base, as an array of associative array values.
     *
     * @param   Zend_Db_Table_Select|array|string       $where      where clause to select a subset of records
     * @param   string|array                            $order      SQL order clause
     * @param   int                                     $limit      SQL limit value
     * @param   int                                     $offset     SQL offset value
     * @return  array       indexed array of associative array values
     * @throws  Parsonline_Exception_ContextException, Exception, Zend_Db_Exception
     */
    public function fetchAllAsArray($where=null, $order=null, $limit=null, $offset=null)
    {
        $dbTable = $this->getDbTable();
        if (!$dbTable) {
            /**
            * @uses    Parsonline_Exception_ContextException
            */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "failed to fetch records. no DbTable data source has been assigned to the mapper"
            );
        }
        /*@var $dbTable    Zend_Db_Table_Abstract */
        
        $dbAdapter = $dbTable->getAdapter();
        /*@var $dbAdapter Zend_Db_Adapter_Abstract */
        $resultSet = $dbTable->fetchAll($where, $order, $limit, $offset);
        if ( !is_object($resultSet) && !($resultSet instanceof Zend_Db_Table_Rowset_Abstract) ) {
            return $resultSet;
        }
        /*@var $resultSet   Zend_Db_Table_Rowset_Abstract */
        $resultSetAsArray = array();
        
        foreach( $resultSet as $row ) {
            array_push(
                    $resultSetAsArray,
                    is_array($row) ? $row : $row->toArray()
                );
        }
        return $resultSetAsArray;
    } // public function fetchAllAsArray()
    
    /**
     * Returns all records (or could be configured to return a subset of them)
     * in the data base, as an array of data model object.
     * 
     * NOTE: throws a Parsonline_Exception_ContextException if failed to
     * auto instanciate the data model.
     * 
     * @see Parsonline_ZF_Model_Mapper_DbMapperAbstract::fetchAllAsArray()
     * 
     * @param   Zend_Db_Table_Select|array|string       $where      where clause to select a subset of records
     * @param   string|array                            $order      SQL order clause
     * @param   int                                     $limit      SQL limit value
     * @param   int                                     $offset     SQL offset value
     * @return  array       indexed array of associative array values
     * @throws  Parsonline_Exception_ContextException, Zend_Db_Exception
     */
    public function fetchAll($where=null, $order=null, $limit=null, $offset=null)
    {
        $resultArray = $this->fetchAllAsArray($where, $order, $limit, $offset);
        $entities = $errors = array();
        list($entities) = $this->convertDataArrayToDataModelArray($resultArray, true);
        return $entities;
    } // public function fetchAll()

    /**
     * searches data with a given array of information and returns an array of arrays.
     *
     * @param   array   $data       associative array of information to have more specific results. field => value, minField => value, maxField => value, ~filed => value (for like search).
     * @param   array   $config     associative array of configurations to have more control over the search results: order, group, limit, offset
     * @return  array   indexed array of associative arrays
     * @throws  Parsonline_Exception, Zend_Db_Exception, Parsonline_Exception_ValueException
     * @uses    Zend_Db_Table_Abstract, Zend_Db_Adapter_Abstract
    */
    public function searchAsArray(array $data=array(), array $config=array())
    {
        if (!is_array($data)) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException(
                        "data should be an assiciateive array to specify the target records"
                    );
        }
        
        if (!is_array($config)) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("config should be an assiciateive array to specify returning results configurations");
        }

        $dbTable = $this->getDbTable();
        if (!$dbTable) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException("failed to search the database. no DBTable object is assigned as data source to the data mapper");
        }
        /*@var $dbTable Zend_Db_Table_Abstract*/
        
        /**
         * @uses    Zend_Db_Table
         */
        $tableColumns = $dbTable->info(Zend_Db_Table::COLS);
        
        /*@var $dbAdapter Zend_Db_Adapter_Abstract */
        $dbAdapter = $dbTable->getAdapter();

        /**
         * @uses    Zend_Db_Table_Select
         */
        $select = $dbTable->select(true);
        /*@var $select  Zend_Db_Table_Select*/

        foreach ($tableColumns as $field) {
            if ( array_key_exists($field, $data) ) {
                $select->where( "$field = ?" , $data[$field] );
            } else if ( array_key_exists("~{$field}", $data) ) {
                $value = $data["~$field"];
                if ( $value ) {
                    $value = $dbAdapter->quote("%{$value}%");
                    $select->where( "$field like {$value}" );
                } else {
                    $select->where( "$field like '%'" );
                }
            } elseif (array_key_exists("!{$field}", $data) ) {
                $value = $data["!{$field}"];
                $select->where("$field <> ?", $value);
            }
            if ( array_key_exists('min'.$field,$data) ) $select->where( "$field > ?" , $data['min'.$field] );
            if ( array_key_exists('max'.$field,$data) ) $select->where( "$field < ?" , $data['max'.$field] );
            
        } // foreach()

        if ( array_key_exists('group',$config) ) $select->group($config['group']);
        if ( isset($config['order'])) $select->order($config['order']);
        if ( isset($config['limit']) ) {
            $offset = isset($config['offset']) ? intval($config['offset']) : null;
            $select->limit( intval($config['limit']), $offset);
        }

        /*
         * run the query by using the associativa array fetch mode.
         */
        $resultSet = $dbAdapter->fetchAll($select, array(), Zend_Db::FETCH_ASSOC);
        
        if ( !is_object($resultSet) && !($resultSet instanceof Zend_Db_Table_Rowset_Abstract) ) return $resultSet;
        /*@var $resultSet   Zend_Db_Table_Rowset_Abstract */
        $resultSetAsArray = array();
        
        foreach( $resultSet as $row ) {
            array_push(
                    $resultSetAsArray,
                    is_array($row) ? $row : $row->toArray()
                );
        }
        return $resultSetAsArray;
    } // public function searchAsArray()
    
    /**
     * searches data with a given array of information and returns an array of
     * data model objects.
     * 
     * NOTE: throws a Parsonline_Exception_ContextException if failed to
     * auto instanciate the data model.
     * 
     * @see Parsonline_ZF_Model_Mapper_DbMapperAbstract::searchAsArray()
     *
     * @param   array   $data       associative array of information to have more specific results. field => value, minField => value, maxField => value, ~filed => value (for like search).
     * @param   array   $config     associative array of configurations to have more control over  the search results: order, sortOrder, group.
     * @return   array   indexed array of associative arrays
     * @throws  Parsonline_Exception, Zend_Db_Exception, Parsonline_Exception_ValueException
     * @uses    Zend_Db_Table_Abstract, Zend_Db_Adapter_Abstract
    */
    public function search(array $data = array(), array $config = array() )
    {
        $resultArray = $this->searchAsArray($data, $config);
        $entities = $errors = array();
        list($entities) = $this->convertDataArrayToDataModelArray($resultArray, true);
        return $entities;
    } // public function search()

    /**
     * Deletes all (or a group of records) from the database table.
     * 
     * @see     deleteRecords()
     * @param   string|array    $where      SQL where clause
     * @return  int     number of deleted records
     * @throws  Parsonline_Exception_ContextException
     */
    public function deleteAll($where=null)
    {
        $dbTable = $this->getDbTable();
        if (!$dbTable) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "failed to delete records from the database. no DbTable is assigned as data source to the data mapper"
            );
        }
        /*@var $dbTable Zend_Db_Table_Abstract*/
        return $dbTable->delete($where);
    } // public function deleteAll()
    
    /**
     * Deletes a range of records based on their identifier value, or other fields values.
     * If no DbTable is assigned to the data mapper, throws a Parsonline_Exception_ContextException.
     * 
     * NOTE: the field data and the id list are all treated as an 'OR' where clause.
     * 
     * @param   array   $idList         an array of identifier values for records to be searched for and deleted
     * @param   array   $fieldData      an associative array of field => data values to be searched for and deleted
     * @return  int     number of deleted records
     * @throws  Parsonline_Exception_ValueException, Parsonline_Exception_ContextException
     */
    public function deleteRecords(array $idList=array(), array $fieldData=array())
    {
        if (!is_array($idList)) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("id list should an array of record identifiers");
        }

        if (!is_array($fieldData)) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException(
                        "field data should be an associative array of field => values"
                    );
        }

        $dbTable = $this->getDbTable();
        if (!$dbTable) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "failed to delete records the database. no DbTable is assigned as data source to the data mapper"
            );
        }
        /*@var $dbTable Zend_Db_Table_Abstract*/
        
        /* @var $dbAdapter Zend_Db_Adapter_Abstract */
        $dbAdapter = $dbTable->getAdapter();

        /**
         * @uses    Zend_Db_Table
         */
        $tableColumns = $dbTable->info(Zend_Db_Table::COLS);
        $tablePrimaryColumn = current($this->getDbTablePrimaryKeyColumns());
        if (!$tablePrimaryColumn) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "failed to delete records from the database. failed to detect table primary key name"
            );
        }
        $tablePrimaryColumn = $dbAdapter->quoteIdentifier(strval($tablePrimaryColumn));

        /*
         * keep the where cluase as an array so adding new items to it would be more
         * sufficient than the immutable string data type
         * added the 0 to act as a false value, so other conditions that might
         * be added later, can be used with an "OR" in the beginning
         */
        $whereClause = array();
        $whereClause[] = "0";
        
        // select records based on their primary id value
        foreach( $idList as $id ) {
            $whereClause[] = $dbAdapter->quoteInto("OR {$tablePrimaryColumn} = ?", $id);
        }

        // select records based on their column values
        foreach($fieldData as $field => $value) {
            $field = strval($field);
            if ( !in_array($field, $tableColumns) ) continue;
            $field = $dbAdapter->quoteIdentifier($field);
            $whereClause[] = $dbAdapter->quoteInto("OR $field = ?", $value);
        }

        if ( count($whereClause) < 2 ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException(
                "failed to delete records. no vaild identifier or column-value pair is specifie"
            );
        }
        return $dbTable->delete( implode(' ', $whereClause) );
    } // public function deleteRecords()
    
    /**
     * Deletes the data model object from the underlying data source.
     * The data model MUST have a unique identifier, returned from
     * the method getDataModelUniqueIdentifier().
     * 
     * NOTE: If the data model unique identifier is achived in some other way
     * than a generic getter method or public property, then please re-implement
     * this method in that specific data mapper class.
     *
     * Returns the number of deleted resources. So 0 means nothing is deleted.
     *
     * @see     getDataModelUniqueIdentifier()
     * 
     * @param   object   $object
     * @return  int
     * @throws  Parsonline_Exception_ContextException, Parsonline_Exception_ObjectInspectionExcpetion
     */
    public function delete($object)
    {
        $id = current($this->getDataModelUniqueIdentifier($object, true));
        if ( empty($id) ) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "failed to delete data model. the object has no unique identifier value"
            );
        }
        return $this->deleteRecords(array($id));
    } // public function delete()

    /**
     * Saves the date model object to the backend DbTable data source.
     * Uses the getDataModelUniqueIdentifier() to detect the unique
     * identifier value of the data model. Iterates over all database
     * columns and tries to fetch their related data out of the data model.
     * If everything went well, saves the model into the data source.
     *
     * NOTE: This method depends on the exsitence of the first primary key field of
     * the database to select the record, mapped to the unique identity value
     * of the data model. So the DbTable MUST have a PK.
     * 
     * NOTE: Inorder for this method to work as expected, the protected
     * property of the class $_dbColsMappedToDataModel should be defined
     * properly.
     *
     * @see     getDataModelUniqueIdentifier()
     * @see     setDbColumnDataToDataModel()
     * @see     getDbColumnDataFromDataModel()
     * 
     * @param   object $model
     * @return  int     number of added storage units to the data source (update a previous record return 0)
     * @throws  Parsonline_Exception_ContextException, Parsonline_Exception_ObjectInspectionException
     */
    public function save($model)
    {
        $dbTable = $this->getDbTable();
        if (!$dbTable) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "failed to save the data model. no DbTable data source is assigned yet"
            );
        }
        /*@var $dbTable Zend_Db_Table_Abstract*/
        $dbAdapter = $dbTable->getAdapter();
        /*@var $dbAdapter Zend_Db_Adapter_Abstract*/
        
        $id = current($this->getDataModelUniqueIdentifier($model));
        $pk = current($this->getDbTablePrimaryKeyColumns());
        if (!$pk) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "failed to save the data model. DbTable has no primary key column",
                2
            );
        }
        
        $cols = $this->getDbTableColumns();
        $recordData = array();

        foreach ($cols as $col) {
            $recordData[$col] = current($this->getDbColumnDataFromDataModel($model, $col));
        }
        
        if ($id) {
            if (!is_numeric($id)) {
                $id = $dbAdapter->quote($id);
            }
            $recordData[$pk] = $id;
            $dbTable->update($recordData, "$pk = {$id}");
            return 0;
        } else {
            if (isset($recordData[$pk])) {
                $recordData[$pk] = null;
            }
            $newId = $dbTable->insert($recordData);
            $this->setDataModelUniqueIdentifier($model, $newId);
            return 1;
        }
    } // public function save()
}