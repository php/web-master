<?php

use App\DB;

require_once __DIR__ . '/../vendor/autoload.php';

$ts = $_SERVER['REQUEST_TIME'];

if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
    die("Magic quotes should not be enabled.");
}

if (ini_get('filter.default') !== 'unsafe_raw') {
    die("filter.default should not be set.");
}

// -----------------------------------------------------------------------------------

// used in scripts which should only be called from particular machines
function require_token()
{
  if (!isset($_GET['token']) || md5($_GET['token']) !== "19a3ec370affe2d899755f005e5cd90e") {
    die("Token not correct.");
  }
}

// -----------------------------------------------------------------------------------

function head($title="", $config = []) {
    $dconfig = ["columns" => 1];

    $config = array_merge($dconfig, $config);
    $SUBDOMAIN = "main";
    $TITLE = $title ?: "main";
    $LINKS = [
        ["href" => "/manage/event.php",        "text" => "Events"],
        ["href" => "/manage/users.php",        "text" => "Users"],
        ["href" => "/manage/user-notes.php",   "text" => "Notes"],
        ["href" => "/manage/github.php",       "text" => "Github"],
    ];
    $CSS = ["/styles/master.css"];
    $SEARCH = [];

    if (strstr($_SERVER["SCRIPT_FILENAME"], "users.php")) {
        $SEARCH = ["method" => "get", "action" => "/manage/users.php", "placeholder" => "Search profiles", "name" => "search"];
        $CSS[] = "/styles/user-autocomplete.css";
    }
    if (strstr($_SERVER["SCRIPT_FILENAME"], "event.php")) {
        $SEARCH = ["method" => "get", "action" => "/manage/event.php", "placeholder" => "Search Events", "name" => "search"];
    }
    if (strstr($_SERVER["SCRIPT_FILENAME"], "user-notes.php")) {
        $SEARCH = ["method" => "get", "action" => "/manage/user-notes.php", "placeholder" => "Search notes (keyword, ID or sect:)", "name" => "keyword"];
    }
    if (isset($_SESSION['credentials'])) {
        array_unshift($LINKS, ["href" => "/manage/users.php?username=" . $_SESSION["credentials"][0], "text" => "My Profile"]);
        $LINKS[] = ["href" => "/login.php?action=logout", "text" => "Logout"];
    }
    include __DIR__ . "/../shared/templates/header.inc";
    if ($config["columns"] > 1) {
        echo '<section class="mainscreen">';
    } else {
        echo '<section class="fullscreen">';
    }
}

function foot($secondscreen = null) {
    $SECONDSCREEN = $secondscreen;

    $JS = [
        "/js/master.js",
    ];
    if (strstr($_SERVER["SCRIPT_FILENAME"], "users.php")) {
        array_push(
            $JS,
            "//people.php.net/js/jquery.autocomplete.min.js",
            "//people.php.net/js/userlisting.php",
            "//people.php.net/js/search.js"
        );
    }

?>
  </section>
<?php
    include __DIR__ . "/../shared/templates/footer.inc";
}

// -----------------------------------------------------------------------------------

function hsc($str)             { return htmlspecialchars((string)$str,ENT_QUOTES,'UTF-8'); }
function format_warn($message) { return "<p class=\"warning\">$message</p>"; }
function warn($message)        { echo format_warn($message); }

// -----------------------------------------------------------------------------------

function array_to_url($array,$overlay=[]) {
    $params = [];
    foreach($array as $k => $v) {
        $params[$k] = rawurlencode($k) . "=" . rawurlencode($v);
    }
    foreach($overlay as $k => $v) {
        if ($array[$k] == $v) {
            $params["forward"] = $array["forward"] ? "forward=0" : "forward=1";
            continue;
        }
        $params["forward"] = "forward=0";
        $params[$k] = rawurlencode($k) . "=" . rawurlencode($v);
    }

    return implode("&amp;", $params);
}

/**
 * @param int $begin
 * @param int $rows
 * @param int $skip
 * @param int $total
 */
