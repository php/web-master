<?php // vim: et ts=2 sw=2

#TODO:
# acls
# handle flipping of the sort views

require '../include/login.inc';
require '../include/email-validation.inc';

define('PHP_SELF', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'));
$valid_vars = array('search','username','id','in','unapproved','begin','max','order','full', 'action', 'noclose');
foreach($valid_vars as $k) {
    $$k = isset($_REQUEST[$k]) ? $_REQUEST[$k] : false;
}
if($id) $id = (int)$id;

$mailto = "group@php.net";

head("user administration");

db_connect();

# ?username=whatever will look up 'whatever' by email or svn username
if ($username && !$id) {
  $query = "SELECT userid FROM users"
         . " WHERE username='$username' OR email='$username'";
  $res = db_query($query);
  if (!($id = @mysql_result($res,0))) {
    warn("wasn't able to find user matching '".clean($username)."'");
  }
}


if ($id && $action) {
  if (!is_admin($user)) {
    warn("you're not allowed to take actions on users.");
    exit;
  }
  switch ($action) {
  case 'approve':
    if (db_query("UPDATE users SET cvsaccess=1, enable=1 WHERE userid=$id")
     && mysql_affected_rows()) {
      $userinfo = fetch_user($id);
      $message =
"Your SVN account ($userinfo[username]) was created.

You should be able to log into the SVN server within the hour, and
your $userinfo[username]@php.net forward to $userinfo[email] should
be active within the next 24 hours.

Welcome to the PHP development team! If you encounter any problems
with your SVN account, feel free to send us a note at group@php.net.";
      mail($userinfo['email'],"SVN Account Request: $userinfo[username]",$message,"From: PHP Group <group@php.net>", "-fnoreply@php.net");

      mail($mailto,"SVN Account Request: $userinfo[username] approved by $user","Approved $userinfo[username]","From: PHP Group <group@php.net>\nIn-Reply-To: <cvs-account-$id-admin@php.net>", "-fnoreply@php.net");
      if (!$noclose) {
        echo '<script language="javascript">window.close();</script>';
        exit;
      }
      warn("record $id ($userinfo[username]) approved");
    }
    else {
      warn("wasn't able to grant svn access to id $id.");
    }
    break;
  case 'remove':
    $userinfo = fetch_user($id);
    if (db_query("DELETE FROM users WHERE userid=$id")
     && mysql_affected_rows()) {
      $message = $userinfo['cvsaccess'] ? 
"Your SVN account ($userinfo[username]) was deleted.

Feel free to send us a note at group@php.net to find out why this
was done."
:
"Your SVN account request ($userinfo[username]) was denied.

The most likely reason is that you did not read the reasons for
which SVN accounts are granted, and your request failed to meet
the list of acceptable criteria.

We urge you to make another appeal for a SVN account, but first
it helps to write the appropriate list and:

 * Introduce yourself
 * Explain what you want to work on
 * And show what work you've already done (patches)

Choose a list that relates to your request:

 * Internals:     internals@lists.php.net 
 * Documentation: phpdoc@lists.php.net 
 * PECL:          pecl-dev@lists.php.net 
 * PEAR:          pear-group@lists.php.net 
 * Other:         group@php.net 

PHP accounts are granted to developers who have earned the trust
of existing PHP developers through patches, and have demonstrated
the ability to work with others.
";
      mail($userinfo['email'],"SVN Account Request: $userinfo[username]",$message,"From: PHP Group <group@php.net>", "-fnoreply@php.net");
      mail($mailto,$userinfo['cvsaccess'] ? "SVN Account Deleted: $userinfo[username] deleted by $user" : "SVN Account Rejected: $userinfo[username] rejected by $user","Nuked $userinfo[username]","From: PHP Group <group@php.net>\nIn-Reply-To: <cvs-account-$id-admin@php.net>", "-fnoreply@php.net");
      db_query("DELETE FROM users_note WHERE userid=$id");
      if (!$noclose) {
        echo '<script language="javascript">window.close();</script>';
        exit;
      }
      warn("record $id ($userinfo[username]) removed");
    }
    else {
      warn("wasn't able to delete id $id.");
    }
    break;
  default:
    warn("that action ('$action') is not understood.");
  }
}

if ($id && $in) {
  if (!can_modify($user,$id)) {
    warn("you're not allowed to modify this user.");
  }
  else {
    if ($error = invalid_input($in)) {
      warn($error);
    }
    else {
      if (!empty($in['rawpasswd'])) {
        // need to get rid of these
        $in['passwd'] = crypt($in['rawpasswd'],substr(md5(time()),0,2));
        $in['svnpasswd'] = gen_svn_pass($user, $in['rawpasswd']);
        $in['md5passwd'] = md5($in['rawpasswd']);
      }

      $cvsaccess = empty($in['cvsaccess']) ? 0 : 1;
      $enable = empty($in['enable']) ? 0 : 1;
      $spamprotect = empty($in['spamprotect']) ? 0 : 1;
      $verified = empty($in['verified']) ? 0 : 1;
      $use_sa = isset($in['use_sa']) ? (int)$in['use_sa'] : 0;
      $greylist = empty($in['greylist']) ? 0 : 1;

      if ($id) {
        # update main table data
        if (isset($in['email']) && isset($in['name'])) {
          $query = "UPDATE users SET name='$in[name]',email='$in[email]'"
                 . (!empty($in['passwd']) ? ",passwd='$in[passwd]'" : "")
                 . (!empty($in['svnpasswd']) ? ",svnpasswd='$in[svnpasswd]'" : "")
                 . (!empty($in['md5passwd']) ? ",md5passwd='$in[md5passwd]'" : "")
                 . (!empty($in['sshkey']) ? ",ssh_keys='$in[sshkey]'" : "")
                 . ((is_admin($user) && isset($in['username'])) ? ",username='$in[username]'" : "")
                 . (is_admin($user) ? ",cvsaccess=$cvsaccess" : "")
                 . ",spamprotect=$spamprotect"
                 . ",verified=$verified"
                 . ",enable=$enable"
                 . ",use_sa=$use_sa"
                 . ",greylist=$greylist"
                 . (!empty($in['passwd']) ? ",pchanged=" . time() : "")
                 . " WHERE userid=$id";
          if (!empty($in['passwd'])) {
            // Kill the session data after updates :)
            $_SERVER["credentials"] = array();
            db_query($query);
          } else {
            db_query($query);
          }

          if(!empty($in['purpose'])) {
              $purpose = htmlspecialchars($in['purpose'], ENT_QUOTES, 'UTF-8');
              $query = "INSERT INTO users_note (userid, note, entered) VALUES ($id, '$purpose', NOW())";
              db_query($query);
          }
        }

        warn("record $id updated");
        $id = false;
      }
      else {
        $query = "INSERT users SET name='$in[name]',email='$in[email]'"
               . (!empty($in['username']) ? ",username='$in[username]'" : "")
               . (!empty($in['passwd']) ? ",passwd='$in[passwd]'" : "")
               . (!empty($in['svnpasswd']) ? ",svnpasswd='$in[svnpasswd]'" : "")
               . (!empty($in['md5passwd']) ? ",md5passwd='$in[md5passwd]'" : "")
               . (!empty($in['sshkey']) ? ",ssh_keys='$in[sshkey]'" : "")
               . (is_admin($user) ? ",cvsaccess=$cvsaccess" : "")
               . ",spamprotect=$spamprotect"
               . ",use_sa=$use_sa"
               . ",greylist=$greylist"
               . ",verified=$verified";
        db_query($query);

        $nid = mysql_insert_id();

        warn("record $nid added");
      }
    }
  }
}

if ($id) {
  $query = "SELECT * FROM users"
         . " WHERE users.userid=$id";
  $res = db_query($query);
  $row = mysql_fetch_array($res);
  if (!$row) $id = false;
}

if ($id) {
?>
<style>
table.useredit tr {
   vertical-align: top;
}
</style>
<table class="useredit">
<form method="post" action="<?php echo PHP_SELF;?>">
<input type="hidden" name="id" value="<?php echo $row['userid'];?>" />
<tr>
 <th align="right">Name:</th>
 <td><input type="text" name="in[name]" value="<?php echo htmlspecialchars($row['name']);?>" size="40" maxlength="255" /></td>
</tr>
<tr>
 <th align="right">Email:</th>
 <td><input type="text" name="in[email]" value="<?php echo htmlspecialchars($row['email']);?>" size="40" maxlength="255" /><br/>
  	<input type="checkbox" name="in[enable]"<?php echo $row['enable'] ? " checked" : "";?> /> Enable email for my account.
 </td>
</tr>
<?php if (!is_admin($user)) {?>
<tr>
 <th align="right">SVN username:</th>
 <td><?php echo htmlspecialchars($row['username']);?></td>
</tr>
<?php } ?>
<tr>
 <td colspan="2">Leave password fields blank to leave password unchanged.</td>
</tr>
<tr>
 <th align="right">Password:</th>
 <td><input type="password" name="in[rawpasswd]" value="" size="20" maxlength="120" /></td>
</tr>
<tr>
 <th align="right">Password (again):</th>
 <td><input type="password" name="in[rawpasswd2]" value="" size="20" maxlength="120" /></td>
</tr>
<?php if (is_admin($user)) {?>
<tr>
 <th align="right">Password (crypted):</th>
 <td><input type="text" name="in[passwd]" value="<?php echo htmlspecialchars($row['passwd']);?>" size="20" maxlength="20" /></td>
</tr>
<tr>
 <th align="right">SVN username:</th>
 <td><input type="text" name="in[username]" value="<?php echo htmlspecialchars($row['username']);?>" size="16" maxlength="16" /></td>
</tr>
<?php }?>
<?php if (is_admin($user)) {?>
<tr>
 <th align="right">SVN access?</th>
 <td><input type="checkbox" name="in[cvsaccess]"<?php echo $row['cvsaccess'] ? " checked" : "";?> /></td>
</tr>
<?php } else { ?>
<tr>
 <th align="right">Has SVN access?</th>
 <td><?php echo $row['cvsaccess'] ? "Yes" : "No";?></td>
</tr>
<?php } ?>
<tr>
 <th align="right">Use Challenge/Response spam protection?</th>
 <td><input type="checkbox" name="in[spamprotect]"<?php echo $row['spamprotect'] ? " checked" : "";?> />
 <?php if ($row['username'] == $user) { ?>
 <br/>
 <a href="challenge-response.php">Show people on my quarantine list</a>
 <?php } ?>
 </td>
</tr>
<tr>
 <th align="right">SpamAssassin threshold</th>
 <td>Block mail scoring <input type="text" name="in[use_sa]" value="<?php echo $row['use_sa'] ?>" size="4" maxlength="4"/> or higher in SpamAssassin tests.  Set to 0 to disable.</td>
</tr>
<tr>
 <th align="right">Greylist</th>
 <td>Delay reception of your incoming mail by a minimum of one hour using a 451 response.<br/>
  Legitimate senders will continue to try to deliver the mail, whereas
  spammers will typically give up and move on to spamming someone else.<br/>
  See <a href="http://projects.puremagic.com/greylisting/whitepaper.html">this whitepaper</a> for more information on greylisting.<br/>
  <input type="checkbox" name="in[greylist]"<?php echo $row['greylist'] ? " checked" : "";?> /> Enable greylisting on my account</td>
</tr>
<tr>
 <th align="right">Verified?</th>
 <td><input type="checkbox" name="in[verified]"<?php echo $row['verified'] ? " checked" : "";?> /> Note: Do not worry about this value. It's sometimes used to check if old-timers are still around.</td>
</tr>
<tr>
 <th align="right">SSH Key</th>
 <td><textarea cols="50" rows="5" name="in[sshkey]"><?php echo htmlspecialchars($row['ssh_keys']) ?></textarea></td>
</tr>
<tr>
 <th align="right">Add Note: </th>
 <td><textarea cols="50" rows="5" name="in[purpose]"></textarea></td>
</tr>
<tr>
 <td><input type="submit" value="<?php echo $id ? "Update" : "Add";?>" />
</tr>
</form>
<?php if (is_admin($user) && !$row['cvsaccess']) {?>
<tr>
 <form method="get" action="<?php echo PHP_SELF;?>">
  <input type="hidden" name="action" value="remove" />
  <input type="hidden" name="noclose" value="1" />
  <input type="hidden" name="id" value="<?php echo $id?>" />
  <td><input type="submit" value="Reject" />
 </form>
 <form method="get" action="<?php echo PHP_SELF;?>">
  <input type="hidden" name="action" value="approve" />
  <input type="hidden" name="noclose" value="1" />
  <input type="hidden" name="id" value="<?php echo $id?>" />
  <td><input type="submit" value="Approve" />
 </form>
</tr>
<?php }?>
</table>
<?php
  if ($id) {
    $res = db_query("SELECT note, UNIX_TIMESTAMP(entered) AS ts FROM users_note WHERE userid=$id");
    echo "<b>notes</b>";
    while ($res && $row = mysql_fetch_assoc($res)) {
      echo "<div>", date("r",$row['ts']), "<br />".htmlspecialchars($row['note'], ENT_QUOTES, 'UTF-8')."</div>";
    }
  }
  foot();
  exit;
}
?>
<div>
<div style="float:right">
   <form method="GET" action="<?php echo PHP_SELF;?>">
    <input type="text" name="search" value="<?php echo clean($search);?>" />
    <input type="submit" value="search"><br>
    <input type="checkbox" name="searchnotes" value="1" <?php echo isset($_GET['searchnotes']) ? 'checked="checked"' : ''?>> Search notes
   </form>
</div>
<div>
    <a href="<?php echo PHP_SELF . "?username=$user";?>">edit your entry</a>
  | <a href="<?php echo PHP_SELF . "?unapproved=1";?>">see outstanding requests</a>
</div>
</div>
<?php

$begin = $begin ? (int)$begin : 0;
$full = $full ? 1 : (!$full && ($search || $unapproved) ? 1 : 0);
$max = $max ? (int)$max : 20;
$searchnotes = !empty($_GET['searchnotes']);

$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS users.userid,cvsaccess,username,name,email FROM users ";
if  ($search) {
    $query .= "WHERE (MATCH(name,email,username) AGAINST ('$search') OR username = '$search') ";

    if ($searchnotes) {
        $in = '';
        $notes_query = "SELECT userid FROM users_note WHERE MATCH(note) AGAINST ('$search')";
        $res = db_query($notes_query);
        while ($row = mysql_fetch_array($res)) {
            if ($in) {
                $in .= ', ';
            }
            $in .= $row[0];
        }
        if ($in) {
            $query .= " OR userid IN ($in) ";
        }
    }
} else {
    $query .= ' WHERE 1=1 ';
}

if ($unapproved) {
    $query .= ' AND (username IS NOT NULL AND NOT cvsaccess) ';
}

if ($order) {
    $query .= " ORDER BY $order ";
}
$query .= " LIMIT $begin,$max ";
$res = db_query($query);
#echo $query;

$res2 = db_query("SELECT FOUND_ROWS()");
$total = mysql_result($res2,0);


$extra = array(
  "search" => stripslashes($search),
  "order" => $order,
  "begin" => $begin,
  "max" => $max,
  "full" => $full,
  "unapproved" => $unapproved,
  "searchnotes" => (int)$searchnotes,
);

show_prev_next($begin,mysql_num_rows($res),$max,$total,$extra);
?>
<table border="0" cellspacing="1" width="100%">
<tr bgcolor="#aaaaaa">
 <th><a href="<?php echo PHP_SELF,'?',array_to_url($extra,array("full" => $full ? 0 : 1));?>"><?php echo $full ? "&otimes;" : "&oplus;";?></a></th>
 <th><a href="<?php echo PHP_SELF,'?',array_to_url($extra,array("order"=>"name"));?>">name</a></th>
 <th><a href="<?php echo PHP_SELF,'?',array_to_url($extra,array("order"=>"email"));?>">email</a></th>
 <th><a href="<?php echo PHP_SELF,'?',array_to_url($extra,array("order"=>"username"));?>">username</a></th>
</tr>
<?php
$color = '#dddddd';
while ($row = mysql_fetch_array($res)) {
?>
<tr bgcolor="<?php echo $color;?>">
 <td align="center"><a href="<?php echo PHP_SELF . "?id=$row[userid]";?>">edit</a></td>
 <td><?php echo htmlspecialchars($row['name']);?></td>
 <td><?php echo htmlspecialchars($row['email']);?></td>
 <td<?php if ($row['username'] && !$row['cvsaccess']) echo ' bgcolor="#ff',substr($color,2),'"';?>><?php echo htmlspecialchars($row['username']);?><?php if ($row['username'] && is_admin($user)) { if (!$row['cvsaccess']) echo ' <a href="'. PHP_SELF . "?action=approve&amp;noclose=1&amp;id=$row[userid]\" title=\"approve\">+</a>"; echo ' <a href="'.PHP_SELF."?action=remove&amp;noclose=1&amp;id=$row[userid]\" title=\"remove\">&times;</a>"; }?></td>
</tr>
<?php
  if ($full && !empty($row['note'])) {?>
<tr bgcolor="<?php echo $color;?>">
 <td></td><td colspan="3"><?php echo htmlspecialchars($row['note']);?></td>
</tr>
<?php
  }
  $color = substr($color,2,2) == 'dd' ? '#eeeeee' : '#dddddd';
}
?>
</table>
<?php show_prev_next($begin,mysql_num_rows($res),$max,$total,$extra); ?>
<p><a href="<?php echo PHP_SELF;?>?id=0">add a new user</a></p>
<?php
foot();

function invalid_input($in) {
  if (isset($in['email']) && strlen($in['email']) && !is_emailable_address($in['email'])) {
    return "'".clean($in['email'])."' does not look like a valid email address";
  }
  if (isset($in['username']) && !preg_match("/^[-\w]+\$/",$in['username'])) {
    return "'".clean($in['username'])."' is not a valid username";
  }
  if (isset($in['rawpasswd']) && $in['rawpasswd'] != $in['rawpasswd2']) {
    return "the passwords you specified did not match!";
  }
  if (isset($in['sshkey']) && !verify_ssh_keys($in['sshkey'])) {
    return "not a valid ssh key!";
  }

  return false;
}

function is_admin($user) {
  #TODO: use acls, once implemented.
  if (in_array($user,array("jimw","rasmus","andrei","zeev","andi","sas","thies","rubys","ssb", "wez", "philip", "davidc", "helly","derick","bjori", "pajoye" ))) return true;
}

# returns false if $user is not allowed to modify $userid
function can_modify($user,$userid) {
  if (is_admin($user)) return true;

  $userid = (int)$userid;

  $quser = addslashes($user);
  $query = "SELECT userid FROM users"
         . " WHERE userid=$userid"
         . "   AND (email='$quser' OR username='$quser')";

  $res = db_query($query);
  return $res ? mysql_num_rows($res) : false;
}

function fetch_user($user) {
  $query = "SELECT * FROM users LEFT JOIN users_note USING (userid)";
  if ((int)$user) {
    $query .= " WHERE users.userid=$user";
  }
  else {
    $quser = addslashes($user);
    $query .= " WHERE username='$quser' OR email='$quser'";
  }

  if ($res = db_query($query)) {
    return mysql_fetch_array($res);
  }

  return false;
}
