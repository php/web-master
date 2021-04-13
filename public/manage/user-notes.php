<?php

use App\Query;

require __DIR__ . '/../../vendor/autoload.php';

// Force login before action can be taken
include __DIR__ . '/../../include/login.inc';
include __DIR__ . '/../../include/email-validation.inc';
include __DIR__ . '/../../include/note-reasons.inc';

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
use the resources listed on <https://php.net/support>
for those purposes. This was clearly stated in the page
you used to submit your note, please carefully re-read
those instructions before submitting future contributions.

Bug submissions and feature requests should be entered at
<https://bugs.php.net/>. For documentation errors use the
bug system, and classify the bug as "Documentation problem".
Support and ways to find answers to your questions can be found
at <https://php.net/support>.

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
    // Pagination start
    $page = isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 0;
    $NextPage = isset($_REQUEST["page"]) ? intval($_REQUEST["page"]) : 0;
    $type = isset($_REQUEST["type"]) ? intval($_REQUEST["type"]) : 0;

    if($page < 0) { $page = 0; }
    if($NextPage < 0) { $NextPage = 0; }
    $limit = $page * 10; $page++;
    $limitVotes = $NextPage * 25; $NextPage++;
    $PrevPage = ($NextPage - 2) > -1 ? $NextPage - 2 : 0;
    // Pagination end

    if(isset($_REQUEST['keyword'])) {
      $query = new Query('SELECT SUM(votes.vote) AS up, (COUNT(votes.vote) - SUM(votes.vote)) AS down, note.*, UNIX_TIMESTAMP(note.ts) AS ts '.
             'FROM note '.
             'LEFT JOIN(votes) ON (note.id = votes.note_id) '.
             'WHERE ');
      if (is_numeric($_REQUEST['keyword'])) {
        $search_heading = 'Search results for #' . (int) $_REQUEST['keyword'];
        $query->add('note.id = ?', [$_REQUEST['keyword']]);
      } elseif (substr($_REQUEST['keyword'], 0, 5) == 'sect:') {
        $search_heading = 'Search results for <em>' . hsc($_REQUEST['keyword']) . '</em>';
        $section = str_replace('*', '%', substr($_REQUEST['keyword'], 5));
        $query->add("note.sect LIKE ? GROUP BY note.id ORDER BY note.sect, note.ts LIMIT ?int, 10", [$section, $limit]);
      } else {
        $search_heading = 'Search results for <em>' . hsc($_REQUEST['keyword']) . '</em>';
        $query->add(
          "note.note LIKE ? GROUP BY note.id LIMIT ?int, 10",
          ['%' . $_REQUEST['keyword'] . '%', $limit]);
      }
      $result = db_query_safe($query->get());
    } else {
      /* Added new voting information to be included in note from votes table. */
      /* First notes */
      if ($type == 1) {
        $search_heading = 'First notes';
        $result = db_query_safe("SELECT SUM(votes.vote) AS up, (COUNT(votes.vote) - SUM(votes.vote)) AS down, note.*, UNIX_TIMESTAMP(note.ts) AS ts ".
               "FROM note ".
               "LEFT JOIN(votes) ON (note.id = votes.note_id) ".
               "GROUP BY note.id ORDER BY note.id ASC LIMIT ?int, 10", [$limit]);
      /* Minor notes */
      } else if ($type == 2) {
        $search_heading = 'Minor notes';
        $result = db_query_safe("SELECT SUM(votes.vote) AS up, (COUNT(votes.vote) - SUM(votes.vote)) AS down, note.*, UNIX_TIMESTAMP(note.ts) AS ts ".
               "FROM note ".
               "LEFT JOIN(votes) ON (note.id = votes.note_id) ".
               "GROUP BY note.id ORDER BY LENGTH(note.note) ASC LIMIT ?int, 10", [$limit]);
      /* Top rated notes */
      } else if ($type == 3) {
        $search_heading = 'Top rated notes';
        $result = db_query_safe("SELECT SUM(votes.vote) AS up, (COUNT(votes.vote) - SUM(votes.vote)) AS down, ".
               "ROUND((SUM(votes.vote) / COUNT(votes.vote)) * 100) AS rate, ".
               "(SUM(votes.vote) - (COUNT(votes.vote) - SUM(votes.vote))) AS arating, ".
               "note.id, note.sect, note.user, note.note, UNIX_TIMESTAMP(note.ts) AS ts ".
               "FROM note ".
               "JOIN(votes) ON (note.id = votes.note_id) ".
               "GROUP BY note.id ORDER BY arating DESC, up DESC, rate DESC, down DESC LIMIT ?int, 10", [$limit]);
      /* Bottom rated notes */
      } else if ($type == 4) {
        $search_heading = 'Bottom rated notes';
        $result = db_query_safe("SELECT SUM(votes.vote) AS up, (COUNT(votes.vote) - SUM(votes.vote)) AS down, ".
               "ROUND((SUM(votes.vote) / COUNT(votes.vote)) * 100) AS rate, ".
               "(SUM(votes.vote) - (COUNT(votes.vote) - SUM(votes.vote))) AS arating, ".
               "note.id, note.sect, note.user, note.note, UNIX_TIMESTAMP(note.ts) AS ts ".
               "FROM note ".
               "JOIN(votes) ON (note.id = votes.note_id) ".
               "GROUP BY note.id ORDER BY arating ASC, up ASC, rate ASC, down DESC LIMIT ?int, 10", [$limit]);
      /* Votes table view */
      } else if ($type == 5) {
        $search_votes = true; // set this only to change the output between votes table and notes table
        if (!empty($_GET['votessearch'])) {
          if (($iprange = wildcard_ip($_GET['votessearch'])) !== false) {
            $search = html_entity_decode($_GET['votessearch'], ENT_QUOTES, 'UTF-8');
            $start = $iprange[0];
            $end = $iprange[1];
            $resultCount = db_query_safe("SELECT count(votes.id) AS total_votes FROM votes JOIN (note) ON (votes.note_id = note.id) WHERE ".
                                    "(hostip >= ? AND hostip <= ?) OR (ip >= ? AND ip <= ?)", [$start, $end, $start, $end]);
            $resultCount = mysql_fetch_assoc($resultCount);
            $resultCount = $resultCount['total_votes'];
            $isSearch = '&votessearch=' . hsc($search);
            $result = db_query_safe(
              'SELECT votes.id, UNIX_TIMESTAMP(votes.ts) AS ts, votes.vote, votes.note_id, note.sect, votes.hostip, votes.ip '.
              'FROM votes JOIN(note) ON (votes.note_id = note.id) '.
              'WHERE (hostip >= ? AND hostip <= ?) OR (ip >= ? AND ip <= ?) '.
              'ORDER BY votes.id DESC LIMIT ?int, 25',
              [$start, $end, $start, $end, $limitVotes]);

          } elseif (filter_var(html_entity_decode($_GET['votessearch'], ENT_QUOTES, 'UTF-8'), FILTER_VALIDATE_IP)) {
            $searchip = (int) ip2long(filter_var(html_entity_decode($_GET['votessearch'], ENT_QUOTES, 'UTF-8'), FILTER_VALIDATE_IP));
            $resultCount = db_query_safe("SELECT count(votes.id) AS total_votes FROM votes JOIN(note) ON (votes.note_id = note.id) WHERE hostip = ? OR ip = ?", [$searchip, $searchip]);
            $resultCount = mysql_fetch_assoc($resultCount);
            $resultCount = $resultCount['total_votes'];
            $isSearch = '&votessearch=' . hsc(long2ip($searchip));
            $result = db_query_safe(
              "SELECT votes.id, UNIX_TIMESTAMP(votes.ts) AS ts, votes.vote, votes.note_id, note.sect, votes.hostip, votes.ip ".
              "FROM votes JOIN(note) ON (votes.note_id = note.id) ".
              "WHERE hostip = ? OR ip = ? ".
              "ORDER BY votes.id DESC LIMIT ?int, 25",
              [$searchip, $searchip, $limitVotes]);
          } else {
            $search = (int) html_entity_decode($_GET['votessearch'], ENT_QUOTES, 'UTF-8');
            $resultCount = db_query_safe("SELECT count(votes.id) AS total_votes FROM votes JOIN(note) ON (votes.note_id = note.id) WHERE votes.note_id = ?", [$search]);
            $resultCount = mysql_fetch_assoc($resultCount);
            $resultCount = $resultCount['total_votes'];
            $isSearch = '&votessearch=' . hsc($search);
            $result = db_query_safe(
              "SELECT votes.id, UNIX_TIMESTAMP(votes.ts) AS ts, votes.vote, votes.note_id, note.sect, votes.hostip, votes.ip ".
              "FROM votes JOIN(note) ON (votes.note_id = note.id) ".
              "WHERE votes.note_id = ? ".
              "ORDER BY votes.id DESC LIMIT ?int, 25",
              [$search, $limitVotes]);
          }
        } else {
          $isSearch = null;
          $resultCount = db_query_safe("SELECT COUNT(votes.id) AS total_votes FROM votes JOIN(note) ON (votes.note_id = note.id)");
          $resultCount = mysql_fetch_assoc($resultCount);
          $resultCount = $resultCount['total_votes'];
          $result = db_query_safe(
            "SELECT votes.id, UNIX_TIMESTAMP(votes.ts) AS ts, votes.vote, votes.note_id, note.sect, votes.hostip, votes.ip ".
            "FROM votes JOIN(note) ON (votes.note_id = note.id) ".
            "ORDER BY votes.id DESC LIMIT ?int, 25",
            [$limitVotes]);
        }
      /* IPs with the most votes -- aggregated data */
      } elseif ($type == 6) {
        $votes_by_ip = true; // only set this get the table for top IPs with votes
        $result = db_query_safe(
          "SELECT DISTINCT(votes.ip), COUNT(votes.ip) as votes, COUNT(DISTINCT(votes.note_id)) as notes, ".
          "INET_NTOA(votes.ip) AS ip, MIN(UNIX_TIMESTAMP(votes.ts)) AS `from`, MAX(UNIX_TIMESTAMP(votes.ts)) AS `to` ".
          "FROM votes ".
          "JOIN (note) ON (votes.note_id = note.id) GROUP BY votes.ip ORDER BY votes DESC LIMIT 100");
      /* Last notes */
      } else {
        $search_heading = 'Last notes';
        $result = db_query_safe(
          "SELECT SUM(votes.vote) AS up, (COUNT(votes.vote) - SUM(votes.vote)) AS down, note.*, UNIX_TIMESTAMP(note.ts) AS ts ".
          "FROM note LEFT JOIN(votes) ON (note.id = votes.note_id) ".
          "GROUP BY note.id ORDER BY note.id DESC LIMIT ?int, 10",
          [$limit]);
      }
    }

    if ($result) {
      /* This is a special table only used for viewing the most recent votes */
        $t = (isset($_GET['type']) ? '&type=' . $_GET['type'] : null);
      if (!empty($search_votes)) {
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
      /* This is a special table only used for viewing top IPs by votes */
      if (!empty($votes_by_ip)) {
        echo "<form method=\"POST\" action=\"" . PHP_SELF . "?action=deletevotes{$t}\" id=\"votesdeleteform\">".
             "<table width=\"100%\">".
             "  <thead>".
             "    <tr style=\"text-align: center; background-color: #99C; font-size: 18px;\">\n".
             "      <td  colspan=\"5\" width=\"100%\" style=\"padding: 5px;\"><strong>IPs With Most Votes</strong></td>\n".
             "    </tr>\n".
             "    <tr style=\"background-color: #99C; 18px;\">\n".
             "      <td style=\"padding: 5px;\"><strong>Client IP Address</strong></td>
                    <td style=\"padding: 5px;\"><strong>Number of Votes</strong></td>
                    <td style=\"padding: 5px;\"><strong>Number of Notes</strong></td>
                    <td style=\"padding: 5px;\"><strong>First Vote Cast</strong></td>
                    <td style=\"padding: 5px;\"><strong>Last Vote Cast</strong></td>\n".
             "    </tr>\n".
             "  </thead>\n".
             "  <tbody>\n";
      }
      if (!empty($search_heading)) {
          echo "<h2>$search_heading</h2>";
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
        $id = $row['id'] ?? null;
        /* This div is only available in cases where the query includes the voting info */
        if (isset($row['up']) && isset($row['down'])) {
          $rating = $row['arating'] ?? ($row['up'] - $row['down']);
          if ($rating < 0) {
            $rating = "<span style=\"color: red;\">$rating</span>";
          } elseif ($rating > 0) {
            $rating = "<span style=\"color: green;\">$rating</span>";
          } else {
            $rating = "<span style=\"color: blue;\">$rating</span>";
          }

          if (isset($row['rate'])) { // not all queries select the rate
            $percentage = $row['rate'];
          } else {
            if ($row['up'] + $row['down']) { // prevents division by zero warning
              $percentage = round(($row['up'] / ($row['up'] +$row['down'])) * 100);
            } else {
              $precentage = 0;
            }
          }
          $percentage = sprintf('%d%%', $percentage);

          echo "<div style=\"float: right; clear: both; border: 1px solid gray; padding: 5px; background-color: lightgray;\">\n".
               "<div style=\"display: inline-block; float: left; padding: 15px;\"><strong>Up votes</strong>: {$row['up']}</div>\n".
               "<div style=\"display: inline-block; float: left; padding: 15px;\"><strong>Down votes</strong>: {$row['down']}</div>\n".
               "<div style=\"display: inline-block; float: left; padding: 15px;\"><strong>Rating</strong>: $rating (<em>$percentage like this</em>)</div>\n".
               " <div style=\"padding: 15px;\">\n".
               "  <a href=\"?action=resetall&id={$id}\">Reset all votes</a> |".
               "  <a href=\"?action=resetup&id={$id}\">Reset up votes</a> |".
               "  <a href=\"?action=resetdown&id={$id}\">Reset down votes</a> |".
               "  <a href=\"?votessearch={$id}&view=notes&type=5\">See Votes</a>\n".
               " </div>\n".
               "</div>\n";
        }
        /* This is a special table only used for viewing the most recent votes */
        if (!empty($search_votes)) {
          $row['ts'] = date('Y-m-d H:i:s', $row['ts']);
          $row['vote'] = '<span style="color: ' . ($row['vote'] ? 'green;">+1' : 'red;">-1') . '</span>';
          $row['hostip'] = long2ip($row['hostip']);
          $row['ip'] = long2ip($row['ip']);
          $notelink = "https://php.net/{$row['sect']}#{$row['note_id']}";
          $sectlink = "https://php.net/{$row['sect']}";
          echo "    <tr style=\"background-color: #F0F0F0;\">\n".
               "      <td style=\"padding: 5px;\"><input type=\"checkbox\" name=\"deletevote[]\" class=\"vdelids\" value=\"{$row['id']}\" /></td>\n".
               "      <td style=\"padding: 5px;\">{$row['ts']}</td>\n".
               "      <td style=\"padding: 5px;\">{$row['vote']}</td>\n".
               "      <td style=\"padding: 5px;\"><a href=\"$notelink\" target=\"_blank\">{$row['note_id']}</a></td>\n".
               "      <td style=\"padding: 5px;\"><a href=\"$sectlink\" target=\"_blank\">{$row['sect']}</a></td>\n".
               "      <td style=\"padding: 5px;\">{$row['hostip']}</td>\n".
               "      <td style=\"padding: 5px;\">{$row['ip']}</td>\n".
               "    </tr>\n";
        /* This is a special table only used for viewing top IPs by votes */
        } elseif(!empty($votes_by_ip)) {
          $from = date('Y-m-d H:i:s', $row['from']);
          $to = date('Y-m-d H:i:s', $row['to']);
          $ip = hsc($row['ip']);
          echo "    <tr style=\"background-color: #F0F0F0;\">\n".
               "      <td style=\"padding: 5px;\"><a href=\"?view=votes&type=5&votessearch=$ip\">$ip</a></td>\n".
               "      <td style=\"padding: 5px;\">{$row['votes']}</td>\n".
               "      <td style=\"padding: 5px;\">{$row['notes']}</td>\n".
               "      <td style=\"padding: 5px;\">{$from}</td>\n".
               "      <td style=\"padding: 5px;\">{$to}</td>\n".
               "    </tr>\n";
        /* Everything else in search should fall through here */
        } else {
          echo "<p class=\"notepreview\">",clean_note($row['note']),
               "<br /><span class=\"author\">",date("d-M-Y h:i",$row['ts'])," ",
          hsc($row['user']),"</span><br />",
               "Note id: $id<br />\n",
               "<a href=\"https://php.net/manual/en/{$row['sect']}.php#{$id}\" target=\"_blank\">http://php.net/manual/en/{$row['sect']}.php#{$id}</a><br />\n",
               "<a href=\"https://main.php.net/note/edit/$id\" target=\"_blank\">Edit Note</a><br />";
          foreach ($note_del_reasons AS $reason => $text) {
            echo '<a href="https://main.php.net/note/delete/', $id, '/', urlencode((string)$reason), '" target=\"_blank\">', 'Delete Note: ', hsc($text), "</a><br />\n";
          }
          echo "<a href=\"https://main.php.net/note/delete/$id\" target=\"_blank\">Delete Note: other reason</a><br />",
               "<a href=\"https://main.php.net/note/reject/$id\" target=\"_blank\">Reject Note</a>",
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
               (isset($_GET['votessearch']) ? hsc($_GET['votessearch']) : '').
               "\" />".
               "</form>\n";
        }
        echo "<form method=\"GET\" action=\"" . PHP_SELF . "\">\n".
             "  <strong>Search for votes by IP address or Note ID</strong> - (<em>wild card searches are allowed e.g. 127.0.0.*</em>): ".
             "<input type=\"text\" name=\"votessearch\" value=\"".
             (isset($_GET['votessearch']) ? hsc($_GET['votessearch']) : '').
             "\" /> <input type=\"submit\" value=\"Search\" />\n".
             "<input type=\"hidden\" name=\"view\" value=\"notes\" />\n".
             "<input type=\"hidden\" name=\"type\" value=\"" . (isset($_GET['type']) ? hsc($_GET['type']) : 5) . "\" />\n".
             "</form>\n";
      }
      /* This is a special table only used for viewing top IPs by votes */
      if (!empty($votes_by_ip)) {
        echo "  </tbody>\n".
             "</table>\n".
             "<p>This information should only be used to determine if there are any IP addresses with an unusually high ".
             "number of votes placed in a small timeframe to help detect spam and other potential abuse.</p>\n".
             "<p>Also note that a <em>0.0.0.0</em> IP address indicates a client IP could not be resolved at the time of voting.</p>";
      }
      if((isset($_REQUEST["view"]) || isset($_REQUEST['keyword'])) && empty($search_votes)) {
        $keyword = isset($_REQUEST['keyword']) ? '&keyword=' . urlencode($_REQUEST['keyword']) : '';
        echo "<p><a href=\"?view=notes&page=$page&type=$type$keyword\">Next 10</a>";
      } elseif (isset($_REQUEST["view"]) && !empty($search_votes)) {
        echo "<p>";
        if (isset($NextPage) && $NextPage > 1) {
          echo "<a href=\"?view=notes&page=$PrevPage&type=$type{$isSearch}\">&lt; Prev 25</a> ";
        }
        if (isset($to) && isset($resultCount) && $to < $resultCount) {
          echo " <a href=\"?view=notes&page=$NextPage&type=$type{$isSearch}\">Next 25 &gt;</a>";
        }
        echo "</p>";
      }
    }
  }
