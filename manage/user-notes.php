<?php

require_once 'functions.inc';
require_once 'cvs-auth.inc';
require_once 'email-validation.inc';
// ** alerts ** remove comment when alerts are on-line
//require_once 'alert_lib.inc';

$mailto = "php-notes@lists.php.net";

$reject_text =
'You are receiving this email because your note posted
to the on-line PHP manual has been removed by one of the editors.

Read the following paragraphs carefully, because they contain
pointers to resources better suited for requesting support or
reporting bugs, none of which are to be included in manual notes
because there are mechanisms and groups in place to deal with
those issues.

The user contributed notes are not an appropriate place to
ask questions, report bugs or suggest new features; please
use the resources listed in <http://www.php.net/support.php>
for those purposes. This was clearly stated in the page
you used to submit your note, please carefully re-read
those instructions before submitting future contributions.

Bug Submissions and Feature Requests should be entered at
<http://bugs.php.net/>. For documentation errors use the
bug system, and classify the bug as "Documentation problem".
Support and ways to find answers to your questions can be found
at <http://www.php.net/support.php>.

Your note has been removed from the on-line manual.';

if (!isset($action)) header("Location: http://www.php.net/");

if($user && $pass) {
  setcookie("MAGIC_COOKIE",base64_encode("$user:$pass"),time()+3600*24*12,'/','.php.net');
}

if (!$user && isset($MAGIC_COOKIE)) {
  list($user, $pass) = explode(":", base64_decode($MAGIC_COOKIE));
}

if (!$user || !$pass || !verify_password($user,$pass)) {
  head();?>
<p>You have to log in first.</p>
<form method="post" action="<?php echo $PHP_SELF;?>">
<input type="hidden" name="action" value="<?php echo clean($action);?>" />
<table>
 <tr>
  <th align="right">CVS username:</th>
  <td><input type="text" name="user" value="<?php echo clean($user);?>" size="10" maxlength="32" /></td>
 </tr>
 <tr>
  <th align="right">CVS password:</th>
  <td><input type="password" name="pass" value="<?php echo clean($pass);?>" size="10" maxlength="32" /></td>
 </tr>
 <tr>
  <td align="center" colspan="2">
    <input type="submit" value="Log in" />
  </td>
 </tr>
</table>
</form>
<?php
  foot();
  exit;
}

if (preg_match("/^(.+)\\s+(\\d+)\$/", $action, $m)) {
  $action = $m[1]; $id = $m[2];
}

@mysql_connect("localhost","nobody","")
  or die("unable to connect to database");
@mysql_select_db("php3")
  or die("unable to select database");

