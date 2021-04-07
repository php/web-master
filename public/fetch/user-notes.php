<?php

# token required, since this should only get accessed from rsync.php.net
if (!isset($_REQUEST['token']) || md5($_REQUEST['token']) != "19a3ec370affe2d899755f005e5cd90e")
  die("token not correct.");

// Changed old mysql_* stuff to PDO
try {
    $dbh = new PDO('mysql:host=localhost;dbname=phpmasterdb', 'nobody', '');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
} catch (PDOException $e) {
    // Old error handling was to simply exit. Do we want to log anything here???
    exit;
}

try {
    $query  = "SELECT DISTINCT note.id,note.sect,note.user,note.note,UNIX_TIMESTAMP(note.ts) AS ts,";
    $query .= "SUM(votes.vote) AS up, (COUNT(votes.vote) - SUM(votes.vote)) AS down,";
    $query .= "ROUND((SUM(votes.vote) / COUNT(votes.vote)) * 100) AS rate";
    $query .= " FROM note";
    $query .= " LEFT JOIN (votes) ON (note.id = votes.note_id)";
    //Only select notes that have been approved
    $query .= " WHERE note.status is NULL";
    $query .= " GROUP BY note.id";
    $query .= " ORDER BY note.sect,ts DESC";

    $stmt = $dbh->prepare($query);
    $stmt->execute();
} catch (PDOException $e) {
    // Old error handling was to simply exit. Do we want to log anything here???
    exit;
}

// Print out a row for all notes, obfuscating the
// email addresses as needed
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
