<?php // vim: et ts=2 sw=2

#TODO:
# acls
# handle flipping of the sort views

require '../include/login.inc';
require '../include/email-validation.inc';
require '../include/email-templates.inc';

define('PHP_SELF', hsc($_SERVER['PHP_SELF']));
$valid_vars = array('search','username','id','in','unapproved','begin','max','order','full', 'action');
foreach($valid_vars as $k) {
    $$k = isset($_REQUEST[$k]) ? $_REQUEST[$k] : false;
}
if($id) $id = (int)$id;

head("user administration");

db_connect();

# ?username=whatever will look up 'whatever' by email or username
if ($username && !$id) {
  $query = "SELECT userid FROM users"
         . " WHERE username='$username' OR email='$username'";
  $res = db_query($query);
  if (!($id = @mysql_result($res,0))) {
    warn("wasn't able to find user matching '".clean($username)."'");
  }
}


if ($id && $action) {
  if (!is_admin($_SESSION["username"])) {
    warn("you're not allowed to take actions on users.");
    exit;
  }

  switch ($action) {
  case 'approve':
    user_approve((int)$id);
    break;

  case 'remove':
    user_remove((int)$id);
    break;

  default:
    warn("that action ('$action') is not understood.");
  }
}

if ($id && $in) {
  if (!can_modify($_SESSION["username"],$id)) {
    warn("you're not allowed to modify this user.");
  }
  else {
    if ($error = invalid_input($in)) {
      warn($error);
    }
    else {
      if (!empty($in['rawpasswd'])) {
        $userinfo = fetch_user($id);
        $in['svnpasswd'] = gen_svn_pass($userinfo["username"], $in['rawpasswd']);
      }

      $cvsaccess = empty($in['cvsaccess']) ? 0 : 1;
      $enable = empty($in['enable']) ? 0 : 1;
      $spamprotect = empty($in['spamprotect']) ? 0 : 1;
      $verified = empty($in['verified']) ? 0 : 1;
      $use_sa = empty($in['use_sa']) ? 0 : (int)$in['use_sa'];
      $greylist = empty($in['greylist']) ? 0 : 1;

      if ($id) {
        # update main table data
        if (!empty($in['email']) && !empty($in['name'])) {
          $query = "UPDATE users SET name='$in[name]',email='$in[email]'"
                 . (!empty($in['svnpasswd']) ? ",svnpasswd='$in[svnpasswd]'" : "")
                 . (!empty($in['sshkey']) ? ",ssh_keys='".escape(html_entity_decode($in[sshkey],ENT_QUOTES))."'" : ",ssh_keys=''")
                 . ((is_admin($_SESSION["username"]) && !empty($in['username'])) ? ",username='$in[username]'" : "")
                 . (is_admin($_SESSION["username"]) ? ",cvsaccess=$cvsaccess" : "")
                 . ",spamprotect=$spamprotect"
                 . ",verified=$verified"
                 . ",enable=$enable"
                 . ",use_sa=$use_sa"
                 . ",greylist=$greylist"
                 . (!empty($in['rawpasswd']) ? ",pchanged=" . $ts : "")
                 . " WHERE userid=$id";
          if (!empty($in['passwd'])) {
            // Kill the session data after updates :)
            $_SERVER["credentials"] = array();
            db_query($query);
          } else {
            db_query($query);
          }

          if(!empty($in['purpose'])) {
              $purpose = hsc($in['purpose']);
              $query = "INSERT INTO users_note (userid, note, entered) VALUES ($id, '$purpose', NOW())";
              db_query($query);
          }

          if(!empty($in['profile_markdown'])) {
            $profile_markdown = $in['profile_markdown'];
            $profile_html = Markdown($profile_markdown);
            $profile_markdown = mysql_real_escape_string($profile_markdown);
            $profile_html = mysql_real_escape_string($profile_html);
            $query = "INSERT INTO users_profile (userid, markdown, html) VALUES ($id, '$profile_markdown', '$profile_html')
                      ON DUPLICATE KEY UPDATE markdown='$profile_markdown', html='$profile_html'";
            db_query($query);
          }
        }

        warn("record $id updated");
        $id = false;
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
 <td><input type="text" name="in[name]" value="<?php echo $row['name'];?>" size="40" maxlength="255" /></td>
</tr>
<tr>
 <th align="right">Email:</th>
 <td><input type="text" name="in[email]" value="<?php echo $row['email'];?>" size="40" maxlength="255" /><br/>
  	<input type="checkbox" name="in[enable]"<?php echo $row['enable'] ? " checked" : "";?> /> Enable email for my account.
 </td>
</tr>
<?php if (!is_admin($_SESSION["username"])) {?>
<tr>
 <th align="right">VCS username:</th>
 <td><?php echo hscr($row['username']);?></td>
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
<?php if (is_admin($_SESSION["username"])) {?>
<tr>
 <th align="right">Password (crypted):</th>
 <td><input type="text" name="in[passwd]" value="<?php echo hscr($row['passwd']);?>" size="20" maxlength="20" /></td>
</tr>
<tr>
 <th align="right">VCS username:</th>
 <td><input type="text" name="in[username]" value="<?php echo hscr($row['username']);?>" size="16" maxlength="16" /></td>
</tr>
<?php }?>
<?php if (is_admin($_SESSION["username"])) {?>
<tr>
 <th align="right">VCS access?</th>
 <td><input type="checkbox" name="in[cvsaccess]"<?php echo $row['cvsaccess'] ? " checked" : "";?> /></td>
</tr>
<?php } else { ?>
<tr>
 <th align="right">Has VCS access?</th>
 <td><?php echo $row['cvsaccess'] ? "Yes" : "No";?></td>
</tr>
<?php } ?>
<tr>
 <th align="right">Use Challenge/Response spam protection?</th>
 <td><input type="checkbox" name="in[spamprotect]"<?php echo $row['spamprotect'] ? " checked" : "";?> />
 <?php if ($row['username'] == $_SESSION["username"]) { ?>
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
 <td><textarea cols="50" rows="5" name="in[sshkey]"><?php echo escape(html_entity_decode($row['ssh_keys'],ENT_QUOTES)); ?></textarea>
  <p>Adding/editing the SSH key takes a few minutes to propagate to the server.<br>
  Multiple keys are allowed, separated using a newline.</p></td>
</tr>
<?php
  if ($id) {
    $res = db_query("SELECT markdown FROM users_profile WHERE userid=$id");
    $row['profile_markdown'] = '';
    if ($profile_row = mysql_fetch_assoc($res)) {
        $row['profile_markdown'] = $profile_row['markdown'];
    }
?>
<tr>
 <th align="right">People Profile<br>(<a href="http://people.php.net/user.php?username=<?php echo urlencode($row['username']);?>"><?php echo hscr($row['username']);?>'s page</a>)</th>
 <td>
     <p>Use <a href="http://michelf.ca/projects/php-markdown/dingus/" title="PHP Markdown: Dingus">Markdown</a>. Type as much as you like.</p>
     <div><textarea cols="100" rows="20" name="in[profile_markdown]"><?php echo clean($row['profile_markdown']); ?></textarea></div>
 </td>
</tr>
<?php
  }
?>
<tr>
 <th align="right">Add Note: </th>
 <td><textarea cols="50" rows="5" name="in[purpose]"></textarea></td>
</tr>
<tr>
 <td><input type="submit" value="<?php echo $id ? "Update" : "Add";?>" />
</tr>
</form>
<?php if (is_admin($_SESSION["username"]) && !$row['cvsaccess']) {?>
<tr>
 <form method="get" action="<?php echo PHP_SELF;?>">
  <input type="hidden" name="action" value="remove" />
  <input type="hidden" name="id" value="<?php echo $id?>" />
  <td><input type="submit" value="Reject" />
 </form>
 <form method="get" action="<?php echo PHP_SELF;?>">
  <input type="hidden" name="action" value="approve" />
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
      echo "<div>", date("r",$row['ts']), "<br />".$row['note']."</div>";
    }
  }
  foot();
  exit;
}
?>
<div>
<div>
  <a href="<?php echo PHP_SELF . "?unapproved=1";?>">see outstanding requests</a>
</div>
</div>
<?php

$begin = $begin ? (int)$begin : 0;
$full = $full ? 1 : (!$full && ($search || $unapproved) ? 1 : 0);
$max = $max ? (int)$max : 20;
$searchnotes = !empty($_GET['searchnotes']); /* FIXME: There is no such option in the search box.. */

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

?>
<table>
<thead>
<?php show_prev_next($begin,mysql_num_rows($res),$max,$total,$extra, false); ?>
</thead>
<tbody>
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
 <td><?php echo $row['name'];?></td>
 <td><?php echo $row['email'];?></td>
 <td<?php if ($row['username'] && !$row['cvsaccess']) echo ' bgcolor="#ff',substr($color,2),'"';?>><?php echo hscr($row['username']);?><?php if ($row['username'] && is_admin($user)) { if (!$row['cvsaccess']) echo ' <a href="'. PHP_SELF . "?action=approve&amp;id=$row[userid]\" title=\"approve\">+</a>"; echo ' <a href="'.PHP_SELF."?action=remove&amp;id=$row[userid]\" title=\"remove\">&times;</a>"; }?></td>
</tr>
<?php
  if ($full && !empty($row['note'])) {?>
<tr bgcolor="<?php echo $color;?>">
 <td></td><td colspan="3"><?php echo hsc($row['note']);?></td>
</tr>
<?php
  }
  $color = substr($color,2,2) == 'dd' ? '#eeeeee' : '#dddddd';
}
?>
</tbody>
<tfooter>
<?php show_prev_next($begin,mysql_num_rows($res),$max,$total,$extra, false); ?>
</tfooter>
</table>
<?php
foot();

