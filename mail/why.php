<?php
/* vim:se ts=2 sw=2 et: */
require 'functions.inc';

head("Why was my email rejected?");
?>
<h1>Why was my email rejected?</h1>

<p>
This page explains why a message that you sent was not accepted through the
PHP.net mail servers.
</p>

<?php

/* Explains why a message was rejected */
$why = $_GET['why'];

switch ($why) {
	case 'SURBL':
?>
<p>
We employ a <a href="http://www.surbl.org">Spam URI Realtime Blocklist (SURBL)</a> which allows us to block messages that contain spam domains inside the message bodies.  The rationale is that if a message references a spammer domain, then it is quite likely to be spam.
</p>

<b>What can I do to get my my message through?</b>
<p>
The quickest way is to edit your message so that the URLs are not "clickable";
in other words, remove the <tt>http://</tt> prefix from the front, or remove or
disguise the <tt>www</tt> prefix.  For example, if <tt>www.example.com</tt> is on a block list:
</p>
<dl>
<dt>http://www.example.com</dt>
<dd>would be blocked</dd>
<dt>www.example.com</dt>
<dd>would be blocked</dd>
<dt>www dot example.com</dt>
<dd>would be allowed through</dd>
</dl>

<p>
If it is <i>your</i> site that is being blocked, then it is <i>your</i>
responsibility as a good netizen to <a href="#remove">get your site removed from the block list</a>.
</p>

<p>
If you find that a lot of your messages are consistently being blocked by
SURBL, and that the cause of the blockage is not a site under your control,
then you may contact systems@php.net to see if there is a more convenient
resolution.
</p>

<?php
    /* TODO: more reasons to go here */
    break;
}
?>

<b>How do I check if I'm on a block list?</b>

<p>
You may check your IP's using <a href="http://www.spamhaus.org/sbl/index.lasso">this form, provided by Spamhaus</a>, or check a wider range of block lists (only some of which are employed by our servers) using the <a href="http://www.rulesemporium.com/cgi-bin/uribl.cgi">Rules Emporium URI Block list checker</a>
</p>

<?php
foot();
?>
