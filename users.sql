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
/* we have a full-text index on name and email for searching, and we
   require unique email addresses for each account. */
/* a user will be able to change the email address associated with
   their account if they know the password. */
CREATE TABLE IF NOT EXISTS users (
  userid INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL DEFAULT '',
  email VARCHAR(255) NOT NULL DEFAULT '',
  passwd VARCHAR(16) NOT NULL DEFAULT '',
  PRIMARY KEY(userid),
  UNIQUE (email),
  FULLTEXT (name,email)
);

/* the users_cvs table contains the cvs username and whether or not
   the user has been approved for cvs access. not all users will have
   cvs usernames. the cvs usernames have to be unique. */
/* this probably could be merged back into the main users table. */
CREATE TABLE IF NOT EXISTS users_cvs (
  userid INT NOT NULL,
  cvsuser CHAR(16) NOT NULL,
  approved INT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY(userid),
  UNIQUE (cvsuser)
);

/* the user_note table just contains notes about each user. */
CREATE TABLE IF NOT EXISTS users_note (
  noteid INT NOT NULL AUTO_INCREMENT,
  userid INT NOT NULL,
  entered DATETIME NOT NULL,
  note TEXT,
  PRIMARY KEY (noteid),
  FULLTEXT (note)
);
