<?php
// $Id$

// -----------------------------------------------------------------------------------

// used in scripts which should only be called from particular machines
function require_token()
{
  if (!isset($_GET['token']) || md5($_GET['token']) != "19a3ec370affe2d899755f005e5cd90e") {
    die("Token not correct.");
  }
}

// -----------------------------------------------------------------------------------

function head($title="") {?>
<html>
<head>
 <title><?php echo $title;?></title>
 <link rel="stylesheet" type="text/css" href="/style.css" />
</head>
<body>
 <div id="header">
  <div id="logo">
   <a href="http://www.php.net/">
    <img src="/include/logo.gif" width="53" height="28" border="0" alt="php" align="absmiddle" />
   </a>
  </div>
  <div id="links">
   <a href="/manage/event.php">events</a> &bull;
   <a href="/inventory.php">inventory</a> &bull;
   <a href="/manage/mirrors.php">mirrors</a> &bull;
   <a href="/manage/users.php">users</a> &bull;
   <a href="/manage/user-notes.php">notes</a>
  </div>
 </div>
<?php
}

function foot() {
  echo "</body></html>";
}

// -----------------------------------------------------------------------------------

function hsc($str)             { return htmlspecialchars($str); }
function strip($var)           { return (get_magic_quotes_gpc() ? stripslashes($var) : $var); }
function clean($var)           { return hsc(strip($var)); }
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
    elseif (!@mysql_select_db("php3")) {
        if ($dieonerror) {
            die(format_warn("Unable to select database!"));
        }
        return FALSE;
    }
    return TRUE;
}

function db_query($query)
{
    $res = @mysql_query($query);
    if (!$res) {
        die(format_warn("Query failed: " . hsc(mysql_error()) . "<tt>\n" . hsc($query) . "</tt>"));
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
