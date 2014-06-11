<?php
require_once "login.inc";
require_once "functions.inc";
require_once "alert_lib.inc";

head();
	
switch ($alert_action) {
	case "add_alert" :
		$sql = "INSERT INTO alerts VALUES ('".real_clean($cuser)."', '".real_clean($sect)."', NOW())";
		if (has_alert($user, $sect))
			echo "<b>You already have an alert for this page</b><br />\n";
		else
			if(do_alert_action($sql))
				echo "<b>Alert for page \"$sect\", added to your list</b></ br>";
			else
				echo "Unknown error while adding alert<br />\n";
		break;
	case "del_alert" :
		$sql = "delete from alerts where user='".real_clean($cuser)."' and sect='".real_clean($sect)."'";
		if (has_alert($user, $sect))
			if(do_alert_action($sql))
				echo "<b>Alert for page \"$sect\", deleted from your list</b></ br>";
			else
				echo "Unknown error while deleting alert<br />\n";
		else
			echo "<b>You do not have an alert for this page</b><br />\n";
		break;
	default :
		echo "<b>Your alerts</b>< br/>";
		break;
}
echo "<hr>";
print_alerts($user);

foot();
