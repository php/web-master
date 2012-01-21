<?php

# token required, since this should only get accessed from rsync.php.net
if (!isset($_REQUEST['token']) || md5($_REQUEST['token']) != "19a3ec370affe2d899755f005e5cd90e")
  die("token not correct.");

// Connect and generate the list from the DB
if (@mysql_connect("localhost","nobody","")) {
  if (@mysql_select_db("phpmasterdb")) {
    $res = @mysql_query("SELECT * FROM country ORDER BY name");
    if ($res) {
      echo "<?php\nif(!\$APC || (\$APC && !\$COUNTRIES=apc_fetch('countries'))) {\n\$COUNTRIES = array(\n";
      while ($row = @mysql_fetch_array($res)) {
        echo "'$row[id]' => '", addslashes($row['name']), "',\n";
      }
      echo ");if(\$APC) apc_store('countries',\$COUNTRIES);\n}\n";
    }
  }
}
