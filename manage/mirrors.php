<?php
require_once 'login.inc';
require_once 'functions.inc';

# http://www.unicode.org/unicode/onlinedat/languages.html
$LANGUAGES = array(
    'en' => 'English',
    'pt_BR' => 'Brazilian Portuguese',
    'bg' => 'Bulgarian',
    'ca' => 'Catalan',
    'zh' => 'Chinese',
    'cs' => 'Czech',
    'da' => 'Danish',
    'nl' => 'Dutch',
    'fi' => 'Finnish',
    'fr' => 'French',
    'de' => 'German',
    'el' => 'Greek',
    'hu' => 'Hungarian',
    'in' => 'Indonesian',
    'it' => 'Italian',
    'ja' => 'Japanese',
    'kr' => 'Korean', # this should be 'ko'. its wrong in phpdoc.
    'lv' => 'Latvian',
    'no' => 'Norwegian',
    'pl' => 'Polish',
    'pt' => 'Portuguese',
    'ro' => 'Romanian',
    'ru' => 'Russian',
    'sk' => 'Slovak',
    'sl' => 'Slovenian',
    'es' => 'Spanish',
    'sv' => 'Swedish',
    'th' => 'Thai',
    'tr' => 'Turkish',
    'uk' => 'Ukranian',
);

head("mirror administration");

mysql_pconnect("localhost","nobody","")
  or die("unable to connect to database");
mysql_select_db("php3");

$active = isset($active) ? 1 : 0;
$has_search = isset($has_search) ? 1 : 0;
$has_stats = isset($has_stats) ? 1 : 0;

