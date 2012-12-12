<?php

# token required, since this should only get accessed from rsync.php.net
if (!isset($_REQUEST['token']) || md5($_REQUEST['token']) != "19a3ec370affe2d899755f005e5cd90e")
  die("token not correct.");

// Changed old mysql_* stuff to PDO
try {
    $dbh = new PDO('mysql:host=localhost;dbname=phpmasterdb', 'nobody', '');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Old error handling was to simply exit. Do we want to log anything here???
    exit;
}

try {
    $query  = "SELECT DISTINCT id,note.sect,user,note,UNIX_TIMESTAMP(ts) AS ts,";
    $query .= "IF(votes=0, 10, rating/votes) AS rate";
    $query .= " FROM note";
    //Only select notes that have been approved
    $query .= " WHERE status is NULL";
    $query .= " ORDER BY sect,rate DESC,ts DESC";

    $stmt = $dbh->prepare($query);
    $stmt->execute();
    $resultset = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $getvotes = $dbh->prepare("SELECT SUM(votes.vote) AS up, (COUNT(votes.vote) - SUM(votes.vote)) AS down FROM votes WHERE votes.note_id = ?");
} catch (PDOException $e) {
    // Old error handling was to simply exit. Do we want to log anything here???
    exit;
}

// Print out a row for all notes, obfuscating the
// email addresses as needed
foreach ($resultset as $row) {
    $user = $row['user'];
    if ($user != "php-general@lists.php.net" && $user != "user@example.com") {
        if (preg_match("!(.+)@(.+)\.(.+)!", $user)) {
            $user = str_replace(array('@', '.'), array(' at ', ' dot '), $user);
        }
    } else {
        $user = '';
    }
    // Calculate the votes for each note here
    try {
        $getvotes->exucute(array($row['id']));
        $votes = $getvotes->fetch(PDO::FETCH_ASSOC);
        if ($votes === false) {
            $votes = array('up' => 0, 'down' => 0);
        }
    } catch(PDOException $e) {
        $votes = array('up' => 0, 'down' => 0);
    }
    // Output here
    echo "$row[id]|$row[sect]|$row[rate]|$row[ts]|$user|",
         base64_encode(gzcompress($row['note'],3)),"|$votes[up]|$votes[down]\n";
}
