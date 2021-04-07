<?php
require __DIR__ . '/../../include/login.inc';
require __DIR__ . '/../../include/email-validation.inc';

define('PHP_SELF', hsc($_SERVER['PHP_SELF']));

$mailto = "php-webmaster@lists.php.net";
#$mailto = "jimw@apache.org";

$days = $months = array();

for ($i = 1; $i <= 7; $i++) {
  $days[$i] = strftime('%A',mktime(12,0,0,4,$i,2001));
}

for ($i = 1; $i <= 12; $i++) {
  $months[$i] = strftime('%B',mktime(12,0,0,$i,1,2001));
}

$re = [1=>'First',2=>'Second',3=>'Third',4=>'Fourth',-1=>'Last',-2=>'2nd Last',-3=>'3rd Last'];
$cat = ["unknown", "User Group Event", "Conference", "Training"];

$type = [1=>'single',2=>'multi',3=>'recur'];

head("event administration");
db_connect();

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;
$in = isset($_REQUEST['in']) ? $_REQUEST['in'] : false;
$begin = isset($_REQUEST['begin']) ? $_REQUEST['begin'] : false;
$max = isset($_REQUEST['max']) ? $_REQUEST['max'] : false;
$search = isset($_REQUEST['search']) ? $_REQUEST['search'] : false;
$order = isset($_REQUEST['order']) ? $_REQUEST['order'] : false;
$full = isset($_REQUEST['full']) ? $_REQUEST['full'] : false;
$unapproved = isset($_REQUEST['unapproved']) ? $_REQUEST['unapproved'] : false;

if($id) $id = (int)$id;

if ($id && $action) {
  switch ($action) {
  case 'approve':
    if (db_query_safe("UPDATE phpcal SET approved=1,app_by=? WHERE id=?", [$cuser, $id])
     && mysql_affected_rows()) {
      $event = fetch_event($id);
      $message = "This event has been approved. It will appear on the PHP website shortly.";
      if ($event['email']) mail($event['email'],"Event #$id Approved: $event[sdesc]",$message,"From: PHP Webmasters <php-webmaster@lists.php.net>", "-fnoreply@php.net -O DeliveryMode=b");

      warn("record $id approved");
    }
    else {
      warn("wasn't able to approve id $id.");
    }
    break;
  case 'reject':
    $event = fetch_event($id);
    if (db_query_safe("DELETE FROM phpcal WHERE id=?", [$id])
     && mysql_affected_rows()) {
      $message = $event['approved'] ?  "This event has been deleted." : "This event has been rejected.";
      $did = $event['approved'] ? 'Deleted' : 'Rejected';

      if ($event['email']) mail($event['email'],"Event #$id $did: $event[sdesc]",$message,"From: PHP Webmasters <php-webmaster@lists.php.net>", "-fnoreply@php.net -O DeliveryMode=b");

      warn("record $id ".strtolower($did));

      unset($id);
    }
    else {
      warn("wasn't able to delete id $id.");
    }
    break;
  default:
    warn("that action ('".html_entity_decode($action,ENT_QUOTES)."') is not understood.");
  }
}

if ($id && $in) {
    $tipo = array_search($in['type'],$type);
    if ($in['sday'] && $in['smonth'] && $in['syear'])
      $sdato = "$in[syear]-$in[smonth]-$in[sday]";
    if ($in['eday'] && $in['emonth'] && $in['eyear'])
      $edato = "$in[eyear]-$in[emonth]-$in[eday]";
    if ($in['recur'] && $in['recur_day'])
      $recur = "$in[recur]:$in[recur_day]";
    $query = new Query('UPDATE phpcal SET ');
    if ($sdato) {
      $query->add('sdato=?, ', [$sdato]);
    }
    if ($edato) {
      $query->add('edato=?, ', [$edato]);
    }
    if ($recur) {
      $query->add('recur=?, ', [$recur]);
    }
    $query->add(
      "tipo=?, ldesc=?, sdesc=?, email=?, url=?, country=?, category=? WHERE id=?",
      [$tipo, $in['ldesc'], $in['sdesc'], $in['email'], $in['url'], $in['country'], $in['category'], $id]
    );
    db_query($query);

    warn("record $id updated");
    unset($id);
}

