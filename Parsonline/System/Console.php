<?php
/**
 * defines the Parsonline_System_Console class.
 *
 * @copyright   Copyright 2009 ParsOnline Inc.
 * @license     all rights reserved.
 * @package     Parsonline
 * @subpackage  System
 */
 
/**
 * Parsonline_System_Console defines functionalities to work in System console windows
 *
 * @author Farzad Ghanei <f.ghanei@parsonline.com>
 * @version 0.0.1 2009-03-09
 */
class Parsonline_System_Console
{
     /**
     * asks the user for confirmation and returns the answer.
     *
     * @param string $message message to show to user as the question
     * @param bool $keepAsking [optional] should keep asking if user entry is not 'yes' or 'not'. default is true.
     * @param mixed $defaultAnswer [optional] the default answer if user does not enter anything.
     * @return bool
     */
    public static function confirm($message,$keepAsking=true)
    {
        $keepAsking = !! $keepAsking;
        if ( func_num_args() > 2 ) {
            $defaultAnswer = !! func_get_arg(2);
        }
        do {
            $badAnswer = false;
            echo $message;
            $answer = trim( strtolower( fgets(STDIN) ) );
            if ( $answer === 'no' ) {
                return false;
            } elseif ($answer == 'yes') {
                return true;
            }
            if ( empty($answer) && isset($defaultAnswer) ) {
                return $defaultAnswer;
            }
            $badAnswer = true;
            echo "please enter 'yes' or 'no'." . PHP_EOL;
        } while ($keepAsking && $badAnswer);
    } // public static function confirm()

    /**
     * asks the user for to enter an input
     *
     * @param string $message message to show to user as the question
     * @param bool $keepAsking [optional] should keep asking the user until she enteres an input
     * @param string $defaultAnswer [optional] default answer if the user does not enter anything.
     * @return string
     */
    public static function prompt($message,$keepAsking=false)
    {
        $keepAsking = !! $keepAsking;
        if ( func_num_args() > 2 ) {
            $defaultAnswer = (string)func_get_arg(2);
        }
        do {
            $badAnswer = false;
            echo $message;
            $answer = fgets(STDIN);
            $answer = str_replace(PHP_EOL,'',$answer);
            if ( empty($answer) && isset($defaultAnswer) ) {
                return $defaultAnswer;
            }
            if (empty($answer) ) {
                $badAnswer = true;
            }
        } while ($keepAsking && $badAnswer);
        return $answer;
    } // public static function prompt()
}