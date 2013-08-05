<?php
/**
 * defines the Parsonline_System_System class.
 *
 * @copyright   Copyright 2010 ParsOnline Inc.
 * @license     all rights reserved.
 * @author      Farzad Ghanei <f.ghanei@parsonline.com>
 * @version     0.0.5 2010-03-06
 * @package     Parsonline
 * @subpackage  System
 */

/**
 * functionalities to give system information in other scripts.
 */
class Parsonline_System_System
{
    /**
     * returns short name of the operating system.
     *
     * @return string
     *		win for Windows
     *		mac for Mac OS
     *		nix for unix like systems
     */
    public static function getOsType()
    {
        $os = strtolower( PHP_OS );
        if ( strpos($os,'mac') !== false || strpos($os, 'darwin') !== false ) {
            $os = "mac";
        } elseif ( strpos($os,'win') !== false ) {
            $os = "win";
        } else {
            $os = "nix";
        }
        return $os;
    } // public static function getOsType()


    /**
     * returns newline character based on the operating system.
     *
     * @return string
     */
    public static function getNewLine()
    {
        $os = strtolower( PHP_OS );
        if ( strpos($os,'win') !== false ) {
            $newline = "\r\n";
        } elseif ( strpos($os,'mac') ) {
            $newline = "\r";
        } else {
            $newline = "\n";
        }
        return $newline;
    } // public static function getNewLine()

    /**
     * returns the path to system temp directory.
     *
     * @return string
     */
    public static function getTempDir()
    {
        /**
         * thanks to http://www.php.net/manual/en/function.sys-get-temp-dir.php#85261
         */
        if ( !function_exists('sys_get_temp_dir') ) {
            function sys_get_temp_dir() {
                if (!empty($_ENV['TMP'])) return realpath($_ENV['TMP']);
                if (!empty($_ENV['TMPDIR'])) return realpath( $_ENV['TMPDIR']);
                if (!empty($_ENV['TEMP'])) return realpath( $_ENV['TEMP']);
                return null;
            } // function sys_get_temp_dir()
        }
        return sys_get_temp_dir();
    } // public static function getTempDir

    /**
     * returns the ammount of memory limit that a PHP script can achieve in bytes.
     *
     * @return int
     */
    public static function getMemoryLimit()
    {
        $maxMemory = strtolower( ini_get('memory_limit') );
        if ( strpos($maxMemory,'k') !== false ) {
            $maxMemory = intval($maxMemory) * 1024;
        } elseif ( strpos($maxMemory,'m') !== false ) {
            $maxMemory = intval($maxMemory) * 1048576;
        } elseif ( strpos($maxMemory,'g') !== false ) {
            $maxMemory = intval($maxMemory) * 1073741824;
        } else {
            $maxMemory = intval($maxMemory);
        }
        return $maxMemory;
    }

    /**
     * returns the ammount of memory available to be used, base on the maximum memory limit for PHP scripts
     * and the currently memory beeing used by the current script process.
     *
     * @return int
     */
    public static function getAvailableMemory()
    {
        return self::getMemoryLimit() - self::getMemoryUsage();
    } // public static function getAvailableMemory()

    /**
     * returns the ammount of memory currently beeing used by the PHP script process.
     * this is based on a code from:
     * http://ir.php.net/manual/en/function.memory-get-usage.php#64156
     *
     * @return int memory usage | false incase of error
     */
    public static function getMemoryUsage()
    {
        if ( self::getOsType() === 'nix' && function_exists('memory_get_usage') ) {
            return memory_get_usage(true);
        }
        /*
         * If its Windows
         * 
         * Tested on Win XP Pro SP2. Should work on Win 2003 Server too
         * Doesn't work for 2000
         * If you need it to work for 2000 look at http://php.net/manual/en/function.memory-get-usage.php#54642
         */
        $processStat = 0;
        if ( self::getOsType() == 'win' ) {
            $output = array();
            exec('tasklist /FI "PID eq ' . getmypid() . '" /FO LIST', $output , $processStat);
            if ( $processStat !== 0 ) { // error occured in execution of takslist
                return false;
            }
            $currentMemory = intval( preg_replace( '/[\D]/', '', $output[5] ) ); // returns in KB
            $currentMemory *= 1024;
        } else	{
            /*
             * We now assume the OS is UNIX
             * Tested on Mac OS X 10.4.6 and Linux Red Hat Enterprise 4
             * This should work on most UNIX systems
             */
            $pid = getmypid();
            exec("ps -eo%mem,rss,pid | grep $pid", $output, $processStat);
            if ( $processStat !== 0 ) { // error occured in execution of takslist
                return false;
            }
            $output = explode("  ", $output[0]);
            //rss is given in 1024 byte units
            $currentMemory = intval($output[1]); // in KB
            $currentMemory *= 1024;
        }
        return $currentMemory;
    } // public static function getMemoryUsage()
}