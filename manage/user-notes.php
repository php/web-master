<?php
// $Id$

// Force login before action can be taken
include '../include/login.inc';
include '../include/email-validation.inc';
include '../include/note-reasons.inc';
//require_once 'alert_lib.inc'; // remove comment if alerts are needed

define("NOTES_MAIL", "php-notes@lists.php.net");
define("PHP_SELF", hsc($_SERVER['PHP_SELF']));

$reject_text =
'You are receiving this email because your note posted
to the online PHP manual has been removed by one of the editors.

Read the following paragraphs carefully, because they contain
pointers to resources better suited for requesting support or
reporting bugs, none of which are to be included in manual notes
because there are mechanisms and groups in place to deal with
those issues.

The user contributed notes are not an appropriate place to
ask questions, report bugs or suggest new features; please
use the resources listed on <http://php.net/support>
for those purposes. This was clearly stated in the page
you used to submit your note, please carefully re-read
those instructions before submitting future contributions.

Bug submissions and feature requests should be entered at
<http://bugs.php.net/>. For documentation errors use the
bug system, and classify the bug as "Documentation problem".
Support and ways to find answers to your questions can be found
at <http://php.net/support>.

Your note has been removed from the online manual.';

db_connect();

$action = (isset($_REQUEST['action']) ? preg_replace('/[^\w\d\s_]/', '', $_REQUEST['action']) : '');
$id = (isset($_REQUEST['id']) ? intval($_REQUEST['id']) : '');

