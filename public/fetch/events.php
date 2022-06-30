<?php

use App\DB;

require_once __DIR__ . '/../../include/functions.inc';
require_once __DIR__ . '/../../vendor/autoload.php';

$valid_vars = ['token','cm','cy','cd','nm'];
foreach($valid_vars as $k) {
    if(isset($_GET[$k])) $$k = $_GET[$k];
}

# token required, since this should only get accessed from rsync.php.net
if (!isset($_REQUEST['token']) || md5($_REQUEST['token']) != "19a3ec370affe2d899755f005e5cd90e")
  die("token not correct.");

$pdo = DB::connect();

// Set default values
if (!isset($cm)) $cm = (int)strftime('%m');
if (!isset($cy)) $cy = (int)strftime('%Y');
if (!isset($cd)) $cd = (int)strftime('%d');
if (!isset($nm)) $nm = 3;

// Fix sql injection. args must be integer
$cm = (int) $cm;
$cy = (int) $cy;
$cd = (int) $cd;
$nm = (int) $nm;

// Collect events for $nm number of months
while ($nm) {
	for($cat=1; $cat<=3; $cat++) {
        $entries = load_month($pdo, $cy, $cm, $cat);
        $last    = strftime('%e', mktime(12, 0, 0, $cm+1, 0, $cy));
        for ($i = $cd; $i <= $last; $i++) {
            if (isset($entries[$i]) && is_array($entries[$i])) {
                foreach($entries[$i] as $row) {
                    echo "$i,$cm,$cy," . '"' . $row['country'].'","' .
                        addslashes($row['sdesc']) . '",' .
                        $row['id'] . ',"' . base64_encode($row['ldesc']) . '","' .
                        $row['url'] . '",' . $row['recur'] . ',' .
                        $row['tipo'] . ',' . $row['sdato'] . ',' .
                        $row['edato'] . ',' . $row['category'] . "\n";
                }
            }
        }
    }
    $nm--;
    $cd = 1;
    if ($nm) {
        $cm++;
        if ($cm == 13) { $cy++; $cm = 1; }
    }
}

/**
 * Find the first, second, third, last, second-last etc. weekday of a month
 *
 * args: day   1 = Monday
 *       which 1 = first
 *             2 = second
 *             3 = third
 *             4 = fourth
 *            -1 = last
 *            -2 = second-last
 *            -3 = third-last
 *
 * @param int $year
 * @param int $month
 * @param int $day
 * @param int $which
 */
function weekday($year, $month, $day, $which)
{
    $ts = mktime(12, 0, 0, $month+(($which>0)?0:1), ($which>0)?1:0, $year);
    $done = FALSE;
    $match = 0;
    $inc = 3600*24;
    while (!$done) {
        if (strftime('%w', $ts) == $day-1) {
            $match++;
        }
        if ($match == abs($which)) { $done = TRUE; }
        else { $ts += (($which>0)?1:-1)*$inc; }
    }
    return $ts;
}

// Get events for one month in one year to be listed
function load_month(DB $pdo, $year, $month, $cat)
{
    // Empty events array
    $events = [];

    // Get approved events starting or ending in the
    // specified year/month, and all recurring events
    $result = $pdo->safeQuery(
        "SELECT * FROM phpcal WHERE (
            (
                (MONTH(sdato) = ? OR MONTH(edato) = ?)
                AND
                (YEAR(sdato) = ? OR YEAR(edato) = ?)
                AND tipo < 3
            ) OR tipo = 3) AND category = ? AND approved = 1",
        [$month, $month, $year, $year, $cat]
    );

    // Go through found events
    foreach ($result as $row) {
        switch($row['tipo']) {

            // One day event
            case 1:
                list(, , $dd) = explode('-', $row['sdato']);
                $events[(int)$dd][] = $row;
            break;
            
            // Multiple-day event
            case 2:
                list(, $mm, $dd) = explode('-', $row['sdato']);
                list(, $m2, $d2) = explode('-', $row['edato']);
                if ((int)$mm == (int)$m2) {
                    for ($i = (int)$dd; $i <= (int)$d2; $i++) {
                        $events[$i][] = $row;
                    }
                } elseif ((int)$mm == $month) {
                    for ($i = (int)$dd; $i < 32; $i++) {
                        $events[$i][] = $row;
                    }
                } else {
                    for ($i = 1; $i <= (int)$d2; $i++) {
                        $events[$i][] = $row;
                    }
                }
                break;
            
            // Recurring event
            case 3:
                list($which,$dd) = explode(':', $row['recur']);
                $ts = weekday((int)$year, (int)$month, (int)$dd, (int)$which);
                $events[(int)strftime('%d', $ts)][] = $row;
                break;
        }
    }
    
    // Return events found
    return $events;
}
