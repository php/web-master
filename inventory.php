<?php
require_once 'functions.inc';
head("machine inventory");
?>
<p>php.net is supported by a number of machines provided by a number
of generous sponsors. this is a basic inventory of those machines and
what services they provide.</p>

<p>Note regarding FreeBSD machines: Upgrades should be 
performed according to <a href="fbsd_upgrade.txt">this guide</a>.</p>

<h2>pair1.php.net (216.92.131.4)</h2>

<p><b>machine:</b> dual p3/1000 xeon, 512MB RAM, 27GB HD, freebsd 4.6</p>

<p><b>aliases:</b> lists.php.net, news.php.net, cvs.php.net, cvsup.php.net, pear.php.net</p>

<p><b>notes:</b> snmp is currently blocked on this machine (at the network
level by pair, due to the recent snmp security advisories).</p>

<h2>pair2.php.net (216.92.131.5)</h2>

<p><b>machine:</b> p3/566, 128MB RAM, 27GB HD, freebsd 4.6</p>

<p><b>aliases:</b> php.net mx</p>

<p><b>notes:</b> this machine maintains a copy of ~ezmlm from pair1, so it can
take over the mailing lists if pair1 ever fails.</p>

<h2>rack1.php.net (209.61.157.217)</h2>

<p><b>machine:</b> dual p3/650, 1GB RAM, 33GB HD, redhat 7.3</p>

<p><b>notes:</b> this machine has been (temporarily?) sidelined. there are
bandwidth limitations, and we need to make sure monitoring is in place before
resuming use of the machine to make sure we don't exceed those limits.</p>

<p><b>aliases:</b> qa.php.net, bugs.php.net, gtk.php.net, master.php.net</p>

<p>limited to 500GB traffic monthly.</p>

<h2>rs1.php.net (64.246.30.37)</h2>

<p><b>machine:</b> p3/1GHz, 1GB RAM 40GB HD, redhat 7.2</p>

<p><b>aliases:</b> www.php.net, conf.php.net</p>

<p><b>notes:</b> This machine is on a dedicated 10mbps switch. whilst it can maintain 10mbps, it cannot burst above that.</p> 

<p><b>stats:</b> MRTG stats (traffic, load avg, cpu usage, etc) are 
available <a href="http://www.php.net/~imajes/mrtg/">here</a>.</p>

<h2>va1.php.net (198.186.203.51)</h2>

<p><b>machine:</b> dual p3/650, 1 GB RAM, 130GB HD, debian-stable</p>

<p><b>aliases:</b> rsync.php.net, snaps.php.net</p>

<p><b>notes:</b> this machine generates the online manuals. daily backups of
the cvs repository and the rsync modules are done. cricket also runs on this
machine to track the usage of all of the machines.</p>

<p>originally compiled by jim winstead, september 2001</p>
<p>last update: $Date$</p>
<?php
foot();
