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

// Include the site API
include 'site.api.php';

// Initialize the core components
api_init($appvars);

// Define page variables
$appvars['page']['title'] = 'PHP: Test and Code Coverage Analysis';
$appvars['page']['head'] = 'PHP: Test and Code Coverage Analysis';

$sql = 'SELECT version_name, version_last_build_time, version_last_attempted_build_date, version_last_successful_build_date FROM versions WHERE';

foreach($appvars['site']['tags'] as $tag)
{
	$sql .= " version_name = '$tag' OR";
}

$sql = substr($sql, 0, -3);
$sql .= ' ORDER BY version_name';
if ($stmt = $mysqlconn->prepare($sql))
       $stmt->execute();

// Outputs the site header to the screen
api_showheader($appvars);

?>
<p>
This page is dedicated to automatic PHP code coverage testing. On a regular 
basis current CVS snapshots are being built and tested on this machine. 
After all tests are done the results are visualized along with a code coverage
analysis.
</p>
<p>
<!-- start links -->
<table class="standard" border="1" cellspacing="0" cellpadding="4">
<tr>
<th>TAG</th>
<th>Last Attempted<br />Build Date</th>
<th>Last Successful<br />Build Date</th>
<th>Last Build <br /> Time</th>
<th>Build Completion<br /> Estimate</th>
</tr>
<?php

// Output PHP versions into a table
while($stmt && $row = $stmt->fetch(PDO::FETCH_NUM))
{
	list($version_name, $version_last_build_time, $version_last_attempted_build_date, $version_last_successful_build_date) = $row;
	
	echo "<tr>";
	echo "<th align='left'><a href='/viewer.php?version=$version_name'>$version_name</a></th>";
	echo '<td>'.$version_last_attempted_build_date.'</td>'."\n";
	echo '<td>'.$version_last_successful_build_date.'</td>'."\n";
	echo '<td>'.time_diff($version_last_build_time).'</td>'."\n";

	echo '<td>';

	$pidfile = "./$version_name/build.pid";
	$process_list = `ps -A`;

	if(@file_exists($pidfile) && strpos($process_list, (int)@file_get_contents($pidfile).' ')) {

		if($version_last_build_time > 0) {
			// Obtain last modified date and time of the process id file
			$estimate_time = filemtime($pidfile) + $version_last_build_time - time();

			if($estimate_time <= 0) {
				echo 'any time soon';
			} else {
				echo time_diff($estimate_time);
			} // End check for a positive time estimation

		// this is the first time the build is running
		} else {
			echo 'Unknown';
		}

	} else {
		@unlink($pidfile);
		echo 'Not running';
	} // End check for a running php build process id

	echo "</td>\n";

	// End additions
	echo "</tr>\n";
}
?>
</table>
<!-- end links -->
</p>

<h1>How to Help</h1>
<p>
<ul>
<li>You can search and view the results collected on user-submitted platforms and versions by accessing the <a href="viewer.php?func=search">other platforms</a> section.</li>
<li>If you would like to be involved please start by visiting the <a href="http://qa.php.net/">PHP QA website</a> and read the section on <a href="http://qa.php.net/howtohelp.php">How You Can Help</a>.</li>
<li>You can also read the section on <a href="http://qa.php.net/write-test.php">how to write tests</a> to help us improve the testing process on any areas you see not covered.</li>
</ul>
<h2>Downloads</h2>
<ul>
<li><a href="downloads/PHP_4_4-gcov-20061006.diff.txt.bz2">PHP 4.4 patch</a> used by our testing process</li>
</ul>
</p>
</td>
</tr>

<?php
// Outputs the site footer to the screen
api_showfooter($appvars);
