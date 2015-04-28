DROP TABLE IF EXISTS vu_Surplus;
CREATE TABLE vu_Surplus (
  SurplusID int(11) NOT NULL AUTO_INCREMENT,
  UserID varchar(40) NOT NULL,
  Created datetime NOT NULL,
  DevType varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  Manufacturer varchar(40) NOT NULL,
  Model varchar(40) NOT NULL,
  Serial varchar(40) NOT NULL,
  AssetTag varchar(20) NOT NULL,
  PRIMARY KEY (SurplusID)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS vu_SurplusConfig;
CREATE TABLE vu_SurplusConfig (
  UserIndex int(11) NOT NULL AUTO_INCREMENT,
  UserID varchar(40) NOT NULL,
  PRIMARY KEY (UserIndex),
  UNIQUE KEY UserID (UserID)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS vu_SurplusHD;
CREATE TABLE vu_SurplusHD (
  DiskID int(11) NOT NULL AUTO_INCREMENT,
  SurplusID int(11) NOT NULL,
  UserID varchar(40) NOT NULL,
  Location varchar(40) NOT NULL,
  Serial varchar(40) NOT NULL,
  DestructionCertificationID varchar(40) NOT NULL,
  CertificationDate datetime NOT NULL,
  PRIMARY KEY (DiskID)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS vu_SurplusLocations;
CREATE TABLE vu_SurplusLocations (
  Location varchar(40) NOT NULL,
  UNIQUE KEY Location (Location)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