/*------ BEGIN SEARCH ------*/
if (!$action) {
  head("user notes");
  
  // someting done before ?
  if ($id) {
    $str = 'Note #' . $id . ' has been ';
    switch ($_GET['was']) {
      case 'delete'    :
      case 'reject'    :
        $str .= ($_GET['was'] == 'delete') ? 'deleted' : 'rejected';
        $str .= ' and removed from the manual';
        break;
      case 'edit'        :
        $str .= ' edited';
        break;
      case 'resetall'    :
        $str .= ' reset to 0 votes.';
        break;
      case 'resetup'     :
        $str .= ' reset to 0 up votes.';
        break;
      case 'resetdown'   :
        $str .= ' reset to 0 down votes.';
        break;
      case 'deletevotes' :
        $str = 'The selected votes have been deleted!'; // INTENTIONALLY missing the concat operator
    }
    echo $str . '<br />';
  }
  
  if (isset($_REQUEST['keyword']) || isset($_REQUEST["view"])) {
    if(isset($_REQUEST['keyword'])) {
      $sql = 'SELECT SUM(votes.vote) AS up, (COUNT(votes.vote) - SUM(votes.vote)) AS down, note.*, UNIX_TIMESTAMP(note.ts) AS ts '.
             'FROM note '.
             'LEFT JOIN(votes) ON (note.id = votes.note_id) '.
             'WHERE ';
      if (is_numeric($_REQUEST['keyword'])) {
        $sql .= 'note.id = ' . (int) $_REQUEST['keyword'];
      } else {
        $sql .= 'note.note LIKE "%' . real_clean($_REQUEST['keyword']) . '%" GROUP BY note.id LIMIT 20';
      }
    } else {
      $page = isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 0;
      $NextPage = isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 0;
      $type = isset($_REQUEST["type"]) ? intval($_REQUEST["type"]) : 0;
      
      if($page < 0) { $page = 0; }
      if($NextPage < 0) { $NextPage = 0; }
      $limit = $page * 10; $page++;
      $limitVotes = $NextPage * 25; $NextPage++;
      $PrevPage = ($NextPage - 2) > -1 ? $NextPage - 2 : 0;
      
      /* Added new voting information to be included in note from votes table. */
      /* First notes */
      if ($type == 1) {
        $sql = "SELECT SUM(votes.vote) AS up, (COUNT(votes.vote) - SUM(votes.vote)) AS down, note.*, UNIX_TIMESTAMP(note.ts) AS ts ".
               "FROM note ".
               "LEFT JOIN(votes) ON (note.id = votes.note_id) ".
               "GROUP BY note.id ORDER BY note.id ASC LIMIT $limit, 10";
      /* Minor notes */
      } else if ($type == 2) {
        $sql = "SELECT SUM(votes.vote) AS up, (COUNT(votes.vote) - SUM(votes.vote)) AS down, note.*, UNIX_TIMESTAMP(note.ts) AS ts ".
               "FROM note ".
               "LEFT JOIN(votes) ON (note.id = votes.note_id) ".
               "GROUP BY note.id ORDER BY LENGTH(note.note) ASC LIMIT $limit, 10";
      /* Top rated notes */
      } else if ($type == 3) {
        $sql = "SELECT SUM(votes.vote) AS up, (COUNT(votes.vote) - SUM(votes.vote)) AS down, ".
               "(SUM(votes.vote) - (COUNT(votes.vote) - SUM(votes.vote))) AS rating, note.*, UNIX_TIMESTAMP(note.ts) AS ts ".
               "FROM note ".
               "JOIN(votes) ON (note.id = votes.note_id) ".
               "GROUP BY note.id ORDER BY rating DESC LIMIT $limit, 10";
      /* Bottom rated notes */
      } else if ($type == 4) {
        $sql = "SELECT SUM(votes.vote) AS up, (COUNT(votes.vote) - SUM(votes.vote)) AS down, ".
               "(SUM(votes.vote) - (COUNT(votes.vote) - SUM(votes.vote))) AS rating, note.*, UNIX_TIMESTAMP(note.ts) AS ts ".
               "FROM note ".
               "JOIN(votes) ON (note.id = votes.note_id) ".
               "GROUP BY note.id ORDER BY rating ASC LIMIT $limit, 10";
      /* Votes table view */
      } else if ($type == 5) {
        $search_votes = true; // set this only to change the output between votes table and notes table
        if (!empty($_GET['votessearch'])) {
          if (($iprange = wildcard_ip($_GET['votessearch'])) !== false) {
            $search = html_entity_decode($_GET['votessearch'], ENT_QUOTES, 'UTF-8');
            $start = $iprange[0]; $end = $iprange[1];
            $resultCount = db_query("SELECT count(votes.id) AS total_votes FROM votes JOIN (note) ON (votes.note_id = note.id) WHERE ".
                                    "(hostip >= $start AND hostip <= $end) OR (ip >= $start AND ip <= $end)");
            $resultCount = mysql_fetch_assoc($resultCount);
            $resultCount = $resultCount['total_votes'];
            $isSearch = '&votessearch=' . hscr($search);
            $sql = "SELECT votes.id, UNIX_TIMESTAMP(votes.ts) AS ts, votes.vote, votes.note_id, note.sect, votes.hostip, votes.ip ".
                   "FROM votes ".
                   "JOIN(note) ON (votes.note_id = note.id) ".
                   "WHERE (hostip >= $start AND hostip <= $end) OR (ip >= $start AND ip <= $end) ".
                   "ORDER BY votes.id DESC LIMIT $limitVotes, 25";
            
          } elseif (filter_var(html_entity_decode($_GET['votessearch'], ENT_QUOTES, 'UTF-8'), FILTER_VALIDATE_IP)) {
            $searchip = (int) ip2long(filter_var(html_entity_decode($_GET['votessearch'], ENT_QUOTES, 'UTF-8'), FILTER_VALIDATE_IP));
            $resultCount = db_query("SELECT count(votes.id) AS total_votes FROM votes JOIN(note) ON (votes.note_id = note.id) WHERE hostip = $searchip OR ip = $searchip");
            $resultCount = mysql_fetch_assoc($resultCount);
            $resultCount = $resultCount['total_votes'];
            $isSearch = '&votessearch=' . hscr(long2ip($searchip));
            $sql = "SELECT votes.id, UNIX_TIMESTAMP(votes.ts) AS ts, votes.vote, votes.note_id, note.sect, votes.hostip, votes.ip ".
                   "FROM votes ".
                   "JOIN(note) ON (votes.note_id = note.id) ".
                   "WHERE hostip = $searchip OR ip = $searchip ".
                   "ORDER BY votes.id DESC LIMIT $limitVotes, 25";
          } else {
            $search = (int) html_entity_decode($_GET['votessearch'], ENT_QUOTES, 'UTF-8');
            $resultCount = db_query("SELECT count(votes.id) AS total_votes FROM votes JOIN(note) ON (votes.note_id = note.id) WHERE votes.note_id = $search");
            $resultCount = mysql_fetch_assoc($resultCount);
            $resultCount = $resultCount['total_votes'];
            $isSearch = '&votessearch=' . hscr($search);
            $sql = "SELECT votes.id, UNIX_TIMESTAMP(votes.ts) AS ts, votes.vote, votes.note_id, note.sect, votes.hostip, votes.ip ".
                   "FROM votes ".
                   "JOIN(note) ON (votes.note_id = note.id) ".
                   "WHERE votes.note_id = $search ".
                   "ORDER BY votes.id DESC LIMIT $limitVotes, 25";
          }
        } else {
          $isSearch = null;
          $resultCount = db_query("SELECT COUNT(votes.id) AS total_votes FROM votes JOIN(note) ON (votes.note_id = note.id)");
          $resultCount = mysql_fetch_assoc($resultCount);
          $resultCount = $resultCount['total_votes'];
          $sql = "SELECT votes.id, UNIX_TIMESTAMP(votes.ts) AS ts, votes.vote, votes.note_id, note.sect, votes.hostip, votes.ip ".
                 "FROM votes ".
                 "JOIN(note) ON (votes.note_id = note.id) ".
                 "ORDER BY votes.id DESC LIMIT $limitVotes, 25";
        }
      /* Last notes */
      } else {
        $sql = "SELECT SUM(votes.vote) AS up, (COUNT(votes.vote) - SUM(votes.vote)) AS down, note.*, UNIX_TIMESTAMP(note.ts) AS ts ".
               "FROM note ".
               "LEFT JOIN(votes) ON (note.id = votes.note_id) ".
               "GROUP BY note.id ORDER BY note.id DESC LIMIT $limit, 10";
      }
    }
    
    if ($result = db_query($sql)) {
      /* This is a special table only used for viewing the most recent votes */
      if (!empty($search_votes)) {
        $t = (isset($_GET['type']) ? '&type=' . $_GET['type'] : null);
        $from = $limitVotes + 1;
        $to = $NextPage * 25;
        $to = $to > $resultCount ? $resultCount : $to;
        if ($resultCount) {
          echo "<p><strong>Showing $from - $to of $resultCount results.</strong></p>";
          echo "<form method=\"POST\" action=\"" . PHP_SELF . "?action=deletevotes{$t}\" id=\"votesdeleteform\">".
               "<table width=\"100%\">".
               "  <thead>".
               "    <tr style=\"text-align: center; background-color: #99C; font-size: 18px;\">\n".
               "      <td  colspan=\"7\" width=\"100%\" style=\"padding: 5px;\"><strong>Most Recent Votes</strong></td>\n".
               "    </tr>\n".
               "    <tr style=\"background-color: #99C; 18px;\">\n".
               "      <td style=\"padding: 5px;\"><input type=\"checkbox\" id=\"votesselectall\" /></td>
                      <td style=\"padding: 5px;\"><strong>Date</strong></td>
                      <td style=\"padding: 5px;\"><strong>Vote</strong></td>
                      <td style=\"padding: 5px;\"><strong>Note ID</strong></td>
                      <td style=\"padding: 5px;\"><strong>Note Section</strong></td>
                      <td style=\"padding: 5px;\"><strong>Host IP</strong></td>
                      <td style=\"padding: 5px;\"><strong>Client IP</strong></td>\n".
               "    </tr>\n".
               "  </thead>\n".
               "  <tbody>\n";
        } else {
          echo "<p><strong>No results found...</strong></p>";
        }
      }
      while ($row = mysql_fetch_assoc($result)) {
        /*
           I had to do this because the JOIN queries will return a single row of NULL values even when no rows match.
           So the `if (mysql_num_rows($result))` check earlier becomes useless and as such I had to replace it with this.
        */
        if (mysql_num_rows($result) == 1 && !array_filter($row)) {
          echo "<p>No results found...</p>";
          continue;
        }
        $id = $row['id'];
        /* This div is only available in cases where the query includes the voting info */
        if (isset($row['up']) && isset($row['down'])) {
          $rating = $row['up'] - $row['down'];
          if ($rating < 0) {
            $rating = "<span style=\"color: red;\">$rating</span>";
          } elseif ($rating > 0) {
            $rating = "<span style=\"color: green;\">$rating</span>";
          } else {
            $rating = "<span style=\"color: blue;\">$rating</span>";
          }
          $percentage = sprintf('%d%%',((($row['up'] + $row['down']) ? $row['up'] / ($row['up'] + $row['down']) : 0) * 100));
          echo "<div style=\"float: right; clear: both; border: 1px solid gray; padding: 5px; background-color: lightgray;\">\n".
               "<div style=\"display: inline-block; float: left; padding: 15px;\"><strong>Up votes</strong>: {$row['up']}</div>\n".
               "<div style=\"display: inline-block; float: left; padding: 15px;\"><strong>Down votes</strong>: {$row['down']}</div>\n".
               "<div style=\"display: inline-block; float: left; padding: 15px;\"><strong>Rating</strong>: $rating (<em>$percentage like this</em>)</div>\n".
               " <div style=\"padding: 15px;\">\n".
               "  <a href=\"?action=resetall&id={$id}\">Reset all votes</a> |".
               "  <a href=\"?action=resetup&id={$id}\">Reset up votes</a> |".
               "  <a href=\"?action=resetdown&id={$id}\">Reset down votes</a>\n".
               " </div>\n".
               "</div>\n";
        }
        /* This is a special table only used for viewing the most recent votes */
        if (!empty($search_votes)) {
          $row['ts'] = date('Y-m-d H:i:s', $row['ts']);
          $row['vote'] = '<span style="color: ' . ($row['vote'] ? 'green;">+1' : 'red;">-1') . '</span>';
          $row['hostip'] = long2ip($row['hostip']);
          $row['ip'] = long2ip($row['ip']);
          $notelink = "http://php.net/{$row['sect']}#{$row['note_id']}";
          $sectlink = "http://php.net/{$row['sect']}";
          echo "    <tr style=\"background-color: #F0F0F0;\">\n".
               "      <td style=\"padding: 5px;\"><input type=\"checkbox\" name=\"deletevote[]\" class=\"vdelids\" value=\"{$row['id']}\" /></td>\n".
               "      <td style=\"padding: 5px;\">{$row['ts']}</td>\n".
               "      <td style=\"padding: 5px;\">{$row['vote']}</td>\n".
               "      <td style=\"padding: 5px;\"><a href=\"$notelink\" target=\"_blank\">{$row['note_id']}</a></td>\n".
               "      <td style=\"padding: 5px;\"><a href=\"$sectlink\" target=\"_blank\">{$row['sect']}</a></td>\n".
               "      <td style=\"padding: 5px;\">{$row['hostip']}</td>\n".
               "      <td style=\"padding: 5px;\">{$row['ip']}</td>\n".
               "    </tr>\n";
        /* Everything else in search should fall through here */
        } else {
          echo "<p class=\"notepreview\">",clean_note($row['note']),
               "<br /><span class=\"author\">",date("d-M-Y h:i",$row['ts'])," ",
               hscr($row['user']),"</span><br />",
               "Note id: $id<br />\n",
               "<a href=\"http://php.net/manual/en/{$row['sect']}.php#{$id}\" target=\"_blank\">http://php.net/manual/en/{$row['sect']}.php#{$id}</a><br />\n",
               "<a href=\"https://master.php.net/note/edit/$id\" target=\"_blank\">Edit Note</a><br />";
          foreach ($note_del_reasons AS $reason => $text) {
            echo '<a href="https://master.php.net/note/delete/', $id, '/', urlencode($reason), '" target=\"_blank\">', 'Delete Note: ', hscr($text), "</a><br />\n";
          }
          echo "<a href=\"https://master.php.net/note/delete/$id\" target=\"_blank\">Delete Note: other reason</a><br />",
               "<a href=\"https://master.php.net/note/reject/$id\" target=\"_blank\">Reject Note</a>",
               "</p>",
               "<hr />";
        }
      }
      /* This is a special table only used for viewing the most recent votes */
      if (!empty($search_votes)) {
        if ($resultCount) {
          echo "  </tbody>\n".
               "</table>\n".
               "<input type=\"submit\" name=\"deletevotes\" value=\"Delete Selected Votes\" />\n".
               "<input type=\"hidden\" name=\"votessearch\" value=\"".
               (isset($_GET['votessearch']) ? hscr($_GET['votessearch']) : '').
               "\" />".
               "</form>\n";
        }
        echo "<form method=\"GET\" action=\"" . PHP_SELF . "\">\n".
             "  <strong>Search for votes by IP address or Note ID</strong> - (<em>wild card searches are allowed e.g. 127.0.0.*</em>): ".
             "<input type=\"text\" name=\"votessearch\" value=\"".
             (isset($_GET['votessearch']) ? hscr($_GET['votessearch']) : '').
             "\" /> <input type=\"submit\" value=\"Search\" />\n".
             "<input type=\"hidden\" name=\"view\" value=\"notes\" />\n".
             "<input type=\"hidden\" name=\"type\" value=\"" . (isset($_GET['type']) ? hscr($_GET['type']) : 5) . "\" />\n".
             "</form>\n";
      }
      if(isset($_REQUEST["view"]) && empty($search_votes)) {
        echo "<p><a href=\"?view=1&page=$page&type=$type\">Next 10</a>";
      } elseif (isset($_REQUEST["view"]) && !empty($search_votes)) {
        echo "<p>";
        if (isset($NextPage) && $NextPage > 1) {
          echo "<a href=\"?view=1&page=$PrevPage&type=$type{$isSearch}\">&lt; Prev 25</a> ";
        }
        if (isset($to) && isset($resultCount) && $to < $resultCount) {
          echo " <a href=\"?view=1&page=$NextPage&type=$type{$isSearch}\">Next 25 &gt;</a>";
        }
        echo "</p>";
      }
    }
  }
  if (empty($_SERVER['QUERY_STRING'])) {
    /* Calculate dates */
    $today = strtotime('midnight');
    $week = !date('w') ? strtotime('midnight') : strtotime('Last Sunday');
    $month = strtotime('First Day of ' . date('F') . ' ' . date('Y'));
    $yesterday = strtotime('midnight yesterday');
    $lastweek = !date('w') ? strtotime('midnight -1 week') : strtotime('Last Sunday -1 week');
    $lastmonth = strtotime('First Day of last month');
    /* Handle stats queries for voting here */
    $stats_sql = $stats = array();
    $stats_sql['Total']       = "SELECT COUNT(votes.id) AS total FROM votes";
    $stats_sql['Total Up']    = "SELECT COUNT(votes.id) AS total FROM votes WHERE votes.vote = 1";
    $stats_sql['Total Down']  = "SELECT COUNT(votes.id) AS total FROM votes WHERE votes.vote = 0";
    $stats_sql['Today']       = "SELECT COUNT(votes.id) AS total FROM votes WHERE UNIX_TIMESTAMP(votes.ts) >= " . real_clean($today);
    $stats_sql['This Week']   = "SELECT COUNT(votes.id) AS total FROM votes WHERE UNIX_TIMESTAMP(votes.ts) >= " . real_clean($week);
    $stats_sql['This Month']  = "SELECT COUNT(votes.id) AS total FROM votes WHERE UNIX_TIMESTAMP(votes.ts) >= " . real_clean($month);
    $stats_sql['Yesterday']   = "SELECT COUNT(votes.id) AS total FROM votes WHERE UNIX_TIMESTAMP(votes.ts) >= " . real_clean($yesterday) . " AND UNIX_TIMESTAMP(votes.ts) < " . real_clean($today);
    $stats_sql['Last Week']   = "SELECT COUNT(votes.id) AS total FROM votes WHERE UNIX_TIMESTAMP(votes.ts) >= " . real_clean($lastweek) . " AND UNIX_TIMESTAMP(votes.ts) < " . real_clean($week);
    $stats_sql['Last Month']  = "SELECT COUNT(votes.id) AS total FROM votes WHERE UNIX_TIMESTAMP(votes.ts) >= " . real_clean($lastmonth) . " AND UNIX_TIMESTAMP(votes.ts) < " . real_clean($month);
    foreach ($stats_sql as $key => $sql_code) {
      $result = db_query($sql_code);
      $row = mysql_fetch_assoc($result);
      $stats[$key] = $row['total'];
    }
    /* Display the stats on the front page only */
?>
<div style="float: right; clear: both; border: 1px solid gray; padding: 5px; background-color: #C8C8C0;">
  <center><p><span style="color: #8A2BE2; font-size: 18px;"><strong>User Contributed Voting Statistics</strong></span></p></center>
  <?php foreach (array_chunk($stats, 3, true) as $statset) { ?>
  <?php foreach ($statset as $figure => $stat) { ?>
  <div style="display: inline-block; float: left; padding: 15px; border-bottom: 1px solid white; color: #483D8B;"><strong><?= $figure ?></strong>: <?= $stat ?></div>
  <?php } ?>
  <p>&nbsp;</p>
  <?php } ?>
</div>
<?php
  }
?>
<p>Search the notes table.</p>
<form method="post" action="<?= PHP_SELF ?>">
<table>
 <tr>   
  <th align="right">Keyword or ID:</th>
  <td><input type="text" name="keyword" value="<?php echo (isset($_REQUEST['keyword']) ? clean($_REQUEST['keyword']) : ''); ?>" size="10" maxlength="32" /></td>
 </tr>
 <tr> 
  <td align="center" colspan="2">
    <input type="submit" value="Search" />
  </td>
 </tr>
</table>
</form>

<p><a href="<?= PHP_SELF ?>?action=mass">Mass change of sections</a></p>
<p><a href="<?= PHP_SELF ?>?view=notes&type=0">View last 10 notes</a></p>
<p><a href="<?= PHP_SELF ?>?view=notes&type=1">View first 10 notes</a></p>
<p><a href="<?= PHP_SELF ?>?view=notes&type=2">View minor 10 notes</a></p>
<p><a href="<?= PHP_SELF ?>?view=notes&type=3">View top 10 rated notes</a></p>
<p><a href="<?= PHP_SELF ?>?view=notes&type=4">View bottom 10 rated notes</a></p>
<p><a href="<?= PHP_SELF ?>?view=notes&type=5">View votes table</a></p>
<?php
  foot();
  exit;
}
/*------ END SEARCH ------*/


