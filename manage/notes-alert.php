<?php
require_once "cvs-auth.inc";
require_once "alerts_lib.inc";

if($user && $pass) {
  setcookie("MAGIC_COOKIE",base64_encode("$user:$pass"),time()+3600*24*12,'/','.php.net');
}

if (!$user && isset($MAGIC_COOKIE)) {
  list($user, $pass) = explode(":", base64_decode($MAGIC_COOKIE));
}

if (!$user || !$pass || !verify_password($user,$pass)) {
	head(); ?>
<p>
<b>You need to login first</b>
</p>
<form method="post" action="<?php echo $PHP_SELF;?>">
<input type="hidden" name="sect" value="<?php echo clean($sect);?>" />
<input type="hidden" name="alert_action" value="<?php echo clean($alert_action);?>" />
<table>
 <tr>
  <th align="right">CVS username:</th>
  <td><input type="text" name="user" value="<?php echo clean($user);?>" size="10" maxlength="32" /></td>
 </tr>
 <tr>
  <th align="right">CVS password:</th>
  <td><input type="password" name="pass" value="<?php echo clean($pass);?>" size="10" maxlength="32" /></td>
 </tr>
 <tr>
  <td align="center" colspan="2">
    <input type="submit" value="Log in" />
  </td>
 </tr>
</table>
</form>

</p>
<?php
	foot();
	exit;
}
	
switch ($alert_action) {
	case "add_alert" :
		$sql = "insert into alerts values ('$user', '$sect', now())";
		if (has_alert($user, $sect))
			echo "<b>You already have an alert for this page</b><br />\n";
		else
			if(do_alert_action($sql))
				echo "<b>Alert for page \"$sect\", added to your list</b></ br>";
			else
				echo "Unknown error while adding alert<br />\n";
		break;
	case "del_alert" :
		$sql = "delete from alerts where admin='$user' and page='$sect'";
		if (has_alert($user, $sect))
			if(do_alert_action($sql))
				echo "<b>Alert for page \"$sect\", deleted from your list</b></ br>";
			else
				echo "Unknown error while deleting alert<br />\n";
		else
			echo "<b>You do not have an alert for this page</b><br />\n";
		break;
	default :
		echo "<b>Your alerts</b>< br/>";
		break;
}
echo "<hr>";
print_alerts($user);

// functions "borrowed" from user-notes.php

function head() {?>
<html>
<head>
 <title>Notes Alert system</title>
 <link rel="stylesheet" type="text/css" href="http://www.php.net/style.css" />
</head>
<body class="popup">
<?php
}

function foot() {?>
<hr>
<a href="javascript:self.close();">Close Window</a>
</body>
</html>
<?php
}

function clean($var) {
  return htmlspecialchars(get_magic_quotes_gpc() ? stripslashes($var) : $var);
}

?>
