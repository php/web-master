<?php
/* $Id$ */

include_once 'cvs-auth.inc';
include_once 'functions.inc';

// User not logged in
$user = $pw = FALSE;

// Set magic cookie if login information is available
if (isset($_POST['save']) && isset($_POST['user']) && isset($_POST['pw'])) {
    setcookie(
        "MAGIC_COOKIE",
        base64_encode("{$_POST['user']}:{$_POST['pw']}"),
        time()+3600*24*12,
        '/',
        '.php.net'
    );
    list($user, $pw) = array($_POST['user'], $_POST['pw']);
}

// Preserve information previously set in magic cookie if available
if (isset($_COOKIE['MAGIC_COOKIE']) && !isset($_POST['user']) && !isset($_POST['pw'])) {
    list($user, $pw) = explode(":", base64_decode($_COOKIE['MAGIC_COOKIE']), 2);
}

// Login form, if the user is not yet logged in
if (!$user || !$pw || !verify_password($user,$pw)) { ?>
<html>
<head>
 <title>You must log in!</title>
</head>
<body>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'], query_string();?>">
<?php echo preserve_form_fields();?>
<input type="hidden" name="save" value="1" />
<table>
 <tr>
  <th align="right">Username:</th>
  <td><input type="text" name="user" value="<?php echo clean($user);?>" />
 </tr>
 <tr>
  <th align="right">Password:</th>
  <td><input type="password" name="pw" value="<?php echo clean($pw);?>" />
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

// ----------------------------------------------------------------------------------

function query_string()
{
    if (!empty($_SERVER['QUERY_STRING'])) {
        return htmlspecialchars("?{$_SERVER['QUERY_STRING']}");
    }
}

function preserve_form_fields()
{
    if (isset($_POST['in']) && is_array($_POST['in'])) {
        while (list($k, $v) = each($_POST['in'])) {
            echo "<input type=\"hidden\" name=\"in[", htmlspecialchars($k),
                 "]\" value=\"", htmlspecialchars($v), "\" />\n";
        }
    }
}
