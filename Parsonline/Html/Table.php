<?php
//Parsonline/Html/Table.php
/**
 * Defines the Parsonline_Html_Table.
 *
 * Parsonline
 * 
 * Copyright (c) 2010-2012 ParsOnline, Inc. (www.parsonline.com)
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
 * @copyright  Copyright (c) 2010-2012 ParsOnline, Inc. (www.parsonline.com)
 * @license    Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Html
 * @version     2.4.0 2012-08-04
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @author      Marzie Majidi <ma.majidi@parsonline.com>
 */

/**
 * Parsonline_Html_Table
 * 
 * Provides functionality to render an HTML table element.
 * Table supports pagination, sorting, specifying odd/even rows, add row counter,
 * client side resizing (via jQuery plugins), etc.
 */
class Parsonline_Html_Table
{
    const  FIRST = 'first';
    const  LAST = 'last' ;
    const ASCENDING = 'asc';
    const DESCENDING = 'desc';
    
    /**
     * A function (or object method) to call to translate strings
     * 
     * @var callable
     */
    protected $_translate = null;

    /**
     *
     * @var string
     */
    protected $_html = '';

    /**
     * Array of row declrations. each value is an associative array of 2 arrays
     * 
     * 1. rowCells:
     * an array of values for the row cells
     * 
     * 2. rowAttribs:
     * an associative array of HTML attributes for the row
     * 
     * @var array
     */
    protected $_rows = array();

    /**
     * Arra of columns
     * 
     * @var array
     */
    protected $_cols = array();

    /**
     * Array of column descriptions
     * 
     * @var array
     */
    protected $_colDescs = array();

    /**
     * Array of sortable cols
     * 
     * @var array
     */
    protected $_sortableCols = array();

    /**
     *
     * @var string
     */
    protected $_cssID = null;

    /**
     * array of string values for CSS classes
     * 
     * @var array
     */
    protected $_cssClassList = array();

    /**
     * associative array whose keys are the column names
     * and value are CSS classes assigned to column
     * 
     * @var array
     */
    protected $_colsCssClasses = array();

    /**
     * associative array
     * 
     * @var array
     */
    protected $_colsExtraAttributes = array();

    /**
     *
     * @var string
     */
    protected $_caption = null;

    /**
     *
     * @var string
     */
    protected $_sortField = null;

    /**
     *
     * @var string
     */
    protected $_sortOrder = null;

    /**
     *
     * @var bool
     */
    protected $_isSortable = false;

    /**
     *
     * @var string
     */
    protected $_sortParamName = null;

    /**
     *
     * @var string
     */
    protected $_orderParamName = null;
    
    /**
     * is used to create the parameters sent by table header links to the server when clicked
     *
     * @var mixed array
     */
    protected $_httpParams = array();
    
    /**
     * is used to send http get request when table header links are clicked.
     *
     * @var string
     */
    protected $_httpSortActionTarget;
    
    /**
     * a protected flag to indicate that if the table must be chunked into pages or not
     *
     * @var bool
     */
    protected $_isPageable = false;
    
    /**
     * The name of the HTTP request param that will be sent to server and has the value for the number of the page to show
     *
     * @var string
     */
    protected $_pageNumParamName;
    
    /**
     * the name of the HTTP request param that will be sent to server and has the value for the number of records per each page
     *
     * @var string
     */
    protected $_pageSizeParamName;
    
    /**
     * is used to send http get request when table page navigation links are clicked.
     *
     * @var string
     */
    protected $_httpPageActionTarget;
    
    /**
     * Total pages that the table contains.
     *
     * @var int
     */
    protected $_totalPages = 1;
    
    /**
     * The number of current page
     *
     * @var int
     */
    protected $_currentPage = 1;
    
    /**
     * number of rows that each page has
     *
     * @var int
     */
    protected $_rowsPerPage = 100;
    
    /**
     * Indicates if there must be an statusbar in the bottom of the table or not.
     * The statusbar shows information about the number of rows and pages.
     *
     * @var bool
     */
    protected $_showStatus = false;
    
    /**
     * Holds a string value as the status of table and will be shown in the statusbar.
     *
     * @var string
     */
    protected $_status = null;

    /**
     * Index number of page to show
     * 
     * @var int
     */
    protected $_pageNumber = null;

    /**
     * number of rows in each page
     * 
     * @var int
     */
    protected $_pageSize = null;
    
    /**
     * Client side JavaScript code to resize the table.
     * 
     * @var string
     */
    protected $_tableResizerJavaScript = null;
    
    /**
     * Constructor.
     *
     * @param   array                   $cols           associative array of column names and column texts
     * @param   callable|Zend_Translate $trans          [optional] a callable or an object with translate() method or Zend_Translate object
     */
    public function __construct(array $cols=array(), $trans=null)
    {
        if ($trans) {
            $this->setTranslator($trans);
        }
        foreach ($cols as $key => $value) {
            if (is_int($key)) {
                array_push($this->_cols, $value);
                array_push($this->_colDescs, $value);
            } else {
                array_push($this->_cols, $key);
                array_push($this->_colDescs, $value);
            }
        }
    } // public function __construct()
    
    /**
     * Returns the translation callable that is used to translate strings
     * for the table.
     * 
     * @return  callable
     */
    public function getTranslator()
    {
        return $this->_translate;
    }
    