if (preg_match("/^(.+)\\s+(\\d+)\$/", $action, $m)) {
  $action = $m[1]; $id = $m[2];
}
/* hack around the rewrite rules */
if (isset($_GET['action']) && ($_GET['action'] == 'resetall' || $_GET['action'] == 'resetup' || $_GET['action'] == 'resetdown' || $_GET['action'] == 'deletevotes')) {
  $action = $_GET['action'];
  $id = isset($_GET['id']) ? $_GET['id'] : null;
}

switch($action) {
case 'mass':
  if (!allow_mass_change($user)) { die("You are not allowed to take this action!"); }
  head("user notes");
  $step = (isset($_REQUEST["step"]) ? $_REQUEST["step"] : 0);
  $where = array();
  if (!empty($_REQUEST["old_sect"])) {
    $where[] = "sect = '". real_clean($_REQUEST["old_sect"]) ."'";
  }
  if (!empty($_REQUEST["ids"])) {
    if (preg_match('~^([0-9]+, *)*[0-9]+$~i', $_REQUEST["ids"])) {
      $where[] = "id IN ($_REQUEST[ids])";
    } else {
      echo "<p><b>Incorrect format of notes IDs.</b></p>\n";
      $step = 0;
    }
  }
  
  if ($step == 2) {
    db_query("UPDATE note SET sect = '". real_clean($_REQUEST["new_sect"]) ."' WHERE " . implode(" AND ", $where));
    echo "<p>Mass change succeeded.</p>\n";
  } elseif ($step == 1) {
    if (!empty($_REQUEST["new_sect"]) && $where) {
      $result = db_query("SELECT COUNT(*) FROM note WHERE " . implode(" AND ", $where));
      if (!($count = mysql_result($result, 0, 0))) {
        echo "<p>There are no such notes.</p>\n";
      } else {
        $step = 2;
        $msg = "Are you sure to change section of <b>$count note(s)</b>";
        $msg .= (!empty($_REQUEST["ids"]) ? " with IDs <b>" . clean($_REQUEST['ids']) . "</b>" : "");
        $msg .= (!empty($_REQUEST["old_sect"]) ? " from section <b>" . clean($_REQUEST['old_sect']) . "</b>" : "");
        $msg .= " to section <b>" . clean($_REQUEST['new_sect']) . "</b>?";
        echo "<p>$msg</p>\n";
?>
<form action="<?= PHP_SELF; ?>?action=mass" method="post">
<input type="hidden" name="step" value="2">
<input type="hidden" name="old_sect" value="<?= clean($_REQUEST["old_sect"]); ?>">
<input type="hidden" name="ids" value="<?= clean($_REQUEST["ids"]); ?>">
<input type="hidden" name="new_sect" value="<?= clean($_REQUEST["new_sect"]); ?>">
<input type="submit" value="Change">
</form>
<?php
      }
    } else {
      if (empty($_REQUEST["new_sect"])) {
        echo "<p><b>You have to fill-in new section.</b></p>\n";
      }
      if (!$where) {
        echo "<p><b>You have to fill-in curent section or notes IDs (or both).</b></p>\n";
      }
    }
  }
  if ($step < 2) {
?>
<form action="<?= PHP_SELF; ?>?action=mass" method="post">
<input type="hidden" name="step" value="1">
<p>Change section of notes which fit these criteria:</p>
<table>
 <tr>
  <th align="right">Current section:</th>
  <td><input type="text" name="old_sect" value="<?= clean($_REQUEST["old_sect"]); ?>" size="30" maxlength="80" /> (filename without extension)</td>
 </tr>
 <tr>
  <th align="right">Notes IDs:</th>
  <td><input type="text" name="ids" value="<?= clean($_REQUEST["ids"]); ?>" size="30" maxlength="80" /> (comma separated list)</td>
 </tr>
 <tr>
  <th align="right">Move to section:</th>
  <td><input type="text" name="new_sect" value="<?= clean($_REQUEST["new_sect"]); ?>" size="30" maxlength="80" /></td>
 </tr>
 <tr> 
  <td align="center" colspan="2">
    <input type="submit" value="Change" />
  </td>
 </tr>
</table>
</form>
<?php
  }
  echo "<p><a href='", PHP_SELF, "'>Back to notes index</a></p>\n";
  foot();
  exit;
case 'approve':
  if ($id) {
    if ($row = note_get_by_id($id)) {
      
      if ($row['status'] != 'na') {
        die ("Note #$id has already been approved");
      }
      
      if ($row['id'] && db_query("UPDATE note SET status=NULL WHERE id=$id")) {
        note_mail_on_action(
            $user,
            $id,
            "note {$row['id']} approved from {$row['sect']} by $user",
            "This note has been approved and will appear in the manual.\n\n----\n\n{$row['note']}"
        );
      }
      
      print "Note #$id has been approved and will appear in the manual";
      exit;
    }
  }
case 'reject':
case 'delete':
  if ($id) {
    if ($row = note_get_by_id($id)) {
      if ($row['id'] && db_query("DELETE note,votes FROM note LEFT JOIN (votes) ON (note.id = votes.note_id) WHERE note.id = $id")) {
        // ** alerts **
        //$mailto .= get_emails_for_sect($row["sect"]);
        $action_taken = ($action == "reject" ? "rejected" : "deleted");
        note_mail_on_action(
            $user,
            $id,
            "note {$row['id']} $action_taken from {$row['sect']} by $user",
            "Note Submitter: " . safe_email($row['user']) . 
        (isset($reason) ? "\nReason: $reason" : " ") .
        "\n\n----\n\n{$row['note']}");
        if ($action == 'reject') {
          note_mail_user($row['user'], "note $row[id] rejected and deleted from $row[sect] by notes editor $user",$reject_text."\n\n----- Copy of your note below -----\n\n".$row['note']);
        }
      }
      
      //if we came from an email, report _something_
      if (isset($_GET['report'])) {
        header('Location: user-notes.php?id=' . $id . '&was=' . $action);
        exit;
      } else {
        //if not, just close the window
        echo '<script language="javascript">window.close();</script>';
      }
      exit;
    }
  }
  /* falls through, with id not set. */
case 'preview':
case 'edit':
  if ($id) {
    $note = (isset($_POST['note']) ? $_POST['note'] : null);
    if (!isset($note) || $action == 'preview') {
      head("user notes");
    }

    $row = note_get_by_id($id);

    $email = (isset($_POST['email']) ? real_clean(html_entity_decode($_POST['email'],ENT_QUOTES)) : real_clean($row['user']));
    $sect = (isset($_POST['sect']) ? real_clean(html_entity_decode($_POST['sect'],ENT_QUOTES)) : real_clean($row['sect']));

    if (isset($note) && $action == "edit") {
      if (db_query("UPDATE note SET note='".real_clean(html_entity_decode($note,ENT_QUOTES))."',user='$email',sect='$sect',updated=NOW() WHERE id=$id")) {

        // ** alerts **
        //$mailto .= get_emails_for_sect($row["sect"]);
        note_mail_on_action(
            $user,
            $id,
            "note {$row['id']} modified in {$row['sect']} by $user",
            strip($note)."\n\n--was--\n{$row['note']}\n\nhttp://php.net/manual/en/{$row['sect']}.php"
        );
        if (real_clean($row["sect"]) != $sect) {
          note_mail_user($email, "note $id moved from $row[sect] to $sect by notes editor $user", "----- Copy of your note below -----\n\n".strip($note));
        }
        header('Location: user-notes.php?id=' . $id . '&was=' . $action);
        exit;
      }
    }

    $note = isset($note) ? $note : $row['note'];

    if ($action == "preview") {
      echo "<p class=\"notepreview\">",clean_note(strip($note)),
           "<br /><span class=\"author\">",date("d-M-Y h:i",$row['ts'])," ",
           clean(strip($email)),"</span></p>";
    }
?>
<form method="post" action="<?= PHP_SELF ?>">
<input type="hidden" name="id" value="<?= $id ?>" />
<table>
 <tr>
  <th align="right">Section:</th>
  <td><input type="text" name="sect" value="<?= clean($sect) ?>" size="30" maxlength="80" /></td>
 </tr>
 <tr>
  <th align="right">email:</th>
  <td><input type="text" name="email" value="<?= clean($email) ?>" size="30" maxlength="80" /></td>
 </tr>
 <tr>
  <td colspan="2"><textarea name="note" cols="70" rows="15"><?= clean($note) ?></textarea></td>
 </tr>
 <tr>
  <td align="center" colspan="2">
    <input type="submit" name="action" value="edit" />
    <input type="submit" name="action" value="preview" />
  </td>
 </tr>
</table>
</form>
<?php
    foot();
    exit;
  }
case 'resetall':
case 'resetup':
case 'resetdown':
  /* Only those with privileges in allow_mass_change may use these options */
  if (!allow_mass_change($user)) {
    die("You do not have access to use this feature!");
  }
  /* Reset votes for user note -- effectively deletes votes found for that note_id in the votes table:  up/down/both */
  head('user notes');
  if ($id) {
    if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
      if ($action == 'resetall' && isset($_POST['resetall'])) {
        $sql = 'DELETE FROM votes WHERE votes.note_id = ' . real_clean($id);
      /* 1 for up votes */
      } elseif ($action == 'resetup' && isset($_POST['resetup'])) {
        $sql = 'DELETE FROM votes WHERE votes.note_id = ' . real_clean($id) . ' AND votes.vote = 1';
      /* 0 for down votes */
      } elseif ($action == 'resetdown' && isset($_POST['resetdown'])) {
        $sql = 'DELETE FROM votes WHERE votes.note_id = ' . real_clean($id) . ' AND votes.vote = 0';
      }
      /* Make sure the note has votes before we attempt to delete them */
      $result = db_query("SELECT COUNT(id) AS id FROM votes WHERE note_id = " . real_clean($id));
      $rows = mysql_fetch_assoc($result);
      if (!$rows['id']) {
        echo "<p>No votes exist for Note ID ".hscr($id)."!</p>";
      } elseif (db_query($sql)) {
        header('Location: user-notes.php?id=' . urlencode($id) . '&was=' . urlencode($action));
      }
    } else {
      $sql = 'SELECT SUM(votes.vote) AS up, (COUNT(votes.vote) - SUM(votes.vote)) AS down, note.*, UNIX_TIMESTAMP(note.ts) AS ts '.
             'FROM note '.
             'JOIN(votes) ON (note.id = votes.note_id) '.
             'WHERE note.id = ' . real_clean($id);
      $result = db_query($sql);
      if (mysql_num_rows($result)) {
        $row = mysql_fetch_assoc($result);
        $out = "<p>\nAre you sure you want to reset all votes for <strong>Note #".hscr($row['id'])."</strong>? ";
        if ($action == 'resetall') {
          $out .= "This will permanently delete all <em>".hscr($row['up'])."</em> up votes and <em>".hscr($row['down'])."</em> down votes for this note.\n</p>\n".
                  "<form method=\"POST\" action=\"\">\n".
                  "  <input type=\"submit\" value\"Yes Reset!\" name=\"resetall\" />\n".
                  "</form>\n";
        } elseif ($action == 'resetup') {
          $out .= "This will permanently delete all <em>".hscr($row['up'])."</em> up votes for this note.\n</p>\n".
                  "<form method=\"POST\" action=\"\">\n".
                  "  <input type=\"submit\" value\"Yes Reset!\" name=\"resetup\" />\n".
                  "</form>\n";
        } elseif ($action == 'resetdown') {
          $out .= "This will permanently delete all <em>".hscr($row['down'])."</em> down votes for this note.\n</p>\n".
                  "<form method=\"POST\" action=\"\">\n".
                  "  <input type=\"submit\" value\"Yes Reset!\" name=\"resetdown\" />\n".
                  "</form>\n";
        }
        echo $out;
      } else {
        echo "<p>Note ".hscr($id)." does not exist!</p>";
      }
    }
  } else {
    echo "<p>Note id not supplied...</p>";
  }
  foot();
  exit;
