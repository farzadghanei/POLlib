<?php
//Parsonline/Exception/ObjectInspectionException.php
/**
 * Defines the Parsonline_Exception_ObjectInspectionException class.
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
 * @package     Exception
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.1.0 2010-12-10
 */

/**
 * Parsonline_Exception_ObjectInspectionException
 *
 * Defines an exception that is used to report a failure in searching for a
 * data in an object.
 *
 * @uses    Parsonline_Exception
 */
require_once('Parsonline/Exception.php');
class Parsonline_Exception_ObjectInspectionException extends Parsonline_Exception
{
    /**
     * Array of names of the property/method being inspected
     * 
     * @var array
     */
    protected $_inspectionTargets = array();

    /**
     * the object that was inspected
     * 
     * @var object
     */
    protected $_inspectedObject = null;
    
    /**
     * Constructor.
     * 
     * Defines an exception that is used to report a failure in searching for a
     * data in an object.
     *
     * @param   string          $message
     * @param   int             $code
     * @param   Exception       $previous
     * @param   string|array    $search     the name of the property or method to search
     * @param   object          $object     the inspected object
     */
    public function __construct($message='', $code=0, Exception $previous=null, $search=null, $object=null)
    {
        parent::__construct($message, $code, $previous);
        if ($search) $this->addInspectionTarget($search);
        if ($object) $this->setInspectedObject($object);
    }
    
    /**
     * Returns the names of the inspected property/method
     * 
     * @return  array
     */
    public function getInspectionTargets()
    {
        return $this->_inspectionTargets;
    }
    
    /**
     * Adds a name (or names) to the inspected property/method list
     * 
     * @param   string|array    $target
     * @return  Parsonline_Exception_ObjectInspectionException
     */
    public function addInspectionTarget($target)
    {
        if (is_array($target)) {
            $this->_inspectionTargets = array_merge(
                                            $this->_inspectionTargets,
                                            $target
                                        );
        } else {
            array_push($this->_inspectionTargets, strval($target));
        }
        return $this;
    } // public function addInspectionTarget()

    /**
     * Returns the name of the inspected property/method
     *
     * @return  string
     */
    public function getInspectedObject()
    {
        return $this->_inspectedObject;
    }

    /**
     * Sets the name of the inspected property/method
     *
     * @param   string  $object
     * @return  Parsonline_Exception_ObjectInspectionException
     */
    public function setInspectedObject($object)
    {
        $this->_inspectedObject = $object;
        return $this;
    }
}