    /**
     * Sets the translator that translates the table strings.
     * It should be callable (function name or an array of (object, method_name).
     * 
     * if the translator is an object and has a method named translate()
     * it would be accepted just to send the object and not the callable declaration.
     * 
     * Note: For the special case (and backwad compatibility) if the translator
     * is an object, with __call() implemented, the object is supposed to have
     * an interface like Zend_Translate from Zend Framework and so the translation
     * callable would be assumed to be the method _() of the object.
     * 
     * the callable should accept a string and return the translated one.
     * 
     * @param   callable|object     $trans
     * @return  Parsonline_Html_Table
     * @throws  Parsonline_Exception_ValueException
     */
    public function setTranslator($trans)
    {
        if ( is_object($trans) ) {
            if (method_exists($trans, "translate")) {
                $trans = array($trans, "translate");
            } elseif ( method_exists($trans, "__call") ) {
                $trans = array($trans, "_");
            }
        }
        if ( !is_callable($trans, false) ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException("translator should be a valid callable");
        }
        
        $this->_translate = $trans;
        return $this;
    } // public function setTranslator()
    
    /**
     * Returns columns array of table.
     *
     * @return  array
     */
    public function getCols()
    {
        return $this->_cols;
    }
    /**
     * Returns number of columns of table.
     *
     * @return int
     */
    public function getColsNum()
    {
        return count($this->_cols);
    }
    
    /**
     * Returns the array of description of table clumns.
     *
     * @return  array
     */
    public function getColDescs()
    {
        return $this->_colDescs;
    }
    /**
     * Returns rows array of table.
     *
     * @return  array
     */
    public function getRows()
    {
        return $this->_rows;
    }
    
    /**
     * Returns number of rows of table.
     *
     * @return int
     */
    public function getRowsNum()
    {
        return count($this->_rows);
    }
    
    /**
     * Returns the name of the CSS Class assigned to the table
     *
     * @return string
     */
    public function getCssID()
    {
        return $this->_cssID;
    }
    
    /**
     * Sets the CSS ID name of the table and ID for the wrapper DIV.
     *
     * @param   string
     * @return  Parsonline_Html_Table
     */
    public function setCssID($id)
    {
        $this->_cssID = $id;
        return $this;
    }
    
    /**
     * Adds a CSS class to the table
     *
     * @param string
     * @return Parsonline_Html_Table
     */
    public function addCssClass($class)
    {
        array_push($this->_cssClassList, (string)$class);
        return $this;
    }
    
    /**
     * returns the list of CSS class names assigned to the table
     *
     * @return  array
     */
    public function getCssClassList()
    {
        return $this->_cssClassList;
    }

    /**
     * Returns index number of page to show.
     *
     * @return int
     */
    public function getPageNumber()
    {
        return $this->_pageNumber;
    }
    
    /**
     * Returns number of rows in each page
     *
     * @return int
     */
    public function getPageSize()
    {
        return $this->_pageSize;
    }

    /**
     * Returns the CSS class names of table columns, if any set. if no param is
     * provided, all columns is default.
     *
     * @param   array   $cols   indexed array of column names
     * @return  array   associative array of column names => css classes
     */
    public function getColsCssClass(array $cols=null)
    {
        if ( empty($cols) ) {
            return $this->_colsCssClasses;
        }
        $results = array();
        foreach ($cols as $col) {
            if ( in_array($col,$this->_cols) ) {
                $results[$col] = $this->_colsCssClasses[$col];
            }
        } // foreach
        return $results;
    }
    
    /**
     * Sets the CSS class of columns of table
     *
     * @param   array   $cols       associative array of column names => CSS classes
     * @return  Parsonline_Html_Table
     */
    public function setColsCssClass(array $cols=null)
    {
        foreach ($cols as $col => $css ) {
            $col = strval($col);
            $css = strval($css);
            if ( in_array($col,$this->_cols) ) {
                $this->_colsCssClasses[$col] = $css;
            }
        } // foreach
        return $this;
    }

    /**
     * Returns the extra attributes of table columns, if any set. if no param is provided, all columns is default.
     *
     * @param   array   $cols   indexed array of column names
     * @return  array   associative array of column names => extra attributes
     */
    public function getColsExtraAtrributes(array $cols=null)
    {
        if ( empty($cols) ) {
            return $this->_colsExtraAttributes;
        }
        $results = array();
        foreach ($cols as $col) {
            if ( in_array($col,$this->_cols) ) {
                $results[$col] = $this->_colsExtraAttributes[$col];
            }
        } // foreach
        return $results;
    }
    
    /**
     * Sets extra HTML attibutes for columns of table
     *
     * @param   array   $cols   associative array of [string] column names => [string] html attributes
     * @return  arsonline_Html_Table
     */
    public function setColsExtraAttributes(array $cols=null)
    {
        foreach ($cols as $col => $attr ) {
            $col = strval($col);
            $attr = strval($attr);
            if ( in_array($col,$this->_cols) ) {
                $this->_colsExtraAttributes[$col] = $attr;
            }
        } // foreach
        return $this;
    }

    /**
     * Returns the caption of the table
     *
     * @return string
     */
    public function getCaption()
    {
        return $this->_caption;
    }
    
    /**
     * Sets the caption of table
     *
     * @param string
     * @return Parsonline_Html_Table
     */
    public function setCaption($newCaption)
    {
        $this->_caption = (string) $newCaption;
        return $this;
    }
    
    /**
     * Returns the name of the HTTP request param that will carry the name of sorting field
     *
     * @return string
     */
    public function getSortParamName()
    {
        return $this->_sortParamName;
    }
    
    /**
     * Returns the name of the HTTP request param that will carry the name of sorting order
     *
     * @return string
     */
    public function getOrderParamName()
    {
        return $this->_orderParamName;
    }

    /**
     * Returns the name of field that is used to sort table data
     * 
     * @return string|null      if not sorted
     */
    public function getSortField()
    {
        return $this->_sortField;
    }

    /**
     * Returns the sorting order of the table data
     * 
     * @return string|null      if not sorted
     */
    public function getSortOrder()
    {
        return $this->_sortOrder;
    }

