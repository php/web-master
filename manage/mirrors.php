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
$has_stats  = isset($has_stats)  ? 1 : 0;

// Select last mirror check time from table
$lct = mysql_query("SELECT UNIX_TIMESTAMP(lastchecked) FROM mirrors ORDER BY lastchecked DESC LIMIT 1");
list($checktime) = mysql_fetch_row($lct);

// We have something to update in the database
if (isset($id) && isset($hostname)) {

    if (is_admin($user)) {
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
                         "lastedited=NOW(), acmt='$acmt' WHERE id = $id";
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
                         "lang, has_stats, created, lastedited, acmt) " .
                         "VALUES ('$hostname', $active, $mirrortype, '$cname', " .
                         "'$maintainer', '$providername', '$providerurl', '$cc', " .
                         "'$lang', $has_stats, NOW(), NOW(), '$acmt')";
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
            if ($mode == "delete" || $mode == "insert") {
                $body = "The mirrors list was updated, and $hostname was " . ($mode == "delete" ? "deleted." : "added.");
                @mail(
                    "php-mirrors@lists.php.net",
                    "PHP Mirrors Updated by $user.",
                    $body,
                    "From: php-mirrors@lists.php.net"
                );
            }
        }
    } else {
        warn("You're not allowed to take actions on mirrors.");
    }
}

// An $id is specified, but no $hostname, show editform
elseif (isset($id)) {
  
  // The $id is not zero, so get mirror information
  if (intval($id) !== 0) {
      $res = mysql_query(
          "SELECT *, " .
          "UNIX_TIMESTAMP(created) AS ucreated, " .
          "UNIX_TIMESTAMP(lastedited) AS ulastedited, " .
          "UNIX_TIMESTAMP(lastupdated) AS ulastupdated, " .
          "UNIX_TIMESTAMP(lastchecked) AS ulastchecked, " .
          "(DATE_SUB(FROM_UNIXTIME($checktime), INTERVAL 3 DAY) < lastchecked) AS up, " .
          "(DATE_SUB(FROM_UNIXTIME($checktime), INTERVAL 7 DAY) < lastupdated) AS current " .
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

  // Local search type displays
  $searchtypes = array(
      '0' => 'Not supported',
      '1' => 'Supported',
      '2' => 'Supported (old method)'
  );

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
   <th align="right">Local Stats:</th>
   <td><input type="checkbox" name="has_stats"<?php echo $row['has_stats'] ? " checked" : ""; ?> /></td>
  </tr>
  <tr>
   <th align="right">Administration comments:</th>
   <td><textarea wrap="virtual" cols="40" rows="12" name="acmt"><?php echo htmlspecialchars($row['acmt']); ?></textarea></td>
  </tr>
  <tr>
   <td colspan="2" align="center"><input type="submit" value="<?php echo $id ? "Change" : "Add"; ?>" />
  </tr>
 </table>
 </td><td valign="top">
<?php

if (intval($id) !== 0) {

    // We need the actual languages include file
    include_once "http://php.net/include/languages.inc";
?>
 <table>
  <tr>
   <th colspan="2"><?php if (!$row['up'] || !$row['current']) { echo '<p class="error">This mirror is automatically disabled</p>'; } else { echo "&nbsp;"; } ?></th>
  </tr>
  <tr>
   <th align="right">Mirror added:</th>
   <td><?php print_date($row['ucreated']); ?></td>
  </tr>
  <tr>
   <th align="right">Last edit time:</th>
   <td><?php print_date($row['ulastedited']); ?></td>
  </tr>
  <tr>
   <th align="right">Last mirror check time:</th>
   <td><?php print_date($row['ulastchecked']); if (!$row['up']) { echo '<br /><i>does not seem to be up!</i>'; } ?></td>
  </tr>
  <tr>
   <th align="right">Last update time:</th>
   <td><?php print_date($row['ulastupdated']); if (!$row['current']) { echo '<i><br />does not seem to be current!</i>'; } ?></td>
  </tr>
  <tr>
   <th align="right">PHP version used:</th>
   <td><?php print_version($row['phpversion']); ?></td>
  </tr>
  <tr>
   <th align="right">Local Search:</th>
   <td><?php echo $searchtypes[$row['has_search']]; ?></td>
  </tr>
  <tr>
   <th align="right">Default Language:</th>
   <td><?php echo $LANGUAGES[$row['lang']] . " [" . $row['lang'] . "]"; ?></td>
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
                   "(DATE_SUB(FROM_UNIXTIME($checktime), INTERVAL 3 DAY) < mirrors.lastchecked) AS up, " .
                   "(DATE_SUB(FROM_UNIXTIME($checktime), INTERVAL 7 DAY) < mirrors.lastupdated) AS current " .
                   "FROM mirrors LEFT JOIN country ON mirrors.cc = country.id " .
                   "ORDER BY country.name, hostname"
       ) or die("query failed");
?>
<p>
 Be sure to read <a href="http://php.net/mirroring">http://php.net/mirroring</a>
 before adding any new mirror. New mirrors should follow the rules stated there.
 Also consult <a href="mailto:mirrors@php.net">mirrors@php.net</a> if you are not
 sure that a mirror should be added / deleted.
</p>
<p>
 If you are unsure what is the TLD for a country, look at
 <a href="http://www.iana.org/cctld/cctld-whois.htm">http://www.iana.org/cctld/cctld-whois.htm</a>.
 Note, that the DNS table for mirror sites is updated directly from this list, without
 human intervention, so if you add/delete/modify a mirror, it will be reflected in the
 DNS table automatically in a short time.
</p>
<p>
 Colors used in the table below: green = special, red = not active, blue = outdated or not working.
 An automatically deactivated mirror cannot be activated manually. It will be activated after
 the next run of the automatic check (if the mirror is all right). Deactivated mirror maintainers
 get notices of the deactivation weekly.
</p>
<p>
 Last mirror check time: <?php echo gmdate("Y/m/d H:i:s", $checktime); ?> GMT. Mirror checks are done
 automatically every hour, there is no direct manual way to start a check.
</p>
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

    // See what needs to be put into the search display cell
    $srccell = '&nbsp;';
    if ($row['has_search'] == "1") { $srccell = 'new'; }
    elseif ($row['has_search'] == "2") { $srccell = 'old'; }
    if ($srccell != '&nbsp;') {
        $srccell = "<a href=\"http://$row[hostname]/search.php\">$srccell</a>";
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
   <?php echo $srccell; ?>
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

// Print out MySQL date, with a zero default
function print_date($date)
{
    if (intval($date) == 0) { echo 'n/a'; }
    else { echo gmdate("Y/m/d H:i:s", $date) . " GMT"; }
}

// Print out PHP version number
function print_version($version)
{
    if ($version == "") { echo 'n/a'; }
    else { echo $version; }
}

function is_admin($user) {
    #TODO: use acls, once implemented.
    if (in_array($user,
        array(
            "jimw","rasmus","andrei","zeev","andi","sas","thies","rubys",
            "ssb","imajes","goba","derick","cortesi")
        )
    ) {
        return true;
    }
}

?>