case 'deletevotes':
  /* Only those with privileges in allow_mass_change may use these options */
  if (!allow_mass_change($user)) {
    die("You do not have access to use this feature!");
  }
  /* Delete votes -- effectively deletes votes found in the votes table matching all supplied ids */
  if (empty($_POST['deletevote']) || !is_array($_POST['deletevote'])) {
    die("No vote ids supplied!");
  }
  $ids = array();
  foreach ($_POST['deletevote'] as $id) {
    $ids[] = (int) $id;
  }
  $ids = implode(',',$ids);
  if (db_query("DELETE FROM votes WHERE id IN ($ids)")) {
    header('Location: user-notes.php?id=1&view=notes&was=' . urlencode($action) .
           (isset($_REQUEST['type']) ? ('&type=' . urlencode($_REQUEST['type'])) : null) .
           (isset($_REQUEST['votessearch']) ? '&votessearch=' . urlencode($_REQUEST['votessearch']) : null)
          );
  }
  exit;
  /* falls through */
default:
  head('user notes');
  echo "<p>'$action' is not a recognized action, or no id was specified.</p>";
  foot();
}

// ----------------------------------------------------------------------------------

// Use class names instead of colors
ini_set('highlight.comment', 'comment');
ini_set('highlight.default', 'default');
ini_set('highlight.keyword', 'keyword');
ini_set('highlight.string',  'string');
ini_set('highlight.html',    'html');

