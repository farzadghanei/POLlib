<?php
//UserSession.php
/**
 * Defines Parsonline_UserSession class.
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
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.1.3 2012-07-03
 */

/**
 * @uses Parsonline_UserSession_IStorage 
 */
require_once("Parsonline/UserSession/IStorage.php");

/**
 * Parsonline_UserSession
 * 
 * Defines a user session, a utility to store user specific information.
 * This class is not related to web sessions or application specific sessions,
 * this utility is a general purpose backend for user session management.
 */
class Parsonline_UserSession
{
    const PATTERN_TIME = '{$TIME}';
    const PATTERN_RAND = '{$RAND}';
    const PATTERN_HASH = '{$HASH}';
    const PATTERN_IDENTITY = '{$IDENTITY}';
    
    /**
     * The object to store session data for the newly cearted sessions.
     * 
     * @var Parsonline_UserSession_IStorage
     */
    protected static $_defaultStorage = null;
    
    /**
     * The pattern to use for generating new session IDs.
     * 
     * substitute values:
     * 
     *      {$TIME}: replaced by current timestamp
     *      {$RAND}: replaced by a random generated number
     *      {$HASH}: replaced by a random hash value
     * 
     * @var string
     */
    protected static $_sessionIdPattern = 'SID{$HASH}';
    
    /**
     * The object to store session data
     * 
     * @var Parsonline_UserSession_IStorage
     */
    protected $_storage = null;
    
    /**
     * Session ID of current active session
     * 
     * @var string
     */
    protected $_id = null;
    
    /**
     * Returns the default storage object to persist session data
     * 
     * @return  Parsonline_UserSession_IStorage 
     */
    public static function getDefaultStorage()
    {
        if (!self::$_defaultStorage) {
            /**
             * @uses     Parsonline_UserSession_Storage_File
             */
            require_once("Parsonline/UserSession/Storage/File.php");
            self::$_defaultStorage = new Parsonline_UserSession_Storage_File();
        }
        return self::$_defaultStorage;
    }
    
    /**
     * Sets the default storage object to persist session data
     * 
     * @param   Parsonline_UserSession_IStorage $storage
     */
    public static function setDefaultStorage(Parsonline_UserSession_IStorage $storage)
    {
        self::$_defaultStorage = $storage;
    }
    
    /**
     * Returns the pattern to use for generating new session IDs
     * 
     * @return string
     */
    public static function getSessionIdPattern()
    {
        return self::$_sessionIdPattern;
    }
    
    /**
     * Sets the pattern to use for generating new session IDs.
     * 
     * use PATTERN_* class constants for substitute variables reference.
     * 
     *      {$TIME}: replaced by current timestamp
     *      {$RAND}: replaced by a random generated number
     *      {$HASH}: replaced by a random hash value
     *      {$IDENTITY}: replaced by the user identity (username)
     * 
     * Note: Since specifying a bad session ID pattern might affect uniqueness of
     * session IDs, it is mandatory to use at least the {$HASH} variable.
     * 
     * @param   string  $pattern
     * @throws  Parsonline_Exception_ValueException on invalid pattern
     */
    public static function setSessionIdPattern($pattern)
    {
        if ( strpos($pattern, self::PATTERN_HASH) === false ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException(
                "Invalid session ID pattern. pattern must have hash pattern variable"
            );
        }
        self::$_sessionIdPattern = $pattern;
    }
    
    /**
     * Constructor.
     * Creates a new session, assigns the new session a session ID eaither specified
     * for the constructor or assigned a new generated one.
     * 
     * @param   string  $id         [optional] the session ID to use
     */
    public function __construct($id=null)
    {
        /*
         * Make sure the storage is setup first, so next steps of initialization
         * could follow.
         */
        $this->setStorage( self::getDefaultStorage() );
        
        if (!$id) $id = $this->generateSessionId();
        $this->setId($id);
    }
    
    /**
     * Returns a unique value appropriate for the user session ID.
     * 
     * @param   string      $identity   [optional] the user identity
     * @param   bool        $check      [optional] check to make sure the session id is not used
     * @return  string
     */
    public function generateSessionId($identity='', $check=true)
    {
        $id = self::getSessionIdPattern();
        $time = time();
        $rand = rand($time, $time + 10000);
        $hash = md5('_session_' . $identity . '***' . $time . '***' . $rand);
        $id = str_replace(self::PATTERN_IDENTITY, $identity, $id);
        $id = str_replace(self::PATTERN_TIME, $time, $id);
        $id = str_replace(self::PATTERN_RAND, $rand, $id);
        $id = str_replace(self::PATTERN_HASH, $hash, $id);
        if ($check && $this->getStorage()->exists($id) ) {
            $id = $this->generateSessionId($identity, $check);
        }
        return $id;
    }
    
