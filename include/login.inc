<?php
/* Force https */
/*
if (!isset($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != "on") {
    $name = basename($_SERVER["SCRIPT_NAME"]);
    if ($name === 'login.php') {
        header("Location: https://master.php.net/" . $name);
    } else {
        header("Location: https://master.php.net/manage/" . $name);
    }
    exit;
}
 */

session_start();
/* $Id$ */

require 'cvs-auth.inc';
require 'functions.inc';

// User not logged in
$cuser = $cpw = FALSE;

if (isset($_POST["user"], $_POST["pw"])) {
    list($cuser, $cpw) = [$_POST['user'], $_POST['pw']];
} elseif (isset($_SESSION["credentials"]) && count($_SESSION["credentials"]) == 2) {
    list($cuser, $cpw) = $_SESSION["credentials"];
}

// Login form, if the user is not yet logged in
if (!$cuser || !$cpw || !verify_password($cuser,$cpw)) {
    $_SESSION = [];
    session_destroy();

    // IS_DEV was 1 or 0 until 22 Feb 2012. It's now a @php.net username hint.
    $cuser = '';
    if (isset($_COOKIE['IS_DEV']) && !is_numeric($_COOKIE['IS_DEV'])) {
        $cuser = clean($_COOKIE['IS_DEV']);
    } else {
        if (!empty($_POST['user'])) {
            $cuser = clean($_POST['user']);
        }
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
  <td><input type="text" name="user" value="<?php echo $cuser;?>" />@php.net
 </tr>
 <tr>
  <th align="right">Password:</th>
  <td><input type="password" name="pw" value="<?php echo clean($cpw);?>" />
<?php if ($cpw): ?>
        <a href="/forgot.php" /> Forgot your password?</a>
<?php endif ?>
    </td>
 </tr>
 <tr>
  <td></td><td><input type="submit" value="Login" /></td>
 </tr>
<?php if ($cpw): ?>
<?php
    $msgs = [
        "Nope.. Wrong (username?) password",
        "Nope.. Thats not it",
        "This isn't going very well..",
    ];
    shuffle($msgs);
    $msg = array_pop($msgs);
?>
 <tr>
 <td colspan="2"><?php echo $msg ?></td>
 </tr>
<?php endif ?>
</table>
</form>
</body>
</html>
<?php
  exit;
}

session_regenerate_id();
// At this point, we have logged in successfully
$_SESSION["credentials"] = [$cuser, $cpw];
$_SESSION["username"] = $cuser;

// Killing magic cookie
setcookie("MAGIC_COOKIE","",$ts-3600,'/','.php.net');
setcookie("MAGIC_COOKIE","",$ts-3600,'/');

// Set a cookie to tell various .php.net services that the user is probably logged in
// The username is saved here so we can automagically fill it in during login prompts
setcookie("IS_DEV", $cuser, $ts+3600*24*12, '/', '.php.net', false, true);



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
        foreach ($_POST['in'] as $k => $v) {
            echo "<input type=\"hidden\" name=\"in[", hsc($k),
                 "]\" value=\"", hsc($v), "\" />\n";
        }
    }
}
