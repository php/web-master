<?php

/*
   The DNS table for mirror sites gets updated using the output of
   this script. If there is no output, or there is any line on the
   output which does not start with + or C (probably an error
   occured), the DNS database is not updated. The DNS list is
   fetched in approximately five minute intervals.
*/

// A token is required, since this should only get
// accessed from an authorized requester
if (!isset($_REQUEST['token']) || md5($_REQUEST['token']) != "19a3ec370affe2d899755f005e5cd90e") {
    die("token not correct.");
}

// Connect to local mysql database
if (@mysql_connect("localhost","nobody","")) {
  
    // Select phpmasterdb database
    if (@mysql_select_db("phpmasterdb")) {
      
        // Select mirrors list ordered by hostname
        $res = @mysql_query("SELECT * FROM mirrors ORDER BY hostname");
        
        // If there is a mysql result
        if ($res) {
          
            // Go through all mirrors
            while ($row = @mysql_fetch_array($res)) {
              
                // If the mirror is not a standard one, or
                // it's name is not a standard mirror name
                // under php.net => skip it
                if ($row['mirrortype'] != 1 || !preg_match("!^\\w{2}\\d?.php.net$!", $row['hostname'])) {
                    continue;
                }
                
                // The CNAME is an IP
                if (preg_match("!^\\d+\\.\\d+\\.\\d+\\.\\d+$!", $row['cname'])) {
                    $firstChar = '+';
                }
                
                // The CNAME is not an IP
                else { $firstChar = 'C'; }

                // Print out DNS update code
                echo $firstChar . $row['hostname'] . ':' . $row['cname'] . "\n";
                echo $firstChar . 'www.' . $row['hostname'] . ':' . $row['cname'] . "\n";
            }
        }
    }
}