if ($id && !$in) {
  $in = fetch_event($id);
  if (!$in) {
    unset($id);
  }
  else {
    @list($in['syear'],$in['smonth'],$in['sday']) = @explode("-",$in['sdato']);
    @list($in['eyear'],$in['emonth'],$in['eday']) = @explode("-",$in['edato']);
    @list($in['recur'],$in['recur_day']) = @explode(':',$in['recur']);
    $in['type'] = $type[$in['tipo']];
  }
}
elseif ($in) {
  foreach ($in as $k => $v) {
    $in[$k] = $v;
  }
}

if ($id) {
?>
<form action="<?php echo PHP_SELF?>" method="post">
<input type="hidden" name="id" value="<?php echo $id?>" />
<table class="useredit">
 <tr>
  <th>Start Date</th>
  <td>
   <select name="in[smonth]"><option></option><?php display_options($months,$in['smonth'])?></select>
   <input type="text" name="in[sday]" size="2" maxlength="2" value="<?php echo hsc($in['sday'])?>" />
   <input type="text" name="in[syear]" size="4" maxlength="4" value="<?php echo $in['syear'] ? hsc($in['syear']) : date("Y")?>" />
   <input type="radio" id="single" name="in[type]" value="single"<?php if ($in['type'] == 'single' || !$in['type']) echo ' checked="checked"';?> />
   <label for="single">One day (no end-date required)</label>
  </td>
 </tr>
 <tr>
  <th>End Date</th>
  <td>
   <select name="in[emonth]"><option></option><?php display_options($months,$in['emonth'])?></select>
   <input type="text" name="in[eday]" size="2" maxlength="2" value="<?php echo hsc($in['eday'])?>" />
   <input type="text" name="in[eyear]" size="4" maxlength="4" value="<?php echo $in['eyear'] ? hsc($in['eyear']) : date("Y")?>" />
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
  <td><input type="text" name="in[sdesc]" value="<?php echo html_entity_decode($in['sdesc'],ENT_QUOTES)?>" size="32" maxlength="32" /></td>
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
  <td><input type="text" name="in[email]" size="40" maxlength="128" value="<?php echo html_entity_decode($in['email'],ENT_QUOTES)?>" /></td>
 </tr>
 <tr>
  <th>URL</th>
  <td><input type="text" name="in[url]" size="40" maxlength="128" value="<?php echo html_entity_decode($in['url'],ENT_QUOTES)?>" /></td>
 </tr>
 <tr>
  <th colspan="2" align="left">Long Description</th>
 </tr>
 <tr>
  <td colspan="2"><textarea name="in[ldesc]" cols="60" rows="10" wrap="virtual"><?php echo html_entity_decode($in['ldesc'],ENT_QUOTES);?></textarea></td>
 </tr>
 <tr>
  <td align="center" colspan="2">
    <input type="submit" value="Submit" />
  </td>
 </tr>
</table>
</form>
<table class="useredit">
<tr>
 <form method="get" action="<?php echo PHP_SELF;?>">
  <input type="hidden" name="action" value="reject" />
  <input type="hidden" name="id" value="<?php echo $id?>" />
<?php if ($in['approved']) {?>
  <td><input type="submit" value="Delete" />
<?php } else {?>
  <td><input type="submit" value="Reject" />
<?php }?>
 </form>
<?php if (!$in['approved']) {?>
 <form method="get" action="<?php echo PHP_SELF;?>">
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
<table class="useredit">
 <tr>
  <td>
   <a href="<?php echo PHP_SELF?>">see upcoming events</a>
   | <a href="<?php echo PHP_SELF . "?unapproved=1"?>">see unapproved events</a>
  </td>
 </tr>
</table>
<?php

$begin = $begin ? (int)$begin : 0;
$full = $full ? 1 : (!$full && ($search || $unapproved) ? 1 : 0);
$max = $max ? (int)$max : 20;
$forward = filter_input(INPUT_GET, "forward", FILTER_VALIDATE_INT) ?: 0;

$searchby = new Query();
if ($search) {
  $searchby->add(' WHERE MATCH(sdesc,ldesc,email) AGAINST (?)', [$search]);
} elseif ($unapproved) {
  $searchby->add(' WHERE NOT approved');
} else {
  $searchby->add(' WHERE NOT (tipo = 1 AND sdato < NOW()) AND NOT (tipo = 2 AND edato < NOW())');
}

$query = new Query("SELECT COUNT(id) FROM phpcal");
$query->addQuery($searchby);
$res = db_query($query);
$total = (int)mysql_result($res,0);

$query = new Query("SELECT phpcal.*,country.name AS cname FROM phpcal LEFT JOIN country ON phpcal.country = country.id");
$query->addQuery($searchby);
if ($order) {
  if (!in_array($order, ['sdato', 'sdesc', 'email', 'country', 'category'], true)) {
    $order = 'sdato';
  }
  if ($forward) {
    $ext = "ASC";
  } else {
    $ext = "DESC";
  }
  // Safe because we checked that $order is part of a fixed set.
  $query->add(" ORDER BY $order $ext");
}
$query->add(' LIMIT ?int, ?int', [$begin, $max]);
$res = db_query($query);

$extra = [
  "search" => $search,
  "order" => $order,
  "begin" => $begin,
  "max" => $max,
  "full" => $full,
  "unapproved" => $unapproved,
  "forward"    => $forward,
];

show_prev_next($begin,mysql_num_rows($res),$max,$total,$extra);
?>
<table class="useredit">
<tr>
 <th><a href="<?php echo PHP_SELF,'?',array_to_url($extra,["full" => $full ? 0 : 1]);?>"><?php echo $full ? "&otimes;" : "&oplus;";?></a></th>
 <th><a href="<?php echo PHP_SELF,'?',array_to_url($extra,["order"=>"sdato"]);?>">date</a></th>
 <th><a href="<?php echo PHP_SELF,'?',array_to_url($extra,["order"=>"sdesc"]);?>">summary</a></th>
 <th><a href="<?php echo PHP_SELF,'?',array_to_url($extra,["order"=>"email"]);?>">email</a></th>
 <th><a href="<?php echo PHP_SELF,'?',array_to_url($extra,["order"=>"country"]);?>">country</a></th>
 <th><a href="<?php echo PHP_SELF,'?',array_to_url($extra,["order"=>"category"]);?>">category</a></th>
</tr>
<?php
while ($row = mysql_fetch_array($res,MYSQL_ASSOC)) {
?>
<tr>
 <td align="center"><a href="<?php echo PHP_SELF . "?id=$row[id]";?>">edit</a></td>
 <td><?php echo html_entity_decode($row['sdato'],ENT_QUOTES);?></td>
 <td><?php echo html_entity_decode($row['sdesc'],ENT_QUOTES);?></td>
 <td><?php echo html_entity_decode($row['email'],ENT_QUOTES);?></td>
 <td><?php echo html_entity_decode($row['cname'],ENT_QUOTES);?></td>
 <td><?php echo html_entity_decode($cat[$row['category']],ENT_QUOTES);?></td>
</tr>
<?php
  if ($full && $row['ldesc']) {?>
<tr>
 <td></td><td colspan="5"><?php echo html_entity_decode($row['ldesc'],ENT_QUOTES);?></td>
</tr>
<?php
  }
}
?>
</table>
<?php
show_prev_next($begin,mysql_num_rows($res),$max,$total,$extra);
foot();