    /**
     * Returns the name of the HTTP request param that will carry the name of
     * target page number.
     *
     * @return string
     */
    public function getPageNumParamName()
    {
        return $this->_pageNumParamName;
    }
    
    /**
     * Returns the name of the HTTP request param that will carry the name of
     * page size.
     *
     * @return string
     */
    public function getPageSizeParamName()
    {
        return $this->_pageSizeParamName;
    }
    
    /**
     * Returns total number of pages the table contains.
     *
     * @return int
     */
    public function getTotalPages()
    {
        return $this->_totalPages;
    }
    
    /**
     * Returns the number of the currect page of the table.
     *
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->_currentPage;
    }
    
    /**
     * returns the status text of table
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->_status;
    }
    
    /**
     * sets the status of table to a new value
     *
     * @param   string  $status
     * @return  Parsonline_Html_Table
     */
    public function setStatus($status)
    {
        $this->_status = (string) $status;
        return $this;
    }
    
    /**
     * Adds a value at the end of the status of the table
     *
     * @param   string  $status
     * @return  Parsonline_Html_Table
     */
    public function addStatus($status)
    {
        $this->_status .= $status;
        return $this;
    }
    
    /**
     * Sets the table sortable property to ture. this flag is used to indicate if the table headers should be links or not.
     * the href of links send the column name as the value of a parameter in a get method. the name of the parameter is
     * determined by the parameter of this method.
     *
     * @param array     $sortableCols       an array of cols names that are going to be sortable
     * @param string    $sortParamName      the name of the sort parameter to be sent to server by clickig on table header links.
     * @param string    $orderParamName     the name of the order parameter to be sent to server by clickig on table header links.
     * @param string    $action             name of the file on server that table header links will target to.
     * @param array     $params             an associative array that has all the params and values to be sent to the action file via this link
     *
     * @return Parsonline_Html_Table
     * @throws Parsonline_Exception_ValueException on invalid action
     */
    public function setSortable(array $sortableCols=array(), $sortParamName='sort', $orderParamName='order', $action='', array $params=array())
    {
        $this->_isSortable = true;
        foreach ($sortableCols as $col) {
            if (in_array($col, $this->_cols)) {
                array_push($this->_sortableCols, $col);
            }
        }
        $this->_sortParamName = str_replace('+', ' ', $sortParamName);
        $this->_orderParamName = str_replace('+', ' ', $orderParamName);
        if (is_string($action) && $action !== '') {
            $this->_httpSortActionTarget = $action;
        } elseif ($this->_httpPageActionTarget !== '') {
            $this->_httpSortActionTarget = $this->_httpPageActionTarget;
        } else {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException("action should not be empty");
        }
        if (isset($params) && is_array($params) && ! empty($params)) {
            $this->_httpParams = array_merge($this->_httpParams, $params);
        }
        return $this;
    } // public function setSortable()
    
    /**
     * Sets the table pageable property to ture. this flag is used to indicate if the data of table should be chunked
     * into pages or not. some links will also be generated to enable navigation through pages. the href of these links
     * send the target page name, and the number of records in each page, as the value of a parameter in a get method.
     * the name of these parameter are determined by the parameters of this method.
     *
     * @param   string      $pageNumParam       the name of the parameter to send the server the number of target page
     * @param   string      $pageSizeParam      the name of the parameter to send the server the number of records per page
     * @param   string      $action             name of the file on server that table navigation links will target to.
     * @param   array       $params             an associative array that has all the params and values to be sent to the action file via this link
     * @param   array       $params             an associative array that has all the params and values to be sent to the action file via this link
     * @param   array       $pageConf           an associative array of settings and values for the pagination. is used to fake paginated tables
     * 
     * @return  Parsonline_Html_Table           self reference to Table object
     * @throws Parsonline_Exception_ValueException   on invalid action or missed page config array
     */
    public function setPageable($pageNumParam='page', $pageSizeParam='rpp', $action='', $params=array(), $pageConf=null)
    {
        $this->_isPageable = true;
        $this->_pageNumParamName = $pageNumParam;
        $this->_pageSizeParamName = $pageSizeParam;
        if (is_string($action) && $action !== '') {
            $this->_httpPageActionTarget = $action;
        } elseif ($this->_httpSortActionTarget !== '') {
            $this->_httpPageActionTarget = $this->_httpSortActionTarget;
        } else {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException("action should not be empty");
        }
        if (isset($params) && is_array($params) && ! empty($params)) {
            $this->_httpParams = array_merge($this->_httpParams, $params);
        }
        // just show the table paged
        if ( !empty($pageConf) && is_array($pageConf) ) {
            if ( !isset($pageConf['totalPages']) || !is_int($pageConf['totalPages']) ) {
                /**
                 * @uses    Parsonline_Exception_ValueException
                 */
                require_once("Parsonline/Exception/ValueException.php");
                throw new Parsonline_Exception_ValueException("total page config is invalid or not specified");
            }
            $totalPages = $pageConf['totalPages'];
            if (! isset($pageConf['totalRows']) || !is_int($pageConf['totalRows']) ) {
                /**
                 * @uses    Parsonline_Exception_ValueException
                 */
                require_once("Parsonline/Exception/ValueException.php");
                throw new Parsonline_Exception_ValueException("total rows page config is invalid or not specified");
            }
            $totalRows = $pageConf['totalRows'];
            if (! isset($pageConf['rowsPerPage']) || !is_int($pageConf['rowsPerPage']) ) {
                /**
                 * @uses    Parsonline_Exception_ValueException
                 */
                require_once("Parsonline/Exception/ValueException.php");
                throw new Parsonline_Exception_ValueException("rows per page config is invalid or not specified");
            }
            $rowsPerPage = $pageConf['rowsPerPage'];
            if ( $rowsPerPage < 1 || $rowsPerPage > $totalRows) {
                $rowsPerPage = $totalRows;
            }
        }
        return $this;
    } // public function setPageable()
    
