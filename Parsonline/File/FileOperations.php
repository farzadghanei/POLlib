<?php
//Parsonline/File/FileOperations.php
/**
 * Defines Parsonline_File_FileOperations class.
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
 * @package     File
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     3.0.0 2012-07-07
 */

/**
 * @uses  Parsonline_System
 * @uses  Parsonline_ZipArchiveImproved
 */
require_once("Parsonline/System.php");
require_once("Parsonline/ZipArchiveImproved.php");

/**
 * Parsonline_File_FileOperaionts
 * 
 * Provides static methods to operate on filesystems.
 */
class Parsonline_File_FileOperations
{
    /**
     * searches for a specific string file name in a base path.
     * Returns an associative array with keys:
     *   'found': array of paths
     *   'error': array of exceptions
     * 
     * 
     * @param   string      $base       base dir to start the search
     * @param   string      $search     the name of the desired file/directories
     * @return  array    associative array of found results and errors.
     */
    public static function search($base, $search)
    {
        $base = strval($base);
        $search = strval($search);
        $results = array();
        $results['error'] = array();
        $results['found'] = array();
        if ( empty($base) || !file_exists($base) || !is_readable($base) ) {
            $results['error'][] = new Exception("Failed to search. base path '$base' is not a readable path");
            return $results;
        }
        if ( basename($base) == $search ) $results['found'][] = $base;
        if ( is_file($base) ) {
            return $results;
        }
        $dirHandle = opendir($base);
        if ( !is_resource($dirHandle) ) {
            $results['error'][] = new Exception("Failed to open a handle to directory '$base'");
            return $results;
        }
        
        while ( ($fileName = readdir($dirHandle)) !== false ) {
            if ($fileName === '.' || $fileName === '..') {
                continue;
            }
            $canonicalFileName = $base . DIRECTORY_SEPARATOR . $fileName;
            $subFilesResult = self::search($canonicalFileName,$search);
            if ( is_array($subFilesResult['error']) && !empty($subFilesResult['error']) ) $results['error'] = array_merge($results['error'], $subFilesResult['error']);
            if ( is_array($subFilesResult['found']) && !empty($subFilesResult['found']) ) $results['found'] = array_merge($results['found'], $subFilesResult['found']);
        } // while
        closedir($dirHandle);
        return $results;
    } // public static function search()

    /**
     * Copies a files and subdirectories in a directory recuresively to a
     * destination directory.
     * Returns the number of copied files.
     * 
     * If the destination path does not exist, creates it.
     * 
     * @param   string    $source
     * @param   string    $destination
     * @return  integer
     * @throws  Parsonline_Exception_ValueException on no readable soure directory
     * @throws  Parsonline_Exception_SystemException on failure to copy or access
     *          source or destination paths.
     */
    public static function copyDir($source, $destination)
    {
        if ( !file_exists($source) || !is_dir($source) || !is_readable($source) ) {
            /**
             * @uses    Parsonline_Exception_ValueException
             */
            require_once("Parsonline/Exception/ValueException.php");
            throw new Parsonline_Exception_ValueException(
                "Failed to copy directory '$source'. source directory is not a readable directory path"
            );
        }
        
        if( !is_dir($destination) ) {
            if ( !mkdir($destination) ) {
                /**
                 *@uses Parsonline_Exception_SystemException 
                 */
                require_once("Parsonline/Exception/SystemException.php");
                throw new Parsonline_Exception_SystemException("could not create destination directory '$destination'");
            }
        }
        
        $curdir = opendir($source);
        if ( !$curdir ) {
            /**
             *@uses Parsonline_Exception_SystemException 
             */
            require_once("Parsonline/Exception/SystemException.php");
            throw new Parsonline_Exception_SystemException("could not open the source directory '$source'");
        }
        $num = 0;
        while ( $file = readdir($curdir) ) {
            if ( $file == '.' || $file == '..' ) {
                continue;
            }
            $sourceFile = $source . DIRECTORY_SEPARATOR . $file;
            $destinationFile = $destination . DIRECTORY_SEPARATOR . $file;
            
            if ( is_dir($sourceFile) ) {
                $num += self::copyDir($sourceFile, $destinationFile);
            } else {
                if ( file_exists($destinationFile) ) {
                    $overWrite = filemtime($sourceFile) - filemtime($destinationFile);
                } else {
                    $overWrite = 1;
                }
                
                if ( $overWrite > 0 ) {
                    if ( copy($sourceFile,$destinationFile) ) {
                        $num++;
                    } else {
                        /**
                        *@uses Parsonline_Exception_SystemException 
                        */
                        require_once("Parsonline/Exception/SystemException.php");
                        throw new Parsonline_Exception_SystemException("Failed to copy the file '$sourceFile' to '$destinationFile'");
                    }
                }
            }
        } // while
        closedir($curdir);
        return $num;
    } // public static function copyDir()
    
