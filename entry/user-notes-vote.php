<?php
/*
  This script acts as the backend communication API for the user notes vote feature.
  Requests come in here from the php.net to update the database with new votes.
  master.php.net should respond with a JSON object (with one required property [status] and two optional properties
  [votes] and [message]).
  The JSON object [status] property contains a status returned by the server for that request.
  It's value may be either a boolean true or false. If the status is true the php.net will know the vote went through successfully.
  The optional [votes] property may then be supplied to update the php.net with the new value of the votes for that note.
  If the status is false the php.net will know the request for voting failed and an optional [message] property may be
  set to supply the php.net with a message string, explaining why the request failed.

  Example Success:

                   { "status": true, "votes": 1 }

  Example Failed:

                   { "status": false, "message": "You have already voted today!" }
                   { "status": false, "message": "Invalid request..." }
*/

undo_magic_quotes();

/*
    This function will revert the GPCRS superglobals to their raw state if the default.filter/magic_quotes is on.
    Please do not use this function unless your code has no dependency on magic_quotes and is properly escaping data.
*/
function undo_magic_quotes() {
    if (!empty($_POST)) {
        $args = [];
        foreach ($_POST as $key => $val) $args[$key] = ['filter' => FILTER_UNSAFE_RAW, 'flags' => is_array($val) ? 
                                                              FILTER_REQUIRE_ARRAY : FILTER_REQUIRE_SCALAR];
        $_POST = filter_input_array(INPUT_POST, $args);
        $_REQUEST = filter_input_array(INPUT_POST, $args);
    }
    if (!empty($_GET)) {
        $args = [];
        foreach ($_GET as $key => $val) $args[$key] = ['filter' => FILTER_UNSAFE_RAW, 'flags' => is_array($val) ? 
                                                            FILTER_REQUIRE_ARRAY : FILTER_REQUIRE_SCALAR];
        $_GET = filter_input_array(INPUT_GET, $args);
        $_REQUEST += filter_input_array(INPUT_GET, $args);
    }
    if (!empty($_COOKIE)) {
        $args = [];
        foreach ($_COOKIE as $key => $val) $args[$key] = ['filter' => FILTER_UNSAFE_RAW, 'flags' => is_array($val) ?
                                                               FILTER_REQUIRE_ARRAY : FILTER_REQUIRE_SCALAR];
        $_COOKIE = filter_input_array(INPUT_COOKIE, $args);
        $_REQUEST += filter_input_array(INPUT_COOKIE, $args);
    }
    if (!empty($_SERVER)) {
        $args = [];
        $append = [];
        foreach ($_SERVER as $key => $val) {
            if ($key == 'REQUEST_TIME' || $key == 'REQUEST_TIME_FLOAT') {
                $append[$key] = $val;
                continue;
            }
            $args[$key] = ['filter' => FILTER_UNSAFE_RAW, 'flags' => is_array($val) ?
                                FILTER_REQUIRE_ARRAY : FILTER_REQUIRE_SCALAR];
        }
        $_SERVER = filter_input_array(INPUT_SERVER, $args);
        $_SERVER += $append;
    }
}

