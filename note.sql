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
  id mediumint(9) NOT NULL auto_increment,
  sect varchar(80) NOT NULL default '',
  user varchar(80) default NULL,
  note text,
  ts datetime NOT NULL default '0000-00-00 00:00:00',
  status varchar(16) default NULL,
  lang varchar(16) default NULL,
  votes int(11) NOT NULL default '0',
  rating int(11) NOT NULL default '0',
  updated datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY idx_sect (sect)
) TYPE=MyISAM PACK_KEYS=1;

CREATE TABLE IF NOT EXISTS alerts (
  user INT NOT NULL default '0',
  sect VARCHAR(80) not NULL default '',
  updated TIMESTAMP(14) NOT NULL
) TYPE=MyISAM;
