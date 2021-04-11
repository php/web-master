<?php // vim: et ts=2 sw=2

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../include/functions.inc';
require __DIR__ . "/../include/mailer.php";

$id = $_REQUEST['id'] ?? false;
$user = $_REQUEST['user'] ?? false;
$key = $_REQUEST['key'] ?? false;
$n1 = $_REQUEST['n1'] ?? false;
$n2 = $_REQUEST['n2'] ?? false;

$ts = $_SERVER["REQUEST_TIME"];

function random_password() {
  return bin2hex(random_bytes(16));
}

head("forgotten password");

db_connect();

if ($id && $key) {
  if ($n1 && $n2) {
    if ($n1 === $n2) {
      $svnpasswd = gen_pass($n1);
      $res = db_query_safe("UPDATE users SET forgot=NULL,svnpasswd=?,pchanged=? WHERE userid=? AND forgot=?", [$svnpasswd, $ts, $id, $key]);
      if ($res && mysql_affected_rows()) {
        echo '<p>Okay, your password has been changed. It could take as long as an hour before this change makes it to the VCS server and other services. To change your password again, you\'ll have to start this process over to get a new key.</p>';
        foot();
        exit;
      }
      else {
        echo '<p class="warning">Naughty you, the key you used to access this page doesn\'t match what we have on file for that userid.</p>';
      }
    }
    else {
      echo '<p class="warning">Those two passwords didn\'t match!</p>';
    }
  }
?>
<p>You're in the home stretch now. Just choose a new password
(typing it twice, to avoid typos and another trip around this
merry-go-round).</p>
<form method="post" action="<?php echo htmlentities($_SERVER['PHP_SELF']) ?>">
password: <input type="password" name="n1" value="<?= hsc($n1)?>" />
<br />again: <input type="password" name="n2" value="<?= hsc($n1)?>" />
<br /><input type="submit" value="do it!" />
<input type="hidden" name="id" value="<?= hsc($id)?>" />
<input type="hidden" name="key" value="<?= hsc($key)?>" />
</form>
<?php
  foot();
  exit;
}
elseif ($user) {
  $res = db_query_safe("SELECT * FROM users WHERE username = ?", [$user]);
  if ($res && ($row = mysql_fetch_array($res,MYSQL_ASSOC))) {
    $newpass = random_password();
    $query = "UPDATE users SET forgot=? WHERE userid=?";
    $res = db_query_safe($query, [$newpass, $row['userid']]);
    if ($res) {
      $body =
"Someone filled out the form that says you forgot your php.net VCS
password. If it wasn't you, don't worry too much about it. Unless
someone is reading your mail, there's not much they can do. (But you
may want to change your password using the instructions below, just to
be safe.)

To change your password, simply use the URL below and choose a new
password.

  https://main.php.net/forgot.php?id=$row[userid]&key=$newpass

Let us know if you have any further problems.
--
group@php.net
";
      mailer(
        $row['username'] . '@php.net',
        "Password change instructions for $row[username]", $body,
        new MailAddress('group@php.net', 'PHP Group'));
      echo '<p>Okay, instructions on how to change your password have been sent to your email address. If you don\'t receive them, you\'ll have to contact group@php.net for help.</p>';
      foot();
      exit;
    }
    else {
      echo '<p class="warning">Something strange happened. You\'ll have to contact group@php.net for help.</p>';
    }
  }
  else {?>
<p class="warning">There's nobody named <?php echo hsc($user)?> around here. Perhaps you need to contact
group@php.net for help.</p>
<?php
  }
}
?>
<p>Forgot your <acronym title="Version Control System">VCS</acronym> password, huh? Just fill in your VCS username, and
instructions will be sent to you on how to change your password.</p>
<form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
 <label for="user">username:</label>
 <input type="text" id="user" name="user" value="<?php echo hsc($user)?>" />
 <input type="submit" value="send help" />
</form>
<?php
foot();

