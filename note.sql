/* the note table holds notes for the php manual. */
/* TODO: there is a similar table for php-gtk's manual. it should probably be
   merged with this one. */
/* TODO: the user stuff should be linked to the users table so people could
   edit their own notes. */
/* TODO: lang should probably be linked to a languages table of some sort.
   but we're not really using it yet, so maybe we don't want it at all. */

/* used by:
   main.php.net/entry/user-note.php
   main.php.net/entry/user-notes-vote.php
   main.php.net/fetch/user-notes.php
   main.php.net/manage/user-notes.php
*/

CREATE TABLE IF NOT EXISTS note (
  id mediumint(9) NOT NULL auto_increment,
  sect varchar(80) NOT NULL default '',
  user varchar(80) default NULL,
  note text,
  ts datetime NOT NULL,
  status varchar(16) default NULL,
  lang varchar(16) default NULL,
  votes int(11) NOT NULL default '0',
  rating int(11) NOT NULL default '0',
  updated datetime,
  PRIMARY KEY  (id),
  KEY idx_sect (sect)
) ENGINE=MyISAM PACK_KEYS=1;

-- New votes table added for keeping track of user notes ratings
CREATE TABLE IF NOT EXISTS `votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `note_id` mediumint(9) NOT NULL,
  `ip` bigint(20) unsigned NOT NULL DEFAULT '0',
  `hostip` bigint(20) unsigned NOT NULL DEFAULT '0',
  `ts` datetime NOT NULL,
  `vote` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `note_id` (`note_id`,`ip`,`vote`),
  KEY `hostip` (`hostip`)
) ENGINE=MyISAM AUTO_INCREMENT=1;
