<?php

$mailto = 'php-notes@lists.php.net';
$failto = 'jimw@php.net';

if (!isset($user) || empty($note) || empty($sect))
  die("missing some parameters.");

@mysql_connect("localhost","nobody", "")
  or die("failed to connect to database");
@mysql_select_db("php3")
  or die("failed to select database");

$sect = ereg_replace("\.php$","",$sect);

$query = "INSERT INTO note (user, note, sect, ts, lang) VALUES ";
# no need to call htmlspecialchars() -- we handle it on output
$query .= "('$user','$note','$sect',NOW(),'$lang')";

//echo "<!--$query-->\n";
if (@mysql_query($query)) {
  $new_id = mysql_insert_id();	
  $msg = stripslashes($note);
  $msg .= "\n-- \n";
  $msg .= "http://www.php.net/manual/en/$sect.php\n";
  $msg .= "http://master.php.net/manage/user-notes.php?action=edit+$new_id\n";
  $msg .= "http://master.php.net/manage/user-notes.php?action=delete+$new_id\n";
  $msg .= "http://master.php.net/manage/user-notes.php?action=reject+$new_id\n";
  # make sure we have a return address.
  if (!$user) $user = "php-general@lists.php.net";
  mail($mailto,"note $new_id added to $sect",$msg,"From: $user\r\nMessage-ID: <note-$new_id@php.net>");
} else {
  // mail it.
  mail($failto, "failed manual note query", $query);
  die("failed to insert record");
}
