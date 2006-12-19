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
  |         Nuno Lopes <nlopess@php.net>                                 |
  +----------------------------------------------------------------------+
*/

/* $Id$ */

// File: Core Site Configuration File
// Desc: contains core site settings, this file is essential and always included by the API.


// Set up the key variables
$appvars = array(); // Application variables array
$appvars['site'] = array(); // Application site-specific variable array
$appvars['page'] = array(); // Application page-specific variable array

// Define the file that contains the text for each PHP version
$appvars['site']['tagsfile'] = dirname(__FILE__).'/../cron/tags.inc';

// Define the file that contains all needed database connections
$appvars['site']['dbcsfile'] = dirname(__FILE__).'/../cron/database.php';

// Define links for the side bar (used in site.api.php)
$appvars['site']['sidebarsubitems_localbuilds'] = array(
		'coverage'  => 'lcov',
		'compile-results' => 'compile_results',
		'graphs' => 'graph',
		'parameter-parsing' => 'params',
		'system' => 'system',
		'test-failures' => 'tests',
		'valgrind' => 'valgrind'
	);

// Define sidebar links that are active when viewing a builder's submissions
$appvars['site']['sidebarsubitems_otherplatforms'] = array(
		'compile-results' => 'compile_results',
		'system' => 'system',
		'test-failures' => 'tests',
		'valgrind' => 'valgrind'
	);

