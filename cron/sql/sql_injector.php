<?php

include dirname(__FILE__) . '/../database.php';

if (empty($mysqlconn)) {
	die("Database not open.\n");
}

if ($argc < 2) {
	die("Usage:\tphp $argv[0] [sql file]\n\n");
}

$sql = file_get_contents($argv[1]);
if (!$sql) {
	die("Couldn't open the sql file: $argv[1].\n\n");
}


$mysqlconn->exec($sql);
$error = $mysqlconn->errorInfo();
if ($error[0] !== '00000') {
	print_r($error);
} else {
	echo "Done!\n\n";
}
