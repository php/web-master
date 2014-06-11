--
-- Table structure for table 'mirrors'
--

CREATE TABLE mirrors (
  id int(8) NOT NULL auto_increment,
  mirrortype int(1) NOT NULL default '1',
  cc char(3) NOT NULL default '',
  lang varchar(5) NOT NULL default '',
  hostname varchar(40) NOT NULL default '',
  cname varchar(80) NOT NULL default '',
  maintainer varchar(255) NOT NULL default '',
  providername varchar(255) NOT NULL default '',
  providerurl varchar(255) NOT NULL default '',
  has_stats int(1) NOT NULL default '0',
  has_search int(1) NOT NULL default '0',
  active int(1) NOT NULL default '0',
  created datetime NOT NULL default '0000-00-00 00:00:00',
  lastedited datetime NOT NULL default '0000-00-00 00:00:00',
  lastupdated datetime NOT NULL default '0000-00-00 00:00:00',
  lastchecked datetime NOT NULL default '0000-00-00 00:00:00',
  phpversion varchar(16) NOT NULL default '',
  acmt text,
  ocmt text,
  maintainer2 varchar(255) NOT NULL default '',
  load_balanced varchar(4) default NULL,
  ext_avail text,
  local_hostname varchar(255) default NULL,
  ipv4_addr varchar(55) default NULL,
  ipv6_addr varchar(55) default NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY hostname (hostname)
) TYPE=MyISAM;

