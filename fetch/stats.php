<?php
// $Id$

include_once 'functions.inc';

require_token();

if (db_connect(FALSE)) {
   echo db_get_one("SELECT COUNT(*) FROM php3.note"), "\n";
   echo db_get_one("SELECT COUNT(*) FROM phphosts.hosts"), "\n";
   echo db_get_one("SELECT COUNT(*) FROM php3.bugdb"), "\n";
   echo db_get_one("SELECT COUNT(*) FROM php3.bugdb WHERE status IN ('Open', 'Assigned', 'Analyzed', 'Critical')"), "\n";
}
