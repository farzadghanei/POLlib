<?php
//Parsonline/UserSession/Storage/File.php
/**
 * Defines Parsonline_UserSession_Storage_File class.
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
 * @package     Parsonline_UserSession
 * @subpackage  Storage
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.0.3 2012-07-03
*/

/**
 * @uses    Parsonline_UserSession_IStorage
 */
require_once('Parsonline/UserSession/IStorage.php');

/**
 * @uses    Parsonline_Exception
 * @uses    Parsonline_Exception_ValueException 
 * @uses    Parsonline_Exception_ContextException
 * @uses    Parsonline_Exception_IOException
 */

/**
 * Parsonline_UserSession_Storage_File
 * 
 * Provides a storage for user sessions, using files on disk.
 *
 * @see     Parsonline_UserSession
 */
class Parsonline_UserSession_Storage_File implements Parsonline_UserSession_IStorage
{
    const PATTERN_ID = '{$ID}';
    
    /**
     * The default session path for all sessions
     * 
     * @var string
     */
    public static $_defaultSessionPath = null;
    
    /**
     * The path to store the user sessions
     * 
     * @var string
     */
    protected $_sessionPath = null;
    
    /**
     * The pattern to use for session filenames.
     * supprted substitute variables:
     * 
     *  {$ID}: substitute by session ID
     *  
     * @var string
     */
    protected $_sessionFilenamePattern = '';
    
    /**
     * Constructor.
     * Creates a new user session file storage.
     * 
     * @param   string  $path   [optional] the default path to store sessions
     * 
     */
    public function __construct($path=null)
    {
        if (!$path) $path = self::getDefaultSessionPath();
        if ($path) {
            $this->setSessionPath($path);
        }
        $this->setSessionFilenamePattern('sess_' . self::PATTERN_ID);
    }
    
    /**
     * Returns the default path that store user session data files.
     * By default uses system temporary directory.
     * 
     * @return  string 
     */
    public static function getDefaultSessionPath()
    {
        if (!self::$_defaultSessionPath) {
            self::setDefaultSessionPath(sys_get_temp_dir());
        }
        return self::$_defaultSessionPath;
    }
    
