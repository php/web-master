<?php

#TODO:
# /TODO
# acls
# trigger passwd file update on cvs.php.net
# trigger mail alias update on php.net mx
# some sort of search
# handle flipping of the sort views

require_once 'login.inc';
require_once 'functions.inc';
require_once 'email-validation.inc';

head("user administration");

@mysql_connect("localhost","nobody","")
  or die("unable to connect to database");
@mysql_select_db("php3");

# ?username=whatever will look up 'whatever' by email or cvs username
if (isset($username) && !isset($id)) {
  $query = "SELECT userid FROM users"
         . " WHERE username='$username' OR email='$username'";
  $res = query($query);
  if (!($id = @mysql_result($res,0))) {
    warn("wasn't able to find user matching '".clean($username)."'");
  }
}

if (isset($id) && isset($in)) {
  if (!can_modify($user,$id)) {
    warn("you're not allowed to modify this user.");
  }
  else {
    if ($error = invalid_input($in)) {
      warn($error);
    }
    else {
      if ($in[rawpasswd]) {
        $in[passwd] = crypt($in[rawpasswd],substr(md5(time()),0,2));
      }
      $cvsaccess = $in[cvsaccess] ? 1 : 0;

      if ($id) {
        # update main table data
        if (isset($in[email]) && isset($in[name])) {
          $query = "UPDATE users SET name='$in[name]',email='$in[email]'"
                 . ($in[passwd] ? ",passwd='$in[passwd]'" : "")
                 . ($in[username] ? ",username='$in[username]'" : "")
                 . ",cvsaccess=$cvsaccess"
                 . " WHERE userid=$id";
          query($query);
        }

        warn("record $id updated");
        unset($id);
      }
      else {
        $query = "INSERT users SET name='$in[name]',email='$in[email]'"
               . ($in[username] ? ",username='$in[username]'" : "")
               . ($in[passwd] ? ",passwd='$in[passwd]'" : "")
               . ",cvsaccess=$cvsaccess";
        query($query);

        $nid = mysql_insert_id();

        warn("record $nid added");
      }
    }
  }
}

if ($id) {
  $query = "SELECT * FROM users"
         . " WHERE users.userid=$id";
  $res = query($query);
  $row = mysql_fetch_array($res);
}

if (isset($id)) {
?>
<table>
<form method="POST" action="<?php echo $PHP_SELF;?>">
<input type="hidden" name="id" value="<?php echo $row[userid];?>" />
<tr>
 <th align="right">Name:</th>
 <td><input type="text" name="in[name]" value="<?php echo htmlspecialchars($row[name]);?>" size="40" maxlength="255" /></td>
</tr>
<tr>
 <th align="right">Email:</th>
 <td><input type="text" name="in[email]" value="<?php echo htmlspecialchars($row[email]);?>" size="40" maxlength="255" /></td>
</tr>
<tr>
 <td colspan="2">Leave password fields blank to leave password unchanged.</td>
</tr>
<tr>
 <th align="right">Password:</th>
 <td><input type="password" name="in[rawpasswd]" value="" size="20" maxlength="20" /></td>
</tr>
<tr>
 <th align="right">Password (again):</th>
 <td><input type="password" name="in[rawpasswd2]" value="" size="20" maxlength="20" /></td>
</tr>
<tr>
 <th align="right">Password (crypted):</th>
 <td><input type="text" name="in[passwd]" value="<?php echo htmlspecialchars($row[passwd]);?>" size="20" maxlength="20" /></td>
</tr>
<tr>
 <th align="right">CVS username:</th>
 <td><input type="text" name="in[username]" value="<?php echo htmlspecialchars($row[username]);?>" size="16" maxlength="16" /></td>
</tr>
<tr>
 <th align="right">CVS access?</th>
 <td><input type="checkbox" name="in[cvsaccess]"<?php echo $row[cvsaccess] ? " checked" : "";?> /></td>
</tr>
<tr>
 <td><input type="submit" value="<?php echo $id ? "Change" : "Add";?>" />
</tr>
</table>
<?
  foot();
  exit;
}
?>
<table width="100%">
 <tr>
  <td><a href="<?php echo "$PHP_SELF?username=$user";?>">edit your entry</a></td>
  <td align="right">
   <form method="GET" action="<?php echo $PHP_SELF;?>">
    <input type="text" name="search" value="<?php echo clean($search);?>" />
    <input type="submit" value="search">
   </form>
 </tr>
