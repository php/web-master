<?php
require 'functions.inc';

# token required, since this should only get accessed from rsync.php.net
if (!isset($token) || md5($token) != "19a3ec370affe2d899755f005e5cd90e")
  die("token not correct.");

// Connect and generate the list from the DB
if (@mysql_pconnect("localhost","nobody","")) {
   echo mysql_get_one("SELECT COUNT(*) FROM php3.note"), "\n";
   echo mysql_get_one("SELECT COUNT(*) FROM phphosts.hosts"), "\n";
   echo mysql_get_one("SELECT COUNT(*) FROM php3.bugdb"), "\n";
   echo mysql_get_one("SELECT COUNT(*) FROM php3.bugdb WHERE status='Open' OR status='Assigned' OR status='Analyzed' OR status='Critical'"), "\n";
}
