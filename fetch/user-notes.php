<?php

# token required, since this should only get accessed from rsync.php.net
if (!isset($token) || md5($token) != "19a3ec370affe2d899755f005e5cd90e")
  die("token not correct.");

@mysql_connect("localhost","nobody","")
  or exit;
@mysql_select_db("php3")
  or exit;

$query  = "SELECT DISTINCT id,note.sect,user,note,UNIX_TIMESTAMP(ts) AS ts,";
$query .= "IF(votes=0, 10, rating/votes) AS rate";
$query .= " FROM note";
$query .= " ORDER BY sect,rate DESC,ts";

$res = @mysql_query($query) or exit;

while ($row = mysql_fetch_array($res,MYSQL_ASSOC)) {
  echo "$row[id]|$row[sect]|$row[rate]|$row[ts]|$row[user]|",
       base64_encode(gzcompress($row[note],3)),"\n";
}
