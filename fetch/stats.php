<?php
// $Id$

include_once '../include/functions.inc';

require_token();

if (db_connect(FALSE)) {
   echo db_get_one("SELECT COUNT(*) FROM phpmasterdb.note"), "\n";
   echo db_get_one("SELECT COUNT(*) FROM phphosts.hosts"), "\n";
   echo db_get_one("SELECT COUNT(*) FROM phpmasterdb.bugdb"), "\n";
   echo db_get_one("SELECT COUNT(*) FROM phpmasterdb.bugdb WHERE status IN ('Open', 'Assigned', 'Analyzed', 'Critical')"), "\n";
}
