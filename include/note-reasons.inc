<?php
/* $Id: */

// stock reasons for deleting notes
$note_del_reasons = [
  'integrated',
  'useless',
  'bad code',
  'spam',
  'non-english',
  'in docs',
];
$note_del_reasons_pad = 0;
foreach ($note_del_reasons AS $r) {
  if (($l = strlen($r)) > $note_del_reasons_pad) {
    $note_del_reasons_pad = $l;
  }
}
++$note_del_reasons_pad;
?>
