<?php
// Force login and include common functions
include_once 'login.inc';
include_once 'functions.inc';

// This page is for mirror administration
head("mirror administration");

// Connect to database and select php3 db
mysql_pconnect("localhost","nobody","")
  or die("unable to connect to database");
mysql_select_db("php3");

// Get boolean values from form
$active     = isset($active)     ? 1 : 0;
$has_search = isset($has_search) ? 1 : 0;
$has_stats  = isset($has_stats)  ? 1 : 0;

// We have something to update in the database
if (isset($id) && isset($hostname)) {

    // No query need to be made
    $query = FALSE;
    
    // What to update?
    switch($mode) {

        // Perform a full data update on a mirror
        case "update":
            $query = "UPDATE mirrors SET hostname='$hostname', active=$active, " .
                     "mirrortype=$mirrortype, cname='$cname', maintainer='$maintainer', " .
                     "providername='$providername', providerurl='$providerurl', " .
                     "cc='$cc', lang='$lang', has_stats=$has_stats, " .
                     "has_search=$has_search, lastedited=NOW() WHERE id = $id";
            $msg = "$hostname updated";
        break;

        // Delete a mirror site (specified by the ID)
        case "delete":
            $query = "DELETE FROM mirrors WHERE id = $id";
            $msg = "$hostname deleted";
        break;
    
        // Insert a new mirror site into the database
        case "insert":
            $query = "INSERT INTO mirrors (hostname, active, mirrortype, " .
                     "cname, maintainer, providername, providerurl, cc, " .
                     "lang, has_stats, has_search, created, lastedited) " .
                     "VALUES ('$hostname', $active, $mirrortype, '$cname', " .
                     "'$maintainer', '$providername', '$providerurl', '$cc', " .
                     "'$lang', $has_stats, $has_search, NOW(), NOW())";
            $msg = "$hostname added";
        break;
    }
    
    // If there is any query to execute
    if ($query) {
    
        // Try to execute query, and provide failure information if unable to
        if (!mysql_query($query)) {
            echo "<h2 class=\"error\">Query failed: ", mysql_error(), "</h2>";
        }
        
        // Else provide update message
        else {
            echo "<h2>$msg</h2>";
        }
        
        // In case a of a mirror is deleted, mail a notice to the
        // php-mirrors list, so any malicios deletions can be tracked
        if ($mode == "delete") {
            $body = "The mirrors list was updated, and $hostname was deleted.";
            @mail(
                "php-mirrors@lists.php.net",
                "PHP Mirrors Updated by $user.",
                $body,
                "From: php-mirrors@lists.php.net"
            );
        }
    }
}