</table>
<?php

$begin = $begin ? (int)$begin : 0;
$full = $full ? 1 : (!isset($full) && $search ? 1 : 0);
$max = $max ? (int)$max : 20;

$limit = "LIMIT $begin,$max";
$orderby = $order ? "ORDER BY $order" : "";

$searchby = $search ? "WHERE MATCH(name,email,username) AGAINST ('$search') OR MATCH(note) AGAINST ('$search')" : "";

$query = "SELECT DISTINCT COUNT(users.userid) FROM users";
if ($searchby)
  $query .= " LEFT JOIN users_note USING(userid) $searchby";
$res = mysql_query($query)
  or die("query '$query' failed: ".mysql_error());
$total = mysql_result($res,0);

$query = "SELECT DISTINCT users.userid,cvsaccess,username,name,email,note FROM users LEFT JOIN users_note USING (userid) $searchby $orderby $limit";

#echo "<pre>$query</pre>";
$res = mysql_query($query)
  or die("query '$query' failed: ".mysql_error());

$extra = array(
  "search" => stripslashes($search),
  "order" => $order,
  "begin" => $begin,
  "max" => $max,
  "full" => $full,
);

show_prev_next($begin,mysql_num_rows($res),$max,$total,$extra);
?>
<table border="0" cellspacing="1" width="100%">
<tr bgcolor="#aaaaaa">
 <th><a href="<?php echo "$PHP_SELF?",array_to_url($extra,array("full" => $full ? 0 : 1));?>"><?php echo $full ? "&otimes;" : "&oplus;";?></a></th>
 <th><a href="<?php echo "$PHP_SELF?",array_to_url($extra,array("order"=>"name"));?>">name</a></th>
 <th><a href="<?php echo "$PHP_SELF?",array_to_url($extra,array("order"=>"email"));?>">email</a></th>
 <th><a href="<?php echo "$PHP_SELF?",array_to_url($extra,array("order"=>"username"));?>">username</a></th>
</tr>
<?php
$color = '#dddddd';
while ($row = mysql_fetch_array($res)) {
?>
<tr bgcolor="<?php echo $color;?>">
 <td align="center"><a href="<?php echo "$PHP_SELF?id=$row[userid]";?>">edit</a></td>
 <td><?php echo htmlspecialchars($row[name]);?></td>
 <td><?php echo htmlspecialchars($row[email]);?></td>
 <td<?php if ($row[username] && !$row[cvsaccess]) echo ' bgcolor="#ff',substr($color,2),'"';?>><?php echo htmlspecialchars($row[username]);?></td>
</tr>
<?php
  if ($full && $row[note]) {?>
<tr bgcolor="<?php echo $color;?>">
 <td></td><td colspan="3"><?php echo htmlspecialchars($row[note]);?></td>
</tr>
<?php
  }
  $color = substr($color,2,2) == 'dd' ? '#eeeeee' : '#dddddd';
}
?>
</table>
<?php show_prev_next($begin,mysql_num_rows($res),$max,$total,$extra); ?>
<p><a href="<?php echo $PHP_SELF;?>?id=0">add a new user</a></p>
<?php
foot();

function invalid_input($in) {
  if (isset($in[email]) && !is_emailable_address($in[email])) {
    return "'".clean($in[email])."' does not look like a valid email address";
  }
  if ($in[username] && !preg_match("/^[-\w]+\$/",$in[username])) {
    return "'".clean($in[username])."' is not a valid username";
  }
  if ($in[rawpasswd] && $in[rawpasswd] != $in[rawpasswd2]) {
    return "the passwords you specified did not match!";
  }
  return false;
}

# returns false if $user is not allowed to modify $userid
function can_modify($user,$userid) {
  #TODO: use acls, once implemented.
  if (in_array($user,array("jimw","rasmus","andrei","zeev","andi","sas","thies","rubys","ssb"))) return true;

  $userid = (int)$userid;

  $quser = addslashes($user);
  $query = "SELECT userid FROM users"
         . " WHERE userid=$userid"
         . "   AND (email='$quser' OR username='$quser')";

  $res = mysql_query($query);
  return $res ? mysql_num_rows($res) : false;
}
