-- SQL Dump
-- Host: localhost
-- Server version: 5.0.22
-- PHP Version: 5.1.4-0.1
-- 
-- Database: `phpqagcov`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `local_builds`
-- 

DROP TABLE IF EXISTS `local_builds`;
CREATE TABLE `local_builds` (
`build_id` int(11) NOT NULL auto_increment,
`version_id` int(11) NOT NULL,
`build_datetime` datetime NOT NULL default '2000-01-01 12:00:00',
`build_numerrors` int(11) NOT NULL,
`build_numwarnings` int(11) NOT NULL,
`build_numfailures` int(11) NOT NULL,
`build_numleaks` int(11) NOT NULL,
`build_percent_code_coverage` float NOT NULL,
`build_os_info` tinytext collate latin1_general_ci NOT NULL,
`build_compiler_info` tinytext collate latin1_general_ci NOT NULL,
PRIMARY KEY  (`build_id`),
KEY `version_id` (`version_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='Store local build statistics for graph building';

-- --------------------------------------------------------

-- 
-- Table structure for table `remote_builds`
-- 

DROP TABLE IF EXISTS `remote_builds`;
CREATE TABLE `remote_builds` (
`user_id` bigint(20) NOT NULL auto_increment,
`user_name` varchar(20) collate latin1_general_ci NOT NULL,
`user_pass` varchar(50) collate latin1_general_ci NOT NULL,
`user_email` varchar(40) collate latin1_general_ci NOT NULL,
`last_build_xml` longtext collate latin1_general_ci NOT NULL,
`last_user_os` char(30) collate latin1_general_ci NOT NULL,
`last_user_os_version` char(8) collate latin1_general_ci NOT NULL,
PRIMARY KEY  (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='Store general build information submitted from users';

INSERT INTO `remote_builds` (`user_id`, `user_name`, `user_pass`, `user_email`, `last_build_xml`, `last_user_os`, `last_user_os_version`) VALUES (1, 'johndoe', '527bd5b5d689e2c32ae974c6229ff785', 'john@example.com', '', 'Linux', '2.6.12.6');

-- --------------------------------------------------------

-- 
-- Table structure for table `versions`
-- 

DROP TABLE IF EXISTS `versions`;
CREATE TABLE `versions` (
`version_id` tinyint(4) NOT NULL auto_increment,
`version_name` varchar(30) collate latin1_general_ci NOT NULL,
`version_last_build_time` int(11) NOT NULL,
`version_last_attempted_build_date` datetime NOT NULL,
`version_last_successful_build_date` datetime NOT NULL,
PRIMARY KEY  (`version_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='Store the PHP versions accepted and local build information';

INSERT INTO `versions` (`version_id`, `version_name`, `version_last_build_time`, `version_last_attempted_build_date`, `version_last_successful_build_date`) VALUES (1, 'PHP_4_4', 1716, '2006-08-21 00:13:23', '2006-08-21 00:13:23'),
(2, 'PHP_5_1', 4834, '2006-08-21 03:51:52', '2006-08-21 03:51:52'),
(3, 'PHP_5_2', 5680, '2006-08-21 03:51:19', '2006-08-21 03:51:19'),
(4, 'PHP_HEAD', 17851, '2006-08-21 03:52:19', '2006-08-21 03:52:19');