switch($action) {
case 'approve':
  if ($id) {
    if ($result = mysql_query("SELECT * FROM note WHERE id=$id")) {
      if (!mysql_num_rows ($result)) {
      	die ("Note #$id doesn't exist.  It has probably been deleted/rejected already");
      }
      
      $row = mysql_fetch_array ($result);
      
      if ($row['status'] != 'na') {
      	die ("Note #$id has already been approved");
      }
      
      if ($row['id'] && mysql_query ("UPDATE note SET status=NULL WHERE id=$id")) {
        mail ($mailto, "note $row[id] approved from $row[sect] by $user", "This note has been approved and will appear in the manual\n\n----\n\n".$row['note'], "From: $user@php.net\r\nIn-Reply-To: <note-$id@php.net>");
      }
      
      print "Note #$id has been approved and will appear in the manual";
      exit;
    } else {
        head();
        echo "<p>An unknown error occured. Try again later.</p><pre>",mysql_error(),"</pre>";
        foot();
        exit;
    }
  }
case 'reject':
case 'delete':
  if ($id) {
    if ($result = mysql_query("SELECT * FROM note WHERE id=$id")) {
      if (!mysql_num_rows ($result)) {
      	die ("Note #$id doesn't exist.  It has probably been deleted/rejected already");
      }
      
      $row = mysql_fetch_array($result);
      if ($row['id'] && mysql_query("DELETE FROM note WHERE id=$id")) {
        // ** alerts **
        //$mailto .= get_emails_for_sect($row["sect"]);
        mail($mailto,"note $row[id] ".($action == "reject" ? "rejected" : "deleted")." from $row[sect] by $user","Note Submitter: $row[user]\n\n----\n\n".$row['note'],"From: $user@php.net\r\nIn-Reply-To: <note-$id@php.net>");
        if ($action == 'reject') {
          $email = clean_antispam($row['user']);
          if (is_emailable_address($email)) {
            # use an envelope sender that lets us ignore bounces
            mail($email,"note $row[id] rejected and deleted from $row[sect] by notes editor $user",$reject_text."\n\n----- Copy of your note below -----\n\n".$row['note'],"From: webmaster@php.net", '-fbounces-ignored@php.net');
          }
        }
      }
      
      //if we came from an email, report _something_
      if (isset ($_GET['report'])) {
      	print "Note #$id has been ";
	if ($action == 'reject') {
		print 'rejected';
	} else if ($action == 'delete') {
		print 'deleted';
	}
	print ' and removed from the manual';
      } else {
        //if not, just close the window
        echo '<script language="javascript">window.close();</script>';
      }
      exit;
    }
    head();
    echo "<p>An unknown error occured. Try again later.</p><pre>",mysql_error(),"</pre>";
    foot();
    exit;
  }
  /* falls through, with id not set. */
case 'preview':
case 'edit':
  if ($id) {
    head();

    if ($result = @mysql_query("SELECT *,UNIX_TIMESTAMP(ts) AS ts FROM note WHERE id=$id")) {
      if (!mysql_num_rows ($result)) {
      	die ("Note #$id doesn't exist.  It has probably been deleted/rejected already");
      }
      $row = mysql_fetch_array($result);
    }

    $email = isset($email) ? $email : addslashes($row['user']);

    if (isset($note) && $action == "edit") {
      if (@mysql_query("UPDATE note SET note='$note',user='$email',updated=NOW() WHERE id=$id")) {

        // ** alerts **
        //$mailto .= get_emails_for_sect($row["sect"]);
        mail($mailto,"note $row[id] modified in $row[sect] by $user",stripslashes($note)."\n\n--was--\n$row[note]\n\nhttp://www.php.net/manual/en/$row[sect].php","From: $user@php.net\r\nIn-Reply-To: <note-$id@php.net>");
        echo "<p>note $id edited.</p>";
      }
      else {
        echo "<p>An unknown error occured. Try again later.</p><pre>",mysql_error(),"</pre>";
        foot();
        exit;
      }
    }

    $note = isset($note) ? $note : addslashes($row['note']);

    if ($action == "preview") {
      echo "<p class=\"notepreview\">",clean_note(stripslashes($note)),
           "<br /><span class=\"author\">",date("d-M-Y h:i",$row['ts'])," ",
           clean($email),"</span></p>";
    }
?>
<form method="post" action="<?php echo $PHP_SELF;?>">
<input type="hidden" name="id" value="<?php echo $id;?>" />
<table>
 <tr>
  <th align="right">email:</th>
  <td><input type="text" name="email" value="<?php echo clean($email);?>" size="30" maxlength="80" /></td>
 </tr>
 <tr>
  <td colspan="2"><textarea name="note" cols="60" rows="10" wrap="virtual"><?php echo clean($note);?></textarea></td>
 </tr>
 <tr>
  <td align="center" colspan="2">
    <input type="submit" name="action" value="edit" />
    <input type="submit" name="action" value="preview" />
  </td>
 </tr>
</table>
</form>
<?php
    foot();
    exit;
  }
  /* falls through */
default:
  head();
  echo "<p>'$action' is not a recognized action, or no id was specified.</p>";
  foot();
}

function clean_note($text) {
    $text = htmlspecialchars($text);
    $fixes = array('<br>','<p>','</p>');
    reset($fixes);
    while (list(,$f)=each($fixes)) {
        $text=str_replace(htmlspecialchars($f), $f, $text);
        $text=str_replace(htmlspecialchars(strtoupper($f)), $f, $text);
    }
    $text = "<tt>".nl2br($text)."</tt>";
    return $text;
}
