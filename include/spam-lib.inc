<?php
// $Id$

// Not implemented yet
$spamassassin_path = '/opt/ecelerity/3rdParty/bin/spamassassin';

// List of usual SPAM words
$words_blacklist = [
	'000webhost',
	'adipex',
	'alprazolam',
	'arimidex',
	'ativan',
	'bitcoin',
	'bontril',
	'carisoprodol',
	'coralclub24',
	'http://cars-4-you.org',
	'http://the-best-cars.org',
	'casino',
	'ciprofloxacin',
	'clonazepam',
	'digoxin',
	'earning',
	'ephedra',
	'esomeprazole',
	'firearms',
	'glucophage',
	'http://20six.co.uk',
	'hydrochlorothiazide',
	'hydrocodone',
	'lisinopril',
	'lopressor',
	'lorazepam',
	'meridia',
	'metronidazole',
	'mining',
	'naproxen',
	'nexium',
	'paroxetine',
	'phentermine',
	'poker',
	'potassium chloride',
	'pravachol',	
	'testosterone',
	'tramadol',
	'ultram',
	'valium',
	'vicodin',
	'vicoprofen',
	'xanax',
	'zanaflex',
	'http://republika.pl',
];

function check_spam_words ($text, $badwords) {
	foreach($badwords as $badword) {
    	if (strpos($text, $badword) !== false) {
        	return true;
    	}
	}
	return false;
}

// Miscellaneous URL related spam checks
// * BBCode links (a common spam technique)
// * Too many http://'s
function check_spam_urls ($text, $httplimit = 4) {
    if (preg_match('/\[(url|link)=[^]]+\]/', $text)) {
        return true;
    }
    if (substr_count($text, 'http://') >= $httplimit) {
        return true;
    }
    return false;
}

// Check with spam assassin if the text is spam or not
// This has not been fully tested or implemented
function check_spam_assassin ($text, $sa_path) {

	$spam = shell_exec ('echo ' . escapeshellarg($text) . " | $sa_path -L -e 8");

	$match = '';
	if (preg_match ('/^X-Spam-Status:.+(?:\n\t.+)*'.'/m', $spam, $match)) {
    	$spam_data = $match[0];
	} else {
    	$spam_data = 'Error matching the SpamAssassin data';
	}
	return $spam_data;
}

// Check if an IP is marked as spammer
// Test with 127.0.0.2 for positive and 127.0.0.1 for negative
function is_spam_ip ($ip) {
    $reverse_ip = implode('.', array_reverse(explode('.', $ip)));

    // spammers lists
    // [0] => dns server, [1] => exclude ip
    $lists[] = ['bl.spamcop.net'];
    $lists[] = ['dnsbl.sorbs.net', '127.0.0.10']; // exclude dynamic ips list

    foreach ($lists as $list) {
        $host = $reverse_ip . '.' . $list[0];
        $dns  = gethostbyname ($host);

        if ($dns != $host && (empty($list[1]) || $dns != $list[1])) {
            return $ip;
        }
    }
    return false;
}
