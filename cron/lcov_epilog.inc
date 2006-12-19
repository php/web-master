<?php
date_default_timezone_set('UTC');
$time = filemtime(__FILE__);
echo '<p align="center"><small>Generated at ' . date('r', $time) . ' (' . time_diff($time, true) . ' ago)</small></p>';

api_showfooter($appvars);
?>