    /**
     * Deletes a file or directory tree.
     * Returns an associative array to report the operations.
     * 
     * Keys are:
     *   files: an array of deleted filenames
     *   directories: an array of deleted directories
     *   errors: is an array of exceptions occurred.
     * 
     * Note: If the target does not exist, all of the result sub arrays are going to
     * be empty.
     * 
     * @param   string  $target file/directory name
     * @param   bool    $force  if delete should try to delete read-only files.
     * @return  mixed   associative array
     */
    public static function delete($target, $force=false)
    {
        $target = strval($target);
        $force = true && $force;
        $results = array();
        $results['files'] = array();
        $results['directories'] = array();
        $results['errors'] = array();
        
        if ( empty($target) || !file_exists($target) ) {
            return $results;
        }
        
        if ( !is_writable($target) ) {
            $writable = false;
            if ($force) {
                if ( chmod($target, 0664) ) {
                    $writable = true;
                }
            }
            if (!$writable) {
                /**
                 *@uses Parsonline_Exception_IOException 
                 */
                require_once("Parsonline/Exception/IOException.php");
                $results['errors'][] = new Parsonline_Exception_IOException("The path '$target' is not writable");
                return $results;
            }
        }

        if ( is_file($target) ) {
            if ( !unlink($target) ) {
                /**
                 *@uses Parsonline_Exception_IOException 
                 */
                require_once("Parsonline/Exception/IOException.php");
                $results['errors'][] = new Parsonline_Exception_IOException("Failed to delete file '$target'");
            } else {
                $results['files'][] = $target;
            }
            return $results;
        }
        $dirHandle = opendir($target);
        if ( $dirHandle ) {
            /**
            *@uses Parsonline_Exception_IOException 
            */
            require_once("Parsonline/Exception/IOException.php");
            $results['errors'][] = new Parsonline_Exception_IOException("Failed to opedn directory '$target'");
            return $results;
        }
        
        while ( ($filename = readdir($dirHandle)) !== false ) {
            if ($filename === '.' || $filename === '..') {
                continue;
            }
            $filepath = $target . DIRECTORY_SEPARATOR . $filename;
            $subFilesResult = self::delete($filepath, $force);
            
            if ( is_array($subFilesResult['files']) && $subFilesResult['files'] ) {
                $results['files'] = array_merge($results['files'], $subFilesResult['files']);
            }
            if ( is_array($subFilesResult['directories']) && $subFilesResult['directories'] ) {
                $results['directories'] = array_merge($results['directories'], $subFilesResult['directories']);
            }
            if ( is_array($subFilesResult['errors']) && $subFilesResult['errors'] ) {
                $results['errors'] = array_merge($results['errors'], $subFilesResult['errors']);
            }
        }
        closedir($dirHandle);
        
        if ( empty($results['errors']) ) {
            rmdir($target);
            $results['directories'][] = $target;
        }
        
        return $results;
    } // public static function delete()

    /**
     * Function to recursively add a directory, sub-directories and files
     * to a zip archive
     * 
     * @param   string      path of directory to add to ZIP
     * @param   ZipArchive  a ZipArchive object to add files to
     * @param   string      the directory in ZipArchive in which files are going to be added
     * @return  integer     number of files added to archive
     * @throws  Parsonline_Exception_IOException on failure to access directory files
     */
    private static function addDirectoryToZip($dirpath, ZipArchive $zipArchive, $dirInZipFile='')
    {
        if ( !is_dir($dirpath) || !is_readable($dirpath) ) {
            /**
             *@uses  Parsonline_Exception_IOException
             */
            require_once("Parsonline/Exception/IOException.php");
            throw new Parsonline_Exception_IOException("The path '$dirpath' is not a readable directory");
        }
        
        $dirHandle = opendir($dirpath);
        if ( !is_resource($dirHandle) ) {
            /**
             *@uses  Parsonline_Exception_IOException
             */
            require_once("Parsonline/Exception/IOException.php");
            throw new Parsonline_Exception_IOException("Failed to open directory '$dirpath'");
        }
        
        $fileCounter = 0;
        $zipArchive->addEmptyDir($dirpath);
        while ( ($filename = readdir($dirHandle)) !== false ) {
            if ($filename === '.' || $filename === '..') {
                continue;
            }
            $filePath = $dirpath . DIRECTORY_SEPARATOR . $filename;
            $filenameInZip = empty($dirInZipFile) ? $filename : ($dirInZipFile.'/'.$filename);
            if( !is_file($filePath) ){
                $fileCounter += self::addDirectoryToZip($filePath, $zipArchive, $filenameInZip);
            } else {
                $filenameInZip = $dirInZipFile . '/' . $filename;
                $zipArchive->addFile($filePath, $filenameInZip);
                $fileCounter++;
            }
        }
        closedir($dirHandle);
        return $fileCounter;
    } // private static function addFolderTozip()

