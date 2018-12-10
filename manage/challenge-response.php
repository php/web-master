<?php # $Id$

/* Show the list of people caught up in the CR system
 * for the current user. */

require_once 'login.inc';
require_once 'functions.inc';
require_once 'email-validation.inc';

head("challenge response anti-spam thingamy");

mysql_connect("localhost","nobody","")
  or die("unable to connect to database");
mysql_select_db("phpmasterdb")
  or die("unable to select database");

if (isset($_POST['confirm_them']) && isset($_POST['confirm']) && is_array($_POST['confirm'])) {
	foreach ($_POST['confirm'] as $address) {
		$addr = mysql_real_escape_string($address);
		db_query("insert into accounts.confirmed (email, ts) values ('$addr', NOW())");
	}
}

$user_db = mysql_real_escape_string($user);
$res     = db_query("select distinct sender from phpmasterdb.users left join accounts.quarantine on users.email = rcpt where username='$user_db' and not isnull(id)");

$inmates = [];
while ($row = mysql_fetch_row($res)) {
	$inmates[] = $row[0];
}

function sort_by_domain($a, $b)
{
	list($al, $ad) = explode('@', $a, 2);
	list($bl, $bd) = explode('@', $b, 2);

	$x = strcmp($ad, $bd);
	if ($x)
		return $x;

	return strcmp($al, $bl);
}

usort($inmates, 'sort_by_domain');

?>

<h1>Addresses in quarantine for <?php echo hsc($user); ?>@php.net</h1>

<form method="post" action="<?php echo hsc($_SERVER['PHP_SELF']); ?>">

<table>
	<tr>
		<td>&nbsp;</td>
		<td>Sender</td>
		<td>Domain</td>
	</tr>

<?php
$i = 0;
foreach ($inmates as $prisoner) {
	list($localpart, $domain) = explode('@', $prisoner, 2);
	$bgcolor = ($i & 1) ? '#eeeeee' : '#ffffff';
?>
<tr bgcolor="<?php echo $bgcolor; ?>">
	<td><input type="checkbox" name="confirm[]" value="<?php echo hscr($prisoner) ?>"/></td>
	<td align="right"><?php echo hscr($localpart) ?></td>
	<td align="left">@ <?php echo hscr($domain) ?></td>
</tr>
<?php
}
?>
</table>

<p>
If you see an address listed here that you are 100% sure is a legitimate
sender, you may tick the appropriate box and confirm them.  Quarantine is
processed every 15 minutes; once you have confirmed an address, be prepared to
wait that long before the mail is delivered.
</p>

<input type="submit" name="confirm_them" value="Confirm Ticked Senders"/>

</form>

<?php
$res = db_query("select count(id) from phpmasterdb.users left join accounts.quarantine on users.email = rcpt where username='$user_db'");

$n = 0;
if (mysql_num_rows($res) > 0) {
	$n = mysql_result($res, 0);
}

echo "You have <b>$n</b> messages in quarantine<br>";

foot();
?>
