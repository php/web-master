/* the note table holds notes for the php manual. */
/* TODO: there is a similar table for php-gtk's manual. it should probably be
   merged with this one. */
/* TODO: the user stuff should be linked to the users table so people could
   edit their own notes. */
/* TODO: lang should probably be linked to a languages table of some sort.
   but we're not really using it yet, so maybe we don't want it at all. */

/* used by:
   master.php.net/entry/user-note.php
   master.php.net/fetch/user-notes.php
   master.php.net/manage/user-notes.php
*/

CREATE TABLE IF NOT EXISTS note (
  id INT NOT NULL AUTO_INCREMENT,
  sect VARCHAR(80) NOT NULL DEFAULT '',
  user VARCHAR(80) DEFAULT NULL,
  note TEXT,
  ts DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  status VARCHAR(16) DEFAULT NULL,
  lang VARCHAR(16) DEFAULT NULL,
  votes INT NOT NULL DEFAULT '0',
  rating INT NOT NULL DEFAULT '0',
  updated DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY (sect)
) TYPE=MyISAM PACK_KEYS=1;

CREATE TABLE IF NOT EXISTS alerts (
  user INT NOT NULL,
  sect VARCHAR(80) not NULL,
  updated TIMESTAMP(14) NOT NULL
);
