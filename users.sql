/* user-related tables */

/* various things that may hang off the users table in the future:
   * ownership of bugs and bug comments
   * 'subscription' to bugs
   * 'subscription' to notes updates of manual sections
   * cvs acls
*/

/* the users table is the main one. it contains the name, email, and
   crypted password for each user. the password is crypted using the
   standard unix DES-based crypt (for interop with cvs) */
/* we have a full-text index on name, username and email for searching, and we
   require unique email addresses for each account. the username must also
   be unique (when present). */
/* a user will be able to change the email address associated with
   their account if they know the password. */
/* the cvsaccess field requires more thought. we might want to expand
   it to a more general flags field or something. it already implies
   an email alias in addition to cvs access. */
/* dns_allow states whether or not a user gets a <username>.people.php.net hostname.
   Abusive users can have their dns privilidges revoked using this field.
   dns_type is (currently) one of 'A','NS','CNAME' or 'NONE'.
   dns_target is dependent on dns_type and should be self explanatory */
CREATE TABLE users (
  userid int(11) NOT NULL auto_increment,
  passwd varchar(16) NOT NULL default '',
  svnpasswd varchar(32) NOT NULL default '',
  md5passwd varchar(32) NOT NULL default '',
  name varchar(255) NOT NULL default '',
  email varchar(255) NOT NULL default '',
  username varchar(16) default NULL,
  cvsaccess int(1) NOT NULL default '0',
  spamprotect int(1) NOT NULL default '1',
  forgot varchar(16) default NULL,
  dns_allow int(1) NOT NULL default '1',
  dns_type varchar(5) NOT NULL default 'NONE',
  dns_target varchar(255) NOT NULL default '',
  last_commit datetime default NULL,
  num_commits int(11) NOT NULL default '0',
  verified int(1) NOT NULL default '0',
  use_sa int(11) default '5',
  greylist int(11) NOT NULL default '0',
  enable int(1) NOT NULL default '0',
  pchanged int(11) default '0',
  ssh_keys TEXT default NULL,
  PRIMARY KEY  (userid),
  UNIQUE KEY email (email),
  UNIQUE KEY username (username),
  FULLTEXT KEY name (name,email,username)
) TYPE=MyISAM;

/* the user_note table just contains notes about each user. */
CREATE TABLE users_note (
  noteid int(11) NOT NULL auto_increment,
  userid int(11) NOT NULL default '0',
  entered datetime NOT NULL default '0000-00-00 00:00:00',
  note text,
  PRIMARY KEY  (noteid),
  FULLTEXT KEY note (note)
) TYPE=MyISAM;

/* the users_profile table contains up to one profile row for each user */
CREATE TABLE users_profile (
  userid int(11) NOT NULL,
  markdown TEXT NOT NULL default '',
  html TEXT NOT NULL default '',
  PRIMARY KEY (userid)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
