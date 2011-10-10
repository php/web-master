#
# Table structure for table 'phpcal'
#

CREATE TABLE phpcal (
  id int(8) NOT NULL auto_increment,
  sdato date default NULL,
  edato date default NULL,
  recur varchar(12) default NULL,
  sdesc varchar(32) NOT NULL default '',
  url varchar(128) default NULL,
  email varchar(128) NOT NULL default '',
  ldesc text,
  tipo int(1) NOT NULL default '0',
  approved int(1) NOT NULL default '0',
  app_by varchar(16) default NULL,
  country char(3) NOT NULL default '',
  category tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY sdato (sdato),
  KEY edato (edato),
  KEY country (country),
  KEY category (category),
  FULLTEXT KEY sdesc (sdesc,ldesc,email)
) TYPE=MyISAM;

