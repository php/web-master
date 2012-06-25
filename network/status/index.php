<?php
/**
 * This file is a one-glance network status page
 * initially intended to show all current official
 * mirrors and their respective activation status.
 * It should grab some of its information in real-time
 * from the mirrors so that maintainers, php.net
 * admins, and others can help to diagnose issues
 * with a mirror.
 *------------------------------------------------------
 * Authors: Daniel P. Brown <danbrown@php.net>
 *
 */

/* $Id */

require_once dirname(dirname(dirname(__FILE__))).'/include/functions.inc';

head('Network Status',0);

db_connect();

$lct = db_query("SELECT UNIX_TIMESTAMP(lastchecked) FROM mirrors ORDER BY lastchecked DESC LIMIT 1");
list($checktime) = mysql_fetch_row($lct);

page_mirror_list();
foot();




// =============================================================================
// Functions
// =============================================================================

// Mirror listing page function
function page_mirror_list($moreinfo = false)
{
    global $checktime;
    
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
    $summary  = '<br/><br/>
    <div align="center" style="float:left;clear:left;position:relative;">
    <table border="0" cellspacing="0" cellpadding="3" id="mirrors">
     <tr>
      <td colspan="3" style="background-color:#ffdddd;"><center><b>Node Information</b></center></td>
      <td colspan="5" style="background-color:#ddddff;"><center><b>Health &amp; Compliance</b></center></td>
     </tr>
     <tr>
      <td style="background-color:#ffdddd;">&nbsp;</td>
      <td style="background-color:#ffdddd;"><center><b>Node Name</b></center></td>
      <td style="background-color:#ffdddd;"><center><b>Sponsor</b></center></td>
      <td style="background-color:#ddddff;"><center><b>Synchrony</b></center></td>
      <td style="background-color:#ddddff;"><center><b>SQLite</b></center></td>
      <td style="background-color:#ddddff;"><center><b>PHP</b></center></td>
      <td colspan="2" style="background-color:#ddddff;">&nbsp;</td>
     </tr>';

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
        $summary .= '<tr><td colspan="8"></td></tr>' . "\n";

        // Print out a country header, if a new country is found
        if ($prevcc != $row['cc']) {
            $summary .= '<tr><th colspan="8" style="background-color:#dddddd;color:#444444;">' . $row['countryname'] . "</th></tr>\n";
        }
        $prevcc = $row['cc'];

        // Active mirror site
        if ($row['active']) {
        
            // Special active mirror site (green)
            if ($row['mirrortype'] != 1) { $siteimage = "special"; }
        
            // Not special, but active
            else {
                // Not up to date or not current
                if (!$row['up'] || !$row['current']) {
		    $siteimage = 'pulsing_red';
                }
                // Up to date and current
                else {
                    $siteimage = 'green';
                }
            }
        }
        // Not active mirror site (maybe deactivated by the
        // mirror check bot, because of a /manual alias,
        // or deactivated by some admin)
        else {
            $siteimage = "pulsing_red";
        }

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

	// Mirrors updated within the last two hours are good.
	// More than two hours but less than one day are in warning state.
	// More than one day is an error.
	if (strtotime($row['lastupdated']) >= strtotime('2 hours ago')) {
		$synchrony_icon = '<img src="images/ok.gif" title="Last updated'.date('d-M-Y H:i',strtotime($row['lastupdated'])).' GMT"/>';
	} elseif (strtotime($row['lastupdated']) >= strtotime('24 hours ago')) {
		$synchrony_icon = '<img src="images/warn.gif" title="Last updated '.date('d-M-Y H:i',strtotime($row['lastupdated'])).' GMT"/>';
	} else {
		$synchrony_icon = '<img src="images/fail.gif" title="Last updated '.date('d-M-Y H:i',strtotime($row['lastupdated'])).' GMT"/>';
	}

	// If PHP is not at least 5.3.3 or 5.4.0+, warn or error, depending on version.
	// PHP 5.3.10+ or 5.4.x == Okay
	// PHP >= 5.3.3 && <= 5.3.9 == Warn
	// All others == Fail
	$_phpv = explode('.',$row['phpversion']);
	$php_version = (int)$_phpv[0].$_phpv[1].str_pad($_phpv[2],2,'0',STR_PAD_LEFT);
	if ($php_version >= 5310) {
		$php_icon = '<img src="images/ok.gif" title="PHP '.$row['phpversion'].'"/>';
	} elseif ($php_version >= 5303) {
		$php_icon = '<img src="images/warn.gif" title="PHP '.$row['phpversion'].'"/>';
	} else {
		$php_icon = '<img src="images/fail.gif" title="PHP '.$row['phpversion'].'"/>';
	}
		
	// If the mirror doesn't have pdo_sqlite available, it's an error.
	// There is no warning status here.
	if (in_array('pdo_sqlite',$sqlites)) {
		$sqlite_icon = '<img src="images/ok.gif" title="SQLites: '.$searchcell.'"/>';
	} else {
		$sqlite_icon = '<img src="images/fail.gif" title="SQLites: '.$searchcell.'"/>';
	}

        // Mirror status information
        $summary .= "<tr class=\"mirrorstatus\">\n" .
                    "<td bgcolor=\"#ffffff\" align=\"right\">\n" .
                    "<img src=\"images/{$siteimage}.gif\" /></td>\n";

        // Print out mirror site link
        $summary .= '<td style="background-color:#ffdddd;"><a href="http://'.$row['hostname'].'/" target="_blank">' .
                    $row['hostname'] . '</a><br /></td>' . "\n";

        // Print out mirror provider information
        $summary .= '<td style="background-color:#ffdddd;"><a href="'.$row['providerurl'].'">' .
                    $row['providername'] . '</a><br /></td>' . "\n";

        // Print out the sync status of the mirror
        $summary .= '<td align="center" style="background-color:#ddddff;width:80px;">'.$synchrony_icon.'</td>' . "\n";

        // Print out mirror search table cell
        $summary .= '<td align="center" style="background-color:#ddddff;width:80px;">'.$sqlite_icon. '</td>' . "\n";

        // Print out version information for this mirror
        $summary .= '<td align="center" style="background-color:#ddddff;width:80px;">'.$php_icon.'</td>' . "\n";

        // Print out mirror stats table cell
        $summary .= '<td align="right" style="background-color:#ddddff;">&nbsp;</td>' . "\n";

        // Print out mirror edit link
        $summary .= '<td align="right" style="background-color:#ddddff;">&nbsp;</td>' . "\n";

        // End of row
        $summary .= '</tr>';

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

    // Create version specific statistics
    $stats['version5_percent']   = sprintf('%.1f%%', $stats['phpversion_counts'][5] / $stats['mirrors'] * 100);
    $stats['has_stats_percent']  = sprintf('%.1f%%', $stats['has_stats']            / $stats['mirrors'] * 100);

    $last_check_time = get_print_date($checktime);
    $current_time    = get_print_date(time());
   
    if(empty($stats['disabled'])) $stats['disabled'] = 0;
    $stats['ok']   = $stats['mirrors'] - $stats['autodisabled'] - $stats['disabled'];
    $moreinfo_flag = empty($moreinfo) ? 1 : 0;
    
    $has_sqlite_counts = '';
    foreach ($stats['sqlite_counts'] as $stype => $scount) {
        $has_sqlite_counts .= '<tr><td><img src="/images/mirror_search.png" /></td><td>'.$stype.'</td>';
        $has_sqlite_counts .= '<td>'. $scount .' <small>('.round(($scount / $stats['mirrors']) * 100).'%)</small></td></tr>';
    }

echo <<<EOS
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/curvycorners.js"></script>
<link type="text/css" rel="stylesheet" href="js/jquery.qtip.min.css"/>
<script type="text/javascript" src="js/jquery.qtip.min.js"></script>
<script>
$('.selector').qtip({
	content: {
		attr: 'alt'
	}
})
</script>

<div style="left:5%;position:relative;width:75%;">

<h1>PHP Global Network Infrastructure Health</h1>
<b>Last check time:</b> {$last_check_time}<br/>
<b>Current time:</b> {$current_time}<br/>
<br/>

<b>In each node's compliance entries, you can place your
mouse over the icon to get additional details.</b><br/>
<br/>

<h2>Key:</h2>
<img src="images/green.gif"/> Node Active
<img src="images/pulsing_red.gif"/> Node Inactive
<img src="images/ok.gif"/> Node Is Fully Compliant
<img src="images/warn.gif"/> Node Is Partially Compliant
<img src="images/fail.gif"/> Node Is Non-Compliant<br/>

<br/>

<div id="phpversions_off" style="display:block;width:100%;">
 <!--<a href="#" onclick="javascript:pop('phpversions');">PHP Version Summary</a>-->
 <a href="#" onclick="$('#phpversions').toggle('slow');">PHP Version Summary</a>
 <div id="phpversions" style="display:none;text-align:center;width:100%;">
  <div style="float:left;margin-left:35%;">
  {$versions}
  </div>
 <div style="clear:left;height:1px;"></div>
 </div>
</div>

$summary

</div>
EOS;

}

// Print out MySQL date, with a zero default
function get_print_date($date) {
    if (intval($date) == 0) { return 'n/a'; }
    else { return gmdate("D, d M Y H:i:s", $date) . " GMT"; }
}
