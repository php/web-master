<table class='sidebartoc' width="100%" cellpadding="2" cellspacing="0" border="0"><!-- sidebar -->
<tr valign="top"><td class="sidebartoc">
<ul id="sidebartoc">
<li class="header home"><a href="/">PHP&nbsp;GCOV</a></li>
<?php

$cnt = count($tags);
foreach($tags as $tag)
{
	$cls = '';
	if (!--$cnt && isset($mytag))
	{
		$cls = 'last';
	}
	if ($tag == $mytag)
	{
		if (strlen($cls))
		{
			$cls .= ' ';
		}
		$cls .= 'active';
	}
	if (strlen($cls))
	{
		$cls = " class='$cls'";
	}
	echo "<li$cls><a href='/$tag'>$tag</a></li>\n";
}

if (isset($mytag))
{
	$subitems = array(
		'coverage'  => 'lcov/',
		'run-tests' => 'run-tests.log.php',
		'make log'  => 'make.log.php',
		'build.sh'  => 'build.php',
		);
	foreach($subitems as $item => $href)
	{
		$cls = " class='small'";
		echo "<li$cls><a href='/$mytag/$href'>$item</a></li>\n";
	}
}

?>
</ul></td></tr></table><!-- sidebar -->
