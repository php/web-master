<?php // vim: et ts=2 sw=2

#TODO:
# acls
# handle flipping of the sort views

require '../include/login.inc';
require '../include/email-validation.inc';
require '../include/email-templates.inc';

function csrf_generate(&$mydata, $name) {
  $mydata["CSRF"][$name] = $csrf = hash("sha512", mt_rand(0,mt_getrandmax()));
  return "$name:$csrf";
}
function csrf_validate(&$mydata, $name) {
  $val = filter_input(INPUT_POST, "csrf", FILTER_UNSAFE_RAW);
  list($which, $hash) = explode(":", $val, 2);

  if ($which != $name) {
    warn("Failed CSRF Check");
    foot();
    exit;
  }

  if ($mydata["CSRF"][$name] != $hash) {
    warn("Failed CSRF Check");
    foot();
    exit;
  }

  csrf_generate($mydata, $name);
  return true;
}

$indesc = [
  "id"               => FILTER_VALIDATE_INT,
  "rawpasswd"        => FILTER_UNSAFE_RAW,
  "rawpasswd2"       => FILTER_UNSAFE_RAW,
  "svnpasswd"        => FILTER_SANITIZE_STRIPPED,
  "cvsaccess"        => ["filter" => FILTER_CALLBACK, "options" => function($v) { if ($v == "on") { return true; } return false; }],
  "enable"           => ["filter" => FILTER_CALLBACK, "options" => function($v) { if ($v == "on") { return true; } return false; }],
  "spamprotect"      => ["filter" => FILTER_CALLBACK, "options" => function($v) { if ($v == "on") { return true; } return false; }],
  "greylist"         => ["filter" => FILTER_CALLBACK, "options" => function($v) { if ($v == "on") { return true; } return false; }],
  "verified"         => FILTER_VALIDATE_INT,
  "use_sa"           => FILTER_VALIDATE_INT,
  "email"            => FILTER_SANITIZE_EMAIL,
  "name"             => FILTER_SANITIZE_SPECIAL_CHARS,
  "sshkey"           => FILTER_SANITIZE_SPECIAL_CHARS,
  "purpose"          => FILTER_SANITIZE_SPECIAL_CHARS,
  "profile_markdown" => FILTER_UNSAFE_RAW,
];

$rawin    = filter_input_array(INPUT_POST) ?: [];
$in       = isset($rawin["in"]) ? filter_var_array($rawin["in"], $indesc, false) : [];
$id       = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT) ?: 0;
$username = filter_input(INPUT_GET, "username", FILTER_SANITIZE_STRIPPED) ?: 0;

head("user administration");

db_connect();

# ?username=whatever will look up 'whatever' by email or username
if ($username) {
  $tmp = filter_input(INPUT_GET, "username", FILTER_CALLBACK, ["options" => "mysql_real_escape_string"]) ?: "";
  $query = "SELECT userid FROM users"
         . " WHERE username='$tmp' OR email='$tmp'";
  $res = db_query($query);

  if (!($id = @mysql_result($res, 0))) {
    warn("wasn't able to find user matching '$username'");
  }
}
if ($id) {
  $query = "SELECT * FROM users WHERE users.userid=$id";
  $res = db_query($query);
  $userdata = mysql_fetch_array($res);
  if (!$userdata) {
    warn("Can't find user#$id");
  }
}

