<?php

require 'cvs-auth.inc';

if (isset($save) && isset($user) && isset($pw)) {
  setcookie("MAGIC_COOKIE",base64_encode("$user:$pw"),time()+3600*24*12,'/','.php.net');
}
if (isset($MAGIC_COOKIE) && !isset($user) && !isset($pw)) {
  list($user,$pw) = explode(":", base64_decode($MAGIC_COOKIE));
}

function query_string() {
  if ($GLOBALS['QUERY_STRING']) {
    return htmlspecialchars("?$GLOBALS[QUERY_STRING]");
  }
}

function preserve_form_fields() {
  if ($GLOBALS['REQUEST_METHOD'] == "POST" && @is_array($GLOBALS['in'])) {
    while (list($k,$v) = each($GLOBALS['in'])) {
      echo "<input type=\"hidden\" name=\"in[", htmlspecialchars($k),
           "]\" value=\"", htmlspecialchars($v), "\" />\n";
    }
  }
}

if (!$user || !$pw || !verify_password($user,$pw)) {?>
<html>
<head>
 <title>you must log in</title>
</head>
<body>
<form method="post" action="<?php echo $PHP_SELF, query_string();?>">
<?php echo preserve_form_fields();?>
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
