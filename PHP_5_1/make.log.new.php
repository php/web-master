<?php require '../main.inc'; require 'mytag.inc'; ?>
<html>
<head>
<title>PHP: Test and Code Coverage analysis of $mytag</title>
<link rel="stylesheet" href="/style.css" />
<link rel="shortcut icon" href="/favicon.ico" />
</head>
<body bgcolor="#ffffff" text="#000000" link="#000099" alink="#0000ff" vlink="#000099">
<?php include '../header.inc'; ?>
<table border="0" cellspacing="0" cellpadding="0"><!-- outer -->
<tr>
<td align="left" valign="top" width="120" bgcolor="#f0f0f0">
<?php include '../sidebar.inc'; ?>
</td>
<td bgcolor="#cccccc" background="/images/checkerboard.gif" width="1"><img src="/images/spacer.gif" width="1" height="1" border="0" alt="" /></td>
<td align="left" valign="top">
<table cellpadding="10" cellspacing="0" width="100%"><tr><td align="left" valign="top"><!- content -->
<?php
	$date = @filemtime(dirname(__FILE__) . "/make.log.new");
	$last = @filemtime(dirname(__FILE__) . "/make.log");
	$show = file_exists(dirname(__FILE__) . "/make.log.new") && ($last === false || $date > $last);
	if ($show)
	{
		$started = date("M d Y H:i:s", $date);
	}
	else
	{
		$started = 'not running';
	}
?>
<h1><?php echo $mytag; ?>: make log (<?php echo $started; ?>)</h1>
<?php
	if ($show)
	{
		echo "<pre>\n";
		fpassthru(fopen(dirname(__FILE__) . '/make.log.new', 'r'));
		echo "</pre>\n";
	}
	else
	{
		echo "Not running\n";
	}
?>
</td>
</tr>
</table><!-- content -->
</td>
</tr>
</table><!-- outer -->
<?php include '../footer.inc'; ?>
</body>
</html>
