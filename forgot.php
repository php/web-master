<?php
require 'functions.inc';

function random_password() {
  $alphanum = array_merge(range("a","z"),range("A","Z"),range(0,9));

  $return = '';
  for ($i = 0; $i < 12; $i++) {
    $return .= $alphanum[rand(0,count($alphanum)-1)];
  }
  return $return;
}

head("forgotten password");

mysql_connect("localhost","nobody","")
  or die("unable to connect to database");
mysql_select_db("php3")
  or die("unable to select database");

if ($id && $key) {
  if ($n1 && $n2) {
    if ($n1 == $n2) {
      $passwd = addslashes(crypt(stripslashes($n1), substr(md5(time()), 0, 2)));
      $res = @mysql_query("UPDATE users SET forgot=NULL,passwd='$passwd' WHERE userid='$id' AND forgot='$key'");
      if ($res && mysql_affected_rows()) {
        echo '<p>Okay, your password has been changed. It could take as long as an hour before this change makes it to the CVS server and other services. To change your password again, you\'ll have to start this process over to get a new key.</p>';
        foot();
        exit;
      }
      else {
        echo '<p class="warning">Naughty you, the key you used to access this page doesn\'t match what we have on file for that userid. You\'ll need to <a href="',$PHP_SELF,'">start this process over</a>.</p>';
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
<form method="post" action="<?php echo $PHP_SELF?>">
password: <input type="password" name="n1" value="<?php echo htmlentities(stripslashes($n1))?>" />
<br />again: <input type="password" name="n2" value="<?php echo htmlentities(stripslashes($n1))?>" />
<br /><input type="submit" value="do it!" />
<input type="hidden" name="id" value="<?php echo htmlentities(stripslashes($id))?>" />
<input type="hidden" name="key" value="<?php echo htmlentities(stripslashes($key))?>" />
</form>
<?php
  foot();
  exit;
}
elseif ($user) {
  $res = @mysql_query("SELECT * FROM users WHERE username = '$user'");
  if ($res && ($row = mysql_fetch_array($res,MYSQL_ASSOC))) {
    $newpass = random_password();
    $query = "UPDATE users SET forgot='$newpass' WHERE userid=$row[userid]";
    $res = @mysql_query($query);
    if ($res) {
      $body =
"Someone filled out the form that says you forgot your php.net CVS
password. If it wasn't you, don't worry too much about it. Unless
someone is reading your mail, there's not much they can do. (But you
may want to change your password using the instructions below, just to
be safe.)

To change your password, simply use the URL below and choose a new
password.

  http://master.php.net/forgot.php?id=$row[userid]&key=$newpass

Let us know if you have any further problems.
-- 
group@php.net
";
      mail($row['email'],"Password change instructions for $row[username]",$body,'From: PHP Group <group@php.net>');
      echo '<p>Okay, instructions on how to change your password have been sent to your email address. If you don\'t receive them, you\'ll have to contact group@php.net for help.</p>';
      foot();
      exit;
    }
    else {
      echo '<p class="warning">Something strange happened. You\'ll have to contact group@php.net for help.</p>';
    }
  }
  else {?>
<p class="warning">There's nobody named <?php echo
htmlentities(stripslashes($user))?> around here. Perhaps you need to contact
group@php.net for help.</p>
<?php
  }
}
?>
<p>Forgot your cvs password, huh? Just fill in your cvs username, and
instructions will be sent to you on how to change your password.</p>
<form method="post" action="<?php echo $PHP_SELF?>">
 <label for="user">cvs username:</labe>
 <input type="text" id="user" name="user" value="<?php echo htmlentities(stripslashes($user))?>" />
 <input type="submit" value="send help" />
</form>
<?php
foot();
