<?php

require_once __DIR__ . '/../../include/functions.inc';

# token required, since this should only get accessed from php.net mx
if (!isset($_REQUEST['token']) || md5($_REQUEST['token']) != "19a3ec370affe2d899755f005e5cd90e")
  die("token not correct.");

// Connect and generate the list from the DB
db_connect();
$res = db_query_safe("SELECT username,email,spamprotect FROM users WHERE email != '' AND cvsaccess");
if ($res) {
  while ($row = @mysql_fetch_array($res)) {
    echo "$row[username]@php.net: ",
         ($row['spamprotect'] ? "|/local/bin/automoderate," : ""),
         "$row[email];\n";
    echo "$row[username]@pair2.php.net: ",
         ($row['spamprotect'] ? "|/local/bin/automoderate," : ""),
         "$row[email];\n";
  }
}