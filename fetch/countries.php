<?php

# token required, since this should only get accessed from rsync.php.net
if (!isset($token) || md5($token) != "19a3ec370affe2d899755f005e5cd90e")
  die("token not correct.");

// Connect and generate the list from the DB
if (@mysql_pconnect("localhost","nobody","")) {
  if (@mysql_select_db("php3")) {
    $res = @mysql_query("SELECT * FROM country ORDER BY name");
    if ($res) {
      echo "<?php\n\$COUNTRIES = array(\n";
      while ($row = @mysql_fetch_array($res)) {
        echo "'$row[id]' => '", addslashes($row['name']), "',\n";
      }
      echo ");\n";
    }
  }
}
