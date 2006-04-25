<?php

header("Content-type: text/html; charset=utf-8");

require_once 'login.inc';
require_once 'functions.inc';
require_once 'email-validation.inc';

$mailto = "php-mirrors@lists.php.net";
#$mailto = "jimw@apache.org";

for ($i = 1; $i <= 7; $i++) {
  $days[$i] = strftime('%A',mktime(12,0,0,4,$i,2001));
}

for ($i = 1; $i <= 12; $i++) {
  $months[$i] = strftime('%B',mktime(12,0,0,$i,1,2001));
}

$re = array(1=>'First',2=>'Second',3=>'Third',4=>'Fourth',-1=>'Last',-2=>'2nd Last',-3=>'3rd Last');
$cat = array("unknown", "User Group Event", "Conference", "Training");

$type = array(1=>'single',2=>'multi',3=>'recur');

head("event administration");

@mysql_connect("localhost","nobody","")
  or die("unable to connect to database");
@mysql_select_db("phpmasterdb");

if (isset($id)) $id = (int)$id;

if (isset($id) && isset($action)) {
  switch ($action) {
  case 'approve':
    if (db_query("UPDATE phpcal SET approved=1,app_by='$user' WHERE id=$id")
     && mysql_affected_rows()) {
      $event = fetch_event($id);
      $message = "This event has been approved. It will appear on the PHP website shortly.";
      if ($event['email']) mail($event['email'],"Event #$id Approved: $event[sdesc]",$message,"From: PHP Webmasters <php-mirrors@lists.php.net>");

      mail($mailto,"Event #$id Approved: $event[sdesc]",$message,"From: $user@php.net\nIn-Reply-To: <event-$id@php.net>");
      warn("record $id approved");
    }
    else {
      warn("wasn't able to approve id $id.");
    }
    break;
  case 'reject':
    $event = fetch_event($id);
    if (db_query("DELETE FROM phpcal WHERE id=$id")
     && mysql_affected_rows()) {
      $message = $event['approved'] ?  "This event has been deleted." : "This event has been rejected.";
      $did = $event['approved'] ? 'Deleted' : 'Rejected';

      if ($event['email']) mail($event['email'],"Event #$id $did: $event[sdesc]",$message,"From: PHP Webmasters <php-mirrors@lists.php.net>");

      mail($mailto,"Event #$id $did: $event[sdesc]",$message,"From: $user@php.net\nIn-Reply-To: <event-$id@php.net>");

      warn("record $id ".strtolower($did));

      unset($id);
    }
    else {
      warn("wasn't able to delete id $id.");
    }
    break;
  default:
    warn("that action ('$action') is not understood.");
  }
}

if (isset($id) && isset($in)) {
  if ($error = invalid_input($in)) {
    warn($error);
  }
  else {
    $tipo = array_search($in['type'],$type);
    if ($in['sday'] && $in['smonth'] && $in['syear'])
      $sdato = "$in[syear]-$in[smonth]-$in[sday]";
    if ($in['eday'] && $in['emonth'] && $in['eyear'])
      $edato = "$in[eyear]-$in[emonth]-$in[eday]";
    if ($in['recur'] && $in['recur_day'])
      $recur = "$in[recur]:$in[recur_day]";
    $query = "UPDATE phpcal SET "
           . "tipo=$tipo,"
           . ($sdato ? "sdato='$sdato'," : "")
           . ($edato ? "edato='$edato'," : "")
           . ($recur ? "recur='$recur'," : "")
           . "ldesc='$in[ldesc]',"
           . "sdesc='$in[sdesc]',"
           . "email='$in[email]',"
           . "url='$in[url]',"
           . "country='$in[country]',"
           . "category='$in[category]'"
           . " WHERE id=$id";
    db_query($query);

    warn("record $id updated");
    unset($id);
  }
}

if ($id && !$in) {
  $in = fetch_event($id);
  if (!$in) {
    unset($id);
  }
  else {
    list($in['syear'],$in['smonth'],$in['sday']) = explode("-",$in['sdato']);
    list($in['eyear'],$in['emonth'],$in['eday']) = explode("-",$in['edato']);
    list($in['recur'],$in['recur_day']) = explode(':',$in['recur']);
    $in['type'] = $type[$in['tipo']];
  }
}
elseif ($in) {
  foreach ($in as $k => $v) {
    $in[$k] = stripslashes($v);
  }
}