    /**
     * Creates a ZIP backup of a source path (directory/file)
     * and returns the number of files added to ZIP archive.
     *
     * @param   string  $source
     * @param   string  $destination
     * @return  integer
     * @throws  Parsonline_Exception_IOException on failure to access source
     * @throws  Parsonline_Exception on failure to add files to ZIP
     */
    public static function archiveToZIP($source, $destination='')
    {
        if ( empty($source) || !file_exists($source) || !is_readable($source) ) {
            /**
             *@uses  Parsonline_Exception_IOException
             */
            require_once("Parsonline/Exception/IOException.php");
            throw new Parsonline_Exception_IOException("The source path '$source' is not a readable path");
        }
        if ( empty($destination) ) {
            $destination = $source . '-' . date('Y-m-d-H-i-s') . '.zip';
        } else {
            $destination = (string) $destination;
        }
        
        /**
         *@uses Parsonline_ZipArchiveImproved 
         */
        $zipHandler = new Parsonline_ZipArchiveImproved();
        if ( $zipHandler->open($destination, Parsonline_ZipArchiveImproved::OVERWRITE) !== true ) {
            /**
             * @uses    Parsonline_Exception
             */
            require_once("Parsonline/Exception.php");
            throw new Parsonline_Exception("Failed to open handler to zip file on '{$destination}'");
        }
        
        // source is a file
        if ( is_file($source) ) {
            $filename = basename($source);
            if ( !$zipHandler->addFile($source,$filename) ) {
                /**
                * @uses    Parsonline_Exception
                */
                require_once("Parsonline/Exception.php");
                throw new Parsonline_Exception("Failed to add '{$source}' to '{$filename}' in ZIP file onfile on '{$destination}'");
            }
            $zipHandler->close();
            return 1;
        }
        // source is a directory
        $sourceFilename = $source;
        $lastDirCharPos = strrpos($source,DIRECTORY_SEPARATOR);
        if ( $lastDirCharPos !== false ) {
            $sourceFilename = substr($source, $lastDirCharPos + 1);
        }
        $fileCounter = self::addDirectoryToZip($source, $zipHandler, $sourceFilename);
        $zipHandler->close();
        return $fileCounter;
    }

    /**
     * Appends a string to end of a file and returns number of bytes wrote
     * to the file.
     *
     * @param   string  $filename
     * @param   string  $content
     * @return  int
     * @throws  Exception
     */
    public static function appendContent($filename, $content)
    {
        if ( !file_exists($filename) || !is_file($filename) || !is_writable($filename) ) {
            /**
             *@uses  Parsonline_Exception_IOException
             */
            require_once("Parsonline/Exception/IOException.php");
            throw new Parsonline_Exception_IOException("The path '$filename' is not a writable file");
        }
        
        if ( empty($content) ) {
            return 0;
        }
        
        $result = file_put_contents($filename, $content, FILE_APPEND);
        
        if ( $result === false ) {
            /**
             *@uses  Parsonline_Exception_IOException
             */
            require_once("Parsonline/Exception/IOException.php");
            throw new Parsonline_Exception_IOException("Failed to write data to file '$filename'");
        }
        return $result;
    }

