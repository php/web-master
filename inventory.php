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

<p><b>machine:</b> dual p3/1000 xeon, 512MB RAM, 27GB HD, freebsd 4.8</p>

<p><b>aliases:</b> lists.php.net, news.php.net, pear.php.net</p>

<h2>pair2.php.net (216.92.131.5)</h2>

<p><b>machine:</b> p3/566, 128MB RAM, 27GB HD, freebsd 4.8</p>

<p><b>aliases:</b> php.net mx</p>

<p><b>notes:</b> this machine maintains a copy of ~ezmlm from pair1, so it can
take over the mailing lists if pair1 ever fails.</p>

<h2>pair11.php.net (216.92.131.65)</h2>

<p><b>machine:</b> dual p3/1000 xeon, 512MB RAM, 70GB HD, freebsd 4.8</p>

<p><b>aliases:</b> cvs, cvsup, lxr, viewcvs</p>

<h2>pair12.php.net (216.92.131.66)</h2>

<p><b>machine:</b> dual p3/1000 xeon, 512MB RAM, 70GB HD, freebsd 4.9</p>

<p><b>aliases:</b> pear, pecl</p>

<h2>rs1.php.net (64.246.30.37)</h2>

<p><b>machine:</b> p3/1GHz, 1GB RAM 40GB HD, redhat 7.2</p>

<p><b>aliases:</b> www, conf, pres, talks</p>

<p><b>notes:</b> This machine is on a dedicated 10mbps switch. whilst it can maintain 10mbps, it cannot burst above that.</p> 

<p><b>stats:</b> MRTG stats (traffic, load avg, cpu usage, etc) are 
available <a href="http://www.php.net/~imajes/mrtg/">here</a>.</p>

<p><b>technical contact:</b> connect to irc.ev1.net:7000, join #rackshack</p>

<h2>rn1.php.net (12.165.50.194)</h2>

<p><b>machine:</b> Compaq DL380-G2 1.1GHz, 1GB RAM, 6x36GB SCSI HDs in RAID5 resulting in a 167GB cluster.</p>

<p><b>aliases:</b> gtk, embed, smarty, qa, snapsmaster</p>

<p><b>notes:</b> this machine resides in Reno, Nevada. A second machine in Raleigh, NC will be online soon as a mirror of 
this box. This allows for absolute failover redundancy, and the possibilty of internal linkage. This box is quite a monster,
 so having failover allows us the possibility to experiment with various distributed setups.</p>

<h2>rn2.php.net (67.72.78.18)</h2>

<p><b>machine:</b> (same as rn1) P3-1133, 1.2GB RAM, 165GB HD</p>

<p><b>aliases:</b> bonsai, master, bugs</p>

<h2>sc1.php.net (66.225.196.49)</h2>

<p><b>machine:</b> 2xP4-2400 (HT), 2GB RAM, 155GB HD</p>

<p><b>aliases:</b> rsync</p>

<!-- this machine generates the online manuals. daily backups of
the cvs repository and the rsync modules are done. cricket also runs on this
machine to track the usage of all of the machines.</p> -->

<h2>ez1.php.net (128.39.198.38)</h2>

<p><b>machine:</b>Dell PE 650, 1GB RAM, 120GB disk</p>
<p><b>aliases:</b> snaps, museum </p>
<p><b>technical contact:</b> Ole Sigurd Nyvold Hansen (Ole.S.Hansen AT hit.no)</p>

<h2>sp1.php.net (69.28.246.234)</h2>

<p><b>machine:</b> Celeron 2.5GHz, 1GB RAM, 120GB HD</p>
<p><b>aliases:</b></p>
<p><b>technical contact:</b> Cameron Jones (cameron.jones AT spry.com)</p>

<p>originally compiled by jim winstead, september 2001</p>
<p>last update: $Id$</p>
<?php
foot();
