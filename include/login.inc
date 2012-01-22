<?php
session_start();
/* $Id$ */

require 'cvs-auth.inc';
require 'functions.inc';

// User not logged in
$user = $pw = FALSE;

if (isset($_POST["user"], $_POST["pw"])) {
    list($user, $pw) = array($_POST['user'], $_POST['pw']);
} elseif (isset($_SESSION["credentials"]) && count($_SESSION["credentials"]) == 2) {
    list($user, $pw) = $_SESSION["credentials"];
}

// Login form, if the user is not yet logged in
if (!$user || !$pw || !verify_password($user,$pw)) {
    $_SESSION["credentials"] = array();
    session_destroy();

    if (!isset($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != "on") {
        $name = basename($_SERVER["SCRIPT_NAME"]);
        if ($name === 'login.php') {
            header("Location: https://master.php.net/" . $name);
        } else {
            header("Location: https://master.php.net/manage/" . $name);
        }
        exit;
    }
?>

<html>
<head>
 <title>You must log in!</title>
</head>
<body>
<form method="post" action="<?php echo $_SERVER['SCRIPT_NAME'], query_string();?>">
<?php echo preserve_form_fields();?>
<input type="hidden" name="save" value="1" />
<table>
 <tr>
  <th align="right">Username:</th>
  <td><input type="text" name="user" value="<?php echo clean($user);?>" />@php.net
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

// At this point, we have logged in successfully
if (!isset($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != "on") {
    echo '<meta http-equiv="refresh" content="2;https://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'">'.PHP_EOL;
    exit('Bad puppy. Use <a href="https://'.$_SERVER['HTTP_HOST'].'/'.$_SERVER['PHP_SELF'].'">HTTPS</a>.');
}

$_SESSION["credentials"] = array($user, $pw);

// Killing magic cookie
setcookie("MAGIC_COOKIE","",$ts-3600,'/','.php.net');
setcookie("MAGIC_COOKIE","",$ts-3600,'/');

// Set a cookie to tell various .php.net services that the user is probably loggedin
setcookie("IS_DEV", 1, $ts+3600*24*12, '/', '.php.net', false, true);



// ----------------------------------------------------------------------------------

function query_string()
{
    if (!empty($_SERVER['QUERY_STRING'])) {
        return hsc("?{$_SERVER['QUERY_STRING']}");
    }
}

function preserve_form_fields()
{
    if (isset($_POST['in']) && is_array($_POST['in'])) {
        while (list($k, $v) = each($_POST['in'])) {
            echo "<input type=\"hidden\" name=\"in[", hsc($k),
                 "]\" value=\"", hsc($v), "\" />\n";
        }
    }
}