// An $id is specified, but no $hostname, show editform
elseif (isset($id)) {
  
  // The $id is not zero, so get mirror information
  if (intval($id) !== 0) {
      $res = mysql_query(
          "SELECT *, " .
          "(DATE_SUB(NOW(), INTERVAL 3 DAY) < lastchecked) AS up, " .
          "(DATE_SUB(NOW(), INTERVAL 7 DAY) < lastupdated) AS current " .
          "FROM mirrors WHERE id = $id"
      );
      $row = mysql_fetch_array($res);
  }

  // The $id is not valid, so provide common defaults for new mirror
  else {
      $row = array(
          'providerurl' => 'http://',
          'active'      => 1,
          'mirrortype'  => 1,
          'lang'        => 'en'
      );
  }

  // Print out mirror data table with or without values
?>
<form method="POST" action="<?php echo $PHP_SELF; ?>">
 <input type="hidden" name="id" value="<?php echo $row['id']; ?>" />
 <input type="hidden" name="mode" value="<?php echo $id ? 'update' : 'insert'; ?>" />
 <table><tr><td>
 <table>
  <tr>
   <th align="right">Hostname (without http://):</th>
   <td><input type="text" name="hostname" value="<?php echo hsc($row['hostname']); ?>" size="40" maxlength="40" /></td>
  </tr>
  <tr>
   <th align="right">Active?</th>
   <td><input type="checkbox" name="active"<?php echo $row['active'] ? " checked" : ""; ?> /></td>
  </tr>
  <tr>
   <th align="right">Type:</th>
   <td><select name="mirrortype"><?php show_mirrortype_options($row['mirrortype']); ?></select></td>
  </tr>
  <tr>
   <th align="right">Cname (without http://):</th>
   <td><input type="text" name="cname" value="<?php echo hsc($row['cname']); ?>" size="40" maxlength="80" /></td>
  </tr>
  <tr>
   <th align="right">Maintainer's Name and Email:</th>
   <td><input type="text" name="maintainer" value="<?php echo hsc($row['maintainer']); ?>" size="40" maxlength="255" /></td>
  </tr>
  <tr>
   <th align="right">Provider's Name:</th>
   <td><input type="text" name="providername" value="<?php echo hsc($row['providername']); ?>" size="40" maxlength="255" /></td>
  </tr>
  <tr>
   <th align="right">Provider URL (with http://):</th>
   <td><input type="text" name="providerurl" value="<?php echo hsc($row['providerurl']); ?>" size="40" maxlength="255" /></td>
  </tr>
  <tr>
   <th align="right">Country:</th>
   <td><select name="cc"><?php show_country_options($row['cc']); ?></select></td>
  </tr>
  <tr>
   <th align="right">Default Language:</th>
   <td><select name="lang"><?php show_language_options($row['lang']); ?></select></td>
  <tr>
   <th align="right">Local Stats</th>
   <td><input type="checkbox" name="has_stats"<?php echo $row['has_stats'] ? " checked" : ""; ?> /></td>
  <tr>
   <th align="right">Local Search</th>
   <td><input type="checkbox" name="has_search"<?php echo $row['has_search'] ? " checked" : ""; ?> /></td>
  </tr>
  <tr>
   <td colspan="2" align="center"><input type="submit" value="<?php echo $id ? "Change" : "Add"; ?>" />
  </tr>
 </table>
 </td><td valign="top">
<?php if (intval($id) !== 0) { ?>
 <table>
  <tr>
   <th colspan="2"><?php if (!$row['up'] || !$row['current']) { echo '<p class="error">This mirror is automatically disabled</p>'; } else { echo "&nbsp;"; } ?></th>
  </tr>
  <tr>
   <th>Mirror added:</th>
   <td><?php echo $row['created']; ?></td>
  </tr>
  <tr>
   <th>Last edit time:</th>
   <td><?php echo $row['lastedited']; ?></td>
  </tr>
  <tr>
   <th>Last mirror check time:</th>
   <td><?php echo $row['lastchecked']; if (!$row['up']) { echo '<br /><i>does not seem to be up!</i>'; } ?></td>
  </tr>
  <tr>
   <th>Last update time:</th>
   <td><?php echo $row['lastupdated']; if (!$row['current']) { echo '<i><br />does not seem to be current!</i>'; } ?></td>
  </tr>
  <tr>
   <th>PHP version used on mirror site:</th>
   <td><?php echo $row['phpversion']; ?></td>
  </tr>
 </table>
<?php } else { echo "&nbsp;"; } ?>
 </td></tr></table>
</form>
<?
  // Form printed, exit script
  foot();
  exit();
}

// Query whole mirror list and display all of them. The query is
// similar to one in the mirror fetch script. We need to get mirror
// status data to show colors and need to order by country too to make
// still non-officially named mirrors show in the right place
$res = mysql_query("SELECT mirrors.*, " .
                   "(DATE_SUB(NOW(), INTERVAL 3 DAY) < mirrors.lastchecked) AS up, " .
                   "(DATE_SUB(NOW(), INTERVAL 7 DAY) < mirrors.lastupdated) AS current " .
                   "FROM mirrors LEFT JOIN country ON mirrors.cc = country.id " .
                   "ORDER BY country.name, hostname"
       ) or die("query failed");
?>
<p>Colors: green = special, red = not active, blue = outdated or not working</p>
<table border="0" cellspacing="1" width="100%">
 <tr bgcolor="#aaaaaa">
  <td></td>
  <th>Name</th>
  <th>Maintainer</th>
  <th>Provider</th>
  <th>Stats</th>
  <th>Search</th>
  <th></th>
 </tr>
<?php

// Start with this color (#dddddd)
$c = 'd';

// Go through all mirror sites
while ($row = mysql_fetch_array($res)) {
    
    // Active mirror site
    if ($row['active']) {
        
        // Special active mirror site (green)
        if ($row['mirrortype'] != 1) { $sitecolor = "#{$c}{$c}ff{$c}{$c}"; }
        
        // Not special, but active
        else {
            // Not up to date or not current (blue)
            if (!$row['up'] || !$row['current']) {
                $sitecolor = "#{$c}{$c}{$c}{$c}ff";
            }
            // Up to date and current (gray)
            else {
                $sitecolor = "#{$c}{$c}{$c}{$c}{$c}{$c}";
            }
        }
    }
    // Not active mirror site (red)
    else {
        $sitecolor = "#ff{$c}{$c}{$c}{$c}";
    }

?>
 <tr bgcolor="<?php echo $sitecolor; ?>">
  <td align="center">
   <a href="<?php echo "$PHP_SELF?id=$row[id]";?>">edit</a>
  </td>
  <td>
   <a href="<?php echo "http://", hsc($row['hostname']); ?>"><?php echo hsc(preg_replace("!\\.php\\.net$!", "", $row['hostname'])); ?></a>
  </td>
  <td>
   <?php echo hsc($row['maintainer']); ?>
  </td>
  <td>
   <a href="<?php echo hsc($row['providerurl']); ?>"><?php echo hsc($row['providername']); ?></a>
  </td>
  <td align="center">
   <?php echo $row['has_stats'] ? "<a href=\"http://$row[hostname]/stats/\">go</a>" : "&nbsp;"; ?>
  </td>
  <td align="center">
   <?php echo $row['has_search'] ? "<a href=\"http://$row[hostname]/search.php\">go</a>" : "&nbsp;"; ?>
  </td>
  <td align="center">
   <?php echo ($row['mirrortype'] == 1) ? "<a href=\"$PHP_SELF?mode=delete&hostname=$row[hostname]&id=$row[id]\">delete</a>" : "&nbsp;"; ?>
  </td>
 </tr>
<?php
  // Switch color to the alternate one
  $c = ($c == 'd' ? 'e' : 'd');
}
?>
</table>
<p><a href="<?php echo $PHP_SELF;?>?id=0">Add a new mirror</a></p>
<?php

// Print out footer (end of script run)
foot();

// Show language options for mirror site
function show_language_options($lang = "en")
{

    // This should always contain the same as
    // phpweb/include/languages.inc, or
    // otherwise bad things may happen
    $LANGUAGES = array(
        'en'    => 'English',
        'pt_BR' => 'Brazilian Portuguese',
        'bg'    => 'Bulgarian',
        'ca'    => 'Catalan',
        'zh'    => 'Chinese',
        'zh_cn' => 'Chinese',
        'cs'    => 'Czech',
        'da'    => 'Danish',
        'nl'    => 'Dutch',
        'fi'    => 'Finnish',
        'fr'    => 'French',
        'de'    => 'German',
        'el'    => 'Greek',
        'hu'    => 'Hungarian',
        'in'    => 'Indonesian',
        'it'    => 'Italian',
        'ja'    => 'Japanese',
        'ko'    => 'Korean',
        'kr'    => 'Korean', // This should be 'ko'. It is wrong in phpdoc!
        'lv'    => 'Latvian',
        'no'    => 'Norwegian',
        'pl'    => 'Polish',
        'pt'    => 'Portuguese',
        'ro'    => 'Romanian',
        'ru'    => 'Russian',
        'sk'    => 'Slovak',
        'sl'    => 'Slovenian',
        'es'    => 'Spanish',
        'sv'    => 'Swedish',
        'th'    => 'Thai',
        'tr'    => 'Turkish',
        'uk'    => 'Ukranian',
    );
  
    // Write out an <option> for all languages
    foreach ($LANGUAGES as $code => $name) {
        echo "<option value=\"$code\"" ,
             $lang == $code ? " selected" : "" ,
             ">$name</option>";
    }
}

// Show mirror type options defaulting to current type
function show_mirrortype_options($type = 1)
{
    // There are two mirror types
    $types = array(1 => "standard", 2 => "special", 0 => "download");

    // Write out an <option> for all types
    foreach ($types as $code => $name) {
        echo "<option value=\"$code\"", 
             $type == $code ? " selected" : "",
             ">$name</option>";
    }
}

?>