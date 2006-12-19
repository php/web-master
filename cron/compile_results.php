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

// Compile Results generation script

if(!defined('CRON_PHP'))
{
	echo basename($_SERVER['PHP_SELF']).': Sorry this file must be called by a cron script.'."\n";
	exit;
}

$totalnumerrors = 0;
$totalnumwarnings = 0;

$data = file_get_contents("$tmpdir/php_build.log");

// Check if build file was readable, and if not, notify the user
if($data === false)
{
	echo basename($_SERVER['PHP_SELF']).": it appears the build process has succeeded but the PHP build log file at $tmpdir/php_build.log could not be opened for processing.  If the problem persists, you may want to check the permissions for the cron scripts in the temporary directory to ensure that the user that runs the cron scripts has at least read and write access for the directory $tmpdir and all files contained within this directory.\n";
}
else
{
	// Regular expression to select the error and warning information
	// tuned for gcc 3.4, 4.0 and 4.1
	$gcc_regex = '/^((.+)(\(\.text\+[[:xdigit:]]+\))?: In function [`\'](\w+)\':\s+)?'.
		'((?(1)(?(3)[^:\n]+|\2)|[^:\n]+)):(\d+): (?:(error|warning):\s+)?(.+)'.
		str_repeat('(?:\s+\5:(\d+): (?:(error|warning):\s+)?(.+))?', 99). // capture up to 100 errors
		'/mS';

	preg_match_all($gcc_regex, $data, $data, PREG_SET_ORDER);

	$stats = array();

	$index_write = '';

	foreach ($data as $error)
	{

		$file     = $error[5];

		// Remove the phpdir portion from the file path if it occurs
		if(substr($file, 0, strlen($phpdir)) == $phpdir)
		{
			$filepath = substr($file, strlen($phpdir));
		}
		else 
		{
			$filepath = $file;
		} // End check for phpdir in file path
		
		// If stats are not previously set for this file, initialize it to the default values
		if(!isset($stats[$file]))
		{
			@$stats[$file][0] = 0; // number of file errros
			@$stats[$file][1] = 0; // number of file warnings
			@$stats[$file][2] = '';   // data to write
		}

		$function = $error[4] ? $error[4] : '(top level)';
		$write = '';

		// the real data starts at 6th element
		for ($i = 6; isset($error[$i]); $i += 3) 
		{
			$line = $error[$i];
			$type = $error[$i+1] ? $error[$i+1] : 'error'; // warning or error (default)
			$msg  = $error[$i+2];

			$lxrpath = '';

			// This section only applies to the master server
			if($is_master)
			{
				if(substr($filepath, 0, 6) == '/Zend/')
				{
					$lxrpath = str_replace('/Zend/','/ZendEngine2/', $filepath);
					$lxrpath = "http://lxr.php.net/source{$lxrpath}#{$line}";
				}
				else
				{
					$lxrpath = "http://lxr.php.net/source/php-src{$filepath}#{$line}";
				}

				$write .= <<< HTML
 <tr>
  <td>$function</td>
  <td><a href="$lxrpath">$line</a></td>
  <td>$type: $msg</td>
 </tr>
HTML;
				if($type == 'error')
				{
					@++$stats[$file][0]; // number of file errros
					++$totalnumerrors;
				}		
				elseif($type == 'warning')
				{
					@++$stats[$file][1]; // number of file warnings
					++$totalnumwarnings;
				}
				else 
				{
				} // End check for type

			} // End check if master server
			else
			{
				$compile_result = array();
				$compile_result['file'] = $filepath;
				$compile_result['function'] = $function;
				$compile_result['line'] = $line;
				$compile_result['type'] = $type;
				$compile_result['msg'] = $msg;
			
				$xmlarray['compile_results'][] = $compile_result;
			} // End check for client machine

		} // End loop through the number of elements in a single result file

		@$stats[$file][2] .= $write;   // data to write

	} // End loop through the compile results

	// Continue check for master server
	if($is_master)
	{
		$total = $totalnumerrors+$totalnumwarnings;
		if ($total)
		{
			$index_write .= <<< HTML

<p>Number of Errors: {$totalnumerrors}<br />
Number of Warnings: {$totalnumwarnings}<br />
Total: {$total}</p>

<table border="1">
<tr>
<th>File</th>
<th>Number of errors</th>
<th>Number of warnings</th>
</tr>
HTML;
		} else {
			$index_write = "<p>Congratulations! There are no compiler warnings/errors.</p>\n";
		}
	} // End check for master server

	foreach ($stats as $file => $data) 
	{
		$hash       = md5($file); // files have consistent start character
	
		// Compare first portion of file name to phpsrc
		if(substr($file, 0, strlen($phpdir)) == $phpdir)	
		{
			$short_file = substr($file, strlen($phpdir));
		}
		else // If phpsrc does not occur, display full file name (todo: verify)
		{
			$short_file = $file;
		}

		if($is_master)
		{
			// Add content to the core compile results file
			$index_write .= <<< HTML
<tr>
<td><a href="viewer.php?version=$phpver&func=compile_results&file=$hash">$short_file</a></td>
<td>$data[0]</td>
<td>$data[1]</td>
</tr>
HTML;

			// Add content to the individual compile results file
			$write = <<< HTML
<table border="1">
 <tr>
  <th>Function</th>
  <th>Line</th>
  <th>Message</th>
 </tr>
HTML;

			file_put_contents("$outdir/$hash.inc",
				'<?php $filename="'.basename($file).'"; ?>'.
				$write.$data[2].'</table>'.html_footer());
			} // End check for master server

	} // End loop through errors and warnings

	if($is_master)
	{
		file_put_contents("$outdir/compile_results.inc", 
			$index_write.'</table>'.html_footer());

	} // End final check for master server

} // End check if data could be read

?>
