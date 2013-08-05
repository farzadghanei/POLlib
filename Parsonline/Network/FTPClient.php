<?php
//Parsonline/Network/FTPClient.php
/**
 * Defines Parsonline_Network_FTPClient class.
 *
 * Parsonline
 *
 * 
 * Copyright (c) 2011-2012 ParsOnline, Inc. (www.parsonline.com)
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
 * @copyright  Copyright (c) 2011-2012 ParsOnline, Inc. (www.parsonline.com)
 * @license    Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @category    Parsonline
 * @package     Parsonline_Network
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.2.0 2012-07-08
 */

/**
 * Parsonline_Network_FTPClient
 *
 * An FTP Client class to handle FTP operations for the application.
 */
class Parsonline_Network_FTPClient
{
    const TYPE_FILE = 'file';
    const TYPE_DIR = 'dir';
    const TYPE_LINK = 'link';
    const TYPE_SOCKET = 'socket';
    
    /**
     * Hostname of remote FTP server
     * 
     * @var string
     */
    protected $_host = '';
    
    /**
     * The port number to connect
     * 
     * @var int
     */
    protected $_port = 21;
    
    /**
     * The network operations timeout
     * 
     * @var int
     */
    protected $_timeout = 90;
    
    /**
     * Username to connect to remote FTP server
     * 
     * @var string
     */
    protected $_username = '';
    
    /**
     * Password to connect to remote FTP server
     * 
     * @var string
     */
    protected $_password = '';
    
    /**
     * A resource for ftp connection returned by ftp_connect
     * 
     * @var resource 
     */
    protected $_ftp = null;
    
    /**
     * An array of loggers
     * 
     * @var array
     */
    protected $_loggers = array();
    
    /**
     * Constructor.
     * 
     * Creates a new FTP Client and configures it based on the passed
     * options.
     * 
     * @param   array   $options
     * @throws  Exception specific to each setter method
     */
    public function __construct(array $options=array())
    {
        if ($options) {
            $this->configureByArray($options);
        }
    }
    
    /**
     * Distructor.
     * 
     * Called on object removal. Disconnects the FTP resource.
     */
    public function __destruct()
    {
        $this->close();
    }
    
    /**
     * Returns the host name/address of the FTP server
     * 
     * @return string
     */
    public function getHost()
    {
        return $this->_host;
    }
    
    /**
     * Sets the host name/address of the FTP server
     * 
     * @param   string  $host
     * @return  Parsonline_Network_FTPClient
     */
    public function setHost($host)
    {
        $this->_host = strval($host);
        return $this;
    }
    
    /**
     * Returns the port number of the FTP server
     * 
     * @return int
     */
    public function getPort()
    {
        return $this->_port;
    }
    
    /**
     * Sets the port number of the FTP server
     * 
     * @param   int     $port
     * @return  Parsonline_Network_FTPClient
     */
    public function setPort($port)
    {
        $this->_port = intval($port);
        return $this;
    }
    
    /**
     * Returns the FTP operations timeout
     * 
     * @return int
     */
    public function getTimeout()
    {
        return $this->_timeout;
    }
    
    /**
     * Sets the FTP operations timeout
     * 
     * @param   int     $time
     * @return  Parsonline_Network_FTPClient
     */
    public function setTimeout($time)
    {
        $this->_timeout = intval($time);
        return $this;
    }
    
    /**
     * Returns the username of remote FTP server
     * 
     * @return string
     */
    public function getUsername()
    {
        return $this->_username;
    }
    
    /**
     * Sets the username of remote FTP server
     * 
     * @param   string  $username
     * @return  Parsonline_Network_FTPClient 
     */
    public function setUsername($username)
    {
        $this->_username = strval($username);
        return $this;
    }
    
    /**
     * Returns the password of remote FTP server
     * 
     * @return string
     */
    public function getPassword()
    {
        return $this->_password;
    }
    
    /**
     * Sets the password of remote FTP server
     * 
     * @param   string  $pass
     * @return  Parsonline_Network_FTPClient 
     */
    public function setPassword($pass)
    {
        $this->_password = strval($pass);
        return $this;
    }
    
    /**
     * Removes all registered callable loggers.
     *
     * @return  Parsonline_Network_FTPClient
     */
    public function clearLoggers()
    {
        $this->_loggers = array();
        return $this;
    }

    /**
     * Returns an array of callable references (function, or object method) that
     * are called on each log.
     *
     * @return  array
     */
    public function getLoggers()
    {
        return $this->_loggers;
    }

