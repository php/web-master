<?php

// We place this on the screen so that the user
// knows something should be getting ready to happen
if (!isset($_GET['host'])) die('Waiting....');

// If it's not a legitimate *.php.net address, make 'em buzz off
if (!preg_match('/^[a-z]+[0-9]?\.php\.net$/i',$_GET['host']))
	die('Uhhh, yeeeaaaahhh.... that\'s not gonna\' happen.');

// Go ahead and include the functions and configuration and such now
require_once dirname(dirname(dirname(__FILE__))).'/include/functions.inc';

/**
 * In the interest of keeping this (free) key from
 * being copied and reused, we'll store it in a file here:
 * 	/etc/ipinfodb-api-key.txt
 * If you're reading this source and are interested in
 * getting your own free API key, I'd recommend going
 * to http://www.ipinfodb.com/ and signing up.
 */
$ipinfodb_api_key = trim(file_get_contents('/etc/ipinfodb-api-key.txt'));

// Database connection and query
db_connect();
$res = db_query("SELECT * FROM mirrors LEFT JOIN country ON mirrors.cc = country.id WHERE mirrors.hostname='".mysql_real_escape_string($_GET['host'])."' LIMIT 0,1");
$row = mysql_fetch_assoc($res);

// Grab the real-time info from the mirror
$data = file_get_contents('http://'.$_GET['host'].'/mirror-info') or die('Unable to reach '.$_GET['host'].' via HTTP GET request.');
$conf = explode('|',$data);

// Last time the mirror was synchronized
$last_updated = date('\o\n l, \t\h\e jS \o\f F, Y, \a\t H:i:s',$conf[2]);

// Available SQLites
$sqlites = implode(', ',decipher_available_sqlites($conf[3]));

// Whether or not stats are available
$stats = $conf[4] == '1' ? 'does' : 'does not';

// The mirror's primary ISO language code
$lang_iso = $conf[5];

// Whether the mirror is active or inactive
$active = $conf[7] == '1' ? 'active' : 'inactive';

// The mirror's actual CNAME, IP info, and network stats
$cname = $row['cname'];
$ip_info = str_replace(PHP_EOL,'; ',trim(`host $cname | grep -i address`));
$ping_stats = nl2br(trim(`ping -c1 -i1 -w1 $cname | grep -v PING | grep -v "ping statistics"`));
$ip_addr = gethostbyname($_GET['host']);

// Our hostname info for showing in the probe report
$my_host = trim(`hostname`);

// The maintainer's name with email address stripped
$maintainer = preg_replace('/\s?\<.*\>/U','',$row['maintainer']);

// The date the mirror became official
preg_match('/^([0-9]{4,}-[0-9]{2,}-[0-9]{2,})/',$row['created'],$cd);
$ctime = strtotime(preg_replace('/([0-4\-])\s.*/','',$cd[0]));
$created = strstr('0000-00-00',$row['created']) ? 'before Tuesday, the 17th of September, 2002' : date('l, \t\h\e jS \o\f F, Y',$ctime);

// The mirror type (normal = 1, special = 2)
$mirrortype = $row['mirrortype'] == '1' ? 'official' : 'special official';

// Geographical information from IP using danbrown's API key
$geoip_info = explode(';',file_get_contents('http://api.ipinfodb.com/v3/ip-city/?key='.$ipinfodb_api_key.'&ip='.$ip_addr));

// If we have location information to offer, build that output here
if (is_array($geoip_info) && $geoip_info[0] == 'OK') {
	$geoip_info['region'] = $geoip_info[5];
	$geoip_info['city'] = $geoip_info[6];
	$geoip_info['lat_lon'] = $geoip_info[8].','.$geoip_info[9];
	$geoip_info['maplink'] = 'https://maps.google.com/maps?q='.$geoip_info['lat_lon'].'&hl=en&t=h&z=11';

	if ($geoip_info['city'] != '-') {
		$geoip_info['html']  = 'The mirror seems to be physically located in or near <b>'.$geoip_info['city'];
		$geoip_info['html'] .= !empty($geoip_info['region']) && $geoip_info['region'] != $geoip_info['city'] ? ', '.$geoip_info['region'] : '';
		$geoip_info['html'] .= '</b>, which can be <b><a href="'.$geoip_info['maplink'].'" target="_blank">seen right here</a></b>.';
	} else {
		$geoip_info['html'] = 'The mirror may or may not be <b><a href="'.$geoip_info['maplink'].'" target="_blank">near this point on the map.</a></b>';
	}
	$geoip_info['html'] .= '<br/>'.PHP_EOL.'<br/>'.PHP_EOL;
} else {
	$geoip_info['html'] = '';
}

// Build our HTML block
$html =<<<HTML
The node named <b><a href="{$conf[0]}" target="_blank">{$_GET['host']}</a></b> is an <b>{$active}</b>
{$mirrortype} php.net mirror serving the community from <b>{$row['name']}</b>.  It is sponsored by
<b><a href="{$row['providerurl']}" target="_blank">{$row['providername']}</a></b> and
primarily maintained by <b>{$maintainer}</b>.  Its hostname <b>{$ip_info}</b>.  It was
last updated <b>{$last_updated}</b>.  It is presently running <b>PHP {$conf[1]}</b> with
<b>{$sqlites} SQLite</b> available.  The node <b>{$stats}</b> have statistics available, and
is configured to use the <b>ISO language code "{$lang_iso}"</b> as its primary language.  This
mirror has been in service since <b>{$created}</b>.<br/>
<br/>

{$geoip_info['html']}

From here at <b>{$_SERVER['HTTP_HOST']} ({$my_host})</b>, a single probe to the node responds:<br/>
<br/>
{$ping_stats}

HTML;

// Output the HTML now
echo $html;