?>

<h2>Menu</h2>
<?php if (allow_mass_change($cuser)): ?><p><a href="<?= PHP_SELF ?>?action=mass">Mass change of sections</a></p><?php endif; ?>
<p><a href="<?= PHP_SELF ?>?view=notes&type=0">View last 10 notes</a></p>
<p><a href="<?= PHP_SELF ?>?view=notes&type=1">View first 10 notes</a></p>
<p><a href="<?= PHP_SELF ?>?view=notes&type=2">View minor 10 notes</a></p>
<p><a href="<?= PHP_SELF ?>?view=notes&type=3">View top 10 rated notes</a></p>
<p><a href="<?= PHP_SELF ?>?view=notes&type=4">View bottom 10 rated notes</a></p>
<p><a href="<?= PHP_SELF ?>?view=notes&type=5">View votes table</a></p>
<p><a href="<?= PHP_SELF ?>?view=notes&type=6">IPs with the most votes</a></p>
<p><a href="<?= PHP_SELF ?>?action=sect">Search notes within a section</a></p>
<p><a href="<?= PHP_SELF ?>?action=voting_stats">User contributed voting statistics</a></p>
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
  $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
}

switch($action) {
case 'mass':
  head("user notes");
  if (!allow_mass_change($cuser)) {
    warn("You are not allowed to take this action!");
    foot();
    exit;
  }
  $step = (isset($_REQUEST["step"]) ? (int)$_REQUEST["step"] : 0);
  $where = new Query();
  if (!empty($_REQUEST["old_sect"])) {
    $where->add("sect = ?", [$_REQUEST["old_sect"]]);
  }
  if (!empty($_REQUEST["ids"])) {
    if (preg_match('~^([0-9]+, *)*[0-9]+$~i', $_REQUEST["ids"])) {
      if ($where->get() !== '') {
        $where->add(' AND ');
      }
      // Safe because we checked that ids is a comma-separated list of numbers.
      $where->add("id IN (".$_REQUEST['ids'].")");
    } else {
      echo "<p><b>Incorrect format of notes IDs.</b></p>\n";
      $step = 0;
    }
  }

  if ($step == 2) {
    $query = new Query('UPDATE note SET sect = ? WHERE ', [$_REQUEST["new_sect"]]);
    $query->addQuery($where);
    db_query($query);
    echo "<p>Mass change succeeded.</p>\n";
  } elseif ($step == 1) {
    if (!empty($_REQUEST["new_sect"]) && $where) {
      $query = new Query('SELECT COUNT(*) FROM note WHERE ');
      $query->addQuery($where);
      $result = db_query($query);
      if (!($count = mysql_result($result, 0, 0))) {
        echo "<p>There are no such notes.</p>\n";
      } else {
        $step = 2;
        $msg = "Are you sure to change section of <b>$count note(s)</b>";
        $msg .= (!empty($_REQUEST["ids"]) ? " with IDs <b>" . hsc($_REQUEST['ids']) . "</b>" : "");
        $msg .= (!empty($_REQUEST["old_sect"]) ? " from section <b>" . hsc($_REQUEST['old_sect']) . "</b>" : "");
        $msg .= " to section <b>" . hsc($_REQUEST['new_sect']) . "</b>?";
        echo "<p>$msg</p>\n";
?>
<form action="<?= PHP_SELF; ?>?action=mass" method="post">
<input type="hidden" name="step" value="2">
<input type="hidden" name="old_sect" value="<?= hsc($_REQUEST["old_sect"]); ?>">
<input type="hidden" name="ids" value="<?= hsc($_REQUEST["ids"]); ?>">
<input type="hidden" name="new_sect" value="<?= hsc($_REQUEST["new_sect"]); ?>">
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
  <td><input type="text" name="old_sect" value="<?= hsc($_REQUEST["old_sect"]); ?>" size="30" maxlength="80" /> (filename without extension)</td>
 </tr>
 <tr>
  <th align="right">Notes IDs:</th>
  <td><input type="text" name="ids" value="<?= hsc($_REQUEST["ids"]); ?>" size="30" maxlength="80" /> (comma separated list)</td>
 </tr>
 <tr>
  <th align="right">Move to section:</th>
  <td><input type="text" name="new_sect" value="<?= hsc($_REQUEST["new_sect"]); ?>" size="30" maxlength="80" /></td>
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

      if ($row['id'] && db_query_safe("UPDATE note SET status=NULL WHERE id=?", [$id])) {
        note_mail_on_action(
            $cuser,
            $id,
            "note {$row['id']} approved from {$row['sect']} by $cuser",
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
      if ($row['id'] && db_query_safe("DELETE note,votes FROM note LEFT JOIN (votes) ON (note.id = votes.note_id) WHERE note.id = ?", [$id])) {
        $action_taken = ($action == "reject" ? "rejected" : "deleted");
        note_mail_on_action(
            $cuser,
            $id,
            "note {$row['id']} $action_taken from {$row['sect']} by $cuser",
            "Note Submitter: " . safe_email($row['user']) .
        (isset($reason) ? "\nReason: $reason" : " ") .
        "\n\n----\n\n{$row['note']}");
        if ($action == 'reject') {
          note_mail_user($row['user'], "note $row[id] rejected and deleted from $row[sect] by notes editor $cuser",$reject_text."\n\n----- Copy of your note below -----\n\n".$row['note']);
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
    $note = $_POST['note'] ?? null;
    if (!isset($note) || $action == 'preview') {
      head("user notes");
    }

    $row = note_get_by_id($id);

    $email = (isset($_POST['email']) ? html_entity_decode($_POST['email'],ENT_QUOTES) : $row['user']);
    $sect = (isset($_POST['sect']) ? html_entity_decode($_POST['sect'],ENT_QUOTES) : $row['sect']);

    if (isset($note) && $action == "edit") {
      if (db_query_safe('UPDATE note SET note=?,user=?,sect=?,updated=NOW() WHERE id=?', [html_entity_decode($note,ENT_QUOTES), $email, $sect, $id])) {
        note_mail_on_action(
            $cuser,
            $id,
            "note {$row['id']} modified in {$row['sect']} by $cuser",
            $note."\n\n--was--\n{$row['note']}\n\nhttps://php.net/manual/en/{$row['sect']}.php"
        );
        if ($row["sect"] != $sect) {
          note_mail_user($email, "note $id moved from $row[sect] to $sect by notes editor $cuser", "----- Copy of your note below -----\n\n".$note);
        }
        header('Location: user-notes.php?id=' . $id . '&was=' . $action);
        exit;
      }
    }

    $note = $note ?? $row['note'];

    if ($action == "preview") {
      echo "<p class=\"notepreview\">",clean_note($note),
           "<br /><span class=\"author\">",date("d-M-Y h:i",$row['ts'])," ",
      hsc($email),"</span></p>";
    }
?>
<form method="post" action="<?= PHP_SELF ?>">
<input type="hidden" name="id" value="<?= $id ?>" />
<table>
 <tr>
  <th align="right">Section:</th>
  <td><input type="text" name="sect" value="<?= hsc($sect) ?>" size="30" maxlength="80" /></td>
 </tr>
 <tr>
  <th align="right">email:</th>
  <td><input type="text" name="email" value="<?= hsc($email) ?>" size="30" maxlength="80" /></td>
 </tr>
 <tr>
  <td colspan="2"><textarea name="note" cols="70" rows="15"><?= hsc($note) ?></textarea></td>
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
  head('user notes');
  if (!allow_mass_change($cuser)) {
    warn("You do not have access to use this feature!");
    foot();
    exit;
  }
  /* Reset votes for user note -- effectively deletes votes found for that note_id in the votes table:  up/down/both */
  if ($id) {
    if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
      /* Make sure the note has votes before we attempt to delete them */
      $result = db_query_safe("SELECT COUNT(id) AS id FROM votes WHERE note_id = ?", [$id]);
      $rows = mysql_fetch_assoc($result);
      if (!$rows['id']) {
        echo "<p>No votes exist for Note ID ". hsc($id) ."!</p>";
      } else {
        if ($action == 'resetall' && isset($_POST['resetall'])) {
          db_query_safe('DELETE FROM votes WHERE votes.note_id = ?', [$id]);
          /* 1 for up votes */
        } elseif ($action == 'resetup' && isset($_POST['resetup'])) {
          db_query_safe('DELETE FROM votes WHERE votes.note_id = ? AND votes.vote = 1', [$id]);
          /* 0 for down votes */
        } elseif ($action == 'resetdown' && isset($_POST['resetdown'])) {
          db_query_safe('DELETE FROM votes WHERE votes.note_id = ? AND votes.vote = 0', [$id]);
        }
        header('Location: user-notes.php?id=' . urlencode($id) . '&was=' . urlencode($action));
      }
    } else {
      $sql = 'SELECT SUM(votes.vote) AS up, (COUNT(votes.vote) - SUM(votes.vote)) AS down, note.*, UNIX_TIMESTAMP(note.ts) AS ts '.
             'FROM note '.
             'JOIN(votes) ON (note.id = votes.note_id) '.
             'WHERE note.id = ?';
      $result = db_query_safe($sql, [$id]);
      if (mysql_num_rows($result)) {
        $row = mysql_fetch_assoc($result);
        $out = "<p>\nAre you sure you want to reset all votes for <strong>Note #". hsc($row['id']) ."</strong>? ";
        if ($action == 'resetall') {
          $out .= "This will permanently delete all <em>". hsc($row['up']) ."</em> up votes and <em>". hsc($row['down']) ."</em> down votes for this note.\n</p>\n".
                  "<form method=\"POST\" action=\"\">\n".
                  "  <input type=\"submit\" value\"Yes Reset!\" name=\"resetall\" />\n".
                  "</form>\n";
        } elseif ($action == 'resetup') {
          $out .= "This will permanently delete all <em>". hsc($row['up']) ."</em> up votes for this note.\n</p>\n".
                  "<form method=\"POST\" action=\"\">\n".
                  "  <input type=\"submit\" value\"Yes Reset!\" name=\"resetup\" />\n".
                  "</form>\n";
        } elseif ($action == 'resetdown') {
          $out .= "This will permanently delete all <em>". hsc($row['down']) ."</em> down votes for this note.\n</p>\n".
                  "<form method=\"POST\" action=\"\">\n".
                  "  <input type=\"submit\" value\"Yes Reset!\" name=\"resetdown\" />\n".
                  "</form>\n";
        }
        echo $out;
      } else {
        echo "<p>Note ". hsc($id) ." does not exist!</p>";
      }
    }
  } else {
    echo "<p>Note id not supplied...</p>";
  }
  foot();
  exit;
case 'deletevotes':
  /* Only those with privileges in allow_mass_change may use these options */
  if (!allow_mass_change($cuser)) {
    die("You do not have access to use this feature!");
  }
  /* Delete votes -- effectively deletes votes found in the votes table matching all supplied ids */
  if (empty($_POST['deletevote']) || !is_array($_POST['deletevote'])) {
    die("No vote ids supplied!");
  }
  $ids = [];
  foreach ($_POST['deletevote'] as $id) {
    $ids[] = (int) $id;
  }
  $ids = implode(',',$ids);
  // This is safe, because $ids is an array of integers.
  if (db_query_safe("DELETE FROM votes WHERE id IN ($ids)")) {
    header('Location: user-notes.php?id=1&view=notes&was=' . urlencode($action) .
           (isset($_REQUEST['type']) ? ('&type=' . urlencode($_REQUEST['type'])) : null) .
           (isset($_REQUEST['votessearch']) ? '&votessearch=' . urlencode($_REQUEST['votessearch']) : null)
          );
  }
  exit;
case 'sect':
  head('user notes');
?>
<h2>Search within a section</h2>
<p>
  You can search notes within specified section of the PHP manual using form below or
  by prepending your query with <em>sect:</em> in regular search form (like <em>sect:book.mysql</em>).
</p>
<p>
  You can use <em>*</em> as a wildcard, like <em>mysql.*</em>. Query like <em>function.json-*</em> should
  show all notes for JSON functions (use <em>sect:function.json-*</em> in case of generic form).
</p>
<form method="get" action="<?= PHP_SELF ?>">
  <strong>Section:</strong>
  <input type="hidden" name="action" value="sect" />
  <input type="text" name="query" /><br />
  <input type="submit" value="Search" />
</form>
<?php
  if (isset($_GET['query'])) {
    header('Location: user-notes.php?keyword=sect:' . $_GET['query']);
    exit;
  }

  foot();
  exit;
case 'voting_stats':
    head('user notes');
    /* Calculate dates */
    $today = strtotime('midnight');
    $week = !date('w') ? strtotime('midnight') : strtotime('Last Sunday');
    $month = strtotime('First Day of ' . date('F') . ' ' . date('Y'));
    $yesterday = strtotime('midnight yesterday');
    $lastweek = !date('w') ? strtotime('midnight -1 week') : strtotime('Last Sunday -1 week');
    $lastmonth = strtotime('First Day of last month');
    /* Handle stats queries for voting here */
    $stats_sql = $stats = [];
    $stats_sql['Total']       = new Query('SELECT COUNT(votes.id) AS total FROM votes');
    $stats_sql['Total Up']    = new Query('SELECT COUNT(votes.id) AS total FROM votes WHERE votes.vote = 1');
    $stats_sql['Total Down']  = new Query('SELECT COUNT(votes.id) AS total FROM votes WHERE votes.vote = 0');
    $stats_sql['Today']       = new Query('SELECT COUNT(votes.id) AS total FROM votes WHERE UNIX_TIMESTAMP(votes.ts) >= ?', [$today]);
    $stats_sql['This Week']   = new Query('SELECT COUNT(votes.id) AS total FROM votes WHERE UNIX_TIMESTAMP(votes.ts) >= ?', [$week]);
    $stats_sql['This Month']  = new Query('SELECT COUNT(votes.id) AS total FROM votes WHERE UNIX_TIMESTAMP(votes.ts) >= ?', [$month]);
    $stats_sql['Yesterday']   = new Query('SELECT COUNT(votes.id) AS total FROM votes WHERE UNIX_TIMESTAMP(votes.ts) >= ? AND UNIX_TIMESTAMP(votes.ts) < ?', [$yesterday, $today]);
    $stats_sql['Last Week']   = new Query('SELECT COUNT(votes.id) AS total FROM votes WHERE UNIX_TIMESTAMP(votes.ts) >= ? AND UNIX_TIMESTAMP(votes.ts) < ?', [$lastweek, $week]);
    $stats_sql['Last Month']  = new Query('SELECT COUNT(votes.id) AS total FROM votes WHERE UNIX_TIMESTAMP(votes.ts) >= ? AND UNIX_TIMESTAMP(votes.ts) < ?', [$lastmonth, $month]);
    foreach ($stats_sql as $key => $query) {
        $result = db_query($query);
        $row = mysql_fetch_assoc($result);
        $stats[$key] = $row['total'];
    }
    ?>
    <h2>User contributed voting statistics</h2>
    <div style="float: left; border: 1px solid gray; padding: 5px; background-color: #C8C8C0; margin-bottom: 20px;">
        <?php foreach (array_chunk($stats, 3, true) as $statset) { ?>
            <?php foreach ($statset as $figure => $stat) { ?>
                <div style="display: inline-block; float: left; padding: 15px; border-bottom: 1px solid white; color: #483D8B;"><strong><?= $figure ?></strong>: <?= $stat ?></div>
            <?php } ?>
            <p>&nbsp;</p>
        <?php } ?>
    </div>

    <p style="clear: both;"><a href="<?= PHP_SELF ?>">Go back to the notes management</a></p>
    <?php
    // I didn't want to copy the whole menu and making it a type instead of action
    // would mean reworking whole architecture this page has so it's an easy win tbh
    foot();
break;
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
    return preg_replace(
        '!((mailto:|(http|ftp|nntp|news):\/\/).*?)(\s|<|\)|"|\\|\'|$)!',
        '<a href="\1" target="_blank">\1</a>\4',
        $text
    );
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
        [
            '&nbsp;',
            '<br />',
            '<font color="',
            '</font>',
            "\n ",
            '  '
        ],
        [
            ' ',
            "<br />\n",
            '<span class="',
            '</span>',
            "\n&nbsp;",
            '&nbsp; '
        ],
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
    if ($result = db_query_safe('SELECT *, UNIX_TIMESTAMP(ts) AS ts FROM note WHERE id=?', [$id])) {
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
            [
                "vrana", "goba", "nlopess", "didou", "bjori", "philip", "bobby", "danbrown", "mgdm", "googleguy", "levim",
            ]
        )
    ) {
        return TRUE;
    } else { return FALSE; }
}

// Return safe to print version of email address
function safe_email($mail)
{
    if (in_array($mail, ["php-general@lists.php.net", "user@example.com"])) {
        return '';
    }
    elseif (preg_match("!(.+)@(.+)\.(.+)!", $mail)) {
        return str_replace(['@', '.'], [' at ', ' dot '], $mail);
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
    $end = [];
    foreach (array_keys($start, "*", true) as $key) {
        $start[$key] = "0";
        $end[$key] = "255";
    }
    foreach ($start as $key => $part) {
        if (!isset($end[$key])) {
            $end[$key] = $part;
        }
    }
    ksort($end);
    $start = ip2long(implode('.',$start));
    $end = ip2long(implode('.',$end));
    if ($end - $start <= 0) {
      return false;
    }
    return [$start, $end];
}