    /**
     * Splits the table rows into chunks and changes the table rows array to the
     * desired chunk, indicated by a number as parameter.
     * the number of rows in each chunk is specified by the second parameter.
     *
     * @param int       $pageNumber     index number of page to show
     * @param int       $recordsPerPage number of rows in each page
     * @return array     the current page of rows or false
     */
    public function doPage($pageNumber=1, $recordsPerPage=0)
    {
        if ($this->getRowsNum() > 1) { // table must not be too short
            if ( !isset($pageNumber) || !is_int($pageNumber) || $pageNumber < 1) { // pageNumber must an integer more than 1
                $pageNumber = 1;
            }
            if ( !isset($recordsPerPage) || !is_int($recordsPerPage) || $recordsPerPage < 1) { // recordsPerPage must be an integer at least 1
                $recordsPerPage = $this->getRowsNum();
            }
            $totalPages = intval( ceil($this->getRowsNum() / $recordsPerPage) );
            if ($pageNumber > $totalPages) { // pageNumber can not be more than total pages, if so use the last page
                $pageNumber = $totalPages;
            }
            $this->_totalPages = $totalPages;
            $this->_currentPage = $pageNumber;
            $this->_rowsPerPage = $recordsPerPage;
            $pages = array_chunk($this->_rows, $recordsPerPage);
            return $this->_rows = $pages[$pageNumber - 1]; // array index starts from zero
        }
        return $this->_rows;
    } // public function doPage()

    /**
     * Fakes the pagination. this is useful for when data has been pageinated
     * before inserted into the table but you want to use the pagination of
     * the table to fetch other pages of data.
     * 
     * @param int   $totalRows      number of total rows to be used as number of table total rows
     * @param int   $pageNumber     number of current page
     * @param int   $recordsPerPage number of rows per page
     * @return Parsonline_Html_Table
     * @throws  Parsonline_Exception_ValueException on total rows less than 1
     */
    public function doFakedPage($totalRows, $pageNumber=1, $recordsPerPage=0)
    {
        if ( 1 > $totalRows ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException("total rows argument should be larger than 1");
        }
        $pageNumber = intval($pageNumber);
        if ( $pageNumber < 1) { // pageNumber must an integer more than 1
            $pageNumber = 1;
        }
        $recordsPerPage = intval($recordsPerPage);
        if ( $recordsPerPage < 1) { // recordsPerPage must be an integer at least 1
            $recordsPerPage = $totalRows;
        }
        $totalPages = intval( ceil($totalRows / $recordsPerPage) );
        if ($pageNumber > $totalPages) { // pageNumber can not be more than total pages, if so use the last page
            $pageNumber = $totalPages;
        }
        $this->_totalPages = $totalPages;
        $this->_currentPage = $pageNumber;
        $this->_rowsPerPage = $recordsPerPage;
        return $this;
    } // public function doFakedPage()
    
    /**
     * Parses an associative array of parameters, and finds out current table
     * state (sort and page data) from the parameters. validates values in the parameters and
     * makes sure values are reasonable.
     * Returns an array, of arrays in this format:
     *          [page] (Array):
     *              [number] (int):     current page number, default is 1
     *              [size] (int):       page size, default is maxPageSize
     *          [sort] (Array):
     *              [name] (string):    sorting field name, false if no sort field is specified
     *              [order] (string):   sorting order (ascending, descending). default is ASC
     *
     * 
     * @param   array   $params         associative array of parameters
     * @param   int     $maxPageSize    maximum size of rows in each page, default is 20
     * @return  array   associative array
     * @throws  Parsonline_Exception_ValueException on empty params or max page size less than 1
     * @throws  Parsonline_Exception_ContextException on table object has no page num/size param name assigned yet
     */
    public function getTableStateFromParams(array $params, $maxPageSize=20)
    {
        $maxPageSize = intval($maxPageSize);
        if (!$params) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException("params should be a none-empty associative array");
        }
        if ( $maxPageSize < 1 ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException("max page size should be larger than 1");
        }
        
