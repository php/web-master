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
                $body = "The mirrors list was updated, and $hostname was " .
                        ($mode == "delete" ? "deleted." : "added.");
                
                // Also include the reason if it is provided
                if (!empty($reason)) {
                    $body .= "\n\nReason:\n" . wordwrap($reason, 70);
                }
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
   <th align="right">Administration comments:</th>
   <td><textarea wrap="virtual" cols="40" rows="12" name="acmt"><?php echo hsc($row['acmt']); ?></textarea></td>
  </tr>
  <tr>
   <td colspan="2" align="center"><input type="submit" value="<?php echo $id ? "Change" : "Add"; ?>" />
  </tr>
 </table>
 <hr />
<?php

if (intval($id) !== 0) {
    include_once "languages.inc";
?>
 <table>
  <tr>
   <th colspan="2">
    <?php
        if (!$row['up'] || !$row['current']) {
            echo '<p class="error">This mirror is automatically disabled';
            $row['ocmt'] = trim($row['ocmt']);
            if (!empty($row['ocmt'])) {
                echo '<br />Last error: ' . $row['ocmt'];
            }
            echo '</p>';
        } else { echo "&nbsp;"; }
    ?>
   </th>
  </tr>
  <tr>
   <th align="right">Mirror added:</th>
   <td><?php echo get_print_date($row['ucreated']); ?></td>
  </tr>
  <tr>
   <th align="right">Last edit time:</th>
   <td><?php echo get_print_date($row['ulastedited']); ?></td>
  </tr>
  <tr>
   <th align="right">Last mirror check time:</th>
   <td><?php echo get_print_date($row['ulastchecked']); if (!$row['up']) { echo '<br /><i>does not seem to be up!</i>'; } ?></td>
  </tr>
  <tr>
   <th align="right">Last update time:</th>
   <td><?php echo get_print_date($row['ulastupdated']); if (!$row['current']) { echo '<i><br />does not seem to be current!</i>'; } ?></td>
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
   <th align="right">Local Stats:</th>
   <td><?php echo ($row['has_search'] ? "" : "<strong>not</strong> "); ?>supported</td>
  </tr>
  <tr>
   <th align="right">Default Language:</th>
   <td><?php echo $LANGUAGES[$row['lang']] . " [" . $row['lang'] . "]"; ?></td>
  </tr>
 </table>
<?php } else { echo "&nbsp;"; } ?>
</form>
<hr />

<?php if ($row['mirrortype'] == 1) {  // only allow standard mirror deletions ?>
<form method="POST" action="<?php echo $PHP_SELF; ?>">
 <input type="hidden" name="id" value="<?php echo $row['id']; ?>" />
 <input type="hidden" name="hostname" value="<?php echo $row['hostname']; ?>" />
 Delete mirror for this reason:<br />
 <textarea name="reason" wrap="virtual" cols="40" rows="12"></textarea>
 <input type="submit" name="mode" value="delete">
</form>
<?php } ?>

<?
    // Form printed, exit script
    foot();
    exit();
}

// Query whole mirror list and display all mirrors. The query is
// similar to one in the mirror fetch script. We need to get mirror
// status data to show proper icons and need to order by country too
$res = mysql_query("SELECT mirrors.*,
                   UNIX_TIMESTAMP(lastupdated) AS ulastupdated,
                   UNIX_TIMESTAMP(lastchecked) AS ulastchecked,
                   (DATE_SUB(FROM_UNIXTIME($checktime), INTERVAL 3 DAY) < mirrors.lastchecked) AS up,
                   (DATE_SUB(FROM_UNIXTIME($checktime), INTERVAL 7 DAY) < mirrors.lastupdated) AS current,
                   country.name as countryname
                   FROM mirrors LEFT JOIN country ON mirrors.cc = country.id
                   ORDER BY country.name, hostname"
       ) or die("query failed");
?>
<div id="resources">
 <h1>Resources</h1>
 <a href="http://php.net/mirroring.php" target="_blank">Guidelines</a><br />
 <a href="mailto:mirrors@php.net">Mailing list</a><br />
 <a href="http://www.iana.org/cctld/cctld-whois.htm" target="_blank">Country TLDs</a>
 <h1>Last check time</h1>
 <?php echo gmdate("Y/m/d H:i:s", $checktime); ?> GMT
</div>

<p>
 Note, that the DNS table for mirror sites is updated directly from this list, without
 human intervention, so if you add/delete/modify a mirror, it will be reflected in the
 DNS table automatically in a short time.
</p>
<p>
 An automatically deactivated mirror cannot be activated manually. It will be activated after
 the next run of the automatic check (if the mirror is all right). Deactivated mirror maintainers
 get notices of the deactivation weekly. Manualy disabled mirrors are not checked by the
 bot, so they need some time after reactivated to get listed again. Mirror checks are done
 automatically every hour, there is no direct manual way to start a check.
</p>

<?php

// Start table
$summary = '<div align="center">
<table border="0" cellspacing="0" cellpadding="3" id="mirrors">';

// Previous country code
$prevcc = "n/a";

$stats = array(
    'mirrors' => mysql_num_rows($res)
);