if (isset($id)) {
?>
<form action="<?php echo $PHP_SELF?>" method="post">
<input type="hidden" name="id" value="<?php echo $id?>" />
<table bgcolor="#eeeeee" border="0" cellspacing="0" cellpadding="3" width="100%">
 <tr>
  <th>Start Date</th>
  <td>
   <select name="in[smonth]"><option></option><?php display_options($months,$in['smonth'])?></select>
   <input type="text" name="in[sday]" size="2" maxlength="2" value="<?php echo htmlentities($in['sday'])?>" />
   <input type="text" name="in[syear]" size="4" maxlength="4" value="<?php echo $in['syear'] ? htmlentities($in['syear']) : date("Y")?>" />
   <input type="radio" id="single" name="in[type]" value="single"<?php if ($in['type'] == 'single' || !$in['type']) echo ' checked="checked"';?> />
   <label for="single">One day (no end-date required)</label>
  </td>
 </tr>
 <tr>
  <th>End Date</th>
  <td>
   <select name="in[emonth]"><option></option><?php display_options($months,$in['emonth'])?></select>
   <input type="text" name="in[eday]" size="2" maxlength="2" value="<?php echo htmlentities($in['eday'])?>" />
   <input type="text" name="in[eyear]" size="4" maxlength="4" value="<?php echo $in['eyear'] ? htmlentities($in['eyear']) : date("Y")?>" />
   <input type="radio" id="multi" name="in[type]" value="multi"<?php if ($in['type'] == 'multi') echo ' checked="checked"';?> />
   <label for="multi">Multi-day event</label>
  </td>
 </tr>
 <tr>
  <th>OR<br>Recurring</th>
  <td>
   <select name="in[recur]"><option></option><?php display_options($re,$in['recur'])?></select>
   <select name="in[recur_day]"><option></option><?php display_options($days,$in['recur_day'])?></select>
   <input type="radio" id="recur" name="in[type]" value="recur"<?php if ($in['type'] == 'recur') echo ' checked="checked"';?> />
   <label for="recur">Recurring (every month)</label>
  </td>
 </tr>
 <tr>
  <th>Short<br>Description</th>
  <td><input type="text" name="in[sdesc]" value="<?php echo htmlentities($in['sdesc'])?>" size="32" maxlength="32" /></td>
 </tr>
 <tr>
  <th>Country</th>
  <td>
   <select name="in[country]">
    <option value="">- Select a country -</option>
    <?php show_country_options($in['country']);?>
   </select>
  </td>
 </tr>
 <tr>
  <th>Event Category</th>
  <td>
   <select name="in[category]">
<?php
        display_options($cat,$in['category']);
?>
   </select>
  </td>
 </tr>
 <tr>
  <th>Email</th>
  <td><input type="text" name="in[email]" size="40" maxlength="128" value="<?php echo htmlentities($in['email'])?>" /></td>
 </tr>
 <tr>
  <th>URL</th>
  <td><input type="text" name="in[url]" size="40" maxlength="128" value="<?php echo htmlentities($in['url'])?>" /></td>
 </tr>
 <tr>
  <th colspan="2" align="left">Long Description</th>
 </tr>
 <tr>
  <td colspan="2"><textarea name="in[ldesc]" cols="60" rows="10" maxlength="78" wrap="virtual"><?php echo htmlentities($in['ldesc']);?></textarea></td>
 </tr>
 <tr>
  <td align="center" colspan="2">
    <input type="submit" value="Submit" />
  </td>
 </tr>
</table>
</form>
<table>
<tr>
 <form method="get" action="<?php echo $PHP_SELF;?>">
  <input type="hidden" name="action" value="reject" />
  <input type="hidden" name="id" value="<?php echo $id?>" />
<?php if ($in['approved']) {?>
  <td><input type="submit" value="Delete" />
<?php } else {?>
  <td><input type="submit" value="Reject" />
<?php }?>
 </form>
<?php if (!$in['approved']) {?>
 <form method="get" action="<?php echo $PHP_SELF;?>">
  <input type="hidden" name="action" value="approve" />
  <input type="hidden" name="id" value="<?php echo $id?>" />
  <td><input type="submit" value="Approve" />
 </form>
<?php }?>
</tr>
</table>
<?php
  foot();
  exit;
}
?>
<table width="100%">
 <tr>
  <td>
   <a href="<?php echo "$PHP_SELF";?>">see upcoming events</a>
   | <a href="<?php echo "$PHP_SELF?unapproved=1";?>">see unapproved events</a>
  </td>
  <td align="right">
   <form method="GET" action="<?php echo $PHP_SELF;?>">
    <input type="text" name="search" value="<?php echo clean($search);?>" />
    <input type="submit" value="search">
   </form>
 </tr>
