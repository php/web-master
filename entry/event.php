<?php

$mailto = 'php-webmaster@lists.php.net';
#$mailto = 'jimw@apache.org';

$re = [
        1 => 'First',
        2 => 'Second',
        3 => 'Third',
        4 => 'Fourth',
       -1 => 'Last',
       -2 => 'Second to Last',
       -3 => 'Third to Last'
];
$cat = ["unknown", "User Group Event", "Conference", "Training"];

function day($in) {
  return strftime('%A',mktime(12,0,0,4,$in,2001));
}

@mysql_connect("localhost","nobody", "")
  or die("failed to connect to database");
@mysql_select_db("phpmasterdb")
  or die("failed to select database");

$valid_vars = ['sdesc','ldesc','email','country','category','type','url','sane','smonth','sday','syear','emonth','eday','eyear','recur','recur_day'];
foreach($valid_vars as $k) {
  $$k = isset($_REQUEST[$k]) ? mysql_real_escape_string($_REQUEST[$k]) : false;
}

if (empty($sdesc) || empty($email) || empty($country) || empty($category) || empty($type) || empty($url))
  die("missing some parameters.");

// The answer to the "spam question"
if ($sane != 3) {
	die("I feel for you");
}

// utf8 safe truncate, while php not compile with mb_string
$l = 32; while (strlen($sdesc) > 32) { $sdesc = iconv_substr($sdesc, 0, $l--, 'UTF-8'); }

switch($type) {
  case 'single':
    if (!checkdate($smonth, $sday, $syear))
      die("invalid start date");

    $query = "INSERT INTO phpcal SET tipo=1,"
           . "sdato='$syear-$smonth-$sday',"
           . "sdesc='$sdesc',"
           . "url='$url',"
           . "email='$email',"
           . "ldesc='$ldesc',"
           . "country='$country',"
           . "category='$category'";
    $msg = "Date: $syear-$smonth-$sday\n";
    break;
  case 'multi':
    if (!checkdate($smonth, $sday, $syear))
      die("invalid start date");
    if (!checkdate($emonth, $eday, $eyear))
      die("invalid end date");

    $sd = mktime(0,0,0,$smonth,$sday,$syear);
    $ed = mktime(0,0,59,$emonth,$eday,$eyear);
    if ($sd > $ed)
      die("end date is after start date");

    if ($smonth == $emonth && $sday == $eday && $syear == $eyear)
      die("start and end dates are identical");
  
    $query = "INSERT INTO phpcal SET tipo=2,"
           . "sdato='$syear-$smonth-$sday',"
           . "edato='$eyear-$emonth-$eday',"
           . "sdesc='$sdesc',"
           . "url='$url',"
           . "email='$email',"
           . "ldesc='$ldesc',"
           . "country='$country',"
           . "category='$category'";

    $msg = "Start Date: $syear-$smonth-$sday\n"
         . "End Date: $eyear-$emonth-$eday\n";
    break;
  case 'recur':
    if (!is_numeric($recur) || !is_numeric($recur_day))
      die("recurring event sequence is invalid");

    $query = "INSERT INTO phpcal SET tipo=3,"
           . "recur='$recur:$recur_day',"
           . "sdesc='$sdesc',"
           . "url='$url',"
           . "email='$email',"
           . "ldesc='$ldesc',"
           . "country='$country',"
           . "category='$category'";

    $msg = "Recurs Every: $re[$recur] ".day($recur_day)."\n";

    break;
  default:
    die("invalid type");
}

mysql_query($query) or die("query failed: ".mysql_error()."<br />$query");

$new_id = mysql_insert_id();	

$msg .= "Country: ".stripslashes($country)."\n"
      . "Category: ".$cat[$category]."\n"
      . ($url ? "URL: ".stripslashes($url)."\n" : "")
      . "\n".wordwrap(stripslashes($ldesc),72);

# add signature/actions
$msg .= "\n-- \n"
      . "https://master.php.net/manage/event.php?id=$new_id&action=approve\n"
      . "https://master.php.net/manage/event.php?id=$new_id&action=reject\n"
      . "https://master.php.net/manage/event.php?id=$new_id\n";
