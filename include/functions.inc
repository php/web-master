<?php

function clean($var) {
  return htmlspecialchars(get_magic_quotes_gpc() ? stripslashes($var) : $var);
}

function warn($message) {
  echo "<p class=\"warning\">$message</p>";
}

function query($query) {
  $res = @mysql_query($query);
  if (!$res) {
    die("<p class=\"warning\">query failed: " . mysql_error() . "<tt>\n$query</tt></p>");
  }
  return $res;
}

function head($title="") {?>
<html>
<head>
 <title><?php echo $title;?></title>
 <link rel="stylesheet" type="text/css" href="/style.css" />
</head>
<?php
}

function foot() {
  echo "</body></html>";
}

function array_to_url($array,$overlay=0) {
  if ($overlay) $array = array_merge($array,$overlay);
  $out = "";
  while (list($k,$v) = each($array)) {
    $out .= "&amp;" . rawurlencode($k) . "=" . rawurlencode($v);
  }
  return substr($out,5); # skip first &amp;
}

function show_prev_next($begin,$rows,$skip,$total,$extra=array()) {
  global $PHP_SELF;?>
<table border="0" cellspacing="1" width="100%">
 <tr bgcolor="#eeeeee">
  <td align="left" width="33%">
   <?php 
     if ($begin > 0) {
       printf("<a href=\"%s?%s\">&laquo; Previous %d",
              $PHP_SELF,
              array_to_url($extra, array("begin" => max(0,$begin-$skip))),
              min($skip,$begin));
     }
   ?>
   &nbsp;
  </td>
  <td align="center" width="33%">
   <?php echo "Displaying ",$begin+1,"-",$begin+$rows," of $total";?>
  </td>
  <td align="right" width="33%">
   &nbsp;
   <?php 
     if ($begin+$rows < $total) {
       printf("<a href=\"%s?%s\">Next %d &raquo;",
              $PHP_SELF,
              array_to_url($extra, array("begin" => $begin+$skip)),
              min($skip,$total-($begin+$skip)));
     }
   ?>
  </td>
 </tr>
</table>
<?php
}

