<?php

# token required, since this should only get accessed from cvs.php.net
if (!isset($token) || md5($token) != "585ae1060f9b881490d1a1e3c7353e89")
  die("token not correct.");

// Connect and generate the list from the DB
if (@mysql_connect("localhost","nobody","")) {
  if (@mysql_select_db("phpmasterdb")) {
    $res = @mysql_query("SELECT username,passwd FROM users WHERE passwd != '' AND cvsaccess");
    if ($res) {
      while ($row = @mysql_fetch_array($res)) {
        echo "$row[username]:$row[passwd]:cvs\n";
      }
      # the read-only cvsread account
      echo "cvsread::cvs\n";
    }
  }
}
