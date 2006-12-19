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

// File that generates the system.inc file
// This file gathers the essential information regarding the configuration for the build

// For client the information is gathered here for use in the XML generation
// for server the information is gathered here and output to the system.inc file

// Configure Section
$config = file("$phpdir/config.nice");
$config = array_slice($config, 4); //remove inital comments
$configureinfo = implode('', $config);

// Compiler Section
$compiler = explode("\n", `cc --version`);
$compilerinfo = $compiler[0];

// Operating System Section
// Todo: this section need to be revised for systems without the uname command
$osinfo = `uname -srm`;

// Valgrind (calculated but not displayed)
// Todo: This section is diabled since it needs tweaking for Windows installations
//$valgrind = @explode("\n", `valgrind --version`);
//$valgrindinfo = $valgrind[0];

// If master server, updated system.inc in addition to gathering the information
if($is_master)
{
	$write  = "<h2>Configure Used:</h2>\n";
	$write .= "<pre>".$configureinfo."</pre>\n";

	$write .= "<h2>Compiler Used:</h2>\n";
	$write .= '<p>'.$compilerinfo. "</p>\n";

	$write .= "<h2>Operating System:</h2>\n";
	$write .= '<p>'.$osinfo."</p>\n";

	file_put_contents("$outdir/system.inc", $write . html_footer());
}
?>
