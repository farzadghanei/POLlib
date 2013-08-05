<?php
//Parsonline/UserSession/IStorage.php
/**
 * Defines Parsonline_UserSession_IStorage interface.
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
 * @copyright   Copyright (c) 2012 ParsOnline, Inc. (www.parsonline.com)
 * @license     Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Parsonline_UserSession
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.0.1 2012-06-24
 */

/**
 * Parsonline_UserSession_IStorage
 *
 * an interface for all session storages that Parsonline_UserSession could use
 * as storage
 */
interface Parsonline_UserSession_IStorage
{
    /**
     * Creates a new session with the specified session ID.
     * 
     * @param   string  $id
     * @param   bool    $overwrite
     * @return  bool
     * @throws  Exception on failure to create
     */
    public function create($id, $overwrite);
    
    /**
     * Reads data of the session..
     * Returns null if session name is empty or not created yet.
     * 
     * @param   string  $sessionId
     * @return  string|null
     * @throws  Exception on failure to read session
     */
    public function read($id);
    
    /**
     * Writes the specified data to the session.
     * Returns number of bytes wrote the session.
     * 
     * @param   string  $sessionId
     * @param   array   $data
     * @return  int
     * @throws  Exception on failure to write data
     */
    public function write($id, array $data);
    
    /**
     * Deletes the specified session.
     * Returns true on success or false on failure.
     * 
     * @param   string  $id
     * @return  bool
     */
    public function delete($id);
    
    /**
     * Returns true of a session with the specified ID exists or not.
     * 
     * @param   string  $id
     * @return  bool
     */
    public function exists($id);
}