        $pageSizeParamName = $this->getPageSizeParamName();
        $pageNumParamName = $this->getPageNumParamName();
        if (!$pageNumParamName || !$pageSizeParamName) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException("the table has no page size/num param name set");
        }
        
        // defaults
        $pageSize = $maxPageSize;
        $page = 1;
        $sortField = false;
        $sortOrder = self::ASCENDING;

        $totalRows = $this->getRowsNum();
        $sortParamName = $this->getSortParamName();
        $sortOrderParamName = $this->getOrderParamName();

        if ( array_key_exists($pageSizeParamName, $params) ) {
            $pageSize = intval( $params[ $this->getPageSizeParamName() ] );
            if ($pageSize < 1 || $pageSize > $maxPageSize) {
                $pageSize = $maxPageSize;
            }
        }

        if ( array_key_exists($pageNumParamName,$params) ) {
            $page = intval( $params[ $pageNumParamName ] );
            if ($page < 1 || $page > ceil($totalRows / $pageSize) ) {
                $page = 1;
            }
        }

        if ( array_key_exists($sortParamName, $params) ) {
            $sortField = strval($params[ $this->getSortParamName() ]);
            if ( !in_array($sortField, $this->getCols()) ) $sortField = false;
            $sortOrder = array_key_exists($sortOrderParamName, $params) ? $params[ $sortOrderParamName ] : self::ASCENDING;
        }
        $this->_pageNumber = $page;
        $this->_pageSize = $pageSize;
        return
        array(
            'page' =>   array(
                            'number'    => $page,
                            'size'      => $pageSize
                        ),
            'sort' =>   array(
                            'field'    => $sortField,
                            'order'    => $sortOrder
                        )
        );
    } // public function getTableStateFromParams()

    /**
     * Adds a row to the table.
     * If the columns count in the new row is less than the number of the columns of
     * the tables, adds empty values for the rest of columns to the row.
     * If the columns in the new row is larger than the table columns, throws
     * an exception.
     * 
     * Accepts an optional attributes paramter for the row.
     *
     * @param   array   $row         new row data
     * @param   array   $rowAttrs   associative array of HTML attributes for the row
     * @return  Parsonline_Html_Table
     * 
     */
    public function addRow(array $row, array $rowAttrs = array())
    {
        $colsNum = $this->getColsNum();
        $rowColsNum = count($row);
        if ($rowColsNum > $colsNum) {
            $diff = $rowColsNum - $colsNum;
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException("new row has {$diff} columns more than the table columns");
        }
        
        $_row = array();
        $colIndex = 0;
        
        foreach ($this->_cols as $currentColName) {
            if ($rowColsNum < $colIndex) {
                $cellValue = '';
            } else {
                $cellValue = $row[$colIndex]; // puting the row cells in their appropriate places.
                if ($cellValue === null) { // null values are converted to empty strings
                    $cellValue = '';
                }
            }
            $_row[$currentColName] = $cellValue;
            $colIndex++;
        }
        $_row['rowCells'] = $_row;
        $_row['rowAttrs'] = $rowAttrs;
        array_push($this->_rows, $_row);
        return $this;
    } // public function addRow()
    
    /**
     * Adds a column to the table. if the parameter is not a string,
     * it will convert it to a string. If a column with the same name exists
     * returns false.
     * If table has rows throws a context excpetion.
     *
     * @param string    $col     name of the new column. if not a string, it will be converted to a string.
     * @param string    $desc description of the new column. if not a string, it will be converted to a string.
     * @return bool
     * @throws  Parsonline_Exception_Context_MethodCallException on table has rows
     */
    public function addCol($col, $desc='')
    {
        if ($this->_rows) {
            /**
             * @uses    Parsonline_Exception_Context_MethodCallException
             */
            require_once('Parsonline/Exception/Context/MethodCallException.php');
            throw new Parsonline_Exception_Context_MethodCallException("can not add columns to table with existing rows");
        }
        $col = (string)$col;   
        if (!in_array($col, $this->_cols)) {
            array_push($this->_cols, $col);
            array_push($this->_colDescs, (string)$desc);
            return true;
        }
        return false;
    } // public function addCol()
    
    /**
     * Translates a message using the internal translator.
     * If no translator were avialable, or translator failes,
     * returns the message itself.
     * 
     * @param   string  $message
     * @return  string
     */
    protected function _translate($message)
    {
        if ($this->_translate) {
            $translated = call_user_func($this->_translate, $message);
            if ($translated === false) return $message;
            return (string) $translated;
        }
        return $message;
    }
    
    /**
     * Shows a status bar at the end of the table.
     * 
     * @param bool  $showDefaultInfo    if set to true, table information will be used as default status
     * @return Parsonline_Html_Table
     */
    public function showStatusbar($showDefaultInfo=false)
    {
        $this->_showStatus = true;
        if ($showDefaultInfo) {
            $this->_status = sprintf($this->_translate('total rows: %d'), $this->getRowsNum());
        }
        return $this;
    }
    
    /**
     * Hides the status bar from the end of the table.
     * 
     * @return Parsonline_Html_Table
     */
    public function hideStatusbar()
    {
        $this->_showStatus = false;
        return $this;
    }
    
    /**
     * This function is used to indicate which row must be sorted before another
     * row of table, based on their value for a specific filed name.
     * The field name that is used to ordor rows, is kept in a protected field
     * named $sorfField. this is used for ASCENDING sorts.
     *
     * @param   array   $row1
     * @param   array   $row2
     * @return int
     * @throws  Parsonline_Exception_ValueException on rows does not have sortfiled key
     */
    public function compareRowsBySortFieldASC(array $row1, array $row2)
    {
        if ( !$this->_sortField ) return 0;
        
        if ( isset($row1['rowCells']) && is_array($row1['rowCells']) ) $row1 = $row1['rowCells'];
        if ( isset($row2['rowCells']) && is_array($row2['rowCells']) ) $row2 = $row2['rowCells'];
        
        if ( !array_key_exists($this->_sortField, $row1) || !array_key_exists($this->_sortField, $row2) ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("row does not have the sort field key");
        }
        return strnatcasecmp($row1[$this->_sortField], $row2[$this->_sortField]);
    }
    
    /**
     * This function is used to indicate which row must be sorted before another
     * row of table, based on their value for a specific filed name.
     * The field name that is used to ordor rows, is kept in a protected field
     * named $sorfField. this is used for DESCENDING sorts.
     *
     * @param   array   $row1
     * @param   array   $row2
     * @return int
     * @throws  Parsonline_Exception_ValueException on rows does not have sortfiled key
     */
    public function compareRowsBySortFieldDESC(array $row1, array $row2)
    {
        if (!$this->_sortField) return 0;
        
        if ( isset($row1['rowCells']) && is_array($row1['rowCells']) ) $row1 = $row1['rowCells'];
        if ( isset($row2['rowCells']) && is_array($row2['rowCells']) ) $row2 = $row2['rowCells'];
        
        if ( !array_key_exists($this->_sortField, $row1) || !array_key_exists($this->_sortField, $row2) ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("row does not have the sort field key");
        }
        return -1 * strnatcasecmp($row1[$this->_sortField], $row2[$this->_sortField]);
    } // public function compareRowsBySortFieldDESC()
    
    /**
     * Sorts rows of the table, based on their values for a specific filed.
     * Supports ascending or descending sort.
     * The sorting is case insensitive and natural ordering (10 is sorted after 9).
     *
     * @param string        $field
     * @param string        default is ASC for ascending sort. can enter DESC for descending sort.
     * @return bool
     * @throws Parsonline_Exception_ValueException on invalid field name
     */
    public function sortRowsByField($field, $order=self::ASCENDING)
    {
        if (!in_array($field, $this->_cols)) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException(
                "specified sort file '{$field}' is not an existing column name"
            );
        }
        $order = strtolower($order);
        if ($order === self::DESCENDING) {
            $func = 'compareRowsBySortFieldDESC';
        } else {
            $order = self::ASCENDING;
            $func = 'compareRowsBySortFieldASC';
        }
        $this->_sortOrder = $order;
        $this->_sortField = $field;
        return usort($this->_rows, array($this , $func));
    } // public function sortRowsByField()
    
    /**
     * Adds a column to the table, with an auto incrementing value to count rows.
     * this counter should start after the table is sorted, and before it is being paginated.
     * 
     * @param  string  $columnName  [optional] the description of the new column
     * @param  string  $position    [optional] where this column should be added, either as first column or last column
     * @param   int     $start      [optional] start counting from
     * @param   int     $step       [optional] the difference between each row counter to the next one     
     * @return  bool
     * @throws Parsonline_Exception_ValueException on invalid position
     */
    public function addRowCounter($columnName='row', $position=self::FIRST, $start=1, $step=1)
    {   
        $position = strtolower($position);
        if ($position !=self::LAST && $position != self::FIRST) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException(
                "position should either be first or last"
            );
        }

        $rowCounter = intval($start);
        $step = intval($step);
        
        if ( $position == self::LAST ) {
            array_push($this->_cols, $columnName);
            array_push($this->_colDescs, $columnName);
        } else {
            array_unshift($this->_cols, $columnName);
            array_unshift($this->_colDescs, $columnName);
        }
        
        $rows = $this->getRows();
        $this->_rows = array();
        
        foreach ($rows as $row) {
            if ($position == self::LAST) {
                array_push($row['rowCells'], $rowCounter);
            } else {
                array_unshift($row['rowCells'], $rowCounter);
            }
            array_push($this->_rows, $row);
            $rowCounter += $step;
        }
        return $this;
    } // public function addRowCounter()

    /**
     * Adds a jQuery clientside script plugin with capability to convert the table
     * into a resizable table.
     *
     * @param   string  $scriptsPath    path of table resizer scripts
     * @param   array   $scriptNames    array of table resizer scripts name
     * @param   string  $opts           $options for the jQuery tableresizer plugin
     * @return  Parsonline_Html_Table
     */
    public function setScriptTableResizer($scriptsPath, array $scriptNames, $opts='')
    {
       if ( !$scriptsPath ) {
           /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("script path should not be empty");
       }
       
       if (!$scriptNames) {
           /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("script names should not be empty");
       }
     
      if( substr($scriptsPath, -1, 1) != '/') {
           $scriptsPath .= '/';
       }
       
       $buff = array();
       
       foreach ($scriptNames as $scriptName) {
             $buff[] = sprintf('<script language="javascript" type="text/javascript" src="%s"></script>', $scriptsPath . $scriptName);
       }
       
       $selector = 'table';
       if ($this->_cssID) $selector .= '#' . $this->_cssID;
       
       $buff[] = sprintf(
        '
<script language="javascript" type="text/javascript">
    if ($) {
        $(function()
        {
            $(\'%s\').tableresizer({%s});
        });
    }
</script>
       ', $selector, $opts);
        $this->_tableResizerJavaScript = implode('', $buff);
        return $this;
    } // public function setScriptTableResizer()
    
    /**
     * Generates an HTTP location string with parameters, using the
     * $_httpParams and $_sortParamName properties of the table.
     * Is used to generate link addresses for table filed headers,
     * inorder to make them clickable to sort the table.
     *
     * @param string    $fieldName      the name of the field
     * @return string
     * @access  protected
     */
    protected function _genFieldsLinkLocation($fieldName)
    {
        $location = $this->_httpSortActionTarget . '?';
        // build normal location with other params than sorting and ordering
        foreach ($this->_httpParams as $param => $value) {
            if (($param !== $this->_sortParamName) && ($param !== $this->_orderParamName)) {
                $location .= sprintf('&amp;%s=%s', urlencode($param), urlencode($value));
            }
        }
        $location .= sprintf('&amp;%s=%s', urlencode($this->_sortParamName), urlencode($fieldName));
        // order management
        $orderValue = 'asc';
        if (strcmp($fieldName, $this->_sortField) == 0) { //  change the order if user clicks on the sort field again
            if (array_key_exists($this->_orderParamName, $this->_httpParams) && (strcasecmp($this->_httpParams[$this->_orderParamName], 'asc') == 0)) {
                $orderValue = 'desc';
            }
        }
        $location .= sprintf('&amp;%s=%s', urlencode($this->_orderParamName), $orderValue);
        return $location;
    } // protected function genFieldsLinkLocation()
    
    /**
     * Generates an HTTP location string with parameters, using the $httpParams, $pageSizeParamName and
     * $pageNumParamName properties of the table.
     * Used to generate link addresses for table page navigation links
     *
     * @param   string  $targetPage     the number of the page that must be returned when the link is clicked
     * @return  string
     */
    protected function _genPageNavLinkLocation($targetPage)
    {
        // general link location
        $location = $this->_httpPageActionTarget . '?';
        foreach ($this->_httpParams as $param => $value) {
            if ( ($param == $this->_pageNumParamName) || ($param == $this->_pageSizeParamName) ) continue;
            $location .= '&amp;' . urlencode($param) . '=' . urlencode($value);
        }
        // adding page
        $location .= '&amp;' . urlencode($this->_pageSizeParamName) . '=' . $this->_rowsPerPage;
        $location .= '&amp;' . urlencode($this->_pageNumParamName) . '=' . urlencode($targetPage);
        return $location;
    } // protected function _genPageNavLinkLocation()
    
    /**
     * Generates the HTML code for page navigation links.
     * Accepts params, that are used as links to go to next or previous pages.
     * 
     *
     * @param   string      $nexPageChar    [optional] default '>'
     * @param   string      $prevPageChar   [optional] default '<'
     * @param   string      $nexPageIm      [optional] use an image URL for next page link
     * @param   string      $prevPageIm     [optional] use an image URL for prev page link
     * @return string
     */
    protected function _getPageNavigationLinks($nextPageChar='&gt;', $prevPageChar='&lt;', $nextPageImg=null, $prevPageImg=null)
    {
        if (! $this->_isPageable || $this->_totalPages < 2) {
            return '';
        }
        $buff = array();
        $buff[] = "\n" . str_repeat("\t", 4) . "<!-- navigation links for table begin -->";
        $buff[] = "\n" . str_repeat("\t", 4) . '<div class="tableNavigationPanel">';
        $buff[] = "\n" . str_repeat("\t", 5) . '<table>' . "\n" . str_repeat("\t", 6) . '<tr>' . "\n";
        
        // --------------- previous page link cell
        $buff[] = str_repeat("\t", 7) . '<td class="previousPage">&nbsp;' . "\n";
        
        if ($this->_currentPage > 1) {
            $href = $this->_genPageNavLinkLocation($this->_currentPage - 1);
            if ($href) {
                $buff[] = str_repeat("\t", 8) . sprintf('<a href="%s" title="%s">', $href, $this->_translate('previous page'));
                 if($prevPageImg) {
                    $buff[] = "\n" . str_repeat("\t", 9) . sprintf('<img src="%s">',$prevPageImg) . "\n";
                 } else {
                    $buff[] = "\n" . str_repeat("\t", 9) . $prevPageChar . "\n";
                 }
                $buff[] = str_repeat("\t", 8) . "</a>\n";
            }
        }
        $buff[] = str_repeat("\t", 7) . "</td>\n";
        
        // ------------- pages list links cell
        $buff[] = str_repeat("\t", 7) . '<td class="pageList">' . "\n";
        for ($counter = 1; $counter <= $this->_totalPages; $counter ++) {
            if ($counter !== $this->_currentPage) { // other pages than current
                $href = $this->_genPageNavLinkLocation($counter);
            } else { // current page is not a link
                $href = '';
            }
            if ($href) {
                $linkTitle = sprintf($this->_translate('page %d'), $counter);
                $buff[] = str_repeat("\t", 8) . sprintf('<a href="%s" title="%s">', $href, $linkTitle);
                $buff[] = "\n" . str_repeat("\t", 9) . $counter . "\n";
                $buff[] = str_repeat("\t", 8) . "</a>\n";
            } else { // page doesn not have a link
                $buff[] = str_repeat("\t", 8) . '<span class="currentPage">';
                $buff[] = $counter;
                $buff[] = "</span>\n";
            }
            if ($counter < $this->_totalPages) {
                $buff[] = str_repeat("\t", 8) . " | \n";
            }
        }
        $buff[] = str_repeat("\t", 7) . "</td>\n";
        // ------------next page link cell
        $buff[] = str_repeat("\t", 7) . '<td class="nextPage">&nbsp;' . "\n";
        if ($this->_currentPage < $this->_totalPages) {
            $href = $this->_genPageNavLinkLocation($this->_currentPage + 1);
            if ($href) {
                $buff[] = str_repeat("\t", 8) . sprintf('<a href="%s" title="%s">', $href, $this->_translate('next page'));
               if($nextPageImg) {
                    $buff[] = "\n" . str_repeat("\t", 9) . sprintf('<img src="%s">',$nextPageImg) . "\n";
               } else {
                    $buff[] = "\n" . str_repeat("\t", 9) . $nextPageChar . "\n";
               }   
               $buff[] = str_repeat("\t", 8) . "</a>\n";
            }
        }
        
        $buff[] = str_repeat("\t", 7) . "</td>\n";
        $buff[] = "\n" . str_repeat("\t", 6) . '</tr>';
        $buff[] = "\n" . str_repeat("\t", 5) . '</table>';
        $buff[] = "\n" . str_repeat("\t", 4) . '</div>';
        $buff[] = "\n" . str_repeat("\t", 4) . "<!-- navigation links for table end -->\n";
        return implode('', $buff);
    } // protected function _getPageNavigationLinks()
    
    /**
     * Generates the HTML code for the table and returns it.
     *
     * @param   bool    $flush          [optional]if true the HTML code will be printed.
     * @param   string  $nextNavImg     [optional] URL of an image to navigate to next page
     * @param   string  $prevNavImg     [optional] URL of an image to navigate to previous page
     * @return  string
     */
    public function render($flush=false, $nextNavImg=null, $prevNavImg=null)
    {
        $rowClass = "odd";
        $buff = array();
        $buff[] = sprintf("\n<!-- Table [%s]  begins -->\n", $this->_cssID);
        $buff[] = sprintf("<div id=\"%s\">\n", $this->_cssID);
        $buff[] = sprintf("<table class=\"%s\" id=\"%sTable\">\n", implode(' ', $this->_cssClassList), $this->_cssID);
        
        // generating script for table resizer
        if ( !empty($this->_tableResizerJavaScript) ) {
           $buff[] = $this->_tableResizerJavaScript;
        }
        
        // generating header
        if (!empty($this->_caption)) {
            $buff[] = "<thead>\n";
            $buff[] = "\t<tr>\n";
            $buff[] = sprintf("\t\t<th colspan=\"%d\">", $this->getColsNum()) . $this->_caption;
            if ($this->_isPageable) {
                $pageNumberString = sprintf($this->_translate('page %d'), $this->_currentPage);
                $buff[] = sprintf(' - %s/%d', $pageNumberString, $this->_totalPages);
            }
            $buff[] = "</th>\n\t</tr>\n</thead>\n";
        }
        
        // generating field titles
        $buff[] = "<tbody>\n\t<tr>\n";
        
        foreach ($this->_cols as $currentColKey => $currentCol) {
            $columnClass = array_key_exists($currentCol,$this->_colsCssClasses) ? ($this->_colsCssClasses[$currentCol] . ' ') : '';
            $columnAttributes = array_key_exists($currentCol,$this->_colsExtraAttributes) ? (' ' . $this->_colsExtraAttributes[$currentCol] . ' ') : '';
            if ( !empty($this->_sortField) && $currentCol == $this->_sortField) {
                $orderCssClass = strtoupper($this->_sortOrder);
                $buff[] = "\t\t<th class=\"{$columnClass}fieldTitleSorted{$orderCssClass}\" {$columnAttributes}><span class=\"iconSort\"></span>";
                unset($fieldCssClass);
            } else {
                $buff[] = "\t\t<th class=\"{$columnClass}fieldTitle\" {$columnAttributes}>";
            }
            
            $currentColDesc = $this->_colDescs[$currentColKey];
            if ( $this->_isSortable && in_array($currentCol, $this->_sortableCols) ) {
                $buff[] = "\n\t\t\t<a href=\"" . $this->_genFieldsLinkLocation($currentCol) . '" ';
                $buff[] = 'title="' . sprintf($this->_translate('sort table by %s'), $currentColDesc) . "\">\n\t\t\t\t";
            }
            $buff[] = $currentColDesc;
            if ($this->_isSortable && in_array($currentCol, $this->_sortableCols)) {
                $buff[] = "\n\t\t\t</a>\n\t\t</th>\n";
            } else {
                $buff[] = "</th>\n";
            }
        }
        $buff[] = "\t</tr>\n";
        
        //generating rows
        foreach ($this->_rows as $rowSpec) {
            $rowData = $rowSpec['rowCells'];
            $rowAttrs = $rowSpec['rowAttrs']; 
            
            $rowAttribsString = "";
            if ($rowAttrs && is_array($rowAttrs) ) {
                foreach ($rowAttrs as $attr => $value){
                   $rowAttribsString .= sprintf(" %s=\"%s\"", $attr, $value);
                }
            }
            
            if($rowAttribsString != ''){
                $buff[] = "\t<tr $rowAttribsString>\n";
            } else {
                $buff[] = "\t<tr class=\"$rowClass\">\n";
            }
            unset($rowAttrs, $rowAttribsString);
            
            $columnIndex = 0;
            
            foreach ($rowData as $cell) {
                $column = $this->_cols[$columnIndex];
                $columnClass = '';
                $columnAttributes = '';
                if ( array_key_exists($column,$this->_colsCssClasses) ) {
                    $columnClass = $this->_colsCssClasses[$column];
                }
                if ( array_key_exists($column, $this->_colsExtraAttributes) ) {
                    $columnAttributes = $this->_colsExtraAttributes[$column];
                }
                $buff[] = "\t\t<td";
                if ( !empty($columnClass) ) $buff[] = " class=\"{$columnClass}\"";
                $buff[] = " {$columnAttributes}>$cell</td>\n";
                $columnIndex++;
            } // foreach ($row as $cell)
            $buff[] = "\t</tr>\n";
            $rowClass = ($rowClass === 'odd') ? 'even' : 'odd';
        }
        
        $buff[] = "</tbody>\n";
        $buff[] = "<tfoot>\n";
        
        // generating page navigation table
        if ($this->_isPageable) {
            if( $nextNavImg && $prevNavImg) {
                 $navLinksCode = $this->_getPageNavigationLinks('&gt;', '&lt;', $nextNavImg, $prevNavImg);
            } else {
                $navLinksCode = $this->_getPageNavigationLinks('&gt;', '&lt;');
            }
            
            if ($navLinksCode) {
                $buff[] = "\t<tr class=\"tableNavigationPanelContainer\">\n";
                $buff[] = sprintf("\t\t<td colspan=\"%d\" class=\"tableNavigationPanelContainer\">\n", $this->getColsNum());
                $buff[] = $navLinksCode;
                $buff[] = "\n\t\t</td>\n";
                $buff[] = "\t</tr>\n";
            }
        }
        
        // status bar
        if ($this->_showStatus) {
            $buff[] = "\t<tr class=\"statusbar\">\n";
            $buff[] = sprintf("\t\t<td colspan=\"%d\" class=\"statusbar\">\n", $this->getColsNum());
            $buff[] = $this->_status;
            $buff[] = "\n\t\t</td>\n";
            $buff[] = "\t</tr>\n";
        }
        $buff[] = "</tfoot>\n";
        $buff[] = "</table>\n";
        $buff[] = "</div>\n" . "<!-- Table [" . $this->_cssID . "] ends -->\n";
        
        $this->_html = implode('', $buff);
        unset($buff);
        
        if ($flush) {
            $html = $this->_html;
            $this->_html = '';
            echo $html;
            return $html;
        }
        
        return $this->_html;
    } // public function render()
    
    /**
     * String representation of the table. Renders the table.
     * 
     * @return  string
     */
    public function __toString()
    {
        return $this->render(false);
    }
}