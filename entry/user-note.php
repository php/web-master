<?php
// ** alerts ** remove comment when alerts are on-line
//require_once 'alert_lib.inc';

$mailto = 'php-notes@lists.php.net';
$failto = 'jimw@php.net, alindeman@php.net';

if (!isset($user) || empty($note) || empty($sect))
  die("missing some parameters.");

@mysql_connect("localhost","nobody", "")
  or die("failed to connect to database");
@mysql_select_db("phpmasterdb")
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
$query = 'SELECT COUNT(*) FROM note WHERE ts >= (NOW() - INTERVAL 1 MINUTE)';
$result = @mysql_query ($query);

if (!$result) {
  mail ($failto,
       'failed manual note query',
       "Query Failed: $query\nError: ".mysql_error(),
       'From: webmaster@php.net');
  die("failed to query note db");
}

list ($count) = mysql_fetch_row ($result);

if ($count >= 3) {
  //Send error to myself.  If this happens too many times, I'll increase
  //the amount of allowed notes
  mail ('alindeman@php.net,didou@php.net',
	'[php-notes] Quota exceeded',
	'Too many notes submitted in one minute.  Consider increasing quota' . "\n" . 
        'Occured at '.date ('M d, Y g:i:s A') . "\n" .
	"User   : $user\n" .
	"Section: $section\n" .
	"Note   : $note",
	'From: webmaster@php.net'
       );
  die ('[TOO MANY NOTES]');
}

$sect = ereg_replace("\.php$","",$sect);

$query = "INSERT INTO note (user, note, sect, ts, status) VALUES ";
# no need to call htmlspecialchars() -- we handle it on output
$query .= "('$user','$note','$sect',NOW(), NULL)";

//na = not approved.  Don't display notes until they are approved by an editor
//This has been reverted until it has been discussed further.

//echo "<!--$query-->\n";
if (@mysql_query($query)) {
  $new_id = mysql_insert_id();	
  $msg = stripslashes($note);
  $msg .= "\n----\n";
  $msg .= "Manual Page -- http://www.php.net/manual/en/$sect.php\n";
  $msg .= "Edit        -- http://master.php.net/manage/user-notes.php?action=edit+$new_id\n";
  //$msg .= "Approve     -- http://master.php.net/manage/user-notes.php?action=approve+$new_id&report=yes\n";
  $msg .= "Delete      -- http://master.php.net/manage/user-notes.php?action=delete+$new_id&report=yes\n";
  $msg .= "  Reason: bad code                  -- http://master.php.net/manage/user-notes.php?action=delete+$new_id&report=yes&reason=bad+code\n";
  $msg .= "  Reason: spam                      -- http://master.php.net/manage/user-notes.php?action=delete+$new_id&report=yes&reason=spam\n";
  $msg .= "  Reason: useless example           -- http://master.php.net/manage/user-notes.php?action=delete+$new_id&report=yes&reason=useless+example\n";
  $msg .= "  Reason: contains commercial links -- http://master.php.net/manage/user-notes.php?action=delete+$new_id&report=yes&reason=contains+commercial+links\n";
  $msg .= "  Reason: useless note              -- http://master.php.net/manage/user-notes.php?action=delete+$new_id&report=yes&reason=useless+note\n";
  $msg .= "Reject      -- http://master.php.net/manage/user-notes.php?action=reject+$new_id&report=yes\n";
  $msg .= "Search      -- http://master.php.net/manage/user-notes.php\n";
  # make sure we have a return address.
  if (!$user) $user = "php-general@lists.php.net";
  # strip spaces in email address, or will get a bad To: field
  $user = str_replace(' ','',$user);
  // see who requested an alert
  // ** alerts **
  //$mailto .=  get_emails_for_sect($sect);
  mail($mailto,"note $new_id added to $sect",$msg,"From: $user\r\nMessage-ID: <note-$new_id@php.net>");
} else {
  // mail it.
  mail($failto,
      'failed manual note query',
      "Query Failed: $query\nError: ".mysql_error(),
      'From: webmaster@php.net');
  die("failed to insert record");
}
