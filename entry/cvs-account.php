<?php

require 'email-validation.inc';

$mailto = 'internals@lists.php.net';
$failto = 'group@php.net';

if (empty($name) || empty($email) || empty($username) || empty($passwd) || empty($note))
  die("missing some parameters");

$username = strtolower($username);

# these are reserved account names. some of them (like webmaster and group)
# are pre-existing mail aliases. others are addresses that get a ton of spam
# that are used as honeypots for blocking spam. (mail to them gets the sender
# placed in qmail-smtpd's badmailfrom to block future emails.) some of these
# latter addresses were used as examples in the documentation at one point,
# which means they appear on all sorts of spam lists.
if (in_array($username,array('nse','roys','php','foo','group','core','webmaster','mysql','web','aardvark','zygote','jag','sites','er')))
  die("that username is not available");

@mysql_connect("localhost","nobody", "")
  or die("failed to connect to database");
@mysql_select_db("phpmasterdb")
  or die("failed to select database");

if (!is_emailable_address(stripslashes($email)))
  die("that email address does not appear to be valid");

$res = @mysql_query("SELECT userid FROM users WHERE username='$username'");
if ($res && mysql_num_rows($res))
  die("someone is already using that cvs id");

# TODO: fail if someone with that email address has an account. right now
# this goes to the failto address since there's no password recovery
# mechanism

$passwd = crypt(stripslashes($passwd), substr(md5(time()), 0, 2));

$query = "INSERT INTO users (name,email,passwd,username) VALUES ";
$query .= "('$name','$email','$passwd','$username')";

//echo "<!--$query-->\n";
if (@mysql_query($query)) {
  $new_id = mysql_insert_id();	

  mysql_query("INSERT INTO users_note (userid, note, entered)"
             ." VALUES ($new_id, '$note', NOW())");

  $msg = stripslashes($note);

  $from = '"'.stripslashes($name).'" <'.stripslashes($email).">";

  mail($mailto,"CVS Account Request: $username",$msg,"From: $from\r\nMessage-ID: <cvs-account-$new_id@php.net>");

  $msg .= "\n-- \n";
  $msg .= "approve: https://master.php.net/manage/users.php?action=approve&id=$new_id\n";
  $msg .= "reject:  https://master.php.net/manage/users.php?action=remove&id=$new_id\n";
  $msg .= "view:    https://master.php.net/manage/users.php?id=$new_id\n";

  mail($failto,"CVS Account Request: $username",$msg,"From: $from\r\nMessage-ID: <cvs-account-$new_id-admin@php.net>");
} else {
  mail($failto,"CVS Account Request: $username",
      "Failed to insert into database: ".mysql_error()."\n\n".
      "Full name: $name\n".
      "Email:     $email\n".
      "ID:        $username\n".
      "Password:  $passwd\n".
      "Purpose:   $note",
       "From: \"CVS Account Request\" <$email>");
}
