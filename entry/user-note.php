<?php
// ** alerts ** remove comment when alerts are on-line
//require_once 'alert_lib.inc';

$mailto = 'php-notes@lists.php.net';
$failto = 'jimw@php.net';

//lang doesn't seem to be used
$lang = '';

if (!isset($user) || empty($note) || empty($sect))
  die("missing some parameters.");

@mysql_pconnect("localhost","nobody", "")
  or die("failed to connect to database");
@mysql_select_db("php3")
  or die("failed to select database");

/*
After a discussion in #php about the
vulnerability of the user notes system,
I decided to implement a bit of hack
prevention.  This makes sure that only
3 notes can be submitted per minute, which
is very reasonable considering the current
flow of notes usually submitted.  This prevents
a large flood of notes from coming in.
*/
$result = @mysql_query ('SELECT COUNT(*) FROM note WHERE ts >= '.time() - 60);

if (!$result) {
  mail ('alindeman@php.net', "failed manual note query", 'Note quota query failed -- '.mysql_error());
  die("failed to insert record");
}

list ($count) = mysql_fetch_row ($result);

if ($count > 3) {
  //Send error to myself.  If this happens too many times, I'll increase
  //the amount of allowed notes
  mail ('alindeman@php.net',
	'Note quota exceeded',
	'Too many notes submitted in one minute.  Consider increasing quota',
	'From: alindeman@php.net'
       );
  die ('[TOO MANY NOTES]');
}

$sect = ereg_replace("\.php$","",$sect);

$query = "INSERT INTO note (user, note, sect, ts, lang) VALUES ";
# no need to call htmlspecialchars() -- we handle it on output
$query .= "('$user','$note','$sect',NOW(),'$lang')";

//echo "<!--$query-->\n";
if (@mysql_query($query)) {
  $new_id = mysql_insert_id();	
  $msg = stripslashes($note);
  $msg .= "\n-- \n";
  $msg .= "Manual Page -- http://www.php.net/manual/en/$sect.php\n";
  $msg .= "Edit Note   -- http://master.php.net/manage/user-notes.php?action=edit+$new_id\n";
  $msg .= "Delete Note -- http://master.php.net/manage/user-notes.php?action=delete+$new_id&report=yes\n";
  $msg .= "Reject Note -- http://master.php.net/manage/user-notes.php?action=reject+$new_id&report=yes\n";
  # make sure we have a return address.
  if (!$user) $user = "php-general@lists.php.net";
  // see who requested an alert
  // ** alerts **
  //$mailto .=  get_emails_for_sect($sect);
  mail($mailto,"note $new_id added to $sect",$msg,"From: $user\r\nMessage-ID: <note-$new_id@php.net>");
} else {
  // mail it.
  mail($failto, "failed manual note query", $query);
  die("failed to insert record");
}
