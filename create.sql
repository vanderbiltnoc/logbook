--
-- Table structure for table fac_DataCenterLog
--

CREATE TABLE IF NOT EXISTS fac_DataCenterLog (
  EntryID int(10) unsigned NOT NULL auto_increment,
  DataCenterID int(11) NOT NULL,
  VUNetID varchar(20) NOT NULL,
  EscortRequired tinyint(1) NOT NULL default '0',
  RequestTime datetime NOT NULL,
  Reason varchar(255) NOT NULL,
  AuthorizedBy varchar(20) NOT NULL,
  TimeIn datetime NOT NULL,
  TimeOut datetime NOT NULL,
  GuestList text NOT NULL,
  EventType enum('dcaccess','safe') NOT NULL,
  PRIMARY KEY  (EntryID)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_ResourceLog
--

CREATE TABLE IF NOT EXISTS `fac_ResourceLog` (
  `ResourceID` int(11) NOT NULL,
  `VUNetID` varchar(20) NOT NULL,
  `Note` varchar(255) NOT NULL,
  `RequestedTime` datetime NOT NULL,
  `TimeOut` datetime NOT NULL,
  `EstimatedReturn` datetime NOT NULL,
  `ActualReturn` datetime NOT NULL,
  `index` int(11) NOT NULL auto_increment,
  PRIMARY KEY  (`index`),
  KEY `ResourceID` (`ResourceID`,`VUNetID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Table structure for table fac_ScanningLog
--

CREATE TABLE IF NOT EXISTS fac_ScanningLog (
  ScanID int(11) NOT NULL auto_increment,
  DateSubmitted datetime NOT NULL,
  DateScanned datetime default NULL,
  DatePickedUp datetime default NULL,
  CourseNumber varchar(25) NOT NULL,
  Section tinyint(2) NOT NULL,
  Authorized tinyint(1) default NULL COMMENT '1 means not authorized, see note.',
  NumForms smallint(4) default NULL,
  NOCAnalyst varchar(25) default NULL,
  Dropoff varchar(25) NOT NULL,
  Pickup varchar(25) default NULL,
  Notes varchar(255) default NULL,
  PRIMARY KEY  (ScanID)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_ScanningUsers
--

CREATE TABLE IF NOT EXISTS fac_ScanningUsers (
  `index` int(11) NOT NULL auto_increment,
  `vunetid` varchar(25) NOT NULL,
  `email` varchar(64) NOT NULL,
  PRIMARY KEY  (`index`),
  UNIQUE KEY `vunetid` (`vunetid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- Table structure for table fac_ScanningUsersAuth
--

CREATE TABLE IF NOT EXISTS fac_ScanningUsersAuth (
  `ScanID` int(11) NOT NULL,
  `index` int(11) NOT NULL,
  UNIQUE KEY `ScanID` (`ScanID`,`index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

--
-- Table structure for table `fac_ResourceCategory`
--

CREATE TABLE IF NOT EXISTS `fac_ResourceCategory` (
  `CategoryID` int(11) NOT NULL auto_increment,
  `Description` varchar(80) NOT NULL,
  PRIMARY KEY  (`CategoryID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Table structure for table `fac_Resource`
--

CREATE TABLE IF NOT EXISTS `fac_Resource` (
  `ResourceID` int(11) NOT NULL auto_increment,
  `CategoryID` int(11) NOT NULL,
  `Description` varchar(80) NOT NULL,
  `UniqueID` varchar(80) NOT NULL,
  `Active` tinyint(1) NOT NULL,
  `Status` enum('Available','Out','Reserved') NOT NULL default 'Available',
  PRIMARY KEY  (`ResourceID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Add configuration items for logbook
--

INSERT INTO fac_Config VALUES ('log_BaseDN','','Base DN','string','dc=mydomain,dc=com');
INSERT INTO fac_Config VALUES ('log_LDAPRN','','Control User','string','uid=admin,dc=mydomain,dc=com');
INSERT INTO fac_Config VALUES ('log_LDAPPass','','Control Pass','string','s3krit');
INSERT INTO fac_Config VALUES ('log_LDAPHost','','Directory Server','string','server.mydomain.com');
INSERT INTO fac_Config VALUES ('log_PhotoURL','','Picture Lookup URL','string','server.mydomain.com/photo?user=');
