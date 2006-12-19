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

// File to process memory leaks detected by valgrind

if(!defined('CRON_PHP'))
{
        echo basename($_SERVER['PHP_SELF']).': Sorry this file must be called by a cron script.'."\n";
	exit;
}

// $data: contains the contents of $tmpdir/php_test.log

// Output for core file
$index_write = '';

// Output for individual files
$write = '';

// Regular expression to select leaks for both unicode and not
$leak_re = '/LEAK(:(?P<testtype>[a-z|A-Z]))? (?P<title>.+) \[(?P<file>[^\]]+)\]/';

// Find memory leaks in the data
preg_match_all($leak_re, $data, $leaks, PREG_SET_ORDER);

// Clear the old directory variable
$old_dir = '';

// If there are no leaks just notify that no leaks occurred
if (count($leaks) < 1) 
{
	$index_write .= "<p>Congratulations! Currently no memory leaks were found!</p>\n";
} 
else 
{
	$totalnumleaks = count($leaks);
	$index_write .= '<table border="1">'."\n";
} // End check for number of leaks

// Loop through each result
foreach ($leaks as $test) 
{

	$base = "$phpdir/".substr($test['file'],0,-4);

	$dir   = dirname($test['file']);
	$file  = basename($test['file']);
	$title = $test['title'];

	if(isset($test['testtype']) && strtolower($test['testtype']) == 'u') {
		$testtype = 'Unicode';
		$report_file = $base.'u.mem';
	} else {
		$testtype = 'Native';
		$report_file = $base.'mem';
	}

	$hash = 'v' . md5($report_file);

	// Check if master server
	if($is_master)
	{
		if ($old_dir != $dir) 
		{
			$old_dir = $dir;
		
			$index_write .= <<< HTML
<tr>
<td colspan="3" align="center"><b>$dir</b></td>
</tr>
<tr>
<td width="190">File</td>
<td width="100">Test Type</td>
<td width="*">Name</td>
</tr>
HTML;

		} // End check for directory change

		// Add the test to valgrind main page output
		$index_write .= <<< HTML
<tr>
<td><a href="viewer.php?version=$phpver&func=valgrind&file=$hash">$file</a></td>
<td>$testtype</td>
<td>$title</td>
</tr>
HTML;

	} // End check for master server

	$script_php = highlight_file($base.'php', true);
	$report     = str_replace($phpdir, '', file_get_contents($report_file));

	if($is_master)
	{
		$write = <<< HTML
<h2>Script</h2>
<pre>$script_php</pre>
<h2>Report</h2>
<pre>$report</pre>
HTML;

		// Output content for the individual test page
		file_put_contents("$outdir/$hash.inc",
			'<?php $filename="'.basename($file).'"; ?>'."\n"
			.$write.
			html_footer()
		);

	} 
	else
	{
		// If not master server, add the leak to the output array
		$newleak = array();
		$newleak['testtype']    = $testtype;
		$newleak['title']       = $test['title'];
		$newleak['file']        = $test['file'];
		$newleak['script']      = $script_text;
		$newleak['report']      = $report;
		$xmlarray['valgrind'][] = $newleak;

	} // End check for master server 

} // End loop through each leak

// End check for master server
if($is_master)
{
	if (count($leaks) > 0)
	{
		$index_write .= "</table>\n";
	}

	file_put_contents("$outdir/valgrind.inc", 
		$index_write.html_footer());

} // End check for master server

?>
