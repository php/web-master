#! /usr/local/bin/php -q
<?php
$regex = "/(\w+) (.+) ([^ ]+@[^ ]+) (.+)/";

// Change to path for the CVS
$data = file("/repository/CVSROOT/cvsusers");

$c = mysql_connect();
mysql_select_db("php3", $c);
// table cvsusers must be there
$sql = "insert into cvsusers values ";
for ($i=0; $i < count($data); $i++) {
	preg_match($regex, trim($data[$i]), $user);
	list(,$nick,$name,$email,$work) = $user;
	if ($nick && $name && $email && $work) {
		$values = "('$nick','$name','$email','$work')";
		$r = mysql_query($sql.$values, $c);
		if (mysql_affected_rows($c) == 0)
			echo "Error inserting: $nick:$name:$email:$work (".mysql_error().")\n";
		else
			echo "OK $nick:$name:$email:$work\n";
	} else {
		echo "<ERROR> Missing information in line $i: ".trim($data[$i])."\n";
	}
}
mysql_close($c);
?>
