<?php

# token required, since this should only get accessed from rsync.php.net
if (!isset($_REQUEST['token']) || md5($_REQUEST['token']) != "19a3ec370affe2d899755f005e5cd90e")
  die("token not correct.");

@mysql_connect("localhost","nobody","")
  or exit;
@mysql_select_db("phpmasterdb")
  or exit;

$query  = "SELECT DISTINCT id,note.sect,user,note,UNIX_TIMESTAMP(ts) AS ts,";
$query .= "IF(votes=0, 10, rating/votes) AS rate";
$query .= " FROM note";

//Only select notes that have been approved
$query .= " WHERE status is NULL";

$query .= " ORDER BY sect,rate DESC,ts DESC";

$res = @mysql_query($query) or exit;

// Print out a row for all notes, obfuscating the
// email addresses as needed
while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
    $user = $row['user'];
    if ($user != "php-general@lists.php.net" && $user != "user@example.com") {
        if (preg_match("!(.+)@(.+)\.(.+)!", $user)) {
            $user = str_replace(array('@', '.'), array(' at ', ' dot '), $user);
        }
    } else {
        $user = '';
    }
    echo "$row[id]|$row[sect]|$row[rate]|$row[ts]|$user|",
         base64_encode(gzcompress($row['note'],3)),"\n";
}
