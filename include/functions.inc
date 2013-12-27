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
    $SUBDOMAIN = "master";
    $TITLE = $title ?: "master";
    $LINKS = array(
        array("href" => "/manage/event.php",        "text" => "Events"),
        array("href" => "/manage/mirrors.php",      "text" => "Mirrors"),
        array("href" => "/manage/users.php",        "text" => "Users"),
        array("href" => "/manage/user-notes.php",   "text" => "Notes"),
        array("href" => "/manage/github.php",       "text" => "Github"),
    );
    $CSS = array("/shared/styles/master.css");
    $SEARCH = array();

    if (strstr($_SERVER["SCRIPT_FILENAME"], "users.php")) {
        $SEARCH = array("method" => "get", "action" => "/manage/users.php", "placeholder" => "Search profiles", "name" => "search");
        $CSS[] = "/shared/styles/user-autocomplete.css";
    }
    if (strstr($_SERVER["SCRIPT_FILENAME"], "event.php")) {
        $SEARCH = array("method" => "get", "action" => "/manage/event.php", "placeholder" => "Search Events", "name" => "search");
    }
    if (isset($_SESSION['credentials'])) {
        array_unshift($LINKS, array("href" => "/manage/users.php?username=" . $_SESSION["credentials"][0], "text" => "My Profile"));
        $LINKS[] = array("href" => "/login.php?action=logout", "text" => "Logout");
    }
    include __DIR__ . "/../shared/templates/header.inc";
    echo '<section class="fullscreen">';
}

function foot() {
    $JS = array(
        "//ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js",
    );
    if (strstr($_SERVER["SCRIPT_FILENAME"], "users.php")) {
        array_push(
            $JS,
            "//people.php.net/js/jquery.autocomplete.min.js",
            "//people.php.net/js/userlisting.php",
            "//people.php.net/js/search.js"
        );
    }
    if (strstr($_SERVER["SCRIPT_FILENAME"], "mirror.php")) {
        array_push(
            $JS,
            "js/curvycorners.js"
        );
    }

?>
  </section>
  <script type="text/javascript" >
  $(document).ready(function() {
    $('#votesdeleteform').submit(function() {
      if (confirm("Are you sure you want to delete these votes? This will permanently delete these votes from the votes table!"))
        return true;
      else
        return false;
    });
    $('#votesselectall').click(function() {
      if (this.checked) {
        $('input:checkbox.vdelids').each(function () {
             this.checked = true;
        });
      } else {
        $('input:checkbox.vdelids').each(function () {
             this.checked = false;
        });
      }
    });
  });
  </script>
<?php
    include __DIR__ . "/../shared/templates/footer.inc";
}

// -----------------------------------------------------------------------------------

function hsc($str)             { return $GLOBALS['default_filter']?$str:htmlspecialchars($str,ENT_QUOTES,'UTF-8'); }
function hscr($str)            { return htmlspecialchars($str,ENT_QUOTES,'UTF-8'); }
function strip($var)           { return (get_magic_quotes_gpc() ? stripslashes($var) : $var); }
function escape($var)          { return (get_magic_quotes_gpc() ? $var : addslashes($var)); }
function clean($var)           { return $GLOBALS['default_filter']?$var:hsc(strip($var)); }
function format_warn($message) { return "<p class=\"warning\">$message</p>"; }
function warn($message)        { echo format_warn($message); }
function real_clean($var)      { return mysql_real_escape_string($var); }

// -----------------------------------------------------------------------------------

function db_connect($dieonerror = TRUE)
{
    if (!mysql_connect("localhost", "nobody", "")) {
        if ($dieonerror) {
            die(format_warn("Unable to connect to database!"));
        }
        return FALSE;
    }
    elseif (!mysql_select_db("phpmasterdb")) {
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
    $res = mysql_query($query);
    if (!$res) {
        $bt = debug_backtrace();
        die(format_warn("Query failed: " . hsc(mysql_error()) . "<tt>\n" . hsc($query) . "</tt><br />({$bt[0]['file']}:{$bt[0]['line']})"));
    }
    return $res;
}


function db_get_one($query)
{
    $res = mysql_query($query);
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

function unmangle($var_name,$filter_id=FILTER_UNSAFE_RAW) {
	return real_clean(filter_var($var_name,$filter_id));
}

/**
 * If $ext is defined, return TRUE if available, FALSE if not.
 * If $ext is not defined, return a string of all available extensions.
 */
function get_extension_info($mirror_hostname,$ext=null) {
	$exts = db_get_one("SELECT ext_avail FROM mirrors WHERE hostname='".real_clean($mirror_hostname)."'");
	if (!is_null($ext)) {
		if (preg_match('/\b'.$ext.'\b/Ui',$exts)) {
			return true;
		} else {
			return false;
		}
	}
	return $exts;
}

// We use markdown for people profiles
include_once dirname(__FILE__) . '/../vendor/michelf/php-markdown-extra/markdown.php';


// -----------------------------------------------------------------------------------

/*
    This function will revert the GPCRS superglobals to their raw state if the default.filter/magic_quotes is on.
    Please do not use this function unless your code has no dependency on magic_quotes and is properly escaping data.
*/
function undo_magic_quotes() {
    if (!empty($_POST)) {
        $args = array();
        foreach ($_POST as $key => $val) $args[$key] = array('filter' => FILTER_UNSAFE_RAW, 'flags' => is_array($val) ? 
                                                              FILTER_REQUIRE_ARRAY : FILTER_REQUIRE_SCALAR);
        $_POST = filter_input_array(INPUT_POST, $args);
        $_REQUEST = filter_input_array(INPUT_POST, $args);
    }
    if (!empty($_GET)) {
        $args = array();
        foreach ($_GET as $key => $val) $args[$key] = array('filter' => FILTER_UNSAFE_RAW, 'flags' => is_array($val) ? 
                                                            FILTER_REQUIRE_ARRAY : FILTER_REQUIRE_SCALAR);
        $_GET = filter_input_array(INPUT_GET, $args);
        $_REQUEST += filter_input_array(INPUT_GET, $args);
    }
    if (!empty($_COOKIE)) {
        $args = array();
        foreach ($_COOKIE as $key => $val) $args[$key] = array('filter' => FILTER_UNSAFE_RAW, 'flags' => is_array($val) ?
                                                               FILTER_REQUIRE_ARRAY : FILTER_REQUIRE_SCALAR);
        $_COOKIE = filter_input_array(INPUT_COOKIE, $args);
        $_REQUEST += filter_input_array(INPUT_COOKIE, $args);
    }
    if (!empty($_SERVER)) {
        $args = array();
        $append = array();
        foreach ($_SERVER as $key => $val) {
            if ($key == 'REQUEST_TIME' || $key == 'REQUEST_TIME_FLOAT') {
                $append[$key] = $val;
                continue;
            }
            $args[$key] = array('filter' => FILTER_UNSAFE_RAW, 'flags' => is_array($val) ?
                                FILTER_REQUIRE_ARRAY : FILTER_REQUIRE_SCALAR);
        }
        $_SERVER = filter_input_array(INPUT_SERVER, $args);
        $_SERVER += $append;
    }
}

// -----------------------------------------------------------------------------------