// Copied over from phpweb (should be syncronised if changed)
function clean_note($text)
{
    // Highlight PHP source
    $text = highlight_php(trim($text), TRUE);

    // Turn urls into links
    $text = preg_replace(
        '!((mailto:|(http|ftp|nntp|news):\/\/).*?)(\s|<|\)|"|\\|\'|$)!',
        '<a href="\1" target="_blank">\1</a>\4',
        $text
    );
    
    return $text;
}

// Highlight PHP code
function highlight_php($code, $return = FALSE)
{
    // Using OB, as highlight_string() only supports
    // returning the result from 4.2.0
    ob_start();
    highlight_string($code);
    $highlighted = ob_get_contents();
    ob_end_clean();
    
    // Fix output to use CSS classes and wrap well
    $highlighted = '<div class="phpcode">' . str_replace(
        array(
            '&nbsp;',
            '<br />',
            '<font color="',
            '</font>',
            "\n ",
            '  '
        ),
        array(
            ' ',
            "<br />\n",
            '<span class="',
            '</span>',
            "\n&nbsp;",
            '&nbsp; '
        ),
        $highlighted
    ) . '</div>';
    
    if ($return) { return $highlighted; }
    else { echo $highlighted; }
}

// Send out a mail to the note submitter, with an envelope sender ignoring bounces
function note_mail_user($mailto, $subject, $message)
{
    $mailto = clean_antispam($mailto);
    if (is_emailable_address($mailto)) {
        mail(
            $mailto,
            $subject,
            $message,
            "From: ". NOTES_MAIL,
            "-fbounces-ignored@php.net -O DeliveryMode=b"
        );
    }
}

