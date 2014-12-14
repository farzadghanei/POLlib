# POLLib

PHP library developed in [ParsOnline data center][1].

This library was developed by me and my colleagues over time by extracting shared functionality between internal applications developed in ParsOnline. ParsOnline released the library as an open source project.

The project includes a loosely coupled set of modules useful for a variety of general use tasks and are not bound to or related to business logic of ParsOnline applications.
The ZF name space is used for those modules that were used by applications based on [Zend Framework][2] (version 1.x) and require the framework libraries to be available.

## Modules in brief

 * Common: data models to represent hosts and domains and servers.
 * Converter: Helper classes to create different format of string representation.
 * Exception: A set of useful exception classes. All the modules in this library use these exceptions.
 * File operations: High level and easy to use file operations
 * Html: Utility to fetch HTML files with their resources, and a class to create HTML tables with pagination and sorting.
 * Iterator: Sample directory iterator implementations to iterate over all videos in a directory.
 * Network: High level well designed and easy to use classes for SSH2, FTP and Telnet.
 * Parser: Utilities to parse data from domain whois information, HTML, and syslog messages.
 * Streams: Easy to use class to fetch data over streams (files, sockets, etc), a stream wrapper that uses curl:// scheme to proxy requests through cURL library, and a SOAP client that extends PHP internal SOAP client and uses this stream wrapper to use cURL for SOAP calls. This overcomes the shortcomings of PHP internal SOAP client to use services that require NTLM authentication.
 * System: Easy to use and high level classes to get system information, execute processes and create a process pool.
 * UserSession: A full session handling code implemented, could be used to manage sessions for web services.
 * Utils: High level array functionality.
 * ZF: Zend Framrwork specific modules that provide application resources, controller plugins, view helpers, advanced email validator, a powerful data mapper to connect data models to RDBMS using Zend\_Db. An extended Zend\_Form with element modifiers and more.

## Examples

### Parsonline_Network_Telnet

Using the telnet client follows this process:

 * instantiate a telnet client object (might pass configuration options to constructor as an array)
 * extra configurations using setter methods
 * connecting
 * writing commands and reading responses
 * disconnect

```php

<?php
require_once("Parsonline\Network\Telnet.php");
require_once("Parsonline\Network\Telnet\Exception.php");


$telnet = new Parsonline_Network_Telnet("10.10.10.1"); // use any hostname or IP address instead of 10.10.10.1
$telnet->setPort(23); // If you want to connect to TCP ports other than Telnet default (23), (optional)
$telnet->setConnectionTimeout(60); // if you want to change the connection timeout (optional)
$telnet->setPrompt(">>>"); // this is the prompt string of the remote device. this will help telnet library to detect if output of previous command is finished and reached to the command prompt or not.

try {
    $telnet->connect();
    $telnet->write("command to execute on host"); // send the command to be executed on remote host
    $response = $telnet->waitForPrompt(); // read output of the command from remote host
    $telnet->disconnect();
} catch (Parsonline_Network_Telnet_Exception $e) {
    // handle connection or IO error
    $telnet->disconnect();
    die("Error occurred: " . $e);
}
```

[1]: http://www.parsonline.net/en/
[2]: http://framework.zend.com/

