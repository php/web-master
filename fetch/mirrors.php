<?php

// Info on the $MIRRORS array structure
$structinfo = "
/* Structure of an element of the $MIRRORS array:
  0  Country code
  1  Provider name
  2  Local stats flag (1/0)
  3  Provider URL
  4  Mirror type (1 - standard, 2 - special, 0 - download)
  5  Local search engine flag (1/0)
  6  Default language code
*/

";

// A token is required, since this should only get accessed from rsync.php.net
if (!isset($token) || md5($token) != "19a3ec370affe2d899755f005e5cd90e") {
    die("token not correct.");
}

// Connect to local mysql database
if (@mysql_pconnect("localhost","nobody","")) {
  
    // Select php3 database
    if (@mysql_select_db("php3")) {
      
        // Select mirrors list with some on-the-fly counted columns
        $res = @mysql_query(
            "SELECT mirrors.*, country.name AS cname, " .
            "(DATE_SUB(NOW(),INTERVAL 3 DAY) < mirrors.lastchecked) AS up, " .
            "(DATE_SUB(NOW(),INTERVAL 7 DAY) < mirrors.lastupdated) AS current " .
            "FROM mirrors LEFT JOIN country ON mirrors.cc = country.id " .
            "ORDER BY country.name,hostname"
        );
        
        // If there is a mysql result
        if ($res) {
          
            // Start PHP script output
            echo "<?php$structinfo\$MIRRORS = array(\n";
            
            // Go through all result rows
            while ($row = @mysql_fetch_array($res)) {
              
                // Prepend http:// to hostname, if it is not there
                if (!strstr("http:", $row["hostname"])) {
                    $row["hostname"] = "http://$row[hostname]/";
                }
                
                // We don't have any comment for general mirrors
                $comment = '';
                
                // Set inactive mirrors to type 2 so they won't show up in the drop-down,
                // and provide information in comment for diagnostical purposes
                if (!$row["active"]) {
                    $row["mirrortype"] = 2;
                    $comment = '// not active';
                } elseif (!$row["current"]) {
                    $row["mirrortype"] = 2;
                    $comment = '// not up to date';
                } elseif (!$row["up"]) {
                    $row["mirrortype"] = 2;
                    $comment = '// does not seem to work';
                }
                
                // Print out the array element for this mirror
                echo "    \"$row[hostname]\" => array(\"$row[cc]\"," .
                     "\"$row[providername]\",$row[has_stats],\"$row[providerurl]\"" .
                     ",$row[mirrortype],$row[has_search],\"$row[lang]\"),$comment\n";
            }
            echo '    0 => array("xx", "Unknown", 0, "/", 2, 0, "en")', "\n";
            echo ");\n";
            echo "?>\n";
        }
    }
}
