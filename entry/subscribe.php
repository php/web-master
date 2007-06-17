<?php

include "email-validation.inc";

// Check parameters
if (empty($_POST['request']) || empty($_POST['email']) ||
    empty($_POST['maillist']) || empty($_POST['remoteip']) ||
    empty($_POST['referer'])) {
    die("missing some parameters");
}

// Check email address
if (!is_emailable_address($_POST['email'])) {
    die("Invalid email address");
}

// Check request mode
if (!in_array($_POST['request'], array("subscribe", "unsubscribe"))) {
    die("Invalid request mode");
}

// Check mailing list name
if (!preg_match("!^[a-z0-9-]+$!", $_POST['maillist'])) {
    die("Invalid mailing list name");
}

// Generate needed subpart of email address
$sub = str_replace("@", "=", $_POST['email']);

// Try to send the subscription mail
$mail_sent = mail(
    "{$_POST['maillist']}-{$_POST['request']}-$sub@lists.php.net",
    "PHP Mailing List Website Subscription", 
    "This was a request generated from the form at {$_POST['referer']} by {$_POST['remoteip']}.",
    "From: {$_POST['email']}\r\n",
	"-fnoreply@php.net"
);

// Check if we sent mail
if (!$mail_sent) {
    die("Unable to send mail");
}
