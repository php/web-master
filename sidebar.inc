<table class='sidebartoc' width="100%" cellpadding="2" cellspacing="0" border="0"><!-- sidebar -->
<tr valign="top"><td class="sidebartoc">
<ul id="sidebartoc">
<li class="header home"><a href="/">PHP&nbsp;GCOV</a></li>
<?php
foreach($tags as $tag)
{
	$cls = ($tag == $mytag) ? " class='active'" : "";
	echo "<li$cls><a href='/$tag'>$tag</a></li>\n";
}
?>
</ul></td></tr></table><!-- sidebar -->
