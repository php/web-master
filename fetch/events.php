<?php

# token required, since this should only get accessed from rsync.php.net
#if (!isset($token) || md5($token) != "19a3ec370affe2d899755f005e5cd90e")
#  die("token not correct.");

@mysql_connect('localhost','nobody','') or exit;
@mysql_select_db('php3') or exit;

$re = array(1=>'First',2=>'Second',3=>'Third',4=>'Fourth',-1=>'Last',-2=>'2nd Last',-3=>'3rd Last');


if(!isset($a)) {
  if(isset($HTTP_GET_VARS['a'])) $a = $HTTP_GET_VARS['a'];
  else $a=0;
}

if (!isset($cm)) $cm = (int)strftime('%m');
if (!isset($cy)) $cy = (int)strftime('%Y');
if (!isset($cd)) $cd = (int)strftime('%d');
if (!isset($nm)) $nm = 3;

while ($nm) {
  $entries = load_month($cy,$cm);
  $last = last_day($cy,$cm);
  for ($i=$cd; $i<=$last; $i++) {
    if (is_array($entries[$i])) foreach($entries[$i] as $row) {
      echo "$i,$cm,$cy,".'"'.$row['cname'].'","'.addslashes($row['sdesc']).'",'.$row['id'].',"'.base64_encode($row['ldesc']).'","'.$row['url'].'",'.$row['recur'].','.$row['tipo'].','.$row['sdato'].','.$row['edato']."\n";
    }
  }  
  $nm--;
  $cd = 1;
  if($nm) {
    $cm++;
    if($cm==13) { $cy++; $cm=1; }
  }
}

/*
 * Find the first, second, third, last, second-last etc. weekday of a month
 *
 * args: day 1 = Monday
 *       which 1 = first
 *             2 = second
 *             3 = third
 *            -1 = last
 *            -2 = second-last
 */
function weekday($year, $month, $day, $which) {
  $ts = mktime(12,0,0,$month+(($which>0)?0:1),($which>0)?1:0,$year);  
  $done = false;
  $match = 0;
  $inc = 3600*24;
  while(!$done) {
    if(strftime('%w',$ts)==$day-1) {
      $match++;
    }
    if($match==abs($which)) $done=true;
    else $ts += (($which>0)?1:-1)*$inc;
  }
  return $ts;
}

function load_month($year, $month) {
  $result = mysql_query("SELECT phpcal.*,country.name AS cname FROM phpcal LEFT JOIN country ON phpcal.country = country.id WHERE (((MONTH(sdato)=$month OR MONTH(edato)=$month) AND (YEAR(sdato)=$year OR YEAR(edato)=$year) AND tipo < 3) OR tipo=3) AND approved=1");
  if(!$result) echo mysql_error();
  else {
    while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
      switch($row['tipo']) {
        case 1:
          list(,,$dd) = explode('-',$row['sdato']);
          $events[(int)$dd][] = $row;
          break;
        case 2:
          list(,$mm,$dd) = explode('-',$row['sdato']);
          list(,$m2,$d2) = explode('-',$row['edato']);
          if((int)$mm==(int)$m2) {
            for($i=(int)$dd; $i<=(int)$d2; $i++) {
              $events[$i][] = $row;
            }
          } elseif((int)$mm==$month) {
            for($i=(int)$dd; $i<32; $i++) {
              $events[$i][] = $row;
            }
          } else {
            for($i=1; $i<=(int)$d2; $i++) {
              $events[$i][] = $row;
            }  
          }
          break;
        case 3:
          list($which,$dd) = explode(':',$row['recur']);
          $ts = weekday($year,$month,$dd,$which);
          $events[(int)strftime('%d',$ts)][] = $row;
          break;
      }
    }      
  }
  return($events);
}

function start_month($year, $month) {
  $ts = mktime(12,0,0,$month,1,$year);
  return strftime('%w',$ts);  
}

function last_day($year,$month) {
  $ts = mktime(12,0,0,$month+1,0,$year);
  return strftime('%e',$ts);
}

function months() {
  static $months=NULL;

  if(!$months) for($i=1;$i<=12;$i++) {
    $months[$i] = strftime('%B',mktime(12,0,0,$i,1));
  }
  return $months;
}

/* returns array of Days starting with 1 = Sunday */
function days() {
  static $days=NULL;
  if(!$days) for($i=1;$i<=7;$i++) {
    $days[$i] = strftime('%A',mktime(12,0,0,4,$i,2001));
  }
  return $days;
}
