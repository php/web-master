<?php

// Current date in GMT
$date = gmdate("Y/m/d H:i:s");

// Info on the $MIRRORS array structure and some constants
$structinfo = "
/*
 Structure of an element of the $MIRRORS array:

  0  Country code
  1  Provider name
  2  Local stats flag (TRUE / FALSE)
  3  Provider URL
  4  Mirror type [see type constants]
  5  Local search engine flag (TRUE / FALSE)
  6  Default language code
  7  Status [see status constants]

 List generated: $date GMT
*/

// Mirror type constants
define('MIRROR_DOWNLOAD', 0);
define('MIRROR_STANDARD', 1);
define('MIRROR_SPECIAL',  2);
define('MIRROR_VIRTUAL',  3);

// Mirror status constants
define('MIRROR_OK',          0);
define('MIRROR_NOTACTIVE',   1);
define('MIRROR_OUTDATED',    2);
define('MIRROR_DOESNOTWORK', 3);

";

// A token is required, since this should only get accessed from rsync.php.net
if (!isset($token) || md5($token) != "19a3ec370affe2d899755f005e5cd90e") {
    die("token not correct.");
}

// Connect to local mysql database
if (@mysql_pconnect("localhost","nobody","")) {
  
    // Select php3 database
    if (@mysql_select_db("php3")) {
      
        // Select last mirror check time from table
        $lct = mysql_query("SELECT UNIX_TIMESTAMP(lastchecked) FROM mirrors ORDER BY lastchecked DESC LIMIT 1");
        list($checktime) = mysql_fetch_row($lct);

        // Select mirrors list with some on-the-fly counted columns
        $res = @mysql_query(
            "SELECT mirrors.*, country.name AS cname, " .
            "(DATE_SUB(FROM_UNIXTIME($checktime), INTERVAL 3 DAY) < mirrors.lastchecked) AS up, " .
            "(DATE_SUB(FROM_UNIXTIME($checktime), INTERVAL 7 DAY) < mirrors.lastupdated) AS current " .
            "FROM mirrors LEFT JOIN country ON mirrors.cc = country.id " .
            "ORDER BY country.name,hostname"
        );
        
        // If there is a mysql result
        if ($res) {
          
            // Start PHP script output
            echo "<?php$structinfo\$MIRRORS = array(\n";
            
            // Go through all result rows
            while ($row = @mysql_fetch_array($res)) {
              
                // Prepend http:// to hostname
                $row["hostname"] = "http://$row[hostname]/";
                
                // Rewrite the mirrortype to use defined constants
                switch ($row['mirrortype']) {
                    case MIRROR_DOWNLOAD : $row['mirrortype'] = 'MIRROR_DOWNLOAD'; break;
                    case MIRROR_STANDARD : $row['mirrortype'] = 'MIRROR_STANDARD'; break;
                    case MIRROR_SPECIAL  : $row['mirrortype'] = 'MIRROR_SPECIAL'; break;
                }

                // Rewrirte has_search and has_stats to be booleans
                $row["has_search"] = ($row["has_search"] ? 'TRUE' : 'FALSE');
                $row["has_stats"]  = ($row["has_stats"]  ? 'TRUE' : 'FALSE');

                // Presumably the mirror is all right
                $status = 'MIRROR_OK';

                // Provide status information for mirrors
                // computed from current mirror details
                if (!$row["active"]) {      $status = 'MIRROR_NOTACTIVE'; }
                elseif (!$row["up"]) {      $status = 'MIRROR_DOESNOTWORK'; }
                elseif (!$row["current"]) { $status = 'MIRROR_OUTDATED'; }
                
                // Print out the array element for this mirror
                echo "    \"$row[hostname]\" => array(\"$row[cc]\"," .
                     "\"$row[providername]\",$row[has_stats],\"$row[providerurl]\"" .
                     ",$row[mirrortype],$row[has_search],\"$row[lang]\",$status),\n";
            }
            echo ");\n";
            echo "?>\n";
        }
    }
}
