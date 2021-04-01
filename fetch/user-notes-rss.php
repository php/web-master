<?php

require_once __DIR__ . '/../include/functions.inc';

db_connect();

if (isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] <= 1000) {
  $limit = $_GET['limit'];
} else {
  $limit = 100;
}

$query = new Query('SELECT DISTINCT id,sect,user,note,UNIX_TIMESTAMP(ts) AS ts FROM note');
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
  $query->add(" WHERE id = ?", [$_GET['id']]);
} elseif (isset($_GET['section'])) {
  $query->add(" WHERE sect LIKE ");
  $sect = explode(',', $_GET['section']);
  for ($i=0; $i<count($sect) - 1; $i++) {
    $query->add('? OR sect LIKE ', [strtr($sect[$i],'*','%')]);
  }
  $query->add('?', [strtr($sect[count($sect) - 1],'*','%')]);
}
$query->add(" ORDER BY sect,ts DESC");
$query->add(" LIMIT ?", [$limit]);

$res = db_query($query);

$notes = [];
while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
  $notes[$row['id']] = $row;
}

header('Content-type: text/xml');
?>
<?php echo "<?";?>xml version="1.0" encoding="ISO-8859-1"?>
<rdf:RDF
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns="http://purl.org/rss/1.0/"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
>
  <channel rdf:about="http://php.net/">
  <title>PHP Manual User Notes</title>
  <link>https://main.php.net/manage/user-notes.php</link>
  <description/>
  <items>
    <?php if ($notes) { ?>
    <rdf:Seq>
      <rdf:li rdf:resource="https://main.php.net/note/edit/<?php
      echo implode('"/> <rdf:li rdf:resource="https://main.php.net/note/edit/',
        array_keys($notes));?>"/>
    </rdf:Seq>
    <?php } ?>
  </items>
  </channel>
  <image rdf:about="http://php.net/images/php.gif">
    <title>PHP Manual User Notes</title>
    <url>http://php.net/images/php.gif</url>
    <link>https://main.php.net/manage/user-notes.php</link>
  </image>
<?php
foreach ($notes as $note) {
  ?>
  <item>
    <title><?php echo htmlspecialchars(substr($note['note'], 0, 40));
      echo strlen($note['note']) < 40 ? '...' : ''; ?></title>
    <link>https://main.php.net/note/edit/<?php echo $note['id']; ?></link>
    <description>
      <![CDATA[
      <?php echo htmlspecialchars($note['note']); ?>
      ]]>
    </description>
    <dc:date><?php echo date('Y-m-d', $note['ts']);?></dc:date>
    <dc:time><?php echo date('H:i:s', $note['ts']);?></dc:time>
    <dc:creator><?php echo htmlspecialchars(preg_replace('/.@./','*@*',$note['user'])); ?></dc:creator>
    <dc:subject><?php echo htmlspecialchars($note['sect']); ?></dc:subject>
  </item>
  <?php
}
?>
</rdf:RDF>
