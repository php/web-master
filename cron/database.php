<?php
/*
  +----------------------------------------------------------------------+
  | PHP QA GCOV Website                                                  |
  +----------------------------------------------------------------------+
  | Copyright (c) 2005-2006 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author: Daniel Pronych <pronych@php.net>                             |
  +----------------------------------------------------------------------+
*/

/* $Id$ */

// Database connection script

/*
Note: this script is only needed for the server instance but it is shared between the cron and www scripts
*/

$myuser = 'phpgcov';
$mypass = 'phpfi';
$mydsn = 'mysql:host=localhost;dbname=phpqagcov';

try
{
  $mysqlconn = new PDO($mydsn, $myuser, $mypass);
}
catch(PDOException $e)
{
	$mysqlconn = null;
}