$action = filter_input(INPUT_POST, "action", FILTER_CALLBACK, ["options" => "validateAction"]);
if ($id && $action) {
  csrf_validate($_SESSION, $action);
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

if ($in) {
  csrf_validate($_SESSION, "useredit");
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

      $cvsaccess   = empty($in['cvsaccess'])   ? 0 : 1;
      $enable      = empty($in['enable'])      ? 0 : 1;
      $spamprotect = empty($in['spamprotect']) ? 0 : 1;
      $use_sa      = empty($in['use_sa'])      ? 0 : (int)$in['use_sa'];
      $greylist    = empty($in['greylist'])    ? 0 : 1;

      if ($id) {
        # update main table data
        if (!empty($in['email']) && !empty($in['name'])) {
          $query = "UPDATE users SET name='$in[name]',email='$in[email]'"
                 . (!empty($in['svnpasswd']) ? ",svnpasswd='$in[svnpasswd]'" : "")
                 . (!empty($in['sshkey']) ? ",ssh_keys='".escape(html_entity_decode($in['sshkey'],ENT_QUOTES))."'" : ",ssh_keys=''")
                 . ((is_admin($_SESSION["username"]) && !empty($in['username'])) ? ",username='$in[username]'" : "")
                 . (is_admin($_SESSION["username"]) ? ",cvsaccess=$cvsaccess" : "")
                 . ",spamprotect=$spamprotect"
                 . ",enable=$enable"
                 . ",use_sa=$use_sa"
                 . ",greylist=$greylist"
                 . (!empty($in['rawpasswd']) ? ",pchanged=" . $ts : "")
                 . " WHERE userid=$id";
          if (!empty($in['passwd'])) {
            // Kill the session data after updates :)
            $_SERVER["credentials"] = [];
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
?>
<form method="post" action="users.php?id=<?php echo $userdata["userid"]?>">
 <input type="hidden" name="csrf" value="<?php echo csrf_generate($_SESSION, "useredit") ?>" />
<table class="useredit">
<tbody>
<tr>
 <th>Name:</th>
 <td><input type="text" name="in[name]" value="<?php echo $userdata['name'];?>" size="40" maxlength="255" /></td>
</tr>
<tr>
 <th>Email:</th>
 <td><input type="text" name="in[email]" value="<?php echo $userdata['email'];?>" size="40" maxlength="255" /><br/>
  	<input type="checkbox" name="in[enable]"<?php echo $userdata['enable'] ? " checked" : "";?> /> Enable email for my account.
 </td>
</tr>
<tr>
 <th>VCS username:</th>
<?php if (is_admin($_SESSION["username"])): ?>
 <td><input type="text" name="in[username]" value="<?php echo hscr($userdata['username']);?>" size="16" maxlength="16" /></td>
<?php else: ?>
 <td><?php echo hscr($userdata['username']);?></td>
<?php endif ?>
</tr>
<tr>
 <td colspan="2">Leave password fields blank to leave password unchanged.</td>
</tr>
<tr>
 <th>Password:</th>
 <td><input type="password" name="in[rawpasswd]" value="" size="20" maxlength="120" /></td>
</tr>
<tr>
 <th>Password (again):</th>
 <td><input type="password" name="in[rawpasswd2]" value="" size="20" maxlength="120" /></td>
</tr>
<?php if (is_admin($_SESSION["username"])) {?>
<tr>
 <th>VCS access?</th>
 <td><input type="checkbox" name="in[cvsaccess]"<?php echo $userdata['cvsaccess'] ? " checked" : "";?> /></td>
</tr>
<?php } else { ?>
<tr>
 <th>Has VCS access?</th>
 <td><?php echo $userdata['cvsaccess'] ? "Yes" : "No";?></td>
</tr>
<?php } ?>
<tr>
 <th>Use Challenge/Response spam protection?</th>
 <td><input type="checkbox" name="in[spamprotect]"<?php echo $userdata['spamprotect'] ? " checked" : "";?> />
 <?php if ($userdata['username'] == $_SESSION["username"]) { ?>
 <br/>
 <a href="challenge-response.php">Show people on my quarantine list</a>
 <?php } ?>
 </td>
</tr>
<tr>
 <th>SpamAssassin threshold</th>
 <td>Block mail scoring <input type="text" name="in[use_sa]" value="<?php echo $userdata['use_sa'] ?>" size="4" maxlength="4"/> or higher in SpamAssassin tests.  Set to 0 to disable.</td>
</tr>
<tr>
 <th>Greylist</th>
 <td>Delay reception of your incoming mail by a minimum of one hour using a 451 response.<br/>
  Legitimate senders will continue to try to deliver the mail, whereas
  spammers will typically give up and move on to spamming someone else.<br/>
  See <a href="http://projects.puremagic.com/greylisting/whitepaper.html">this whitepaper</a> for more information on greylisting.<br/>
  <input type="checkbox" name="in[greylist]"<?php echo $userdata['greylist'] ? " checked" : "";?> /> Enable greylisting on my account</td>
</tr>
<tr>
 <th>Verified?</th>
 <td><input type="checkbox" name="in[verified]"<?php echo $userdata['verified'] ? " checked" : "";?> /> Note: Do not worry about this value. It's sometimes used to check if old-timers are still around.</td>
</tr>
</tbody>
<tfoot>
<tr>
 <th>SSH Key</th>
 <td><textarea name="in[sshkey]" placeholder="Paste in the contents of your id_rsa.pub"><?php echo escape(html_entity_decode($userdata['ssh_keys'],ENT_QUOTES)); ?></textarea>
  <p>Adding/editing the SSH key takes a few minutes to propagate to the server.<br>
  Multiple keys are allowed, separated using a newline.</p></td>
</tr>
<?php
  if ($id) {
    $res = db_query("SELECT markdown FROM users_profile WHERE userid=$id");
    $userdata['profile_markdown'] = '';
    if ($profile_row = mysql_fetch_assoc($res)) {
        $userdata['profile_markdown'] = $profile_row['markdown'];
    }
?>
<tr>
 <th>People Profile<br>(<a href="http://people.php.net/user.php?username=<?php echo urlencode($userdata['username']);?>"><?php echo hscr($userdata['username']);?>'s page</a>)</th>
 <td>
     <p>Use <a href="http://michelf.ca/projects/php-markdown/dingus/" title="PHP Markdown: Dingus">Markdown</a>. Type as much as you like.</p>
     <div><textarea name="in[profile_markdown]" placeholder="My PHP People page content"><?php echo clean($userdata['profile_markdown']); ?></textarea></div>
 </td>
</tr>
<?php
  }
?>
<tr>
 <th>Add Note: </th>
 <td><textarea name="in[purpose]" placeholder="Administrative notes"></textarea></td>
</tr>
<tr>
 <td colspan="2"><input type="submit" value="Update" />
</tr>
</tfoot>
</table>
</form>
<?php
if (is_admin($_SESSION["username"]) && !$userdata['cvsaccess']) {
?>
<table>
<tr>
<td>
 <form method="post" action="users.php?id=<?php echo $id?>">
  <input type="hidden" name="csrf" value="<?php echo csrf_generate($_SESSION, "remove") ?>" />
  <input type="hidden" name="action" value="remove" />
  <input type="submit" value="Reject" />
 </form>
</td>
<td>
<?php
  $hash = gen_svn_pass($_SESSION["credentials"][0], $_SESSION["credentials"][1]);
  $csrf = "approve:$hash:";
?>
 <form method="post" action="users.php?id=<?php echo $id?>">
  <input type="hidden" name="csrf" value="<?php echo csrf_generate($_SESSION, "approve") ?>" />
  <input type="hidden" name="action" value="approve" />
  <input type="submit" value="Approve" />
 </form>
</td>
</tr>
</table>
<?php
}
?>
<h2 id="notes">Notes:</h2>
<?php
  $res = db_query("SELECT note, UNIX_TIMESTAMP(entered) AS ts FROM users_note WHERE userid=$id");
  while ($res && $userdata = mysql_fetch_assoc($res)) {
    echo "<div class='note'>", date("r",$userdata['ts']), "<br />".$userdata['note']."</div>";
  }
  foot();
  exit;
}
?>
<?php

$unapproved = filter_input(INPUT_GET, "unapproved", FILTER_VALIDATE_INT) ?: 0;
$begin      = filter_input(INPUT_GET, "begin", FILTER_VALIDATE_INT) ?: 0;
$max        = filter_input(INPUT_GET, "max", FILTER_VALIDATE_INT) ?: 20;
$forward    = filter_input(INPUT_GET, "forward", FILTER_VALIDATE_INT) ?: 0;
$search     = filter_input(INPUT_GET, "search", FILTER_CALLBACK, ["options" => "mysql_real_escape_string"]) ?: "";
$order      = filter_input(INPUT_GET, "order", FILTER_CALLBACK, ["options" => "mysql_real_escape_string"]) ?: "";

$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS users.userid,cvsaccess,username,name,email,GROUP_CONCAT(note) note FROM users ";
$query .= " LEFT JOIN users_note ON users_note.userid = users.userid ";

if  ($search) {
    $query .= "WHERE (MATCH(name,email,username) AGAINST ('$search') OR username = '$search') ";

} else {
    $query .= ' WHERE 1=1 ';
}

if ($unapproved) {
    $query .= ' AND NOT cvsaccess ';
}

$query .= " GROUP BY users.userid ";

if ($order) {
  if ($forward) {
    $ext = "ASC";
  } else {
    $ext = "DESC";
  }
  $query .= " ORDER BY $order $ext";
}
$query .= " LIMIT $begin,$max ";
$res = db_query($query);
#echo $query;

$res2 = db_query("SELECT FOUND_ROWS()");
$total = mysql_result($res2,0);


$extra = [
  "search"     => $search,
  "order"      => $order,
  "forward"    => $forward,
  "begin"      => $begin,
  "max"        => $max,
  "unapproved" => $unapproved,
];

?>
<h1 class="browse">Browse users<ul>
  <li><a href="?unapproved=0">See all users</a></li>
  <li><a href="?unapproved=1">See outstanding requests</a></li>
  </ul></h1>
<table id="users">
<thead>
<?php show_prev_next($begin,mysql_num_rows($res),$max,$total,$extra, false); ?>
</thead>
<tbody>
<tr>
  <th><a href="?<?php echo array_to_url($extra,["unapproved"=>!$unapproved]);?>"><?php echo $unapproved ? "&otimes" : "&oplus"; ?>;</a></th>
  <th><a href="?<?php echo array_to_url($extra,["order"=>"username"]);?>">username</a></th>
  <th><a href="?<?php echo array_to_url($extra,["order"=>"name"]);?>">name</a></th>
<?php if (!$unapproved) { ?>
  <th colspan="2"><a href="?<?php echo array_to_url($extra,["order"=>"email"]);?>">email</a></th>
<?php } else { ?>
  <th><a href="?<?php echo array_to_url($extra,["order"=>"email"]);?>">email</a></th>
  <th><a href="?<?php echo array_to_url($extra,["order"=>"note"]);?>">note</a></th>
<?php } ?>
  <th> </th>
</tr>
<?php
while ($userdata = mysql_fetch_array($res)) {
?>
  <tr class="<?php if (!$userdata["cvsaccess"]) { echo "noaccess"; }?>">
    <td><a href="?username=<?php echo $userdata["username"];?>">edit</a></td>
    <td><a href="https://people.php.net/?username=<?php echo hscr($userdata['username'])?>"><?php echo hscr($userdata['username']) ?></a></td>
    <td><?php echo hscr($userdata['name']);?></td>
<?php if (!$unapproved) { ?>
    <td colspan="2"><?php echo hscr($userdata['email']);?></td>
<?php } else { ?>
    <td><?php echo hscr($userdata['email']);?></td>
    <td><?php echo hscr($userdata['note'])?></td>
<?php } ?>
      <td> </td>
  </tr>
<?php
}
?>
</tbody>
<tfoot>
<?php show_prev_next($begin,mysql_num_rows($res),$max,$total,$extra, false); ?>
</tfoot>
</table>
<?php
foot();

