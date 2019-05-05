-- This script upgrades table structure, data, and privileges
-- from/to the exact version numbers specified in the filename.

--
-- First execute a check whether this upgrade should apply. The check
-- below should fail if this upgrade has already been applied, but
-- keep everything unchanged if not.
--

-- @UPGRADE-CHECK@
CREATE TABLE `external_judgement` (`extjudgementid` varchar (255));
DROP TABLE `external_judgement`;

--
-- Create additional structures
--

-- Create external judgement/run tables
CREATE TABLE `external_judgement` (
  `extjudgementid` int(4) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
  `externalid` varchar(255) DEFAULT NULL COMMENT 'Judgement ID in external system, should be unique inside a single contest',
  `cid` int(4) unsigned NOT NULL COMMENT 'Contest ID',
  `submitid` int(4) unsigned NOT NULL COMMENT 'Submission ID being judged by external system',
  `result` varchar(32) DEFAULT NULL COMMENT 'Result string as obtained from external system. null if not finished yet',
  `starttime` decimal(32,9) unsigned NOT NULL COMMENT 'Time judging started',
  `endtime` decimal(32,9) unsigned DEFAULT NULL COMMENT 'Time judging ended, null = still busy',
  `valid` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT 'Old external judgement is marked as invalid when receiving a new one',
  PRIMARY KEY  (`extjudgementid`),
  UNIQUE KEY `externalid` (`cid`,`externalid`(190)),
  KEY `submitid` (`submitid`),
  CONSTRAINT `external_judgement_ibfk_1` FOREIGN KEY (`submitid`) REFERENCES `submission` (`submitid`) ON DELETE CASCADE,
  CONSTRAINT `external_judgement_ibfk_2` FOREIGN KEY (`cid`) REFERENCES `contest` (`cid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Judgement in external system';

CREATE TABLE `external_run` (
  `extrunid` int(4) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique ID',
  `extjudgementid` int(4) unsigned NOT NULL COMMENT 'Judging ID this run belongs to',
  `testcaseid` int(4) unsigned NOT NULL COMMENT 'Testcase ID',
  `externalid` varchar(255) DEFAULT NULL COMMENT 'Run ID in external system, should be unique inside a single contest',
  `cid` int(4) unsigned NOT NULL COMMENT 'Contest ID',
  `result` varchar(32) NOT NULL COMMENT 'Result string as obtained from external system',
  `endtime` decimal(32,9) unsigned NOT NULL COMMENT 'Time run ended',
  `runtime` float NOT NULL COMMENT 'Running time on this testcase',
  PRIMARY KEY  (`extrunid`),
  KEY `extjudgementid` (`extjudgementid`),
  KEY `testcaseid` (`testcaseid`),
  UNIQUE KEY `externalid` (`cid`,`externalid`(190)),
  CONSTRAINT `external_run_ibfk_1` FOREIGN KEY (`extjudgementid`) REFERENCES `external_judgement` (`extjudgementid`) ON DELETE CASCADE,
  CONSTRAINT `external_run_ibfk_2` FOREIGN KEY (`testcaseid`) REFERENCES `testcase` (`testcaseid`) ON DELETE CASCADE,
  CONSTRAINT `external_run_ibfk_3` FOREIGN KEY (`cid`) REFERENCES `contest` (`cid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Run in external system';

--
-- Transfer data from old to new structure
--



--
-- Add/remove sample/initial contents
--

INSERT INTO `configuration` (`name`, `value`, `type`, `public`, `category`, `description`) VALUES
('external_ccs_submission_url', '""', 'string', '0', 'Misc', 'URL of a submission detail page on the external CCS. Placeholder :id: will be replaced by submission ID. Leave empty to not display links to external CCS');

--
-- Finally remove obsolete structures after moving data
--


ALTER TABLE `submission`
    DROP COLUMN `externalresult`;