if (isset($id) && isset($hostname)) {

switch($mode) { 
	case "update":
		
		$query = "UPDATE mirrors SET hostname='$hostname',active=$active,mirrortype=$mirrortype,cname='$cname',maintainer='$maintainer',providername='$providername',providerurl='$providerurl',cc='$cc',lang='$lang',has_stats=$has_stats,has_search=$has_search,lastedited=NOW() WHERE id=$id";
		$msg = "$hostname updated";
	break;

	case "delete":	
		
		$query = "DELETE FROM mirrors WHERE id=$id";	
		$msg = "$hostname deleted";
	break;	

	case "insert";
		$query = "INSERT INTO mirrors (hostname,active,mirrortype,cname,maintainer,providername,providerurl,cc,lang,has_stats,has_search,created,lastedited) VALUES ('$hostname',$active,$mirrortype,'$cname','$maintainer','$providername','$providerurl','$cc','$lang',$has_stats,$has_search,NOW(),NOW())";
		$msg = "$hostname added";
	break;

	default:
		$query = "SELECT * FROM mirrors";

 }
  if (!mysql_query($query)) {
    echo "<h2 class=\"error\">Query failed: ", mysql_error(), "</h2>";
  }
  else {
    echo "<h2>$msg</h2>";
  }
 if($mode == "delete") {
	// quick security mail..
	$body = "The mirrors list was updated, and $hostname was deleted.";
	mail("php-mirrors@lists.php.net", "PHP Mirrors Updated by $user.", $body, "From: php-mirrors@lists.php.net");
 }
}
elseif (isset($id)) {
  if ($id) {
    $res = mysql_query("SELECT * FROM mirrors WHERE id=$id");
    $row = mysql_fetch_array($res);
  }
?>
<table>
<form method="POST" action="<?php echo $PHP_SELF;?>">
<input type="hidden" name="id" value="<?php echo $row[id];?>" />
<?php echo $id ? '<input type="hidden" name="mode" value="update" />' : '<input type="hidden" name="mode" value="insert" />' ; ?>
<tr>
 <th align="right">Hostname:</th>
 <td><input type="text" name="hostname" value="<?php echo htmlspecialchars($row[hostname]);?>" size="40" maxlength="40" /></td>
</tr>
<tr>
 <th align="right">Active?</th>
 <td><input type="checkbox" name="active"<?php echo $row[active] ? " checked" : "";?> /></td>
</tr>
<tr>
 <th align="right">Type:</th>
 <td><select name="mirrortype"><?php show_mirrortype_options($row[mirrortype]);?></select></td>
</tr>
<tr>
 <th align="right">Cname:</th>
 <td><input type="text" name="cname" value="<?php echo htmlspecialchars($row[cname]);?>" size="40" maxlength="80" /></td>
</tr>
<tr>
 <th align="right">Maintainer:</th>
 <td><input type="text" name="maintainer" value="<?php echo htmlspecialchars($row[maintainer]);?>" size="40" maxlength="255" /></td>
</tr>
<tr>
 <th align="right">Provider:</th>
 <td><input type="text" name="providername" value="<?php echo htmlspecialchars($row[providername]);?>" size="40" maxlength="255" /></td>
</tr>
<tr>
 <th align="right">Provider URL (with http://):</th>
 <td><input type="text" name="providerurl" value="<?php echo htmlspecialchars($row[providerurl]);?>" size="40" maxlength="255" /></td>
</tr>
<tr>
 <th align="right">Country:</th>
 <td><select name="cc"><?php show_country_options($row[cc]);?></select></td>
</tr>
<tr>
 <th align="right">Language:</th>
 <td><select name="lang"><?php show_language_options($row[lang]);?></select></td>
<tr>
 <th align="right">Stats?</th>
 <td><input type="checkbox" name="has_stats"<?php echo $row[has_stats] ? " checked" : "";?> /></td>
<tr>
 <th align="right">Search?</th>
 <td><input type="checkbox" name="has_search"<?php echo $row[has_search] ? " checked" : "";?> /></td>
</tr>
<tr>
 <td><input type="submit" value="<?php echo $id ? "Change" : "Add";?>" />
</tr>
</table>
<?
  foot();
  exit;
}

$res = mysql_query("SELECT * FROM mirrors ORDER BY hostname")
  or die("query failed");

?>
<p>blue entries are special (not real mirrors), red entries are not active.</p>
<table border="0" cellspacing="1" width="100%">
<tr bgcolor="#aaaaaa">
 <td></td>
 <th>Hostname</th>
 <th>Maintainer</th>
 <th>Provider</th>
 <th>Stats</th>
 <th>&nbsp;</th>
</tr>
<?php
$color = '#dddddd';
while ($row = mysql_fetch_array($res)) {?>
<tr bgcolor="<?php echo $row[active] ? ($row[mirrortype] == 1 ? $color : substr($color,0,5)."ff") : "#ff".substr($color,3);?>">
 <td align="center"><a href="<?php echo "$PHP_SELF?id=$row[id]";?>">edit</a></td>
 <td><a href="<?php echo ereg("^(f|ht)tp:",$row[hostname]) ? "" : "http://", htmlspecialchars($row[hostname]);?>"><?php echo htmlspecialchars($row[hostname]);?></a></td>
 <td><?php echo htmlspecialchars($row[maintainer]);?>&nbsp;</td>
 <td><a href="<?php echo htmlspecialchars($row[providerurl]);?>"><?php echo htmlspecialchars($row[providername]);?></a></td>
 <td align="center"><?php echo $row[has_stats] ? "<a href=\"http://$row[hostname]/stats/\">go</a>" : "&nbsp;";?></td>
 <td align="center"><?php echo ($row[mirrortype] == 1) ? "<a href=\"$PHP_SELF?mode=delete&hostname=$row[hostname]&id=$row[id]\">delete</a>" : "&nbsp;";?></td>
</tr>
<?php
  $color = $color == '#dddddd' ? '#eeeeee' : '#dddddd';
}
?>
</table>
<p><a href="<?php echo $PHP_SELF;?>?id=0">add a new mirror</a></p>
<?php
foot();

function show_language_options($lang = "") {
  global $LANGUAGES;
  reset($LANGUAGES);
  while (list($k,$v) = each($LANGUAGES)) {
    echo "<option value=\"$k\"", $lang == $k ? " selected" : "", ">$v</option>";
  }
}

function show_mirrortype_options($type = 1) {
  $types = array(1 => "standard", 2 => "special", 0 => "download" );
  while (list($k,$v) = each($types)) {
    echo "<option value=\"$k\"", $type == $k ? " selected" : "", ">$v</option>";
  }
}
?>
