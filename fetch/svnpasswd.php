<?php // vim: et ts=2 sw=2

# token required, since this should only get accessed from cvs.php.net
if (!isset($token) || md5($token) != "585ae1060f9b881490d1a1e3c7353e89") {
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