    /**
     * Registers a callable reference (function, or object method) so on
     * each initiated log, the loggers would be notified.
     *
     * Each logger should accept these paramters (with appropriate default values
     * incase some paramters are not provided):
     *
     *  <li>string  $message    log message</li>
     *  <li>int     $priority   standard log priority, 7 for DEBUG and 0 for EMERG. default is 6 INFO</li>
     *
     * NOTE: no validation is applied on the registered callable. any valid callable
     * reference could be registered, but they should be able to handle the
     * specified parameters.
     * 
     * NOTE: If the logger is an empty object then its 'log' method would be used,
     * so it should have a log method.
     * 
     * @param   callable|object $logger
     * @return  Parsonline_Network_FTPClient
     * @throws  Parsonline_Exception_ValueException on none callable parameter
     */
    public function registerLogger($logger)
    {
        if (is_object($logger)) {
            $logger = array($logger, 'log');
        }
        
        if (!$logger || !is_callable($logger, false)) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once('Parsonline/Exception/ValueException.php');
            throw new Parsonline_Exception_ValueException(
                    "Logger should be a string for function name, or an array of object, method name"
            );
        }
        array_push($this->_loggers, $logger);
        return $this;
    } // public function registerLogger()
    
    /**
     * Sets data of the FTP object from an array.
     * Returns an array, the first index is an array of keys used
     * to configure the object, and the second is an array of keys
     * that were not used.
     *
     * @param   array       $options        associative array of property => values
     * @return  array       array(sucess keys, not used keys)
     */
    public function configureByArray(array &$options)
    {
        $result = array(array(), array());
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if ( in_array($method, $methods) ) {
                $this->$method($value);
                array_push($result[0], $key);
            } else {
                array_push($result[1], $key);
            }
        }
        return $result;
    } // public function setOptionsFromArray()
    
    /**
     * Logs a message to the logger object of the FTPClient.
     * Returns number of called loggers.
     * 
     * @param   string  $message
     * @param   int     $level 
     * @param   string  $signature
     * @return  bool
     */
    protected function _log($message, $level=6, $signature='')
    {
        $called = 0;
        if ($this->_loggers) {
            if ($signature) {
                $message = "[{$signature}]: $message";
            }
            foreach ($this->_loggers as $logger) {
                call_user_func($logger, $message, $level);
            }
            ++$called;
        }
        return $called;
    } // protected function _log()
    
    /**
     * Return the FTP connection resource returned from ftp_connect
     * the resource is used internally.
     * 
     * @param   bool    $refresh        if should reconnect
     * @return  resource
     * @throws  Parsonline_Exception on failed to connect to FTP server
     */
    protected function _getConnection($refresh=false)
    {
        if ($refresh || !$this->_ftp) {
            $this->_log("connecting to FTP server {$this->_host}:{$this->_port}", LOG_DEBUG);
            $this->_ftp = ftp_connect($this->_host, $this->_port, $this->_timeout);
            if (!$this->_ftp) {
                /**
                 * @uses    Parsonline_Exception
                 */
                require_once('Parsonline/Exception.php');
                throw new Parsonline_Exception("Failed to connect to FTP server {$this->_host}:{$this->_port}");
            }
            
            if ($this->_username && !ftp_login($this->_ftp, $this->_username, $this->_password)) {
                /**
                 * @uses    Parsonline_Exception
                 */
                require_once('Parsonline/Exception.php');
                throw new Parsonline_Exception("Failed to authenticate with FTP server {$this->_host}:{$this->_port} with user {$this->_username}");
            }
            $this->_initFTPConnection();
        }
        return $this->_ftp;
    } // protected function _getConnection()
    
    /**
     * Initializes the FTP connection resource right after creating the
     * connection. could be overriden in child classes.
     * 
     * By default changes the passive mode of the FTP to true.
     * 
     */
    protected function _initFTPConnection()
    {
    }
    
    /**
     * This method is called internally in each operation method so shared
     * initializations are grouped together. By default makes sure the FTP
     * connections is stablished.
     * 
     * @return  Parsonline_Network_FTPClient
     */
    protected function _initOperation()
    {
       $this->connect(false);
       return $this;
    }
    
    /**
     * Connect to server. This is not necessary since each operation would
     * automatically calls this.
     * 
     * If client is already connected, no new connections would be made and the
     * method returns false. If client is not connected or reconnect param
     * is true, then connects to remote server and returns true.
     * 
     * @param   bool        $reconnect      force reconnect
     * @return  bool
     */
    public function connect($reconnect=false)
    {
        if (!$reconnect && is_resource($this->_ftp) ) {
            return false;
        }
        $this->_getConnection($reconnect);
        return true;
    }
    
    /**
     * Disconnects the FTP connection. Returns true if there were a connection
     * and disconnected correctly, otherwise returns false.
     * 
     * There is no need to call this method since it would be automatically
     * called when the object is being deleted.
     * 
     * @return  bool
     */
    public function close()
    {
        $closed = false;
        if ( is_resource($this->_ftp) ) {
            $this->_log("closing FTP connection", LOG_DEBUG);
            $closed = ftp_close($this->_ftp);
        }
        return $closed;
    }
    
    /**
     * Sets the passive mode of the FTP connection on or off.
     * 
     * @param   bool    $passive
     * @return  bool
     */
    public function pasv($passive)
    {
        $this->_initOperation();
        $passive = (true && $passive);
        $this->_log(sprintf("setting FTP passive mode to %d", $passive), LOG_DEBUG);
        return ftp_pasv($this->_ftp, $passive);
    }
    
    /**
     * Returns the system type of remote server
     * 
     * @return  string|false
     */
    public function systype()
    {
        $this->_initOperation();
        $this->_log("getting remote system type", LOG_DEBUG);
        return ftp_systype($this->_ftp);
    }
   
    /**
     * Retuns the size of a remote file in bytes.
     * If an error occurred returns -1.
     * 
     * @param   string  $remote
     * @return  int
     */
    public function size($remote)
    {
        $this->_initOperation();
        $this->_log("getting remote file '{$remote}' size", LOG_DEBUG);
        return ftp_size($this->_ftp, $remote);
    }
    
    /**
     * Retuns the last modification time of a remote file as unix timestamp.
     * If an error occurred returns -1.
     * 
     * @param   string  $remote
     * @return  int
     */
    public function mdtm($remote)
    {
        $this->_initOperation();
        $this->_log("getting remote file '{$remote}' last modification time", LOG_DEBUG);
        return ftp_mdtm($this->_ftp, $remote);
    }
    
    /**
     * Returns the current working directory on remote server
     * 
     * @return  string|false
     */
    public function pwd()
    {
        $this->_initOperation();
        $this->_log("getting remote current working directory", LOG_DEBUG);
        return ftp_pwd($this->_ftp);
    }
    
    /**
     * Changes the current working directory on remote server to the specified
     * directory.
     * 
     * @param   string  $dir
     * @return  Parsonline_Network_FTPClient
     * @throws  Parsonline_Exception
     */
    public function chdir($dir)
    {
        $this->_initOperation();
        $this->_log("changing remote directory to '{$dir}'", LOG_DEBUG);
        if ( !ftp_chdir($this->_ftp, $dir) ) {
            /**
             * @uses    Parsonline_Exception
             */
            require_once('Parsonline/Exception.php');
            throw new Parsonline_Exception("Failed to change remote directory to '{$dir}'");
        }
        return $this;
    } // public function chdir()
    
    /**
     * Changes the directory on remote server to the parent directory.
     * 
     * @return  Parsonline_Network_FTPClient
     * @throws  Parsonline_Exception
     */
    public function cdup()
    {
        $this->_initOperation();
        $this->_log("changing remote directory to parent directory", LOG_DEBUG);
        if ( !ftp_cdup($this->_ftp) ) {
            /**
             * @uses    Parsonline_Exception
             */
            require_once('Parsonline/Exception.php');
            throw new Parsonline_Exception("Failed to change remote directory to parent directory");
        }
        return $this;
    } // public function cdup()
    
    /**
     * Changes the file permissions of a remote file
     * 
     * @param   int     $mode
     * @param   string  $remote
     * @return  Parsonline_Network_FTPClient
     * @throws  Parsonline_Exception
     */
    public function chmod($mode, $remote)
    {
        $this->_initOperation();
        $this->_log("changing file permission for '{$remote}'", LOG_DEBUG);
        if ( !ftp_chmod($this->_ftp, $mode, $remote) ) {
            /**
             * @uses    Parsonline_Exception
             */
            require_once('Parsonline/Exception.php');
            throw new Parsonline_Exception("Failed to change remote file permissions on '{$remote}'");
        }
        return $this;
    } // public function chmod()
    
    
    /**
     * Creates a remote directory.
     * 
     * @param   string  $remote
     * @return  Parsonline_Network_FTPClient
     * @throws  Parsonline_Exception
     */
    public function mkdir($remote)
    {
        $this->_initOperation();
        $this->_log("creating remote directory '{$remote}'", LOG_DEBUG);
        if ( !ftp_mkdir($this->_ftp, $remote) ) {
            /**
             * @uses    Parsonline_Exception
             */
            require_once('Parsonline/Exception.php');
            throw new Parsonline_Exception("Failed to create remote directory '{$remote}'");
        }
        return $this;
    } // public function mkdir()
    
    /**
     * List filenames in a remote directory.
     * 
     * @see rawlist()
     * 
     * @param   string  $remote
     * @return  array|false
     */
    public function nlist($remote=null)
    {
        $this->_initOperation();
        if (!$remote) $remote = $this->pwd();
        $this->_log("listing filenames in remote directory '{$remote}'", LOG_DEBUG);
        return ftp_nlist($this->_ftp, $remote);
    }
    
    /**
     * List details of files in a remote directory.
     * 
     * @see     nlist()
     * 
     * @param   string  $remote
     * @return  array|false
     */
    public function rawlist($remote=null)
    {
        $this->_initOperation();
        if (!$remote) $remote = $this->pwd();
        $this->_log("listing file details in remote directory '{$remote}'", LOG_DEBUG);
        return ftp_rawlist($this->_ftp, $remote);
    }
    
    /**
     * Returns true if the remote paht is a directory, or false if it is not.
     * 
     * @param   string  $path
     * @return  bool
     */
    public function isDirectory($path)
    {
        return ($this->size($path) < 0);
    }
    
    /**
     * Parses a string of information of a file (returned by rawlist)
     * and returns an associative array of information
     * keys are:
     * 
     *  type => if it is a directory, file, link, etc. use TYPE_* constants.
     *  rawperms => file permissions like drwx--x--x
     *  links => number of links to the file
     *  owner => owner
     *  group => group
     *  size => file size in bytes
     *  mdtm => last modified time (unix timestamp))
     *  name => file/directory name
     * 
     * @param type $list 
     */
    public function parseFileInfo($list)
    {
        $result = array(
                        'type' => null,
                        'rawperms' => null,
                        'links' => null,
                        'owner' => null,
                        'group' => null,
                        'size' => null,
                        'mdtm' => null,
                        'name' => null
                );
        
        $patterns = array(
                        // dates have time values like: Sep 05 02:01
                        '/(([-dls])[rxw-]{9})\s+(\d+)\s+(\S+)\s+(\S+)\s+(\d+)\s+([a-zA-Z]{3}+\s+(?:0[1-9]|[12][0-9]|3[01])\s+[0-2]\d:[0-5]\d)\s+(.+)/',
                        // dates do no have time, in YYYY MM DD format: 1995 02 02
                        '/(([-dls])[rxw-]{9})\s+(\d+)\s+(\S+)\s+(\S+)\s+(\d+)\s+((?:19|20)\d\d[- \.](?:0[1-9]|1[012])[- \.](?:0[1-9]|[12][0-9]|3[01]))\s+(.+)/'
                    );
        
        $matches = array();
        $reg = 0;
        foreach($patterns as $pattern) {
            $matches = array();
            $reg = preg_match($pattern, $list, $matches);
            if ($reg) break;
        }
        
        if ($reg) {
            $result['rawperms'] = $matches[1];
            switch($matches[2]) {
                case 'd':
                    $type = self::TYPE_DIR;
                    break;
                case 'l':
                    $type = self::TYPE_LINK;
                case 's':
                    $type = self::TYPE_SOCKET;
                default:
                    $type = self::TYPE_FILE;
            }
            $result['type'] = $type;
            unset($type);
            $result['links'] = intval($matches[3]);
            $result['owner'] = $matches[4];
            $result['group'] = $matches[5];
            $result['size'] = intval($matches[6]);
            $result['mdtm'] = strtotime($matches[7]);
            $result['name'] = $matches[8];
        }
        return $result;
    } // public function parseFileInfo()
    
    /**
     * Lists contents of the specified path recursively and returns an associative
     * array. Keys are filenames.
     * 
     * For none-directory files the value is the file type.
     * For directories, the value is an array listing files in the directory.
     * 
     * A maximum limit for recursion could be specified by the depth limit param.
     * Default depth limit is 512.
     * If the recursion limit is reached, the directories are returned as
     * normal files, but their values (types) differ them from normal files.
     * 
     * By default entities in subdirectories are returned as their relative name
     * to their parent directories, but this could be changed by the absPaths param.
     * 
     * @see     recursiveRawList()
     * @see     nlist()
     * 
     * @param   string      $path
     * @param   int         $depthLimit
     * @param   bool        $absPaths       if should generate absolute paths
     * @return  associative array|false
     */
    public function recursiveList($path=null, $depthLimit=512, $absPaths=false)
    {
        $this->_initOperation();
        if (!$path) $path = $this->pwd();
        $this->_log("getting contents list from directory {$path} recursively", LOG_DEBUG);
        $result = array();
        $entities = $this->rawlist($path);
        
        if (!$entities) return false;
        
        foreach($entities as $entity) {
            $info = $this->parseFileInfo($entity);
            
            if (!$info['name']) continue;
            
            if ($absPaths) {
                $name = $path . '/' . $info['name'];
            } else {
                $name = $info['name'];
            }
            
            $limit = $depthLimit;
            if (($info['type'] == self::TYPE_DIR) && (0 < $limit)) {
                $contents = $this->recursiveList($path . '/' . $info['name'], --$limit, $absPaths);
                if ($contents == false) {
                    $contents = $info['type'];
                }
            } else {
                $contents = $info['type'];
            }
            
            $result[$name] = $contents;
        }
        
        return $result;
    } // public function recursiveList()
    
    /**
     * Lists contents of the specified path recursively and returns an associative
     * array.
     * Keys in the array are filenames. values are different for directories and
     * other file types.
     * 
     * For none-directory files the value is the output of rawlist for the file.
     * 
     * For directories, the value is an array with 2 indexes. The first index
     * is a string value output of rawlist for the directory. The seconds index
     * is an associative array describing contents of the directory, in the same
     * manner of the top level arary.
     * 
     * A maximum limit for recursion could be specified by the depth limit param.
     * Default depth limit is 512.
     * If the depth limit is reached, the directories indexes would have a value
     * of false instead of their array of files (the seconds index), to signal
     * the recursion limit is reached.
     * 
     * By default entities in subdirectories are returned as their relative name
     * to their parent directories, but this could be changed by the absPaths param.
     * 
     * @see     recursiveList()
     * @see     rawlist()
     * 
     * @param   string  $path
     * @param   int     $depthLimit
     * @param   bool    $absPaths       if should generate absolute paths
     * @return  associative array
     */
    public function recursiveRawList($path=null, $depthLimit=512, $absPaths=false)
    {
        $this->_initOperation();
        if (!$path) $path = $this->pwd();
        $this->_log("getting raw list from directory {$path} recursively", LOG_DEBUG);
        $result = array();
        $entities = $this->rawlist($path);
        if (!$entities) return $entities;
        
        foreach($entities as $entity) {
            $info = $this->parseFileInfo($entity);
            if (!$info['name']) continue;
            $limit = $depthLimit;
            
            if ($absPaths) {
                $name = $path . '/' . $info['name'];
            } else {
                $name = $info['name'];
            }
            
            if ($info['type'] == self::TYPE_DIR) {
                if (0 >= $limit) {
                    $_files = false;
                } else {
                    $_files = $this->recursiveRawList($path . '/' . $info['name'], --$limit, $absPaths);
                }
                $contents = array($entity, $_files);
                unset($_files);
            } else {
                $contents = $entity;
            }
            $result[$name] = $contents;
        }
        
        return $result;
    } // public function recursiveRawList()
    
    /**
     * Deletes a remote file
     * 
     * @param   string  $remote
     * @return  Parsonline_Network_FTPClient
     * @throws  Parsonline_Exception
     */
    public function delete($remote)
    {
        $this->_initOperation();
        $this->_log("deleting remote file '{$remote}'", LOG_DEBUG);
        if ( !ftp_delete($this->_ftp, $remote) ) {
            /**
             * @uses    Parsonline_Exception
             */
            require_once('Parsonline/Exception.php');
            throw new Parsonline_Exception("Failed to delete remote file '{$remote}'");
        }
        return $this;
    } // public function delete()
    
    /**
     * Deletes a remote empty directory.
     * 
     * @param   string  $remote
     * @return  Parsonline_Network_FTPClient
     * @throws  Parsonline_Exception
     */
    public function rmdir($remote)
    {
        $this->_initOperation();
        $this->_log("deleting remote directory '{$remote}'", LOG_DEBUG);
        if ( !ftp_rmdir($this->_ftp, $remote) ) {
            /**
             * @uses    Parsonline_Exception
             */
            require_once('Parsonline/Exception.php');
            throw new Parsonline_Exception("Failed to delete remote directory '{$remote}'");
        }
        return $this;
    } // public function rmdir()
    
    /**
     * Renames a remote file
     * 
     * @param   string  $remote
     * @param   string  $name
     * @return  Parsonline_Network_FTPClient
     * @throws  Parsonline_Exception
     */
    public function rename($remote, $name)
    {
        $this->_initOperation();
        $this->_log("renaming remote file '{$remote}'", LOG_DEBUG);
        if ( !ftp_rename($this->_ftp, $remote, $name) ) {
            /**
             * @uses    Parsonline_Exception
             */
            require_once('Parsonline/Exception.php');
            throw new Parsonline_Exception("Failed to rename remote file '{$remote}'");
        }
        return $this;
    } // public function rename()
    
    /**
     * Executes a command on remote server.
     * 
     * @param   string  $command
     * @return  bool
     */
    public function exec($command)
    {
        $this->_initOperation();
        $this->_log("executing command '{$command}' on remote server", LOG_DEBUG);
        return ftp_exec($this->_ftp, $command);
    } // public function exec()
    
    /**
     * Allocates space so files would be uploaded on remote host.
     * Many servers do not suppor this method.
     * 
     * @param   string  $size
     * @return  Parsonline_Network_FTPClient
     * @throws  Parsonline_Exception
     */
    public function alloc($size)
    {
        $this->_initOperation();
        $this->_log("requesting storage allocation for '{$size}' bytes", LOG_DEBUG);
        $msg = '';
        if ( !ftp_alloc($this->_ftp, $size, $msg) ) {
            /**
             * @uses    Parsonline_Exception
             */
            require_once('Parsonline/Exception.php');
            throw new Parsonline_Exception("Failed to allocate storage for '{$size}' bytes. message: " . $msg);
        }
        return $this;
    } // public function alloc()
    
    /**
     * Returns FTP specific options of the connection.
     * Runtime options are:
     * 
     *  FTP_TIMEOUT_SEC	 Returns the current timeout used for network related operations. 
     *  FTP_AUTOSEEK	 Returns TRUE if this option is on, FALSE otherwise
     * 
     * @param   int $option
     * @return  mixed
     */
    public function getFTPOption($option)
    {
        $this->_initOperation();
        return ftp_get_option($this->_ftp, $option);
    }
    
    /**
     * Sets a FTP specific options of the connection.
     * 
     * Runtime options are:
     * 
     *  FTP_TIMEOUT_SEC	 Changes the timeout in seconds used for all network related functions.
     *  FTP_AUTOSEEK	 When enabled, GET or PUT requests with a resumepos or startpos parameter will first seek to the requested position within the file. This is enabled by default
     * 
     * @param   int     $option
     * @param   mixed   $value
     * @return  bool
     */
    public function setFTPOption($option, $value)
    {
        $this->_initOperation();
        return ftp_set_option($this->_ftp, $option, $value);
    }
    
    /**
     * Downloads a remote file to a local path in an atomic process.
     * 
     * @see download()
     * 
     * @param   string      $local      local path
     * @param   string      $remote     remote path
     * @param   int         $mode       get mode, use FTP_BINARY|FTP_ASCI
     * @param   int         $resume     position for resume download
     * @return  Parsonline_Network_FTPClient
     * 
     * @throws  Parsonline_Exception_IOException on failed to get the file
     */
    public function get($local, $remote, $mode=FTP_BINARY, $resume=0)
    {
        $this->_initOperation();
        $this->_log("getting remote file '{$remote}' to local '{$local}'");
        if ( !ftp_get($this->_ftp, $local, $remote, $mode, $resume) ) {
            /**
             * @uses    Parsonline_Exception_IOException
             */
            require_once('Parsonline/Exception/IOException.php');
            throw new Parsonline_Exception_IOException("Failed to get remote file '{$remote}' to local '{$local}'");
        }
        return $this;
    } // public function get()
    
    /**
     * Uploads a local file to a remote path in an atomic process.
     * 
     * @see upload()
     * 
     * @param   string      $remote     remote path
     * @param   string      $local      local path
     * @param   int         $mode       get mode, use FTP_BINARY|FTP_ASCI
     * @param   int         $start      position of the start of upload
     * @return  Parsonline_Network_FTPClient
     * 
     * @throws  Parsonline_Exception_IOException on failed to get the file
     */
    public function put($remote, $local, $mode=FTP_BINARY, $start=0)
    {
        $this->_initOperation();
        $this->_log("putting local file '{$local}' to remote '{$remote}'");
        if ( !ftp_put($this->_ftp, $remote, $local, $mode, $start) ) {
            /**
             * @uses    Parsonline_Exception_IOException
             */
            require_once('Parsonline/Exception/IOException.php');
            throw new Parsonline_Exception_IOException(
                "Failed to get remote file '{$remote}' to local '{$local}'"
            );
        }
        return $this;
    } // public function put()
    
    /**
     * Downloads a remote file to a local path, notifying a callable on each
     * chunk downloaded.
     * 
     * The callback should accept an integer value for total size of remote
     * file (could be -1 if failed to detect), and an integer value for size
     * of downloaded file.
     * 
     * @see     get()
     * 
     * @param   string      $local          local path
     * @param   string      $remote         remote path
     * @param   int         $mode           get mode, use FTP_BINARY|FTP_ASCI
     * @param   int         $resumepos      position for resume download
     * @param   callable    $callback       a callable to be notified on updates
     * @param   bool        $rmIncomplete   remove local icomplete downloaded file
     * 
     * @return  Parsonline_Network_FTPClient
     * 
     * @throws  Parsonline_Exception_IOException on failed to download the whole file
     */
    public function download($local, $remote, $mode=FTP_BINARY, $resumepos=0, $callback=null, $rmIncomplete=false)
    {
        $this->_initOperation();
        $this->_log("downloading remote file '{$remote}' to local '{$local}'");
        $totalSize = $this->size($remote);
        $download = ftp_nb_get($this->_ftp, $local, $remote, $mode, $resumepos);
        while ($download == FTP_MOREDATA) {
            clearstatcache(true, $local);
            $size = filesize($local);
            $this->_log("downloaded {$size}/{$totalSize} bytes of remote file '{$remote}' to local '{$local}'", LOG_DEBUG);
            if ($callback) {
                call_user_func($callback, $totalSize, $size);
            }
            $download = ftp_nb_continue($this->_ftp);
        }
        if ($download != FTP_FINISHED) {
            if ($rmIncomplete) {
                $this->_log("removing incomplete downloaded local file '{$local}'", LOG_DEBUG);
                unlink($local);
            }
            /**
             * @uses    Parsonline_Exception_IOException
             */
            require_once('Parsonline/Exception/IOException.php');
            throw new Parsonline_Exception_IOException(
                "Failed to download remote file '{$remote}' to local '{$local}'"
            );
        }
        return $this;
    } // public function download()
    
    /**
     * Uploads a local file to a remote path, notifying a callable on each
     * chunk uploaded.
     * 
     * The callback should accept an integer value for total size of file
     * (could be false if failed to detect), and an integer value for size
     * of uploaded file (could be -1 if failed to detect).
     * 
     * @see     get()
     * 
     * @param   string      $remote         remote path
     * @param   string      $local          local path
     * @param   int         $mode           get mode, use FTP_BINARY|FTP_ASCI
     * @param   int         $startpos       position to start uploading
     * @param   callable    $callback       a callable to be notified on updates
     * @param   bool        $rmIncomplete   remove remote icomplete uploaded file
     * 
     * @return  Parsonline_Network_FTPClient
     * 
     * @throws  Parsonline_Exception_IOException on failed to download the whole file
     */
    public function upload($remote, $local, $mode=FTP_BINARY, $startpos=0, $callback=null, $rmIncomplete=false)
    {
        $this->_initOperation();
        $this->_log("uploading file '{$local}' to remote '{$remote}'");
        $totalSize = filesize($local);
        $prog = ftp_nb_put($this->_ftp, $remote, $local, $mode, $startpos);
        while ($prog == FTP_MOREDATA) {
            $size = $this->size($remote);
            $this->_log("uploaded {$size}/{$totalSize} bytes of file '{$local}' to remote '{$remote}'", LOG_DEBUG);
            if ($callback) {
                call_user_func($callback, $totalSize, $size);
            }
            $prog = ftp_nb_continue($this->_ftp);
        }
        if ($prog != FTP_FINISHED) {
            if ($rmIncomplete) {
                $this->_log("removing incomplete uploaded remote file '{$local}'", LOG_DEBUG);
                unlink($local);
            }
            /**
             * @uses    Parsonline_Exception_IOException
             */
            require_once('Parsonline/Exception/IOException.php');
            throw new Parsonline_Exception_IOException(
                "Failed to download remote file '{$remote}' to local '{$local}'"
            );
        }
        return $this;
    } // public function upload()
    
    /**
     * Downloads a remote directory to a local path. All files in the remote
     * directory would be downloaded.
     * subdirectories would be downloaded into a local file system
     * structure just like the remote one, if the recursion depth limit
     * is not reached.
     * 
     * Accepts a callback function for progress updates.
     * the callback is passed the total number of files
     * and the number of downloaded files.
     * 
     * Returns an associative array whose keys are the remote paths to download
     * and values are their local downloaded files names, relative to local base
     * path.
     * 
     * If set to catchFailures, the failed downloads are included in the returning
     * array, yet their values (local paths) are set to the caught exception.
     * 
     * If set to cactch failures, then it is possible to make the connection reset
     * maybe the next downloads would not fail.
     * 
     * @param   string      $localPath  local path
     * @param   string      $remotePath     remote path
     * @param   int         $mode           FTP_BINARY|FTP_ASCII
     * @param   bool        $depthLimit     limit recursion depth
     * @param   callable    $callback       callback function after each download
     * @param   bool        $catchFailed    catch failure exceptions
     * @param   bool        $autoReconnect  if exception caught, reconnect FTP
     * 
     * @return  associative array
     * 
     * @throws  Parsonline_Exception_IOException on failed to get the file
     */
    public function downloadDirectory($localPath, $remotePath, $mode=FTP_BINARY, $depthLimit=512, $callback=null, $catchFailed=false, $autoReconnect=false)
    {
        $this->_initOperation();        
        $this->_log("Downloading remote directory '{$remotePath}' to local '{$localPath}'");
        
        if ( !$this->isDirectory($remotePath) ) {
            /**
             * @uses    Parsonline_Exception
             */
            require_once('Parsonline/Exception.php');
            throw new Parsonline_Exception(
                "Failed to download the remote path. {$remotePath} is not a directory"
            );
        }
        
        if ( !file_exists($localPath) ) {
            mkdir($localPath);
        }
        
        $downloadedFiles = array();
        $entities = $this->recursiveList($remotePath, $depthLimit, true);
        
        if (!$entities) return $downloadedFiles;
        
        /**
         * @uses Parsonline_Utils_ArrayCalculations
         */
        require_once('Parsonline/Utils/ArrayCalculations.php');
        $totalFiles = Parsonline_Utils_ArrayCalculations::getDeepCount($entities, null, true);
        $totalFiles--; // remove the top level counting. read getDeepCount() documentation.
        
        foreach($entities as $_remoteFilename => $type) {
            $_localFilename = $localPath . '/' . basename($_remoteFilename);
            $_limit = $depthLimit;
            $this->_log("Downloading remote path '{$_remoteFilename}' to local '{$_localFilename}'");
            if ( is_array($type) ) { // remote direcotry
                if ( !file_exists($_localFilename) ) {
                    mkdir($_localFilename);
                }
                $_downloadedFilesInSubdir = $this->downloadDirectory($_localFilename, $_remoteFilename, $mode, --$_limit, null, $catchFailed, $autoReconnect);
                foreach ($_downloadedFilesInSubdir as $_remotePathInSubdir => $_localFileInSubdir) {
                    $downloadedFiles[$_remotePathInSubdir] = $_localFileInSubdir;
                }
                unset($_downloadedFilesInSubdir);
            } elseif ($type == self::TYPE_DIR) {
                if ( !file_exists($_localFilename) ) {
                    mkdir($_localFilename);
                }
            } else {
                if ($catchFailed) {
                    /**
                     * @uses    Parsonline_Exception_IOException
                     */
                    require_once('Parsonline/Exception/IOException.php');
                    try {
                        $this->get($_localFilename, $_remoteFilename, $mode);
                    } catch(Parsonline_Exception_IOException $exp) {
                        $downloadedFiles[$_remoteFilename] = $exp;
                        if ($autoReconnect) $this->_getConnection(true);
                    }
                } else {
                    $this->get($_localFilename, $_remoteFilename, $mode);
                }
            }
            $downloadedFiles[$_remoteFilename] = $_localFilename;
            if ($callback) call_user_func($callback, $totalFiles, count($downloadedFiles));
        }
        
        return $downloadedFiles;
    } // public function downloadDirectory()
}