// Go through all mirror sites
while ($row = mysql_fetch_array($res)) {
    
    // Collect statistical information
    $stats['phpversion'][$row['phpversion']]++;
    
    // Print separator row
    $summary .= '<tr><td colspan="7"></td></tr>' . "\n";

    // Print out a country header, if a new country is found
    if ($prevcc != $row['cc']) {
        $summary .= '<tr><th colspan="7">' . $row['countryname'] . "</th></tr>\n";
    }
    $prevcc = $row['cc'];

    // No info on why the mirror is disabled
    $errorinfo = "";

    // Active mirror site
    if ($row['active']) {
        
        // Special active mirror site (green)
        if ($row['mirrortype'] != 1) { $siteimage = "special"; }
        
        // Not special, but active
        else {
            // Not up to date or not current
            if (!$row['up'] || !$row['current']) {
                $stats['autodisabled']++;
                $siteimage = "error";
                if (!empty($row['ocmt'])) {
                    $errorinfo = $row['ocmt'] . " (last accessed: " .
                                 get_print_date($row['ulastchecked']) . ")";
                } elseif (!$row['current']) {
                    $errorinfo = "content out of date (last updated: " .
                                 get_print_date($row['ulastupdated']) . ")";
                }
            }
            // Up to date and current
            else {
                $siteimage = "ok";
            }
        }
    }
    // Not active mirror site (maybe deactivated by the
    // mirror check bot, because of a /manual alias,
    // or deactivated by some admin)
    else {
        $siteimage = "deactivated";
        if (!empty($row['ocmt']))     { $errorinfo = $row['ocmt']; }
        elseif (!empty($row['acmt'])) { $errorinfo = $row['acmt']; }
        $stats['disabled']++;
    }

    // See what needs to be printed out as search info
    $searchcell = '';
    if ($row['has_search'] == "2") { $searchcell = '('; }
    if (in_array($row['has_search'], array("1", "2"))) {
        $searchcell .= "<a href=\"http://$row[hostname]/search.php\" target=\"_blank\">" .
                       "<img src=\"/images/mirror_search.png\" /></a>";
        $stats['has_search']++;
    }
    if ($row['has_search'] == "2") { $searchcell .= ')'; }
    if (!$searchcell) { $searchcell = "&nbsp;"; }

    // Stats information cell
    $statscell = '&nbsp;';
    if ($row['has_stats'] == "1") {
        $statscell = "<a href=\"http://$row[hostname]/stats/\" target=\"_blank\">" .
                     "<img src=\"/images/mirror_stats.png\" /></a>";
        $stats['has_stats']++;
    }

    // Maintainer contact information cell
    $emailcell = '&nbsp;';
    $maintainer = trim($row['maintainer']);
    if ($row['maintainer']) {
        if (preg_match("!<(.+)>!", $maintainer, $found)) {
            $addr = $found[1];
            $name = str_replace("<$addr>", "", $maintainer);
            $emailcell = '<a href="mailto:' . $addr . '?subject=' . $row['hostname'] .
            '&amp;cc=webmaster@php.net">' . $name . ' <img src="/images/mirror_mail.png" /></a>';
        }
    }

    // Mirror status information
    $summary .= "<tr bgcolor=\"#e0e0e0\">\n" .
                "<td bgcolor=\"#ffffff\" align=\"right\">\n" .
                "<img src=\"/images/mirror_{$siteimage}.png\" /></td>\n";

    // Print out mirror site link
    $summary .= '<td><a href="http://' . $row['hostname'] . '/" target="_blank">' .
                $row['hostname'] . '</a><br /></td>' . "\n";

    // Print out mirror provider information
    $summary .= '<td><a href="' . $row['providerurl'] . '">' .
                $row['providername'] . '</a><br /></td>' . "\n";

    // Print out maintainer email cell
    $summary .= '<td align="right">' . $emailcell . '</td>' . "\n";

    // Print out mirror search table cell
    $summary .= '<td align="center">' . $searchcell . '</td>' . "\n";

    // Print out mirror stats table cell
    $summary .= '<td align="right">' . $statscell . '</td>' . "\n";

    // Print out mirror edit link
    $summary .= '<td align="right"><a href="mirrors.php?id=' . $row['id'] .
                '"><img src="/images/mirror_edit.png"></a></td>' . "\n";

    // End of row
    $summary .= '</tr>';

    // If any info on the error of this mirror is available, print it out
    if ($errorinfo) {
        $summary .= "<tr><tr bgcolor=\"#e0e0e0\"><td bgcolor=\"#ffffff\"></td>" .
                    "<td colspan=\"6\"><img src=\"/images/mirror_info.png\" /> " .
                    "$errorinfo</td></tr>";
    }
}

$summary .= '</table></div>';

// Sort in reverse PHP version order and produce string
arsort($stats['phpversion']);
$versions = "";
foreach($stats['phpversion'] as $version => $amount) {
    if (empty($version)) { $version = "n/a"; }
    $versions .= "<strong>$version</strong>: $amount, ";
}
$versions = substr($versions, 0, -2);

echo <<<EOS
<p>
 Total number of mirrors: {$stats['mirrors']} of which {$stats['disabled']} is manually
 disabled (<img src="/images/mirror_deactivated.png" />) and {$stats['autodisabled']}
 is automatically disabled (<img src="/images/mirror_error.png" />). Other sites are
 properly working (<img src="/images/mirror_ok.png" />) Of all the sites,
 {$stats['has_search']} has onsite search support (<img src="/images/mirror_search.png" />)
 and {$stats['has_stats']} has stats support (<img src="/images/mirror_stats.png" />).
 The PHP versions used on the sites are {$versions}.
</p>

$summary

<p><a href="/manage/mirrors.php?id=0">Add a new mirror</a></p>
EOS;

// Print out footer (end of script run)
foot();

// Show mirror type options defaulting to current type
function show_mirrortype_options($type = 1)
{
    // There are two mirror types
    $types = array(1 => "standard", 2 => "special"); //, 0 => "download");

    // Write out an <option> for all types
    foreach ($types as $code => $name) {
        echo "<option value=\"$code\"", 
             $type == $code ? " selected" : "",
             ">$name</option>";
    }
}

// Print out MySQL date, with a zero default
function get_print_date($date)
{
    if (intval($date) == 0) { return 'n/a'; }
    else { return gmdate("Y/m/d H:i:s", $date) . " GMT"; }
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
