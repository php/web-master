<?php
// List all current supported flags.
// $Id$
include "../jpgraph.php";
include "../jpgraph_flags.php";

// Flag size to use in table 
$s = FLAGSIZE2 ;
$w = 60;
$flags = new FlagImages($s) ;

// Create a nice table wil all flags and their full name (and index)
echo "<table width=100%><tr>\n";
$cols=0;
while( list($key,$val) = each($flags->iCountryNameMap) ) {

    echo '<td width=20%><a href="javascript:window.open(\'listallflags_helper.php?size=4&idx='.$val.'\',\'_new\',\'width=500,height=350\');void(0)"><img src="listallflags_helper.php?size='.$s.'&idx='.$val.'"></a><br>';
    echo "<small>$key</small><br><small><font color=blue><i>idx=$val</i></font></small></td>\n";
    
    if( ++$cols == 5 ) {
	echo "</tr>\n<tr>";
	$cols=0;
    }
}

echo "</tr></table>";

?>

