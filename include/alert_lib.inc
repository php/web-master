<?php

$alink = mysql_connect("localhost","nobody","") or die("failed to connect to database");
@mysql_select_db("phpmasterdb", $alink) or die("failed to select database");

/*{{{ show_alert_form */
function show_alert_form($user, $sect) {
	$PAGE_ALERT = has_alert($user, $sect);
		
	$form = "<form name=\"alertme\" method=\"post\" action=\"/manual/notes_alert.php\" target=\"_blank\">\n";
	$form .= "<input type=\"hidden\" name=\"user\" value=\"$user\">\n";
	$form .= "<input type=\"hidden\" name=\"sect\" value=\"$sect\">\n";
	$form .= "<input type=\"hidden\" name=\"alert_action\" value=\"";
	$form .= ($PAGE_ALERT) ? "del_alert\">\n" : "add_alert\">\n";
	$form .= "<input type=\"submit\" name=\"";
	$form .= ($PAGE_ALERT) ? "del_alert\" value=\"Remove alert" : "add_alert\" value=\"Add alert"; 
	$form .= " for this page\">\n</form>\n";
	echo $form;
	return true;
}/*}}}*/

/*{{{ has_alert */
function has_alert($user, $sect) {
	global $alink, $alert_table;
	$sql = "select * from alerts where user='$user' and sect='$sect'";
	$r = mysql_query($sql, $alink);
	$found = $r && mysql_num_rows($r) == 1;
	return $found;
}
/*}}}*/

/*{{{ do_alert_action */
function do_alert_action($sql) {
	global $alink;
	$r = @mysql_query($sql, $alink);
	$success = ($r && mysql_affected_rows($c) > 0);
	if (!$success)
		echo "<b>Error performing sql: ".mysql_error()." ( in do_alert_action() )";
	return $success;
}
/*}}}*/

/*{{{ print_alerts */
function print_alerts($user) {
	global $alink;
	$sql = "select page,UNIX_TIMESTAMP(updated) as ts from alerts where user='$user' order by sect";
	$r = mysql_query($sql, $alink);
	if (!$r) {
		echo "ERROR: ".mysql_error();
		return false;
	} else {
		$n = mysql_num_rows($r);
		echo "You have alerts for $n pages:<br />\n<ul>\n";
		while ($row = mysql_fetch_assoc($r)) {
			$date = date("Y/m/d h:i:s", $row["ts"]);
			echo "<li>".$row["page"]." ($date)</li>\n";
		}
		echo "</ul>\n";
		return true;
	}
}
/*}}}*/

/*{{{ get_emails_for_sect */
function get_emails_for_sect($sect) {
	global $alink;
	$sql = "select cvsusers.email from alerts,cvsusers ";
	$sql .= "alerts.sect = '$sect' and alerts.user = cvsusers.user";
	$r = mysql_query($sql, $alink);
	if (!$r) {
		return "";
	} else {
		while ($row = mysql_fetch_assoc($r))
			$elist[] = $row["email"];
		return ",".implode(",",$elist);
	}
}
/*}}}*/
?>
