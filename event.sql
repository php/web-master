#
# Table structure for table 'phpcal'
#

CREATE TABLE phpcal (
  id INT(8) NOT NULL AUTO_INCREMENT,
  sdato DATE DEFAULT NULL,
  edato DATE DEFAULT NULL,
  recur VARCHAR(12) DEFAULT NULL,
  sdesc VARCHAR(32) NOT NULL DEFAULT '',
  url VARCHAR(128) DEFAULT NULL,
  email VARCHAR(128) DEFAULT NULL,
  ldesc TEXT,
  tipo INT(1) NOT NULL DEFAULT '0',
  approved INT(1) NOT NULL DEFAULT '0',
  app_by VARCHAR(16) DEFAULT NULL,
  country CHAR(3) NOT NULL DEFAULT '',
  category TINYINT(4) NOT NULL DEFAULT '0',
  PRIMARY KEY  (id),
  KEY sdato (sdato),
  KEY edato (edato),
  KEY country (country),
  KEY category (category),
  FULLTEXT KEY sdesc (sdesc,ldesc,email)
) TYPE=MyISAM;