// Validate that the request to vote on a user note is OK (ip limits, post variables, and db info must pass validation)
function vote_validate_request(PDO $dbh) {
  // Initialize local variables
  $ip = $hostip = $id = $vote = 0;
  $ts = date("Y-m-d H:i:s");

  // Validate POST variables
  if (isset($_POST['ip']) &&
      filter_var($_POST['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE  |
                                                   FILTER_FLAG_NO_PRIV_RANGE |
                                                   FILTER_FLAG_IPV4))
  {
      $ip = sprintf("%u", ip2long($_POST['ip']));      
  } else {
      // If the IP can't be validated use a non routable IP for loose validation (i.e. IPv6 and clients that couldn't send back proper IPs)
      $ip = 0; 
  }
  
  if (isset($_SERVER['REMOTE_ADDR']) &&
      filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE  |
                                                              FILTER_FLAG_NO_PRIV_RANGE |
                                                              FILTER_FLAG_IPV4))
  {
      $hostip = sprintf("%u", ip2long($_SERVER['REMOTE_ADDR']));      
  } else {
      // If the IP can't be validated use a non routable IP for loose validation (i.e. IPv6 and clients that couldn't send back proper IPs)
      $hostip = 0;

  }
  
  if (!empty($_POST['noteid']) && filter_var($_POST['noteid'], FILTER_VALIDATE_INT))
  {
      $id = filter_var($_POST['noteid'], FILTER_VALIDATE_INT);
  } else {
      return false;
  }
  
  if (!empty($_POST['vote']) && ($_POST['vote'] === 'up' || $_POST['vote'] === 'down'))
  {
      $vote = $_POST['vote'] === 'up' ? 1 : 0;
  }

  if (empty($_POST['sect'])) {
      return false;
  }


  // Validate the note exists and is in the requested section
  $noteStmt = $dbh->prepare("SELECT COUNT(*) AS num, sect FROM note WHERE id = :id");
  if (!$noteStmt) {
      return false;
  }
  if (!$noteStmt->execute(['id' => $id])) {
      return false;
  }
  if (false === $noteResult = $noteStmt->fetch(PDO::FETCH_ASSOC)) {
      return false;
  }
  if ($noteResult['sect'] !== $_POST['sect']) {
      return false;
  }
  
  // Validate remote IP has not exceeded voting limits
  $remoteStmt = $dbh->prepare("SELECT COUNT(*) AS num FROM votes WHERE ip = :ip AND ts >= (NOW() - INTERVAL 1 DAY) AND note_id = :id");
  if (!$remoteStmt) {
      return false;
  }
  if (!$remoteStmt->execute(['ip' => $ip, 'id' => $id])) {
      return false;
  }
  if (false === $remoteResult = $remoteStmt->fetch(PDO::FETCH_ASSOC)) {
      return false;
  }
  if ($remoteResult['num'] >= 1) { // Limit of 1 vote, per note, per remote IP, per day.
      return false;
  }
  
  // Validate host IP has not exceeded voting limits
  $hostStmt = $dbh->prepare("SELECT COUNT(*) AS num FROM votes WHERE hostip = :ip AND ts >= (NOW() - INTERVAL 1 HOUR) AND note_id = :id");
  if (!$hostStmt) {
      return false;
  }
  if (!$hostStmt->execute(['ip' => $ip, 'id' => $id])) {
      return false;
  }
  if (false === $hostResult = $hostStmt->fetch(PDO::FETCH_ASSOC)) {
      return false;
  }
  if ($hostResult['num'] >= 100) { // Limit of 100 votes, per note, per host IP, per hour.
      return false;
  }

  // Inser the new vote
  $voteStmt = $dbh->prepare("INSERT INTO votes(note_id,ip,hostip,ts,vote) VALUES(:id,:ip,:host,:ts,:vote)");
  if (!$voteStmt) {
      return false;
  }
  if (!$voteStmt->execute(['id' => $id, 'ip' => $ip, 'host' => $hostip, 'ts' => $ts, 'vote' => $vote])) {
      return false;
  }


  // Get latest vote tallies for this note
  $voteStmt = $dbh->prepare("SELECT SUM(votes.vote) AS up, (COUNT(votes.vote) - SUM(votes.vote)) AS down FROM votes WHERE votes.note_id = :id");
  if (!$voteStmt) {
      return false;
  }
  if (!$voteStmt->execute(['id' => $id])) {
      return false;
  }
  if (false === $voteResult = $voteStmt->fetch(PDO::FETCH_ASSOC)) {
      return false;
  }
  // Return the new vote tally for this note
  return $voteResult['up'] - $voteResult['down'];
}

// Initialize global JSON response object
$jsonResponse = new stdclass;
$jsonResponse->status = false;


// Validate the request
if (!isset($_SERVER['REQUEST_METHOD']) || strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
    $jsonResponse->message = "Invalid request...";
    echo json_encode($jsonResponse);
    exit;
}

// Initialize global PDO database handle
try {
    $dbh = new PDO('mysql:host=localhost;dbname=phpmasterdb', 'nobody', '');
} catch(PDOException $e) {
    $jsonResponse->message = "The server could not complete this request. Please try again later...";
    echo json_encode($jsonResponse);
    exit;
}

// Check master DB for hostip and clientip limits and other validations
if (($jsonResponse->votes = vote_validate_request($dbh)) === false) {
    $jsonResponse->message = "Unable to complete your request at this time. Please try again later...";
    echo json_encode($jsonResponse);
    exit;
}

// If everything passes the response should be the new jsonResponse object with updated votes and success status
$jsonResponse->status = true;
echo json_encode($jsonResponse);
