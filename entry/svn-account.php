<?php

require 'email-validation.inc';
require dirname(__FILE__) . '/../include/svn-auth.inc';
require 'functions.inc';

$valid_vars = array('name','email','username','passwd','note','group');
foreach($valid_vars as $k) {
    if(isset($_REQUEST[$k])) $$k = $_REQUEST[$k];
}

if (empty($name) || empty($email) || empty($username) || empty($passwd) || empty($note) || empty($group))
  die("missing some parameters");

// Sophisticated security/spam protection question
if (empty($yesno) || $yesno != "yes") {
  die("You did not fill the form out correctly");
}

switch($group) {
case "php":
  $mailto = 'internals@lists.php.net';
  $failto = 'group@php.net';
  break;

case "pear":
  $mailto = 'pear-dev@lists.php.net';
  $failto = 'pear-group@php.net';
  break;

case "pecl":
  $mailto = 'pecl-dev@lists.php.net';
  $failto = 'group@php.net';
  break;

case "doc":
  $mailto = 'phpdoc@lists.php.net';
  $failto = 'group@php.net';
  break;

default:
  die ("Unknown group");
}

$username = strtolower($username);

# these are reserved account names. some of them (like webmaster and group)
# are pre-existing mail aliases. others are addresses that get a ton of spam
# that are used as honeypots for blocking spam. (mail to them gets the sender
# placed in qmail-smtpd's badmailfrom to block future emails.) some of these
# latter addresses were used as examples in the documentation at one point,
# which means they appear on all sorts of spam lists.
if (in_array($username,array('nse','roys','php','foo','group','core','webmaster','web','aardvark','zygote','jag','sites','er','sqlite','cvs2svn','nobody','svn','git','root')))
  die("that username is not available");

if (!preg_match('@^[a-z0-9_.-]+$@', $username)) {
  die("that username is invalid, use alphanumeric characters, or more specifically: [a-z0-9_.-]");
}

@mysql_connect("localhost","nobody", "")
  or die("failed to connect to database");
@mysql_select_db("phpmasterdb")
  or die("failed to select database");

if (!is_emailable_address(strip($email)))
  die("that email address does not appear to be valid");

$res = @mysql_query("SELECT userid FROM users WHERE username='$username'");
if ($res && mysql_num_rows($res))
  die("someone is already using that svn id");

# TODO: fail if someone with that email address has an account. right now
# this goes to the failto address since there's no password recovery
# mechanism
$passwd = strip($passwd);
$cvspasswd = crypt($passwd, substr(md5($ts), 0, 2));
$md5passwd = md5($passwd);
$svnpasswd = gen_svn_pass($username, $passwd);
$note = hsc($note);

$query = "INSERT INTO users (name,email,passwd,svnpasswd,md5passwd,username) VALUES ";
$query .= "('$name','$email','$cvspasswd','$svnpasswd','$md5passwd','$username')";

//echo "<!--$query-->\n";
if (@mysql_query($query)) {
  $new_id = mysql_insert_id();

  mysql_query("INSERT INTO users_note (userid, note, entered)"
             ." VALUES ($new_id, '$note [group: $group]', NOW())");

  $msg = $note;
  $from = "\"$name)\" <$email>";

  // The PEAR guys don't want these requests to their -dev@ list, only -group@
  if ($group != "pear") {
    mail($mailto,"SVN Account Request: $username",$msg,"From: $from\r\nMessage-ID: <cvs-account-$new_id@php.net>", "-fnoreply@php.net");
  }

  $msg .= "\n-- \n";
  $msg .= "approve: https://master2.php.net/manage/users.php?action=approve&id=$new_id\n";
  $msg .= "reject:  https://master2.php.net/manage/users.php?action=remove&id=$new_id\n";
  $msg .= "view:    https://master2.php.net/manage/users.php?id=$new_id\n";

  mail($failto,"SVN Account Request: $username",$msg,"From: $from\r\nMessage-ID: <cvs-account-$new_id-admin@php.net>", "-fnoreply@php.net");
} else {
  mail($failto,"SVN Account Request: $username",
      "Failed to insert into database: ".mysql_error()."\n\n".
      "Full name: $name\n".
      "Email:     $email\n".
      "ID:        $username\n".
      "Password:  $cvspasswd\n".
      "Purpose:   $note",
       "From: \"SVN Account Request\" <$email>");
}
