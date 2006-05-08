<?php

$tags = array('PHP_4_4', 'PHP_5_1', 'PHP_5_2', 'PHP_HEAD'); $mytag = NULL;

if (isset($maindir))
{
	$sub = substr(dirname($_SERVER['SCRIPT_FILENAME']), strlen($maindir) + 1);
	$pos = strpos($sub, '/');
	if ($pos)
	{
		$mytag = substr($sub, 0, $pos);
	}
}
?>
