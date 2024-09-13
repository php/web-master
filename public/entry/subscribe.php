<?php

use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/../../vendor/autoload.php';

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
if (!in_array($_POST['request'], ["subscribe", "unsubscribe"])) {
    die("Invalid request mode");
}

// Check mailing list name
if (!preg_match("!^[a-z0-9-]+$!", $_POST['maillist'])) {
    die("Invalid mailing list name");
}

date_default_timezone_set('Etc/UTC');
$mail = new PHPMailer;
$mail->isSMTP();
$mail->SMTPDebug = 0;
$mail->Host = 'mailout.php.net';
$mail->Port = 25;
$mail->setFrom($_POST['email']);
preg_match('/^(.*?)(-digest)?$/', $_POST['maillist'], $matches);
$maillist = $matches[1];
$digest = count($matches) > 2 ? "-digest" : "";
$mail->addAddress("{$maillist}+{$_POST['request']}{$digest}@lists.php.net");
$mail->Subject = "PHP Mailing List Website Subscription";
$mail->Body = "This was a request generated from the form at {$_POST['referer']} by {$_POST['remoteip']}";
$mail_sent = $mail->send();

if (!$mail_sent) {
    die("Mailer Error: " . $mail->ErrorInfo);
}