</table>
<?php

$begin = $begin ? (int)$begin : 0;
$full = $full ? 1 : (!isset($full) && ($search || $unapproved) ? 1 : 0);
$max = $max ? (int)$max : 20;

$limit = "LIMIT $begin,$max";
$orderby = $order ? "ORDER BY $order" : "";

$searchby = $search ? " WHERE MATCH(sdesc,ldesc,email) AGAINST ('$search')" : "";
if (!$searchby && $unapproved) {
  $searchby = ' WHERE NOT approved';
}
if (!$searchby) {
  $searchby = ' WHERE NOT (tipo = 1 AND sdato < NOW()) AND NOT (tipo = 2 AND edato < NOW())';
}

$query = "SELECT COUNT(id) FROM phpcal";
if ($searchby)
  $query .= " $searchby";
$res = db_query($query);
$total = mysql_result($res,0);

$query = "SELECT phpcal.*,country.name AS cname FROM phpcal LEFT JOIN country ON phpcal.country = country.id $searchby $orderby $limit";

#echo "<pre>$query</pre>";
$res = db_query($query);

$extra = array(
  "search" => stripslashes($search),
  "order" => $order,
  "begin" => $begin,
  "max" => $max,
  "full" => $full,
  "unapproved" => $unapproved,
);

show_prev_next($begin,mysql_num_rows($res),$max,$total,$extra);
?>
<table border="0" cellspacing="1" width="100%">
<tr bgcolor="#aaaaaa">
 <th><a href="<?php echo "$PHP_SELF?",array_to_url($extra,array("full" => $full ? 0 : 1));?>"><?php echo $full ? "&otimes;" : "&oplus;";?></a></th>
 <th><a href="<?php echo "$PHP_SELF?",array_to_url($extra,array("order"=>"sdato"));?>">date</a></th>
 <th><a href="<?php echo "$PHP_SELF?",array_to_url($extra,array("order"=>"sdesc"));?>">summary</a></th>
 <th><a href="<?php echo "$PHP_SELF?",array_to_url($extra,array("order"=>"email"));?>">email</a></th>
 <th><a href="<?php echo "$PHP_SELF?",array_to_url($extra,array("order"=>"country"));?>">country</a></th>
 <th><a href="<?php echo "$PHP_SELF?",array_to_url($extra,array("order"=>"category"));?>">category</a></th>
</tr>
<?php
$color = '#dddddd';
while ($row = mysql_fetch_array($res,MYSQL_ASSOC)) {
?>
<tr bgcolor="<?php echo $color;?>">
 <td align="center"><a href="<?php echo "$PHP_SELF?id=$row[id]";?>">edit</a></td>
 <td><?php echo htmlspecialchars($row['sdato']);?></td>
 <td><?php echo htmlspecialchars($row['sdesc']);?></td>
 <td><?php echo htmlspecialchars($row['email']);?></td>
 <td><?php echo htmlspecialchars($row['cname']);?></td>
 <td><?php echo $cat[$row['category']];?></td>
</tr>
<?php
  if ($full && $row['ldesc']) {?>
<tr bgcolor="<?php echo $color;?>">
 <td></td><td colspan="3"><?php echo htmlspecialchars($row['ldesc']);?></td>
</tr>
<?php
  }
  $color = substr($color,2,2) == 'dd' ? '#eeeeee' : '#dddddd';
}
?>
</table>
<?php
show_prev_next($begin,mysql_num_rows($res),$max,$total,$extra);
foot();

function invalid_input($in) {
  return false;
}

function fetch_event($id) {
  $query = "SELECT * FROM phpcal WHERE id=$id";

  if ($res = db_query($query)) {
    return mysql_fetch_array($res,MYSQL_ASSOC);
  }

  return false;
}

function display_options($options,$current) {
  foreach ($options as $k => $v) {
    echo '<option value="', $k, '"',
         ($k == $current ? ' selected="selected"' : ''),
         '>', htmlentities($v), "</option>\n";
  }
}