    /**
     * Prepends a string to beginning of a file.
     *
     * @param   string  $filename
     * @param   string  $content
     * @return  int new file size in bytes
     * @throws  Parsonline_Exception_SystemException if reading the file into memory would
     *          exceed memory limit
     * @throws  Parsonline_Exception_IOException on failure to read/write to file          
     */
    public static function prependContent($filename, $content)
    {
        if ( !file_exists($filename) || !is_file($filename) || !is_writable($filename) ) {
            /**
             *@uses  Parsonline_Exception_IOException
             */
            require_once("Parsonline/Exception/IOException.php");
            throw new Parsonline_Exception_IOException("The path '$filename' is not a writable file");
        }
        
        if ( empty($content) ) {
            return filesize($filename);
        }
        
        if ( (filesize($filename) + strlen($content)) > Parsonline_System::getAvailableMemory() ) {
            /**
             * @uses    Parsonline_Exception_SystemException
             */
            require_once("Parsonline/Exception/SystemException");
            throw new Parsonline_Exception_SystemException("Woud not prepend contents to file '$filename' to avoid exceeding memory limit");
        }
        $fileInitialContent = file_get_contents($filename);
        if ( $fileInitialContent === false  ) {
            /**
             *@uses  Parsonline_Exception_IOException
             */
            require_once("Parsonline/Exception/IOException.php");
            throw new Parsonline_Exception_IOException("Failed to read contents of file '$filename'");
        }
        $result = file_put_contents($filename, $content . $fileInitialContent);
        if ( $result === false ) {
            /**
             *@uses  Parsonline_Exception_IOException
             */
            require_once("Parsonline/Exception/IOException.php");
            throw new Parsonline_Exception_IOException("Failed to write data to file '$filename'");
        }
        return $result;
    }

    /**
     * Returns true if a file is modified before a specific time or not.
     *
     * @param   string      $filename
     * @param   float       $time       unix timestamp, or microtime
     * @return  bool
     * @throws  Parsonline_Exception_IOException on failure to access file data
     */
    public static function isFileOlderThan($filename, $time=null)
    {
        if ($time === null) {
            $time = time();
        } else {
            $time = floatval($time);
        }
        $filename = strval($filename);
        if ( !file_exists($filename) ) {
            /**
             *@uses  Parsonline_Exception_IOException
             */
            require_once("Parsonline/Exception/IOException.php");
            throw new Parsonline_Exception_IOException("Failed to access file '$filename'");
        }
        $fileModifiedTime = filemtime($filename);
        if ($fileModifiedTime === false) {
            /**
             *@uses  Parsonline_Exception_IOException
             */
            require_once("Parsonline/Exception/IOException.php");
            throw new Parsonline_Exception_IOException("Failed to read modification time of file '{$filename}'");
        }
        return floatval($fileModifiedTime) < $time;
    }

    /**
     * Returns the extension of a file.
     * returns null if could not detect extension of the file.
     *
     * @param   string      $filename
     * @return  string|null
     */
    public static function getFileExtenstion($filename)
    {
        if (!$filename) return null;
        $parts = explode(".", $filename);
        if ($parts) {
            return array_pop($parts);
        }
        return null;
    } // public statif function getFileExtension

    /**
     * Returns an array of files in the target directory path.
     * directories and links are ignored and only files are returned.
     * an optional array of extensions can be used as a white list,
     * so only those kinds of files will be returned.
     * an optional array of extensions can be used as a black list, so all files
     * will be returned other than those with the specified extensions.
     *
     * @param   string              $path               path to the directory. if no path is specified, current working directory will be used.
     * @param   array|string        $extWhiteList       string, or an array of file extensions to return
     * @param   array|string        $extBlackList       string, or an array of file extensions to ignore
     * @return  array       array of filenames. filenames are relative to the specified path.
     * @throws  Parsonline_Exception_IOException if failed to access path
     */
    public static function listDirFiles($path='', $extWhiteList=array(), $extBlackList=array() )
    {
        if (!$path) $path = getcwd();
        if ( !file_exists($path) || !is_dir($path) || !is_readable($path) || !is_executable($path) ) {
            /**
             *@uses  Parsonline_Exception_IOException
             */
            require_once("Parsonline/Exception/IOException.php");
            throw new Parsonline_Exception_IOException("Failed to list files in path '$path'. path is not a readable directory.");
        }
        if ( $extWhiteList && !is_array($extWhiteList) ) $extWhiteList = array(strval($extWhiteList));
        if ( $extBlackList && !is_array($extBlackList) ) $extBlackList = array(strval($extBlackList));

        $result = array();
        /**
        * @uses   DirectroyIterator
        */
        $dirIterator = new DirectoryIterator($path);
        foreach ($dirIterator as $file) {
            /**
             * @var     $file    DirectoryIterator
             */
            if ( !$file->isFile() ) continue;
            $canonicalName = $file->getPathname();
            try {
                $extension = self::getFileExtenstion($canonicalName);
            } catch(Exception $exp) {
                continue;
            }
            if ( $extWhiteList && !in_array($extension, $extWhiteList) ) continue;
            if ( $extBlackList && in_array($extension, $extBlackList) ) continue;
            array_push( $result, $file->getFilename() );
        }
        return $result;
    }
}∂