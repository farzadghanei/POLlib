<?php
//Parsonline/Network/SSH2/Stream.php
/**
 * Defines Parsonline_Network_SSH2_Stream class.
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
 * @package     Parsonline_Network_SSH2
 * @subpackage  Stream
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.1.4 2011-03-04
*/

/**
 * @uses    Parsonline_Network_SSH2
 * @uses    Parsonline_Stream
 */
require_once('Parsonline/Network/SSH2.php');
require_once('Parsonline/Stream.php');

/**
 * Parsonline_Network_SSH2_Stream
 * 
 * Provides an Object Oriented interface to a SSH2 dat stream channel.
 *
 * @see     Parsonline_Network_SSH2
 * @see     Parsonline_Network_SSH2_Shell
 */
class Parsonline_Network_SSH2_Stream extends Parsonline_Stream
{    
    /**
     * The SSH object hosting the Stream.
     *
     * @var Parsonline_Network_SSH2
     */
    protected $_ssh = null;

    /**
     * Returns the SSH connection that the Stream is attached to.
     *
     * @return  Parsonline_Network_SSH2
     */
    public function getSSH()
    {
        return $this->_ssh;
    }

    /**
     * Sets the SSH connection that the Stream is attached to.
     *
     * NOTE: This method could only be called once. Once the SSH is set
     * to the stream it could not be changed.
     *
     * @param   Parsonline_Network_SSH2     $ssh
     * @return  Parsonline_Network_SSH2_Stream
     * @throws  Parsonline_Exception_ContextException if the SSH had been set before
     */
    public function setSSH(Parsonline_Network_SSH2 $ssh)
    {
        if ($this->_ssh) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "Failed to modify the SSH connection object of the stream. The SSH property could not be changed after it is set"
            );
        }
        $this->_ssh = $ssh;
        // invalidate current ID so next calls to getSystemResourceID() would include SSH id as well
        $this->_systemResourceID = '';
        return $this;
    } // public function setSSH()

    /**
     * Sets the data stream resource that is beeing used underhood.
     *
     * NOTE: Overrides parent, by changin the stream system resource ID
     * an adding information about the SSH connection ID and the shell ID
     * to the resource ID. Also makes sure the stream could not be modified
     * after the SSH shell has been started. changing the stream of the
     * SSH stream is not a valid operation.
     *
     * @param   resource    &$stream    Reference to the data stream resource
     * @return  Parsonline_Network_SSH2_Stream
     * @throws  Parsonline_Exception_ContextException if stream has been configured before
     */
    public function setStream(&$stream)
    {
        if ($this->_stream) {
            /**
             * @uses    Parsonline_Exception_ContextException
             */
            require_once('Parsonline/Exception/ContextException.php');
            throw new Parsonline_Exception_ContextException(
                "Failed to modify the stream resource. The stream could not be modified after the stream is setup for an SSH connection"
            );
        }
        return parent::setStream($stream);
    } // public function setStream()

    /**
     * Returns the system resource ID associated to the stream.
     * The resource ID is a mixture of the process ID in the host operating
     * system and the resource internal unique identifier, separated by a colon.
     * If the SSH connection is assigned to the stream (most of times should be)
     * the system resource ID would look like PID:SSH connection ID:Stream ID
     *
     * @return  string
     */
    public function getSystemResourceID()
    {
        if (!$this->_systemResourceID) {
            if ($this->_ssh) {
                $hostId = $this->_ssh->getSystemResourceID();
            } else {
                $hostId = intval(getmygid()) . ':_';
            }
            $this->_systemResourceID = $hostId . ':' . intval($this->_stream);
        }
        return $this->_systemResourceID;
    } // public function getSystemResourceID()
}