function show_prev_next($begin, $rows, $skip, $total, $extra = [], $table = true)
{?>
<?php if ($table): ?>
<table border="0" cellspacing="1" width="100%">
<?php endif ?>
 <tr>
  <td>
   <?php
     if ($begin > 0) {
       printf("<a href=\"%s?%s\">&laquo; Previous %d",
              $_SERVER['PHP_SELF'],
              array_to_url($extra, ["begin" => max(0,$begin-$skip)]),
              min($skip,$begin));
     }
   ?>
   &nbsp;
  </td>
<?php if($table): ?>
  <td>
<?php else: ?>
  <td colspan="4">
<?php endif; ?>
   <?php echo "Displaying ",$begin+1,"-",$begin+$rows," of $total";?>
  </td>
  <td>
   &nbsp;
   <?php
     if ($begin+$rows < $total) {
       printf("<a href=\"%s?%s\">Next %d &raquo;",
              $_SERVER['PHP_SELF'],
              array_to_url($extra, ["begin" => $begin+$skip]),
              min($skip,$total-($begin+$skip)));
     }
   ?>
  </td>
 </tr>
<?php if ($table): ?>
</table>
<?php endif ?>
<?php
}

function show_country_options(DB $pdo, $cc = "")
{
    $res = $pdo->safeQuery("SELECT id, name FROM country ORDER BY name");
    foreach ($res as $row) {
        echo "<option value=\"{$row['id']}\"", $cc == $row['id'] ? " selected" : "", ">{$row['name']}</option>";
    }
}

function verify_ssh_keys($string) {
    return count(get_ssh_keys($string)) > 0;
}

function get_ssh_keys($string) {
    $results = [];
    if (preg_match_all('@(ssh-(?:rsa|dss) ([^\s]+) ([^\s]*))@', $string, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $results[] = ['key'  => $match[1],
                               'name' => $match[3]];
        }
    }

    return $results;
}

// -----------------------------------------------------------------------------------
//

function find_group_address_from_notes_for(DB $pdo, $id) {
    $note = $pdo->single("SELECT note FROM users_note WHERE userid = ? LIMIT 1", [$id]);
    $cc = "";
    if (preg_match("/\[group: (\w+)\]/", $note, $matches)) {
      switch($matches[1]) {
      case "php":
        $cc = "internals@lists.php.net";
        break;
      case "pear":
        $cc = "pear-group@lists.php.net";
        break;
      case "pecl":
        $cc = "pecl-dev@lists.php.net";
        break;
      case "doc":
        $cc = "phpdoc@lists.php.net";
        break;
      }
    }
    return $cc;
}

define("MT_USER_APPROVE_MAIL", "group@php.net");
define("MT_USER_REMOVE_MAIL", "group@php.net");
function user_approve(DB $pdo, $id) {
    $res = $pdo->safeQueryReturnsAffectedRows("UPDATE users SET cvsaccess=1, enable=1 WHERE userid=?", [$id]);
    if ($res) {
      $cc = find_group_address_from_notes_for($pdo, $id);
      $mailtext = $cc ? $cc : EMAIL_DEFAULT_CC;
      $userinfo = fetch_user($pdo, $id);

      $message = mt_approve_user($userinfo, $mailtext);
      /* Notify the user */
      mail($userinfo["email"], "VCS Account Request: $userinfo[username]", $message, "From: PHP Group <group@php.net>", "-fnoreply@php.net");

      /* Notify the public records */
      $to = MT_USER_APPROVE_MAIL . ($cc ? ",$cc" : "");
      $subject = "Re: VCS Account Request: $userinfo[username]";
      $message = "VCS Account Approved: $userinfo[username] approved by {$_SESSION["username"]} \o/";
      $headers = "From: PHP Group <group@php.net>\nIn-Reply-To: <cvs-account-$id@php.net>";
      mail($to, $subject, $message, $headers, "-fnoreply@php.net");
      warn("record $id ($userinfo[username]) approved");
      return true;
    } else {
      warn("wasn't able to grant access to id $id.");
      return false;
    }
}

