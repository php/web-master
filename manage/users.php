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
  $query = "SELECT users.userid FROM users LEFT JOIN users_cvs USING (userid)"
         . " WHERE cvsuser='$username' OR email='$username'";
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
      $approved = $in[approved] ? 1 : 0;

      if ($id) {
        # update main table data
        if (isset($in[email]) && isset($in[name])) {
          $query = "UPDATE users SET name='$in[name]',email='$in[email]'"
                 . ($in[passwd] ? ",passwd='$in[passwd]'" : "")
                 . " WHERE userid=$id";
          query($query);
        }
        # update cvsusers stuff
        # TODO: create users_cvs record for user that didn't already have one
        #       (can't just use REPLACE because of the unique index on cvsuser)
        $query = "UPDATE users_cvs"
               . " SET cvsuser='$in[cvsuser]',approved=$approved"
               . " WHERE userid=$id";
        query($query);

        warn("record $id updated");
        unset($id);
      }
      else {
        $query = "INSERT users SET name='$in[name]',email='$in[email]'"
               . ($in[passwd] ? ",passwd='$in[passwd]'" : "");
        query($query);

        $newid = mysql_insert_id();

        # TODO: handle failure better
        $query = "INSERT users_cvs"
               . " SET userid=$newid,cvsuser='$in[cvsuser]',approved=$approved";
        query($query);

        warn("record $newid added");
      }
    }
  }
}

if ($id) {
  $query = "SELECT * FROM users LEFT JOIN users_cvs USING (userid)"
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
 <td><input type="text" name="in[cvsuser]" value="<?php echo htmlspecialchars($row[cvsuser]);?>" size="16" maxlength="16" /></td>
</tr>
<tr>
 <th align="right">CVS access?</th>
 <td><input type="checkbox" name="in[approved]"<?php echo $row[approved] ? " checked" : "";?> /></td>
</tr>
<tr>
 <td><input type="submit" value="<?php echo $id ? "Change" : "Add";?>" />
</tr>
</table>
<?
  foot();
  exit;
}

$begin = $begin ? (int)$begin : 0;
$max = $max ? (int)$max : 30;

$limit = "LIMIT $begin,$max";
$orderby = $order ? "ORDER BY $order" : "";

$query = "SELECT COUNT(*) FROM users";
$res = mysql_query($query)
  or die("query '$query' failed: ".mysql_error());
$total = mysql_result($res,0);

$query = "SELECT users.userid,approved,cvsuser,name,email FROM users LEFT JOIN users_cvs USING (userid) $orderby $limit";
$res = mysql_query($query)
  or die("query '$query' failed: ".mysql_error());

show_prev_next($begin,mysql_num_rows($res),$max,$total);
?>
<table border="0" cellspacing="1" width="100%">
<tr bgcolor="#aaaaaa">
 <td></td>
 <th><a href="<?php echo "$PHP_SELF?begin=$begin&order=name";?>">name</a></th>
 <th><a href="<?php echo "$PHP_SELF?begin=$begin&order=email";?>">email</a></th>
 <th><a href="<?php echo "$PHP_SELF?begin=$begin&order=cvsuser";?>">username</a></th>
</tr>
<?php
$color = '#dddddd';
while ($row = mysql_fetch_array($res)) {
?>
<tr bgcolor="<?php echo $color;?>">
 <td align="center"><a href="<?php echo "$PHP_SELF?id=$row[userid]";?>">edit</a></td>
 <td><?php echo htmlspecialchars($row[name]);?></td>
 <td><?php echo htmlspecialchars($row[email]);?></td>
 <td<?php if ($row[cvsuser] && !$row[approved]) echo ' bgcolor="#ff',substr($color,2),'"';?>><?php echo htmlspecialchars($row[cvsuser]);?></td>
</tr>
<?php
  $color = substr($color,2,2) == 'dd' ? '#eeeeee' : '#dddddd';
}
?>
</table>
<?php show_prev_next($begin,mysql_num_rows($res),$max,$total); ?>
<p><a href="<?php echo $PHP_SELF;?>?id=0">add a new user</a></p>
<?php
foot();

function invalid_input($in) {
  if (isset($in[email]) && !is_emailable_address($in[email])) {
    return "'".clean($in[email])."' does not look like a valid email address";
  }
  if ($in[cvsuser] && !preg_match("/^[-\w]+\$/",$in[cvsuser])) {
    return "'".clean($in[cvsuser])."' is not a valid username";
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
  $query = "SELECT users.userid FROM users LEFT JOIN users_cvs USING (userid)"
         . " WHERE users.userid=$userid"
         . "   AND (email='$quser' OR cvsuser='$quser')";

  $res = mysql_query($query);
  return $res ? mysql_num_rows($res) : false;
}
