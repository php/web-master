<?php

use PHPMailer\PHPMailer\PHPMailer;

class MailAddress {
    public $email;
    public $name;

    public function __construct($email, $name = '') {
        $this->email = $email;
        $this->name = $name;
    }

    public static function noReply($name = '') {
        return new self('noreply@php.net', $name);
    }
}

function mailer($to, $subject, $body, MailAddress $from, array $replyTos = []) {
    $mail = new PHPMailer;
    $mail->isSMTP();
    $mail->SMTPDebug = 0;
    $mail->Host = 'mailout.php.net';
    $mail->Port = 25;
    $mail->CharSet = 'utf-8';
    foreach ($replyTos as $replyTo) {
        $mail->addReplyTo($replyTo->email, $replyTo->name);
    }
    $mail->setFrom($from->email, $from->name);
    $mail->addAddress($to);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail_sent = $mail->send();
}
