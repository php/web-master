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

// Tests generation file

if(!defined('CRON_PHP'))
{
	echo basename($_SERVER['PHP_SELF']).': Sorry this file must be called by a cron script.'."\n";
	exit;
}

require_once $workdir.'/template.php';

// data: contains the contents of $tmpdir/php_test.log
// unicode: true if unicode is included

// Clear the variable that stores the output file for the core file
$index_write = '';

// Clear the variable that stores the output content for the individual files
$write = '';

// Regular expression used to find a single failure
$fail_re = '/FAIL(:(?P<testtype>[a-z|A-Z]))?/';

// Regular expression to find all tests with a pass or failure
$tests_re = '/(?P<status>FAIL|PASS)(:(?P<testtype>[a-z|A-Z]))? (?P<title>.+) \[(?P<file>[^\]]+)\]/';

// Grab all tests that match the tests regular expression
preg_match_all($tests_re, $data, $tests, PREG_SET_ORDER);

// Clear old directory variable
$old_dir = '';

// A single failure is enough to verify that a failure occurred
if (preg_match($fail_re, $data) < 1) 
{
	$index_write .= "<p>Congratulations! Currently there are no test failures!</p>\n";
} 
else 
{
	// If there are leaks start the table

	$index_write .= <<< HTML
<table border="1">
HTML;

} 

// Loop through each result
foreach ($tests as $test) 
{

	$dir   = dirname($test['file']);
	$file  = basename($test['file']);

	$status = strtolower($test['status']); // fail or pass

	$testtype = ''; // test type will be determined later

	$title = $test['title'];

	// Note: that the following period is maintained
	$base = "$phpdir/".substr($test['file'],0,-4);

	$report_file = $base;

	if((isset($test['testtype'])) && (strtolower($test['testtype']) == 'u'))
	{
		$testtype = 'Unicode';
		$report_file .= 'u.';
	}
	else
	{
		$testtype = 'Native';
	}

	// Note: the hash reflects the exact filename for native
	// i.e. unicode would be file.u.phpt and native file.phpt
	$hash  = md5($report_file.'phpt'); 

	// Failed tests provide more content then passed tests
	if($status == 'fail')
	{
		// These variables are only used for failed tests
		$difference = file_get_contents($report_file.'diff')
      or $difference = 'N\A';
	  $expected = file_get_contents($report_file.'exp')
			or $expected = 'N\A';
		$output = file_get_contents($report_file.'out')
			or $output = 'N\A';		
		$script = file_get_contents($base.'php')
    	or $script = 'Script contents not available.';
			
		// Currently only used by master server but may also be useful here
		$totalnumfailures += 1;

		// Ensure content is running on a master server before writing up with the output files
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
<td width="80">Test Type</td>
<td width="*">Name</td>
</tr>
HTML;

			} // End check for change of directory location

			$title_html = htmlspecialchars($title);

			$index_write .= <<< HTML
<tr>
<td>
<a href="viewer.php?version=$phpver&func=tests&file=$hash">$file</a></td>
<td>{$testtype}</td>
<td>{$title_html}</td>
</tr>
HTML;

		// Manipulate the data to work with HTML formatting
		$script_html = highlight_string($script, true);
		$expected_html = htmlspecialchars($expected);
		$difference_html = htmlspecialchars(str_replace($phpdir,'',$difference));
		$output_html = htmlspecialchars(str_replace($phpdir,'',$output));

		$write = <<< HTML

<h2>Script</h2>
<pre>$script_html</pre>
<h2>Expected</h2>
<pre>$expected_html</pre>
<h2>Output</h2>
<pre>$output_html</pre>
<h2>Diff</h2>
<pre>$difference_html</pre>
HTML;

			// output the content for the individual test failure
			file_put_contents("$outdir/$hash.inc",
			'<?php $filename="'.htmlspecialchars(basename($file)).'"; ?>'."\n"
				.$write.
				html_footer()
			);

		} 
		else
		{
			// If not master server, add to output array

			$newtest = array();
			$newtest['difference'] = $difference;
			$newtest['expected'] = $expected;
			$newtest['file'] = $test['file'];
			$newtest['output'] = $output;
			$newtest['script'] = $script;
			$newtest['status'] = $status;
			$newtest['testtype'] = $testtype;
			$newtest['title'] = $title;

			$xmlarray['tests'][] = $newtest;
		} // End check for master server

	} // End check for loop through failed tests
	else
	{
		// So far passed tests are not used locally but are used for output

		if(!$is_master)
		{
			// test title might be useful for passed tests

			$newtest = array();
			$newtest['file'] = $test['file'];
			$newtest['status'] = $status;
			$newtest['testtype'] = $testtype;

			$xmlarray['tests'][] = $newtest;

		} // End check for not master server

	} // End loop through pass tests

} // End loop through tests

// Continue master server check for output of the core tests file
if($is_master)
{
	if ($totalnumfailures > 0)
		$index_write .= "</table>\n";

	file_put_contents("$outdir/tests.inc", $index_write.html_footer());

} // End check for master server for core tests file output

?>
