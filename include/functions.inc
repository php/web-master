<?php
// $Id$

$default_filter = (ini_get('filter.default')=='full_special_chars') ? true : false;
$ts = $_SERVER['REQUEST_TIME'];

// -----------------------------------------------------------------------------------

// used in scripts which should only be called from particular machines
function require_token()
{
  if (!isset($_GET['token']) || md5($_GET['token']) != "19a3ec370affe2d899755f005e5cd90e") {
    die("Token not correct.");
  }
}

// -----------------------------------------------------------------------------------

function head($title="") {
  header("Content-type: text/html; charset=utf-8");
?>
<html>
<head>
  <title>PHP: <?php echo $title ?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link type="text/css" media="all" rel="stylesheet" href="/style.css" />
  <link rel="shortcut icon" href="http://php.net/favicon.ico" />
</head>

<body>
  <h1 id="header"><a href="http://php.net/index.php">PHP: Hypertext Preprocessor</a> - <?php echo $title ?></h1>
  <ul id="mainmenu">
    <li><a href="/manage/event.php">Events</a></li>
    <li><a href="/manage/mirrors.php">Mirrors</a></li>
    <li><a href="/manage/users.php">Users</a></li>
    <li><a href="/manage/user-notes.php">Notes</a></li>
    <li><a href="http://wiki.php.net/">Wiki</a></li>
    <li><a href="http://bugs.php.net/">Bugsweb</a></li>
    <?php if (isset($_SESSION['user'])) { ?>
    <li><a href="/manage/users.php?username=<?php echo $_SESSION['user']; ?>">My php.net Profile</a></li>
    <?php } ?>
  </ul>
<?php warn("Have you changed your password yet?"); ?>
<?php
}

function foot() {
  echo "</body></html>";
}

// -----------------------------------------------------------------------------------

function hsc($str)             { return $GLOBALS['default_filter']?$str:htmlspecialchars($str,ENT_QUOTES,'UTF-8'); }
function hscr($str)            { return htmlspecialchars($str,ENT_QUOTES,'UTF-8'); }
function strip($var)           { return (get_magic_quotes_gpc() ? stripslashes($var) : $var); }
function escape($var)          { return (get_magic_quotes_gpc() ? $var : addslashes($var)); }
function clean($var)           { return $GLOBALS['default_filter']?$var:hsc(strip($var)); }
function format_warn($message) { return "<p class=\"warning\">$message</p>"; }
function warn($message)        { echo format_warn($message); }

// -----------------------------------------------------------------------------------

function db_connect($dieonerror = TRUE)
{
    if (!@mysql_connect("localhost", "nobody", "")) {
        if ($dieonerror) {
            die(format_warn("Unable to connect to database!"));
        }
        return FALSE;
    }
    elseif (!@mysql_select_db("phpmasterdb")) {
        if ($dieonerror) {
            die(format_warn("Unable to select database!"));
        }
        return FALSE;
    }
    return TRUE;
}

function db_query($query)
{
	//var_dump($query);
    $res = @mysql_query($query);
    if (!$res) {
        $bt = debug_backtrace();
        die(format_warn("Query failed: " . hsc(mysql_error()) . "<tt>\n" . hsc($query) . "</tt><br />({$bt[0]['file']}:{$bt[0]['line']})"));
    }
    return $res;
}


function db_get_one($query)
{
    $res = @mysql_query($query);
    if ($res && mysql_num_rows($res)) {
        return mysql_result($res, 0);
    }
    return FALSE;
}

// -----------------------------------------------------------------------------------

function array_to_url($array,$overlay=0) {
  if ($overlay) $array = array_merge($array,$overlay);
  $out = "";
  while (list($k,$v) = each($array)) {
    $out .= "&amp;" . rawurlencode($k) . "=" . rawurlencode($v);
  }
  return substr($out,5); # skip first &amp;
}

function show_prev_next($begin, $rows, $skip, $total, $extra = array())
{?>
<table border="0" cellspacing="1" width="100%">
 <tr bgcolor="#eeeeee">
  <td align="left" width="33%">
   <?php 
     if ($begin > 0) {
       printf("<a href=\"%s?%s\">&laquo; Previous %d",
              $_SERVER['PHP_SELF'],
              array_to_url($extra, array("begin" => max(0,$begin-$skip))),
              min($skip,$begin));
     }
   ?>
   &nbsp;
  </td>
  <td align="center" width="33%">
   <?php echo "Displaying ",$begin+1,"-",$begin+$rows," of $total";?>
  </td>
  <td align="right" width="33%">
   &nbsp;
   <?php 
     if ($begin+$rows < $total) {
       printf("<a href=\"%s?%s\">Next %d &raquo;",
              $_SERVER['PHP_SELF'],
              array_to_url($extra, array("begin" => $begin+$skip)),
              min($skip,$total-($begin+$skip)));
     }
   ?>
  </td>
 </tr>
</table>
<?php
}

function show_country_options($cc = "")
{
    $res = db_query("SELECT id, name FROM country ORDER BY name");
    while ($row = mysql_fetch_assoc($res)) {
        echo "<option value=\"{$row['id']}\"", $cc == $row['id'] ? " selected" : "", ">{$row['name']}</option>";
    }
}

function is_sqlite_type_available($avails, $check) {

	// All possible sqlite types associated with our assigned bitwise values
	$all = array('sqlite' => 1, 'sqlite3' => 2, 'pdo_sqlite' => 4, 'pdo_sqlite2' => 8);
	
	if (!$avails || empty($all[$check])) {
		return false;
	}

	$avail  = (int) $all[$check];
	$avails = (int) $avails;
	
	if (($avails & $avail) === $avail) {
		return true;
	}
	return false;
}

function decipher_available_sqlites($avails) {

	$all    = array(1 => 'sqlite', 2 => 'sqlite3', 4 => 'pdo_sqlite', 8 => 'pdo_sqlite2');
	$mine   = array();
	$avails = (int) $avails;

	if (($avails & 15) === 15) {
		return $all;
	}
	
	foreach ($all as $bit => $name) {
		if (($avails & $bit) === $bit) {
			$mine[$bit] = $name;
		} 
	}
	
	return $mine;
}

function verify_ssh_keys($string) {
    return count(get_ssh_keys($string)) > 0;
}

function get_ssh_keys($string) {
    $results = array();
    if (preg_match_all('@(ssh-(?:rsa|dss) ([^\s]+) ([^\s]*))@', $string, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $results[] = array('key'  => $match[1],
                               'name' => $match[3]);
        }
    }

    return $results;
}
