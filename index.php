<?php require 'main.inc'; ?><html>
<head>
<title>PHP: Test and Code Coverage analysis</title>
<link rel="stylesheet" href="/style.css" />
<link rel="shortcut icon" href="/favicon.ico" />
</head>
<body bgcolor="#ffffff" text="#000000" link="#000099" alink="#0000ff" vlink="#000099">
<?php include 'header.inc'; ?>
<table border="0" cellspacing="0" cellpadding="0"><!-- outer -->
<tr>
<td align="left" valign="top" width="120" bgcolor="#f0f0f0">
<?php include 'sidebar.inc'; ?>
</td>
<td bgcolor="#cccccc" background="/images/checkerboard.gif" width="1"><img src="/images/spacer.gif" width="1" height="1" border="0" alt="" /></td>
<td align="left" valign="top">
<table cellpadding="10" cellspacing="0" width="100%"><tr><td align="left" valign="top"><!- content -->
<h1>PHP: Test and Code Coverage analysis</h1>
<p>
This page is dedicated to automatic PHP code coverage testing. On a regular 
basis current cvs snapshots are being build and tested on this machine. 
After all tests are done the results are visualized along with a code coverage
analysis.
</p>
<p>
<table class='standard' border='1' cellspacing='0' cellpadding='4'><!-- links -->
<tr><th>TAG</th><th>coverage</th><th>run-tests</th><th>make log</th><th>running make</th></tr>
<?php

function show_link($tag, $link, $file = NULL, $l_time = false)
{
	if (is_null($file))
	{
		$file = $link;
	}
	$m_time = @filemtime(dirname(__FILE__) . "/$tag/$file");
	if (file_exists(dirname(__FILE__) . "/$tag/$file") && ($l_time === false || $m_time > $l_time))
	{
		echo "<td align='left'><a href='/$tag/$link'>" . date("M d Y H:i:s", $m_time) . "</td>";
	}
	else
	{
		echo "<td>&nbsp;</td>";
	}
	return $m_time;
}

foreach($tags as $tag)
{
	echo "<tr>";
	echo "<th align='left'>$tag</th>";
	show_link($tag, 'lcov/index.php');
	show_link($tag, 'run-tests.log.php', 'run-tests.html.inc');
	$l_time = show_link($tag, 'make.log.php', 'make.log');
	show_link($tag, 'make.log.new.php', 'make.log.new', $l_time);
	echo "</tr>\n";
}
?>
</table><!-- links -->
</p>
<h1>ToDo</h1>
<p>
<ul>
<li>Integrate gcov testing into PHP_4_4 (<a href='PHP_4_4-gcov-20060323.diff.txt.bz2'>patch</a>)</li>
<li>Running the tests from a cron job (5.1 takes ~25 hours, HEAD takes ~52 hours).</li>
<li>Enable all core extensions.</li>
<li>Integrate PECL extensions.</li>
<li>Integrate PEAR classes.</li>
<li>Integrate external components.</li>
</ul>
</p>
<h1>How to help</h1>
<p>
<ul>
<li>Visit the <a href="http://qa.php.net">QA site</a> and read <a href="http://qa.php.net/howtohelp.php">How You Can Help</a>.</li>
<li>Check out <a href="http://qa.php.net/write-test.php">how to write tests</a> for anything you see uncovered.</a>
<li><p>Set up PHP/GCOV testinng on your systems and report any found issues to the <a href="http://qa.php.net/">QA</a> team.
    You need lcov 1.5 which you can get from here
    <ul>
    <li><a href="/lcov_1.5-1_all.deb">lcov_1.5-1_all.deb</a></li>
    <li><a href="/lcov-1.5-pre1.noarch.rpm">lcov-1.5-pre1.noarch.rpm</a></li>
    </ul>
    </p>
    <p>
    After you have installed lcov from the above, replace genhtml with the
    <ul>
    <li><a href="/genhtml.gcov-php-net">patched genhtml</a></li>
    </ul>
    </p>
</li>
</ul>
</p>
</td>
</tr>
</table><!-- content -->
</td>
</tr>
</table><!-- outer -->
<?php include 'footer.inc'; ?>
</body>
</html>