function user_remove(DB $pdo, $id) {
    $userinfo = fetch_user($pdo, $id);
    $res = $pdo->safeQueryReturnsAffectedRows("DELETE FROM users WHERE userid=?", [$id]);
    if ($res) {
      $cc = find_group_address_from_notes_for($pdo, $id);

      $message = $userinfo['cvsaccess'] ? mt_remove_user($userinfo) : mt_deny_user($userinfo);

      /* Notify the user */
      mail($userinfo['email'],"VCS Account Request: $userinfo[username]",$message,"From: PHP Group <group@php.net>", "-fnoreply@php.net");

      $to = MT_USER_REMOVE_MAIL . ($cc ? ",$cc" : "");
      $subject = "Re: VCS Account Request: $userinfo[username]";
      $message = $userinfo['cvsaccess']
          ? "VCS Account Deleted: $userinfo[username] deleted by {$_SESSION["username"]} /o\\"
          : "VCS Account Rejected: $userinfo[username] rejected by {$_SESSION["username"]} /o\\";

      /* Notify public records */
      mail($to, $subject, $message,"From: PHP Group <group@php.net>\nIn-Reply-To: <cvs-account-$id@php.net>", "-fnoreply@php.net");
      $pdo->safeQuery("DELETE FROM users_note WHERE userid=?", [$id]);
      $pdo->safeQuery("DELETE FROM users_profile WHERE userid=?", [$id]);
      warn("record $id ($userinfo[username]) removed");
      return true;
    } else {
      warn("wasn't able to delete id $id.");
      return false;
    }
}

function is_admin($user) {
  $admins = [
    "jimw",
    "rasmus",
    "sas",
    "stas",
    "mj",
    "philip",
    "davidc",
    "derick",
    "cmb",
    "kalle",
    "krakjoe",
    "pollita",
    "ilutov",
    "bukka",
  ];
  return in_array($user, $admins);
}

function is_mirror_site_admin($user) {
  return is_admin($user);
}

# returns false if $user is not allowed to modify $userid
function can_modify(DB $pdo, $user,$userid) {
  if (is_admin($user)) return true;

  $query = "SELECT 1 FROM users WHERE userid = ? AND (email = ? OR username = ?)";
  $res = $pdo->single($query, [$userid, $user, $user]);
  return (bool) $res;
}

function fetch_user(DB $pdo, $user): array {
  if ((int)$user) {
    return $pdo->row(
      "SELECT * FROM users LEFT JOIN users_note USING (userid) WHERE users.userid = ?",
      [$user]);
  }

  return $pdo->row(
    "SELECT * FROM users LEFT JOIN users_note USING (userid) WHERE username = ? OR email = ?",
    [$user, $user]);
}

function invalid_input($in) {
  if (!empty($in['email']) && strlen($in['email']) && !is_emailable_address($in['email'])) {
    return "'". hsc($in['email']) ."' does not look like a valid email address";
  }
  if (!empty($in['username']) && !preg_match("/^[-\w]+\$/",$in['username'])) {
    return "'". hsc($in['username']) ."' is not a valid username";
  }
  if (!empty($in['rawpasswd']) && $in['rawpasswd'] != $in['rawpasswd2']) {
    return "the passwords you specified did not match!";
  }
  if (!empty($in['sshkey']) && !verify_ssh_keys($in['sshkey'])) {
    return "the ssh key doesn't seem to have the necessary format";
  }

  return false;
}

function validateAction($k) {
  switch($k) {
  case "approve":
  case "remove":
    return $k;
  default:
    warn("that action ('" . hsc($k) . "') is not understood.");
  }

  return false;
}

function display_options($options,$current) {
  foreach ($options as $k => $v) {
    echo '<option value="', $k, '"',
         ($k == $current ? ' selected="selected"' : ''),
         '>', html_entity_decode($v,ENT_QUOTES), "</option>\n";
  }
}
