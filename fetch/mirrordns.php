<?php

// A token is required, since this should only get
// accessed from an authorized requester
if (!isset($token) || md5($token) != "19a3ec370affe2d899755f005e5cd90e") {
    die("token not correct.");
}

// Connect to local mysql database
if (@mysql_pconnect("localhost","nobody","")) {
  
    // Select php3 database
    if (@mysql_select_db("php3")) {
      
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
                    echo '+' . $row['hostname'] . ':' . $row['cname'] . "\n";
                    echo '+www.' . $row['hostname'] . ':' . $row['cname'] . "\n";
                }
                
                // The CNAME is not an IP
                else {
                    echo 'C' . $row['hostname'] . ':' . $row['cname'] . "\n";
                    echo 'Cwww.' . $row['hostname'] . ':' . $row['cname'] . "\n";
                }
            }
        }
    }
}
