<?php
require 'cvs-auth.inc';

#TODO:
# acls
# trigger passwd file update on cvs.php.net
# trigger mail alias update on php.net mx
# some sort of search
# handle flipping of the sort views

if (isset($save) && isset($user) && isset($pw)) {
  setcookie("MAGIC_COOKIE",base64_encode("$user:$pw"),time()+3600*24*12,'/','.php.net');
}
if (isset($MAGIC_COOKIE) && !isset($user) && !isset($pw)) {
  list($user,$pw) = explode(":", base64_decode($MAGIC_COOKIE));
}

echo '<html><head><title>user administration</title></head><body>';

if (!$user || !$pw || !verify_password($user,$pw)) {?>
<form method="POST" action="<?php echo $PHP_SELF?>">
<input type="hidden" name="save" value="1" />
<table>
 <tr>
  <th align="right">Username:</th>
  <td><input type="text" name="user" value="<?php echo htmlspecialchars(stripslashes($user));?>" />
 </tr>
 <tr>
  <th align="right">Password:</th>
  <td><input type="password" name="pw" value="<?php echo htmlspecialchars(stripslashes($pw));?>" />
 </tr>
 <tr>
  <td align="center" colspan="2"><input type="submit" value="Login" /></td>
 </tr>
</table>
</form>
<?php
  echo '</body></html>';
  exit;
}

mysql_connect("localhost","nobody","")
  or die("unable to connect to database");
mysql_select_db("php3");

if (isset($id) && isset($in)) {
  if ($error = invalid_input($in)) {
    echo "<p class=\"error\">$error</p>";
  }
  else {
    if ($id) {
      $res = mysql_query("SELECT * FROM users WHERE userid=$id")
        or die("query failed");
      $row = mysql_fetch_array($res);
      # need to allow all of group@php.net to edit everyone. just me for now.
      if ($user != $row[cvsuser] && $user != 'jimw') {
        die("can't edit someone else");
      }

      $pass = crypt($in[passwd],substr(md5(time()),0,2));
      $query = "UPDATE users SET name='$in[name]',email='$in[email]',"
             . ($in[cvsuser] ? "cvsuser='$in[cvsuser]'" : "")
             . ($in[passwd] ? ",passwd='$pass]'" : "")
             . " WHERE userid=$id";
    }
    else {
      if ($in[passwd]) $in[passwd] = crypt($in[passwd],substr(md5(time()),0,2));
      $query = "INSERT INTO users (name,email,cvsuser,passwd) VALUES ('$in[name]','$in[email]','$in[cvsuser]','$in[passwd]')";
    }

    if (!mysql_query($query)) {
      echo "<h2 class=\"error\">Query '$query' failed: ", mysql_error(), "</h2>";
    }
    else {
      echo "<h2>$in[name] ($in[cvsuser]) ", $id ? "updated" : "added", ".</h2>";
      unset($id);
    }
  }
}

if (isset($id)) {
  if ($id) {
    $res = mysql_query("SELECT * FROM users WHERE userid=$id");
    $row = mysql_fetch_array($res);
    # need to allow all of group@php.net to edit everyone. just me for now.
    if ($user != $row[cvsuser] && $user != 'jimw') {
      die("can't edit someone else");
    }
  }
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
 <th align="right">CVS name:</th>
 <td><input type="text" name="in[cvsuser]" value="<?php echo htmlspecialchars($row[cvsuser]);?>" size="16" maxlength="16" /></td>
</tr>
<tr>
 <td colspan="2">Leave password fields blank to leave password unchanged.</td>
</tr>
<tr>
 <th align="right">Password:</th>
 <td><input type="password" name="in[passwd]" value="" size="20" maxlength="20" /></td>
</tr>
<tr>
 <th align="right">Password (again):</th>
 <td><input type="password" name="in[passwd2]" value="" size="20" maxlength="20" /></td>
</tr>
<tr>
 <td><input type="submit" value="<?php echo $id ? "Change" : "Add";?>" />
</tr>
</table>
<?
  echo '</body></html>';
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

$query = "SELECT userid,cvsuser,name,email FROM users $orderby $limit";
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
while ($row = mysql_fetch_array($res)) {?>
<tr bgcolor="<?php echo $color;?>">
 <td align="center"><a href="<?php echo "$PHP_SELF?id=$row[userid]";?>">edit</a></td>
 <td><?php echo htmlspecialchars($row[name]);?></td>
 <td><?php echo htmlspecialchars($row[email]);?></td>
 <td><?php echo htmlspecialchars($row[cvsuser]);?></td>
</tr>
<?php
  $color = $color == '#dddddd' ? '#eeeeee' : '#dddddd';
}
?>
</table>
<?php show_prev_next($begin,mysql_num_rows($res),$max,$total); ?>
<p><a href="<?php echo $PHP_SELF;?>?id=0">add a new user</a></p>
<?php
echo '</body></html>';

function show_prev_next($begin,$rows,$skip,$total) {
  global $order, $PHP_SELF;?>
<table border="0" cellspacing="1" width="100%">
 <tr bgcolor="#eeeeee">
  <td align="left" width="33%">
   <?php 
     if ($begin > 0) {
       printf("<a href=\"%s\">&laquo; Previous %d",
              "$PHP_SELF?order=$order&amp;begin=".max(0,$begin-$skip),
              min($skip,$begin));
     }
   ?>
   &nbsp;
  </td>
  <td align="center" width="33%">
   <?php echo "Displaying ",$begin+1,"-",$begin+$rows," of $total";?>
  </td>
  <td align="right" width="33%">
   &nbsp;
   <?php 
     if ($begin+$rows < $total) {
       printf("<a href=\"%s\">Next %d &raquo;",
              "$PHP_SELF?order=$order&amp;begin=".($begin+$skip),
              min($skip,$total-($begin+$skip)));
     }
   ?>
  </td>
 </tr>
</table>
<?php
}

function invalid_input($in) {
  if ($in[cvsuser] && !preg_match("/^[-\w]+\$/",$in[cvsuser])) {
    return "'$in[cvsuser]' is not a valid username";
  }
  if ($in[passwd] && $in[passwd] != $in[passwd2]) {
    return "the passwords you specified did not match!";
  }
  return false;
}
