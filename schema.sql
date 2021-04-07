/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `auth`
--

DROP TABLE IF EXISTS `auth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auth` (
  `authid` varchar(64) NOT NULL DEFAULT '',
  `authkey` varchar(255) NOT NULL DEFAULT '',
  `cburl` varchar(255) DEFAULT NULL,
  `owneremail` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`authid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `commits`
--

DROP TABLE IF EXISTS `commits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `commits` (
  `repo` varchar(100) NOT NULL,
  `hash` binary(40) NOT NULL,
  UNIQUE KEY `repo` (`repo`,`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `country`
--

DROP TABLE IF EXISTS `country`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `country` (
  `id` char(3) NOT NULL DEFAULT '',
  `alpha2` char(2) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `lat` int(8) NOT NULL DEFAULT 0,
  `lon` int(8) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mirrors`
--

DROP TABLE IF EXISTS `mirrors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mirrors` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `mirrortype` int(1) NOT NULL DEFAULT 1,
  `cc` char(3) NOT NULL DEFAULT '',
  `lang` varchar(5) NOT NULL DEFAULT '',
  `hostname` varchar(40) NOT NULL DEFAULT '',
  `cname` varchar(80) NOT NULL DEFAULT '',
  `maintainer` varchar(255) NOT NULL DEFAULT '',
  `providername` varchar(255) NOT NULL DEFAULT '',
  `providerurl` varchar(255) NOT NULL DEFAULT '',
  `has_stats` int(1) NOT NULL DEFAULT 0,
  `has_search` int(1) NOT NULL DEFAULT 0,
  `active` int(1) NOT NULL DEFAULT 0,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastedited` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastupdated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastchecked` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `phpversion` varchar(16) NOT NULL DEFAULT '',
  `acmt` text DEFAULT NULL,
  `ocmt` text DEFAULT NULL,
  `maintainer2` varchar(255) NOT NULL DEFAULT '',
  `load_balanced` varchar(4) DEFAULT NULL,
  `ext_avail` text DEFAULT NULL,
  `local_hostname` varchar(255) DEFAULT NULL,
  `ipv4_addr` varchar(55) DEFAULT NULL,
  `ipv6_addr` varchar(55) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hostname` (`hostname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `note`
--

DROP TABLE IF EXISTS `note`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `sect` varchar(80) NOT NULL DEFAULT '',
  `user` varchar(80) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `ts` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `status` varchar(16) DEFAULT NULL,
  `lang` varchar(16) DEFAULT NULL,
  `votes` int(11) NOT NULL DEFAULT 0,
  `rating` int(11) NOT NULL DEFAULT 0,
  `updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `idx_sect` (`sect`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 PACK_KEYS=1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `phpcal`
--

DROP TABLE IF EXISTS `phpcal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phpcal` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `sdato` date DEFAULT NULL,
  `edato` date DEFAULT NULL,
  `recur` varchar(12) DEFAULT NULL,
  `sdesc` varchar(32) NOT NULL DEFAULT '',
  `url` varchar(128) DEFAULT NULL,
  `email` varchar(128) NOT NULL DEFAULT '',
  `ldesc` text DEFAULT NULL,
  `tipo` int(1) NOT NULL DEFAULT 0,
  `approved` int(1) NOT NULL DEFAULT 0,
  `app_by` varchar(16) DEFAULT NULL,
  `country` char(3) NOT NULL DEFAULT '',
  `category` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `sdato` (`sdato`),
  KEY `edato` (`edato`),
  KEY `country` (`country`),
  KEY `category` (`category`),
  FULLTEXT KEY `sdesc` (`sdesc`,`ldesc`,`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sites`
--

DROP TABLE IF EXISTS `sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sites` (
  `cat` varchar(48) NOT NULL DEFAULT '',
  `name` varchar(80) NOT NULL DEFAULT '',
  `url` varchar(80) NOT NULL DEFAULT '',
  `email` varchar(60) NOT NULL DEFAULT '',
  `password` varchar(16) NOT NULL DEFAULT '',
  `approved` enum('N','Y') NOT NULL DEFAULT 'N',
  `approved_by` varchar(20) NOT NULL DEFAULT '',
  `note` text DEFAULT NULL,
  `ts` datetime DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 PACK_KEYS=1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tokens`
--

DROP TABLE IF EXISTS `tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tokens` (
  `tokid` varchar(64) NOT NULL DEFAULT '',
  `generatedfor` varchar(64) NOT NULL DEFAULT '',
  `username` varchar(16) NOT NULL DEFAULT '',
  `expires` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`tokid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `userid` int(11) NOT NULL AUTO_INCREMENT,
  `svnpasswd` varchar(60) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `username` varchar(16) DEFAULT NULL,
  `cvsaccess` int(1) NOT NULL DEFAULT 0,
  `spamprotect` int(1) NOT NULL DEFAULT 1,
  `forgot` varchar(32) DEFAULT NULL,
  `dns_allow` int(1) NOT NULL DEFAULT 1,
  `dns_type` varchar(5) NOT NULL DEFAULT 'NONE',
  `dns_target` varchar(255) NOT NULL DEFAULT '',
  `last_commit` datetime DEFAULT NULL,
  `num_commits` int(11) NOT NULL DEFAULT 0,
  `verified` int(1) NOT NULL DEFAULT 0,
  `use_sa` int(11) DEFAULT 5,
  `greylist` int(11) NOT NULL DEFAULT 0,
  `enable` int(1) NOT NULL DEFAULT 0,
  `pchanged` int(11) DEFAULT 0,
  `ssh_keys` text DEFAULT NULL,
  PRIMARY KEY (`userid`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`),
  FULLTEXT KEY `name` (`name`,`email`,`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_cvs`
--

DROP TABLE IF EXISTS `users_cvs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_cvs` (
  `userid` int(11) NOT NULL DEFAULT 0,
  `cvsuser` char(16) NOT NULL DEFAULT '',
  `approved` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`userid`),
  UNIQUE KEY `cvsuser` (`cvsuser`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_note`
--

DROP TABLE IF EXISTS `users_note`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_note` (
  `noteid` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL DEFAULT 0,
  `entered` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `note` text DEFAULT NULL,
  PRIMARY KEY (`noteid`),
  FULLTEXT KEY `note` (`note`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_profile`
--

DROP TABLE IF EXISTS `users_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_profile` (
  `userid` int(11) NOT NULL,
  `markdown` text NOT NULL,
  `html` text NOT NULL,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `votes`
--

DROP TABLE IF EXISTS `votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `note_id` mediumint(9) NOT NULL,
  `ip` bigint(20) unsigned NOT NULL DEFAULT 0,
  `hostip` bigint(20) unsigned NOT NULL DEFAULT 0,
  `ts` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `vote` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `note_id` (`note_id`,`ip`,`vote`),
  KEY `hostip` (`hostip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2021-04-07  8:26:18
