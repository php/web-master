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
CREATE TABLE IF NOT EXISTS users (
  userid INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL DEFAULT '',
  email VARCHAR(255) NOT NULL DEFAULT '',
  passwd VARCHAR(16) NOT NULL DEFAULT '',
  username VARCHAR(16) DEFAULT NULL,
  cvsaccess INT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY(userid),
  UNIQUE (email),
  UNIQUE (username),
  FULLTEXT (name,email,username)
) TYPE=MyISAM;

/* the user_note table just contains notes about each user. */
CREATE TABLE IF NOT EXISTS users_note (
  noteid INT NOT NULL AUTO_INCREMENT,
  userid INT NOT NULL,
  entered DATETIME NOT NULL,
  note TEXT,
  PRIMARY KEY (noteid),
  FULLTEXT (note)
) TYPE=MyISAM;
