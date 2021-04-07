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
$why = (isset($_GET['why']) ? (string) $_GET['why'] : '');

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
    break;
  case 'XBL':
?>
<p>
We use the <a href="http://www.spamhaus.org/xbl/index.lasso">Spamhaus Exploits
Block List</a>, a realtime database of exploited or trojanned machines, to
block mail that is quite likely to contain either spam or virus infected
messages.
</p>

<p>
Your message was blocked either because your computer, or one of the mail servers that your message was routed through is on the XBL.
</p>

<b>How do I get my message through?</b>

<p>
You need to get your computer(s) off the XBL.  Make sure that you are virus/trojan free, and make sure that if you're running your own mail servers that you are not running an open relay.  Then <a href="#remove">check if you are on a block list</a>.
</p>

<p>
If you are virus free but have picked up a dynamic IP address that was previous
used by someone with an infected computer, you're probably out of luck; if you
can, try disconnecting from the internet and dialling up again to get issued a
new IP.  If you find that you are consistently blocked in this way, contact
your ISP.
</p>

<p>
If it is your ISP's mail server that is blocked, contact your ISP.  If their
mail server is consistently blocked, you should consider moving to a different
provider.
</p>

<?php
    break;
  case 'blacklist':
?>
<p>
You're on our black list.  It is very rare for us to list someone on our own black list, so it must have been for a very very very good reason.
</p>

<b>How do I get off the blacklist?</b>

<p>
You need to be resourceful enough to contact someone on our systems team.  You
won't be able to mail us directly (because you are blacklisted).  You will need
to use an alternative email address or ask someone to contact us on your
behalf.
</p>

<?php
    /* TODO: more reasons to go here */
    break;
}
?>

<b>How do I check if I'm on a block list?</b>

<p>
You may check your IP's using <a href="https://www.spamhaus.org/lookup/">this form, provided by Spamhaus</a>
<!--
XXX: Commented out as Rules Emporium seems to no longer be functioning
, or check a wider range of block lists (only some of which are employed by our servers) using the <a href="http://www.rulesemporium.com/cgi-bin/uribl.cgi">Rules Emporium URI Block list checker</a>
-->
</p>
<p>
The Spamhaus query page provides you with instructions on how to request removal.
</p>

<?php
foot();
?>