// Return data about a note by its ID
function note_get_by_id($id)
{
    if ($result = db_query("SELECT *, UNIX_TIMESTAMP(ts) AS ts FROM note WHERE id='$id'")) {
        if (!mysql_num_rows($result)) {
            die("Note #$id doesn't exist. It has probably been deleted/rejected already.");
        }
        return mysql_fetch_assoc($result);
    }
    return FALSE;
}

// Sends out a notification to the mailing list when
// some action is performed on a user note.
function note_mail_on_action($user, $id, $subject, $body)
{
    mail(NOTES_MAIL, $subject, $body, "From: $user@php.net\r\nIn-Reply-To: <note-$id@php.net>", "-f{$user}@php.net");
}

// Allow some users to mass change IDs in the manual
function allow_mass_change($user)
{
    if (in_array(
            $user,
            array(
                "vrana", "goba", "nlopess", "didou", "bjori", "philip", "bobby", "danbrown", "mgdm", "googleguy",
            )
        )
    ) {
        return TRUE;
    } else { return FALSE; }
}

// Return safe to print version of email address
function safe_email($mail)
{
    if (in_array($mail, array("php-general@lists.php.net", "user@example.com"))) {
        return '';
    }
    elseif (preg_match("!(.+)@(.+)\.(.+)!", $mail)) {
        return str_replace(array('@', '.'), array(' at ', ' dot '), $mail);
    }
    return $mail;
}

// Return a valid IPv4 range (as 0-indexed two element array) based on wildcard IP string
function wildcard_ip($ip)
{
    $start = explode(".", $ip);
    if (count($start) != 4) {
        return false;
    }
    foreach ($start as $part) {
        if ($part === "*") {
            continue;
        }
        if ($part > 255 || $part < 0 || !is_numeric($part)) {
            return false;
        }
    }
    $end = array();
    foreach (array_keys($start, "*", true) as $key) {
        $start[$key] = "0";
        $end[$key] = "255";
    }
    foreach ($start as $key => $part) {
        if (!isset($end[$key])) {
            $end[$key] = $start[$key];
        }
    }
    ksort($end);
    $start = ip2long(implode('.',$start));
    $end = ip2long(implode('.',$end));
    if ($end - $start <= 0) {
      return false;
    }
    return array($start, $end);
}
