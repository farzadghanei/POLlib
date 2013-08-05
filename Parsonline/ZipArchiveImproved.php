<?php
//Parsonline/ZipArchiveImproved.php
/**
 * Defines the Parsonline_ZipArchiveImproved class.
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
 * @package     Parsonline
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     2.1.0 2012-07-03
 */

/**
 * ZipArchiveImproved
 * 
 * Extends PHP intenral ZipArchive class to add some information about the zip
 * file and improve performance by tweaking system limit on open file descriptors.
 *
 * @uses ZipArchive
 */
class Parsonline_ZipArchiveImproved extends ZipArchive
{
    /**
     * The default value for new added file queue maximum size.
     * 
     * @staticvar   int
     */
    protected static $_defaultAddFileQueueMaxSize = 100;
    

    /**
     * Number of files added to the archive
     * 
     * @var int
     */
    protected $_addFileQueueSize = 0;

    /**
     * Maximum number of bufferred added files to archive
     * 
     * @var int
     */
    protected $_addFileQueueMaxSize;

    /**
     * The name of the archive file
     * 
     * @var string
     */
    protected $_archiveFileName = null;
    
    /**
     * Returns the class default value for maximum number of files that could
     * be added to ZIP without reopenning the stream to file.
     * 
     * @return  int
     */
    public static function getDefaultAddFileQueueMaxSize()
    {
        return self::$_defaultAddFileQueueMaxSize;
    }
    
    /**
     * Sets the class default value for maximum number of files that could
     * be added to ZIP without reopenning the stream to file.
     * 
     * @param   int $size
     * @throws  Parsonline_Exception_ValueException on none-positive size
     */
    public static function setDefaultAddFileQueueMaxSize($size)
    {
        $intSize = intval($size);
        if ($intSize < 1) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("Invalid max queue size. Queue size should be a positive integer");
        }
        self::$_defaultAddFileQueueMaxSize = $intSize;
    }
    
    /**
     * Returns the name of the archive file.
     *
     * @return string
     */
    public function getArchiveFileName()
    {
        return $this->_archiveFileName;
    }

    /**
     * Returns the number of files that are going to be added to ZIP
     * without reopenning the stream to file.
     *
     * @return int
     */
    public function getAddFileQueueSize()
    {
        return $this->_addFileQueueSize;
    }
    
    /**
     * Returns the number of remaining files that could be added added to ZIP
     * without reopenning the stream to file.
     *
     * @return int
     */
    public function getAddFileQueueRemainingSize()
    {
        return $this->_addFileQueueMaxSize - $this->_addFileQueueSize;
    }

    /**
     * Returns the maximum number of files that could be added to ZIP
     * without reopenning the stream to file.
     *
     * @return int
     */
    public function getAddFileQueueMaxSize()
    {
        return $this->_addFileQueueMaxSize;
    }
    
    /**
     * Sets the maximum number of files that could added to ZIP
     * without reopenning the stream to file.
     *
     * @param   int
     * @return  ZipArchiveImproved
     * @throws  Parsonline_Exception_ValueException on none-positivie integer
     */
    public function setAddFileQueueMaxSize($size)
    {
        $intSize = intval($size);
        if ($intSize < 1) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php.php');
            throw new Parsonline_Exception_ValueException("Invalid max queue size. Queue size should be a positive integer");
        }
        $this->_addFileQueueMaxSize = $intSize;
        return $this;
    }

    /**
     * Opens a stream to a ZIP archive file.
     * Calls the ZipArchive::open() internally.
     * overwrites ZipArchive::open() to add the archiveFileName functionality.
     *
     * @param   string  $fileName
     * @param   int     $flags
     * @return   mixed  Error codes
     */
    public function open($fileName, $flags)
    {
        $this->_archiveFileName = $fileName;
        $this->_addFileQueueSize = 0;
        $this->setAddFileQueueMaxSize(self::$_defaultAddFileQueueMaxSize);
        return parent::open($fileName,$flags);
    }

    /**
     * Closes the stream to ZIP archive file.
     * Calls the ZipArchive::close() internally.
     * Overwrites ZipArchive::close() to clear the archive file name, and reset
     * the add file queue.
     *
     * @return bool
     */
    public function close()
    {
        $this->_archiveFileName = null;
        $this->_addFileQueueSize = 0;
        return parent::close();
    }

    /**
     * Closes the connection to ZIP file and openes the connection again.
     *
     * @return  bool
     */
    public function reopen()
    {
        if ( !$this->close() ) {
            return false;
        }
        return $this->open($this->_archiveFileName, self::CREATE);
    }

    /**
     * Adds a file to a ZIP archive from the given path.
     * Calls the ZipArchive::addFile() internally.
     * Overwrites ZipArchive::addFile() to handle maximum open file descriptors
     * limit in operating systems. If adding the new file causes the number of
     * added files to queue get larger than the specified maximum queue size,
     * calls the repon() method so IO is flushed.
     *
     * @param   string  $fileName    the path to file to be added to archive
     * @param   string  $localname  [optional] the name of the file in the ZIP archive
     * @return  bool
     */
    public function addFile($fileName, $localname=null)
    {
        if ($this->_addFileQueueSize >= $this->_addFileQueueSize) {
            $this->reopen();
        }
        $added = parent::addFile($fileName, $localname);
        if ($added) {
            $this->_addFileQueueSize++;
        }
        return $added;
    } // public function addFile()
}