    /**
     * Returns the storage object that persists the session data.
     * 
     * @return  Parsonline_UserSession_IStorage
     */
    public function getStorage()
    {
        return $this->_storage;
    }
    
    /**
     * Sets the storage object that persistes the session data.
     * 
     * @param Parsonline_UserSession_IStorage   $storage
     * @return  Parsonline_UserSession
     */
    public function setStorage(Parsonline_UserSession_IStorage $storage)
    {
        $this->_storage = $storage;
        return $this;
    }
    
    /**
     * Returns the session unique identifier
     * 
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }
    
    /**
     * Sets the session unique identifier
     * 
     * @param   string  $id
     * @return  Parsonline_UserSession 
     */
    public function setId($id)
    {
        $this->_id = $id;
        return $this;
    }
    
    /**
     * Initializes current session.
     * 
     * @return  Parsonline_UserSession
     * @throws Parsonline_Exception_ContextException on no session ID seet
     */
    public function init()
    {
        if (!$this->_id) {
            /**
             *@uses  Parsonline_Exception_ContextException
             */
            require_once("Parsonline/Exception/ContextException.php");
            throw new Parsonline_Exception_ContextException(
                "Failed to init user session. No session ID is specified"
            );
        }
        
        $this->_createSession($this->_id, true);
        $this->write(array());
        return $this;
    }
    
    /**
     * Reads data of the session. Returned data would be an array or null
     * if no such session exists.
     * 
     * @return  array|null
     * @throws  Parsonline_Exception_ContextException on no session ID set
     * @throws  Parsonline_Exception_IOException on failure to read session
     * @throws  Parsonline_Exception on invalid session contents
     */
    public function read()
    {
        if (!$this->_id) {
            /**
             *@uses  Parsonline_Exception_ContextException
             */
            require_once("Parsonline/Exception/ContextException.php");
            throw new Parsonline_Exception_ContextException(
                "Failed to read user session. No session ID is specified"
            );
        }
        $contents = $this->getStorage(true)->read($this->_id);
        if (!$contents) return null;
        $data = unserialize($contents);
        return $data;
    }
    
    /**
     * Writes the data to current session.
     * Returns number of bytes wrote the session.
     * 
     * @param   array   $data
     * @return  int
     * @throws  Parsonline_Exception_ContextException on no session ID set
     */
    public function write(array $data)
    {
        if (!$this->_id) {
            /**
             *@uses  Parsonline_Exception_ContextException
             */
            require_once("Parsonline/Exception/ContextException.php");
            throw new Parsonline_Exception_ContextException(
                "Failed to write to user session. No session ID is specified"
            );
        }
        $data = serialize($data);
        return $this->getStorage(true)->write($this->_id, $data);
    }
    
    /**
     * Removes the session.
     * Returns true on success or false on failure.
     * 
     * @return  bool
     * @throws  Parsonline_Exception_ContextException on no session ID set
     */
    public function delete()
    {
        if (!$this->_id) {
            /**
             *@uses  Parsonline_Exception_ContextException
             */
            require_once("Parsonline/Exception/ContextException.php");
            throw new Parsonline_Exception_ContextException(
                "Failed to delete user session. No session ID is specified"
            );
        }
        return $this->getStorage()->delete($this->_id);
    }
    
    /**
     * Returns true of a session exists or false if not.
     * 
     * @return  bool
     * @throws  Parsonline_Exception_ContextException on no session ID set
     */
    public function exists()
    {
        if (!$this->_id) {
            /**
             *@uses  Parsonline_Exception_ContextException
             */
            require_once("Parsonline/Exception/ContextException.php");
            throw new Parsonline_Exception_ContextException(
                "Failed to check user session existence. No session ID is specified"
            );
        }
        return $this->getStorage()->exists($this->_id);
    }
    
    /**
     * Creates a new session for the session id.
     * 
     * @param   string  $id
     * @param   bool    $overwrite
     * @return  boolean
     * @throws  Parsonline_Exception if session already exists and overwrite is false
     */
    protected function _createSession($id, $overwrite=true)
    {
        $storage = $this->getStorage(true);
        if ( $storage->exists($id) ) {
            if ($overwrite) {
                $storage->delete($id);
            } else {
                /**
                 *@uses   Parsonline_Exception
                 */
                require_once("Parsonline/Exception.php");
                throw new Parsonline_Exception("Failed to create session. session id '{$id}' already exists");
            }
        }
        return $storage->create($id, $overwrite);
    }
}