    /**
     * Sets the default path to store user sessions.
     * 
     * @param   string  $path 
     * @throws  Parsonline_Exception_ValueException on not writable directory path
     */
    public static function setDefaultSessionPath($path)
    {
        $canonicalPath = realpath($path);
        if ( !$canonicalPath || !is_dir($canonicalPath) || !is_writable($canonicalPath) ) {
            if (!$canonicalPath) $canonicalPath = $path;
            /**
             *@uses  Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("Invalid path for session path. '$canonicalPath' is not a writable directory.");
        }
        self::$_defaultSessionPath = $path;
    }
    
    /**
     * Returns the path where user sessions are stored in.
     * 
     * @return string
     */
    public function getSessionPath()
    {
        return $this->_sessionPath;
    }
    
    /**
     * Sets the path where user sessions are stored in.
     * Should be a writable directory path.
     * 
     * @param   string      $path
     * @return  Parsonline_UserSession_Storage_File
     * @throws  Parsonline_Exception_ValueException on not writable directory path
     */
    public function setSessionPath($path)
    {
        $canonicalPath = realpath($path);
        if ( !$canonicalPath || !is_dir($canonicalPath) || !is_writable($canonicalPath) ) {
            if (!$canonicalPath) $canonicalPath = $path;
            /**
             *@uses  Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException("Invalid path for session path. '$canonicalPath' is not a writable directory.");
        }
        $this->_sessionPath = $canonicalPath;
        return $this;
    }
    
    /**
     * Returns the pattern that is used to create session filenames.
     * 
     * @return string 
     */
    public function getSessionFilenamePattern()
    {
        $this->_sessionFilenamePattern;
    }
    
    /**
     * Sets the pattern that is used to create session filenames. The pattern
     * might include (and should at least include a unique id) to be replaced
     * by some value.
     * 
     *   {$ID}: is going to be repalced by the session ID
     * 
     * use PATTERN_* class constants for pattern variables.
     * 
     * Note: the directory separator characters are striped from the
     * session filename pattern.
     * 
     * @param   string  $pattern
     * @return  Parsonline_UserSession_Storage_File
     */
    public function setSessionFilenamePattern($pattern)
    {
        $pattern = str_replace(DIRECTORY_SEPARATOR, '', $pattern);
        $this->_sessionFilenamePattern = $pattern;
        return $this;
    }
    
    /**
     * Returns the path to the file that stores the specified session.
     * Returns false if session ID is empty.
     * 
     * Note: the directory separator characters are striped from the
     * session ID.
     * 
     * @param   string  $id     the session ID
     * @return  string|false
     * @throws  Parsonline_Exception_ContextException on no session path set
     */
    public function getSessionFilename($id)
    {
        if ($id) {
            $path = $this->getSessionPath();
            if (!$path) {
                /**
                 *@uses Parsonline_Exception_ContextException
                 */
                require_once('Parsonline/Exception/ContextException.php');
                throw new Parsonline_Exception_ContextException("No user session path is specified");
            }
            $pattern = $this->getSessionFilenamePattern();
            if ($pattern) {
                $filename = str_replace(self::PATTERN_ID, $id, $pattern);
            } else {
                $filename = $id;
            }
            
            $filename = str_replace(DIRECTORY_SEPARATOR, '', $filename);
            return $path . DIRECTORY_SEPARATOR . $filename;
        }
        return false;
    }
    
    /**
     * Creates a new session with the specified session ID.
     * Returns true if session is created, false if session file already exists
     * and overwrite is false.
     * 
     * @param   string  $id
     * @param   bool    $overwrite
     * @return  bool
     * @throws  Parsonline_Exception_IOException on failure to create file
     * @throws  Parsonline_Exception if file already exists, and overwrite is not true
     */
    public function create($id, $overwrite=false)
    {
        $filename = $this->getSessionFilename($id);
        if ( file_exists($filename) ) {
            if ($overwrite) {
                if (!unlink($filename)) {
                    /**
                     *@uses  Parsonline_Exception_IOException
                     */
                    require_once('Parsonline/Exception/IOException.php');
                    throw new Parsonline_Exception_IOException("Failed to delete old sesson file '{$filename}'");
                }
            } else {
                return false;
            }
        }
        
        if ( !touch($filename) || !chmod($filename, 0640) ) {
            /**
            *@uses  Parsonline_Exception_IOException
            */
            require_once('Parsonline/Exception/IOException.php');
            throw new Parsonline_Exception_IOException("Failed to create sesson file '{$filename}'");
        }
        return true;
    }
    
    /**
     * Reads data of the session..
     * Returns null if session name is empty or not created yet.
     * 
     * @param   string  $id
     * @return  string|null
     * @throws  Parsonline_Exception_IOException on failure to read from file
     */
    public function read($id)
    {
        if (!$id) return null;
        $filename = $this->getSessionFilename($id);
        if ( !$filename || !file_exists($filename) ) {
            return null;
        }
        
        if ( !is_file($filename) || !is_readable($filename) ) {
            /**
             *@uses  Parsonline_Exception_IOException
             */
            require_once('Parsonline/Exception/IOException.php');
            throw new Parsonline_Exception_IOException("Failed to read from session '$id'. file '$filename' is not a readable session");
        }
        
        $contents = file_get_contents($filename);
        if ($contents === false) {
            /**
             *@uses  Parsonline_Exception_IOException
             */
            require_once('Parsonline/Exception/IOException.php');
            throw new Parsonline_Exception_IOException("Failed to read from session '$id' file '$filename'");
        }
        return $contents;
    } // public function read()
    
    /**
     * Writes the specified data to the session.
     * Returns number of bytes wrote the session.
     * 
     * @param   string  $id
     * @param   array   $data
     * @return  int
     * @throws  Parsonline_Exception on failure to detect session file name
     * @throws  Parsonline_Exception_IOException on failure to write to file
     */
    public function write($id, array $data)
    {
        if (!$id) return 0;
        $filename = $this->getSessionFilename($id);
        if (!$filename) {
            /**
             *@uses Parsonline_Exception 
             */
            require_once("Parsonline/Exception.php");
            throw new Parsonline_Exception("Failed to detect filename for session '$id'");
        }
                
        if ( !file_exists($filename) || !is_file($filename) || !is_writable($filename) ) {
            /**
             *@uses  Parsonline_Exception_IOException
             */
            require_once('Parsonline/Exception/IOException.php');
            throw new Parsonline_Exception_IOException(
                "Failed to write to session '$id'. session file '{$filename}' is not a writable file"
            );
        }
        
        $bytes = file_put_contents($filename, $data, LOCK_EX);
        if ($bytes === false) {
            /**
             *@uses  Parsonline_Exception_IOException
             */
            require_once('Parsonline/Exception/IOException.php');
            throw new Parsonline_Exception_IOException(
                "Failed to write to file '{$filename}' session '$id'"
            );
        }
        return $bytes;
    }
    
    /**
     * Deletes the specified session.
     * Returns true on success or false on failure.
     * 
     * @param   string  $id
     * @return  bool
     */
    public function delete($id)
    {
        $removed = false;
        if ($id) {
            $filename = $this->getSessionFilename($id);
            if ($filename) $removed = unlink($filename);
        }
        return $removed;
    }
    
    /**
     * Returns true of a session with the specified ID exists or not.
     * 
     * @param   string  $id
     * @return  bool
     */
    public function exists($id)
    {
        if (!$id) return false;
        $filename = $this->getSessionFilename($id);
        return $filename && file_exists($filename) && is_file($filename);
    }
}