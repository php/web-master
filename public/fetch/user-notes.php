<?php

use App\DB;

require __DIR__ . '/../../vendor/autoload.php';

# token required, since this should only get accessed from rsync.php.net
if (!isset($_REQUEST['token']) || md5($_REQUEST['token']) != "19a3ec370affe2d899755f005e5cd90e")
  die("token not correct.");

$dbh = DB::connect();

$query  = "SELECT DISTINCT note.id,note.sect,note.user,note.note,UNIX_TIMESTAMP(note.ts) AS ts,";
$query .= "SUM(votes.vote) AS up, (COUNT(votes.vote) - SUM(votes.vote)) AS down,";
$query .= "ROUND((SUM(votes.vote) / COUNT(votes.vote)) * 100) AS rate";
$query .= " FROM note";
$query .= " LEFT JOIN (votes) ON (note.id = votes.note_id)";
//Only select notes that have been approved
$query .= " WHERE note.status is NULL";
$query .= " GROUP BY note.id";
$query .= " ORDER BY note.sect,ts DESC";

$result = $dbh->safeQuery($query);

// Print out a row for all notes, obfuscating the
// email addresses as needed
foreach ($result as $row) {
    $user = $row['user'];
    $row['rate'] = empty($row['rate']) ? 0 : $row['rate'];
    if ($user != "php-general@lists.php.net" && $user != "user@example.com") {
        if (preg_match("!(.+)@(.+)\.(.+)!", $user)) {
            $user = str_replace(['@', '.'], [' at ', ' dot '], $user);
        }
    } else {
        $user = '';
    }
    // Output here
    echo "$row[id]|$row[sect]|$row[rate]|$row[ts]|$user|",
         base64_encode(gzcompress($row['note'],3)),"|$row[up]|$row[down]\n";
}
