<?php

require 'cvs-auth.inc';

if (isset($save) && isset($user) && isset($pw)) {
  setcookie("MAGIC_COOKIE",base64_encode("$user:$pw"),time()+3600*24*12,'/','.php.net');
}
if (isset($MAGIC_COOKIE) && !isset($user) && !isset($pw)) {
  list($user,$pw) = explode(":", base64_decode($MAGIC_COOKIE));
}

if (!$user || !$pw || !verify_password($user,$pw)) {?>
<html>
<head>
 <title>you must log in</title>
</head>
<body>
<form method="POST" action="<?php echo $PHP_SELF?>">
<input type="hidden" name="save" value="1" />
<table>
 <tr>
  <th align="right">Username:</th>
  <td><input type="text" name="user" value="<?php echo htmlspecialchars(stripslashes($user));?>" />
 </tr>
 <tr>
  <th align="right">Password:</th>
  <td><input type="password" name="pw" value="<?php echo htmlspecialchars(stripslashes($pw));?>" />
 </tr>
 <tr>
  <td align="center" colspan="2"><input type="submit" value="Login" /></td>
 </tr>
</table>
</form>
</body>
</html>
<?php
  exit;
}
