<?php // vim: et ts=2 sw=2

# token required, since this should only get accessed from svn.php.net
if (!isset($token) || md5($token) != "eba62217cacbf19cfe919d637188f40d") {
  die("token not correct.");
}

require dirname(__FILE__). "/../include/svn-auth.inc";

// Connect and generate the list from the DB
if (@mysql_connect("localhost","nobody","")) {
  if (@mysql_select_db("phpmasterdb")) {
    $res = @mysql_query("SELECT username, svnpasswd FROM users WHERE svnpasswd != '' AND cvsaccess ORDER BY username");
    if ($res) {
      while ($row = @mysql_fetch_array($res)) {
        printf("%s:%s:%s\n", $row["username"], REALM, $row["svnpasswd"]);
      }
    }
  }
}

