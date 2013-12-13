<?php // vim: et
// Force login and include common functions
include '../include/login.inc';

define('PHP_SELF', hsc($_SERVER['PHP_SELF']));

// This page is for mirror administration
head("mirror administration");
db_connect();

$valid_fields = array(
	'hostname',
	'mode',
	'active',
	'mirrortype',
	'cname',
	'maintainer',
	'providername',
	'providerurl',
	'cc',
	'lang',
	'has_stats',
	'acmt_prev',
	'acmt',
	'reason',
	'original_log',
	'load_balanced',
);

foreach($valid_fields as $k) {
    if (isset($_REQUEST[$k])) $$k = $_REQUEST[$k];
}


// Get boolean values from form
$active     = isset($active)     ? 1 : 0;
$has_stats  = isset($has_stats)  ? 1 : 0;
$moreinfo   = empty($_GET['mi']) ? 0 : 1;

// Select last mirror check time from table
$lct = db_query("SELECT UNIX_TIMESTAMP(lastchecked) FROM mirrors ORDER BY lastchecked DESC LIMIT 1");
list($checktime) = mysql_fetch_row($lct);

if (isset($_REQUEST['id'])) $id = (int)$_REQUEST['id'];


// We have something to update in the database
if (isset($id) && isset($hostname)) {

    // Allow everyone to disable a mirror, but only elite few to make other changes
    if (is_mirror_site_admin($_SESSION["user"]) || ($mode == "update" && !$active)) {
        // No query need to be made
        $query = FALSE;
        
        // What to update?
        switch($mode) {

            // Perform a full data update on a mirror
            case "update":
		$mod_by_time = '<b>'.strtoupper(date('d-M-Y H:i:s T')).'</b> ['.$_SESSION["user"].'] Mirror updated';
                $query = "UPDATE mirrors SET hostname='$hostname', active=$active, " .
                         "mirrortype=$mirrortype, cname='$cname', maintainer='".unmangle($maintainer)."', " .
                         "providername='".unmangle($providername)."', providerurl='$providerurl', " .
                         "cc='$cc', lang='$lang', has_stats=$has_stats, load_balanced='$load_balanced', " .
                         "lastedited=NOW(), acmt='".unmangle($acmt_prev)."==\n" .
                         $mod_by_time.(isset($acmt) && !empty($acmt) ? ": ".unmangle($acmt) : ".")."' WHERE id = $id";
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
                         "lang, has_stats, created, lastedited, acmt, load_balanced) " .
                         "VALUES ('$hostname', $active, $mirrortype, '$cname', " .
                         "'".unmangle($maintainer)."', '".unmangle($providername)."', '$providerurl', '$cc', " .
                         "'$lang', $has_stats, NOW(), NOW(), '".unmangle($acmt)."', '$load_balanced')";
                $msg = "$hostname added";
            break;
        }
        
        // If there is any query to execute
        if ($query) {
        
            // Try to execute query, and provide information if successfull
            if (db_query($query)) {
                echo '<h2>'.$msg.'</h2>';
            }
            
            // In case a mirror is deleted, mail a notice to the
            // php-mirrors list, so any malicious deletions can be tracked
            if ($mode == "delete" || $mode == "insert") {
                $body = "The mirrors list was updated, and $hostname was " .
                        ($mode == "delete" ? "deleted." : "added.");
                
                // Also include the reason if it is provided
                if (!empty($reason)) {
                    $body .= "\n\nReason:\n".wordwrap(unmangle($reason),70);
		    $body .= PHP_EOL.'=='.PHP_EOL.'Original log follows.'.PHP_EOL.'===='.PHP_EOL;
		    $body .= wordwrap(unmangle($original_log),70);
                }
                @mail(
                    "network-status@lists.php.net",
                    "[mirrors] Update by " . $_SESSION["username"],
                    $body,
                    "From: mirrors@php.net",
                    "-fmirrors@php.net"
                );

            // If a mirror has been modified, send information safe for public eyes to the
            // list: active status, hostname.
            } elseif ($mode == 'update') {
                $body  = 'The mirror '.$hostname.' has been modified by '.$_SERVER["username"].'.  It\'s status is ';
                $body .= isset($active) && $active == true ? 'active.' : 'inactive, and DNS will be disabled.';
		$body .= isset($acmt) && !empty($acmt) ? '  Notes were added to the mirror\'s file.' : '';
		@mail('network-status@lists.php.net','[mirrors] Status change for '.$hostname,$body,"From: mirrors@php.net\r\n", "-fmirrors@php.net");
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
      $res = db_query(
          "SELECT *, " .
          "UNIX_TIMESTAMP(created) AS ucreated, " .
          "UNIX_TIMESTAMP(lastedited) AS ulastedited, " .
          "UNIX_TIMESTAMP(lastupdated) AS ulastupdated, " .
          "UNIX_TIMESTAMP(lastchecked) AS ulastchecked, " .
          "(DATE_SUB(FROM_UNIXTIME($checktime), INTERVAL 3 DAY) < lastchecked) AS up, " .
          "(DATE_SUB(FROM_UNIXTIME($checktime), INTERVAL 7 DAY) < lastupdated) AS current " .
          "FROM mirrors WHERE id = $id"
      );
      $row = mysql_fetch_assoc($res);
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
<form method="POST" action="<?php echo PHP_SELF; ?>">
 <input type="hidden" name="id" value="<?php echo isset($row['id']) ? $row['id'] : ''; ?>" />
 <input type="hidden" name="mode" value="<?php echo empty($id) ? 'insert' : 'update'; ?>" />

 <table>
  <tr>
   <td style="vertical-align:top;">
    <table>
     <tr>
      <th align="right">Hostname (without http://):</th>
      <td><input type="text" name="hostname" value="<?php echo empty($row['hostname']) ? '':hscr($row['hostname']); ?>" size="40" maxlength="40" /></td>
     </tr>
     <tr>
      <th align="right">Active?</th>
      <td><input type="checkbox" name="active"<?php echo empty($row['active']) ? '' : " checked"; ?> /></td>
     </tr>
     <tr>
     <?php if (!empty($row['hostname'])) { ?>
      <th align="right">Round-Robin?</th>
      <td>
       <input type="checkbox" name="load_balanced" value="<?php echo substr($row['hostname'],0,2); ?>" <?php echo preg_match('/\w+/',$row['load_balanced']) ? ' checked="checked"' : ''; ?>/>
      </td>
     <?php } else { ?>
      <th align="right">Round-Robin Country Code</th>
      <td>
       <input type="text" name="load_balanced" size="2" maxlength="4"/><br/>
       <small>Should be the first two letters from the hostname entered above.</small>
      </td>
     <?php } ?>
     </tr>
     <tr>
      <th align="right">Type:</th>
      <td><select name="mirrortype"><?php show_mirrortype_options($row['mirrortype']); ?></select></td>
     </tr>
     <tr>
      <th align="right">CNAME (without http://):</th>
      <td><input type="text" name="cname" value="<?php echo empty($row['cname']) ? '' : hscr($row['cname']); ?>" size="40" maxlength="80" /></td>
     </tr>
     <tr>
      <th align="right">Maintainer's Name and Email:</th>
      <td><input type="text" name="maintainer" value="<?php echo empty($row['maintainer']) ? '' : hscr($row['maintainer']); ?>" size="40" maxlength="255" /></td>
     </tr>
     <tr>
      <th align="right">Provider's Name:</th>
      <td><input type="text" name="providername" value="<?php echo empty($row['providername']) ? '' : hscr($row['providername']); ?>" size="40" maxlength="255" /></td>
     </tr>
     <tr>
      <th align="right">Provider URL (with http://):</th>
      <td><input type="text" name="providerurl" value="<?php echo empty($row['providerurl']) ? '' : hscr($row['providerurl']); ?>" size="40" maxlength="255" /></td>
     </tr>
     <tr>
      <th align="right">Country:</th>
      <td><select name="cc"><?php show_country_options($row['cc']); ?></select></td>
     </tr>
     <tr>
      <th align="right">
       Administrative Comments:<br/>
       <small>NOTE: <i>Username and timestamp will be automatically recorded.</i></small><br/>
       <i>To italicize, enclose text in ""double-double quotes"".</i><small>
      </th>
      <td><textarea wrap="virtual" cols="40" rows="12" name="acmt"></textarea></td>
     </tr>
     <tr>
      <td colspan="2" align="center"><input type="submit" value="<?php echo empty($id) ? "Add" : "Change"; ?>" />
     </tr>
    </table>
   </td>
   <td style="vertical-align:top;overflow:auto;">
    <input type="hidden" name="acmt_prev" value="<?php echo empty($row['acmt']) ? '' : hscr($row['acmt']); ?>"/>
    <b>Administration Comment History:</b><br/>
    <?php
      if (($_acmt = preg_split('/==\r?\n/',$row['acmt'])) != 0) {
        foreach ($_acmt as $_c) {
		$_c = preg_replace('/""(.*)""/Us','<i>$1</i>',$_c);
		echo '<small>'.$_c.'</small><br/>'.PHP_EOL.'<hr/><br/>'.PHP_EOL;
        }
      } else {
        echo 'N/A';
      }
    ?>
   </td>
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
   <th align="right">SQLite available:</th>
   <td><?php echo implode(' : ', decipher_available_sqlites($row['has_search'])); ?></td>
  </tr>
  <tr>
   <th align="right">Available extensions:</th>
   <td><div style="height:48px;width:400px;overflow:auto;"><?php echo str_replace(',',' ',get_extension_info($row['hostname'])); ?></div></td>
  </tr>
  <tr>
   <th align="right">Local Stats:</th>
   <td><?php echo ($row['has_stats'] ? "" : "<strong>not</strong> "); ?>supported</td>
  </tr>
  <tr>
   <th align="right">Default Language:</th>
   <td><?php echo $LANGUAGES[$row['lang']] . " [" . $row['lang'] . "]"; ?></td>
  </tr>
 </table>
<?php } else { echo "&nbsp;"; } ?>
</form>
<hr />

<?php if ($row['mirrortype'] == 1 && $id !== 0) {  // only allow standard mirror deletions ?>
<form method="POST" action="<?php echo PHP_SELF; ?>">
 <input type="hidden" name="id" value="<?php echo $row['id']; ?>" />
 <input type="hidden" name="hostname" value="<?php echo $row['hostname']; ?>" />
 Delete mirror for this reason:<br />
 <small>Administrative comments will automatically append.</small><br/>
 <textarea name="reason" wrap="virtual" cols="40" rows="12"></textarea>
 <input type="hidden" name="original_log" value="<?php echo empty($row['acmt']) ? '' : hscr($row['acmt']); ?>"/>
 <input type="submit" name="mode" value="delete"/>
</form>
<?php }
    
    // Form printed, exit script
    foot();
    exit();
}

page_mirror_list($moreinfo);

foot();

// =============================================================================

// Mirror listing page function
function page_mirror_list($moreinfo = false)
{
    global $checktime;

    // For counting versions and building a statistical analysis
    $php_versions = array(
        '53' => 0,
        '54' => 0,
        '55' => 0,
        'other' => 0,
    );    
    // Query the whole mirror list and display all mirrors. The query is
    // similar to the one in the mirror fetch script. We need to get mirror
    // status data to show proper icons and need to order by country too
    $res = db_query("
        SELECT mirrors.*,
        UNIX_TIMESTAMP(lastupdated) AS ulastupdated,
        UNIX_TIMESTAMP(lastchecked) AS ulastchecked,
        (DATE_SUB(FROM_UNIXTIME($checktime), INTERVAL 3 DAY) < mirrors.lastchecked) AS up,
        (DATE_SUB(FROM_UNIXTIME($checktime), INTERVAL 7 DAY) < mirrors.lastupdated) AS current,
        country.name as countryname
        FROM mirrors LEFT JOIN country ON mirrors.cc = country.id
        ORDER BY country.name, hostname"
    );

    // Start table
    $summary = '<div>
    <table border="0" cellspacing="0" cellpadding="3" id="mirrors">';

    // Previous country code
    $prevcc = "n/a";

    $stats = array(
        'mirrors'       => mysql_num_rows($res),
        'sqlite_counts' => array('none' => 0, 'sqlite' => 0, 'pdo_sqlite' => 0, 'pdo_sqlite2' => 0, 'sqlite3' => 0),
    );

    // Go through all mirror sites
    while ($row = mysql_fetch_array($res)) {
    
        // Collect statistical information
        @$stats['phpversion'][$row['phpversion']]++;
        @$stats['phpversion_counts'][$row['phpversion'][0]]++;

        // Print separator row
        $summary .= '<tr><td colspan="9"></td></tr>' . "\n";

        // Print out a country header, if a new country is found
        if ($prevcc != $row['cc']) {
            $summary .= '<tr><th colspan="9" class="rounded">' . $row['countryname'] . "</th></tr>\n";
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
                    if(empty($stats['autodisabled'])) $stats['autodisabled'] = 1;
                    else $stats['autodisabled']++;
                    $siteimage = "error";
                    if (!empty($row['ocmt'])) {
                        $errorinfo = $row['ocmt'] . " (problem since: " .
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
        /* This info isn't used anymore and "has_search" is now misused under 
         * SQLite availability check
        $searchcell = '';
        if ($row['has_search'] == "2") { $searchcell = '('; }
        if (in_array($row['has_search'], array("1", "2"))) {
            $searchcell .= "<a href=\"http://$row[hostname]/search.php\" target=\"_blank\">" .
                           "<img src=\"/images/mirror_search.png\" /></a>";
            $stats['has_search']++;
        }
        if ($row['has_search'] == "2") { $searchcell .= ')'; }
        if (!$searchcell) { $searchcell = "&nbsp;"; }
        */

        $sqlites = decipher_available_sqlites($row['has_search']);
        if ($sqlites) {
            $searchcell = implode(", ", $sqlites);
            foreach ($sqlites as $sqlite_type) {
                $stats['sqlite_counts'][$sqlite_type]++;
            }
        } else {
            $stats['sqlite_counts']['none']++;
            $searchcell = "&nbsp;";
        }

        // Stats information cell
        $statscell = '&nbsp;';
        if ($row['has_stats'] == "1") {
            $statscell = "<a href=\"http://$row[hostname]/stats/\" target=\"_blank\">" .
                         "<img src=\"/images/mirror_stats.png\" /></a>";
            if(empty($stats['has_stats'])) $stats['has_stats'] = 1;
            else $stats['has_stats']++;
        }

        // Maintainer contact information cell
        $emailcell = '&nbsp;';
        $maintainer = trim($row['maintainer']);
        if ($row['maintainer']) {
            if (preg_match("!<(.+)>!", $maintainer, $found)) {
                $addr = $found[1];
                $name = str_replace("<$addr>", "", $maintainer);
                $emailcell = '<a href="mailto:' . $addr . '?subject=' . $row['hostname'] .
                '&amp;cc=php-mirrors@lists.php.net">' . $name . ' <img src="/images/mirror_mail.png" /></a>';
            }
        }

        // Mirror status information
        $summary .= "<tr class=\"mirrorstatus\">\n" .
                    "<td bgcolor=\"#ffffff\" align=\"right\" class=\"rounded\">\n" .
                    "<img src=\"/images/mirror_{$siteimage}.png\" /></td>\n";

        // Print out mirror site link
        $summary .= '<td class="rounded"><a href="http://' . $row['hostname'] . '/" target="_blank">' .
                    $row['hostname'] . '</a>'.PHP_EOL .
		    ' <a href="http://'.$row['hostname'].'/mirror-info" target="_blank"><img src="/images/mirror_info.png"/></a><br /></td>' . "\n";

        // Print out mirror provider information
        $summary .= '<td class="rounded"><a href="' . $row['providerurl'] . '">' .
                    $row['providername'] . '</a><br /></td>' . "\n";

        // Print out maintainer email cell
        $summary .= '<td align="right" class="rounded">' . $emailcell . '</td>' . "\n";

        // Print out mirror search table cell
        $summary .= '<td align="center" class="rounded">' . $searchcell . '</td>' . "\n";

        // Print out version information for this mirror
        $summary .= '<td align="center" class="rounded">' . $row['phpversion']. '</td>' . "\n";

	// Increment the appropriate version for our statistical overview
	if (preg_match('/^5\.3/',$row['phpversion'])) {
                $php_versions['53']++;
        } elseif (preg_match('/^5.4/',$row['phpversion'])) {
                $php_versions['54']++;
	} elseif (preg_match('/^5.5/',$row['phpversion'])) {
		$php_versions['55']++;
        } else {
                $php_versions['other']++;
        }

	$summary .= '<td align="right" class="rounded">';
	$summary .= preg_match('/\w{2}/',$row['load_balanced']) ? '<img src="/images/Robin.ico" height="16" width="16"/>' : '';
	$summary .= '</td>'.PHP_EOL;

        // Print out mirror stats table cell
        $summary .= '<td align="right" class="rounded">' . $statscell . '</td>' . "\n";

        // Print out mirror edit link
        $summary .= '<td align="right" class="rounded"><a href="mirrors.php?id=' . $row['id'] .
                    '"><img src="/images/mirror_edit.png"></a></td>' . "\n";

        // End of row
        $summary .= '</tr>';

        // If any info on the error of this mirror is available, print it out
        if ($errorinfo) {
            $summary .= "<tr class=\"mirrorerror\"><td bgcolor=\"#ffffff\"></td>" .
                        "<td colspan=\"7\" class=\"rounded\"><img src=\"/images/mirror_notice.png\" /> <small>";
                       if (($errorblock = preg_split('/==\r?\n/',$errorinfo)) != 0) {
                               $summary .= nl2br($errorblock[(count($errorblock)-1)]);
                       } else {
                               $summary .= nl2br($errorinfo);
                       }
           $summary .= '</small></td></tr>';
        }
        // If additional details are desired
        if ($moreinfo) {
            $summary .= '<tr class=\"mirrordetails\"><td bgcolor="#ffffff">&nbsp;</td>' .
                        '<td colspan="7">' . 
                            ' Last update: ' . date(DATE_RSS, $row['ulastupdated']) . 
                            ' SQLites: '     . implode(' : ', decipher_available_sqlites($row['has_search'])) .
                        '</td></tr>';
        }
    }

    $summary .= '</table></div>';

    // Sort by versions in use, descendingly, and produce the HTML string.
    uksort($stats['phpversion'],'strnatcmp');
    $stats['phpversion'] = array_reverse($stats['phpversion']);
    $versions = "";
    $vcount = count($stats['phpversion']);
    $vnow = 0;
    foreach($stats['phpversion'] as $version => $amount) {
        if (empty($version)) { $version = "n/a"; }
        $versions .= '<span style="font-weight:bold;">'.$version.'</span> ('.$amount.')<br/>'.PHP_EOL;
        if (round(($vcount / 2)) == ++$vnow) {
            $versions .= '</div>'.PHP_EOL.'<div style="float:right;margin-right:35%;">';//width:120px;">';
        }
    }
    //$versions = substr($versions, 0, -2);

    // Create version specific statistics
    $stats['version5_percent']   = sprintf('%.1f%%', $stats['phpversion_counts'][5] / $stats['mirrors'] * 100);
    $php53_percent = sprintf('%.1f%%',($php_versions['53'] / $stats['mirrors']) * 100);
    $php54_percent = sprintf('%.1f%%',($php_versions['54'] / $stats['mirrors']) * 100);
    $php55_percent = sprintf('%.1f%%',($php_versions['55'] / $stats['mirrors']) * 100);
    $php_other_versions = sprintf('%.1f%%',($php_versions['other'] / $stats['mirrors']) * 100);
    
    $stats['has_stats_percent']  = sprintf('%.1f%%', $stats['has_stats']            / $stats['mirrors'] * 100);

    $last_check_time = get_print_date($checktime);
    $current_time    = get_print_date(time());
   
    if(empty($stats['disabled'])) $stats['disabled'] = 0;
    $stats['ok']   = $stats['mirrors'] - $stats['autodisabled'] - $stats['disabled'];
    if (empty($moreinfo)) {
	$moreinfo_flag = 1;
	$moreinfo_text = 'See more info';
    } else {
	$moreinfo_flag = 0;
	$moreinfo_text = 'See less info';
    }
    
    $has_sqlite_counts = '';
    foreach ($stats['sqlite_counts'] as $stype => $scount) {
        $has_sqlite_counts .= '<tr><td><img src="/images/mirror_search.png" /></td><td>'.$stype.'</td>';
       $has_sqlite_counts .= '<td>'. $scount .' <small>('.round(($scount / $stats['mirrors']) * 100).'%)</small></td></tr>';
    }

echo <<<EOS

<div id="resources" class="semirounded">
 <table class="semirounded">
  <tr>
   <td><img src="/images/mirror_ok.png" /></td>
   <td>Fine:</td>
   <td>{$stats['ok']}</td>
  </tr>
  <tr>
   <td><img src="/images/mirror_deactivated.png" /></td>
   <td>Manually-Disabled:</td>
   <td>{$stats['disabled']}</td>
  </tr>
  <tr>
   <td><img src="/images/mirror_error.png" /></td>
   <td>Auto-Disabled:</td>
   <td>{$stats['autodisabled']}</td>
  </tr>
  <tr>
   <td>&nbsp;</td>
   <td><strong>Total:</strong></td>
   <td><strong>{$stats['mirrors']}</strong></td>
  </tr>
  <tr>
   <td colspan="3"><hr /></td>
  </tr>
  <tr>
   <td><img src="/images/mirror_stats.png" /></td>
   <td>Stats:</td>
   <td>{$stats['has_stats_percent']}</td>
  </tr>
  <tr>
   <td><img src="/images/mirror_info.png" /></td>
   <td>
    PHP 5.3:<br/>
    PHP 5.4:<br/>
    PHP 5.5:<br/>
    Other:
   </td>
   <td>
    {$php53_percent}<br/>
    {$php54_percent}<br/>
    {$php55_percent}<br/>
    {$php_other_versions}
   </td>
  </tr>
  <tr>
   <td colspan="3"><hr /></td>
  </tr>
  
  {$has_sqlite_counts}

 </table>
 <h1>Resources</h1>
 <a href="/manage/mirrors.php?mi={$moreinfo_flag}">{$moreinfo_text}</a><br />
 <a href="http://php.net/mirroring.php" target="_blank">Guidelines</a><br />
 <a href="mailto:php-mirrors@lists.php.net">Announcement/Discussion List</a><br />
 <a href="mailto:network-status@lists.php.net">Network Status List</a><br/>
 <a href="https://status.php.net/">Network Health Page</a><br/>
 <a href="http://www.iana.org/domains/root/db/" target="_blank">Country TLDs</a>
 <h1>Last check time</h1>
 {$last_check_time}
 <h1>Current time</h1>
 {$current_time} 
</div>

<div style="left:5%;position:relative;width:75%;">

<p>
 Note that the DNS table for mirror sites is updated directly from this list, without
 human intervention, so if you add/delete/modify a mirror, it will be reflected in the
 DNS table automatically in a short time.
</p>

<p>
 An automatically-deactivated mirror cannot be activated manually. It will be activated after
 the next run of the automatic check (if the mirror is alright). Deactivated mirror maintainers
 get notices of the deactivation weekly. Manually-deactivated mirrors are not checked by the
 bot, so they need some time after reactivation to get listed again. Mirror checks are done
 automatically every hour, and there is no direct manual way to start a check (at this time).
</p>

<p>
 <strong>NOTE</strong>: Manual deactivation of a mirror will now also disable its DNS.
</p>

<div id="phpversions_off" style="display:block;text-align:center;width:100%;">
 <!--<a href="#" onclick="javascript:pop('phpversions');">PHP Version Summary</a>-->
 <a href="#" onclick="$('#phpversions').toggle('slow');">PHP Version Summary</a>
 <div id="phpversions" style="display:none;text-align:center;width:100%;">
  <div style="float:left;margin-left:35%;">
  {$versions}
  </div>
 <div style="clear:left;height:1px;"></div>
 </div>
</div>

<p align="center"><a href="/manage/mirrors.php?id=0">Add a new mirror</a></p>

$summary

</div>
EOS;

}

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
    else { return gmdate("D, d M Y H:i:s", $date) . " GMT"; }
}

// Print out PHP version number
function print_version($version)
{
    if ($version == "") { echo 'n/a'; }
    else { echo $version; }
}

// Check if a user should modify the mirror data 
// TODO: use acls, once implemented
function is_mirror_site_admin($user) {
    if (in_array(
            $user,
            array(
                "jimw", "rasmus", "andrei", "zeev", "andi", "sas", "thies",
                "rubys", "ssb", "imajes", "goba", "derick", "cortesi", "wez", 
                "bjori", "philip", "danbrown", "tyrael",
            )
        )
    ) {
        return TRUE;
    } else { return FALSE; }
}
