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

// Cron configuration file for the PHP scripts


// If is_master = true; after build, stores the outcome locally
// If is_master = false; after build, sends data to remote server
// Note: if is_master = false; a valid username and password is required
// to post the data to the central server
$is_master = true;

// Note: these options are specific to is_master = false
$server_submit_url = 'http://gcov.php.net/post.php';
$server_submit_user = 'johndoe';
$server_submit_pass = 'john';

// PHP Mailer configuration settings
if($is_master)
{
	// PHP Mailer configuration settings
	$mail_from_name = 'GCOV Admin';
	$mail_from_email = 'internals@lists.php.net';

	$mail_smtp_mode = 'disabled';
	$mail_smtp_host = null;
	$mail_smtp_port = null;
	$mail_smtp_user = null;

	// Include the required database connections
	require_once 'database.php';

} // End check if server is a master
else
{
	// Any client configuration would be made here

} // End  check if client instance
