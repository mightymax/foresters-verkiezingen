# Verkiezingstool Bestuur De Foresters



## SQL Script for DB (Sqlite3)
	PRAGMA foreign_keys=OFF;
	BEGIN TRANSACTION;
	CREATE TABLE kandidaat(code CHAR(7), hoort_by CHAR(7) NULL, naam, rol varchar (25), bio text, aangemeld_op DATETIME, is_group int(1) DEFAULT 0, hash VARCHAR(150) NULL, votes INT DEFAULT 0, PRIMARY KEY (code));
	INSERT INTO kandidaat VALUES('PWPV61X','PWPV61Y','Mark Lindeman','Secretaris','',NULL,0,NULL,0);
	INSERT INTO kandidaat VALUES('PWPV61Y',NULL,'Sjoerd Stoker','Voorzitter','',NULL,1,NULL,4);
	INSERT INTO kandidaat VALUES('PWPV61Z','PWPV61Y','Remco Teerhuis','Penningmeester','',NULL,0,NULL,0);
	INSERT INTO kandidaat VALUES('TL12345',NULL,'Tom Leguit','Secretaris','',NULL,0,NULL,0);
	INSERT INTO kandidaat VALUES('MB12345',NULL,'Marc Brunekreef','Voorzitter','',NULL,0,NULL,0);
	INSERT INTO kandidaat VALUES('DO12345',NULL,'Dennis Oostindie','Penningmeester','',NULL,0,NULL,0);
	CREATE TABLE leden(nummer CHAR(7), email VARCHAR(100), voted_on DATETIME NULL, voted_for INT NULL, hash CHAR(6) NULL, login_on DATETIME, PRIMARY KEY (nummer));
	INSERT INTO leden VALUES('PWPV61X','mark@lindeman.nu','2020-02-26 12:15:50',NULL,'T2OICN','2020-02-26 12:05:23');
	CREATE TABLE votes (kandidaat code CHAR(7), voted_on DATETIME);
	INSERT INTO votes VALUES('PWPV61Y','2020-02-26 12:15:50');
	CREATE INDEX ix_leden_email ON leden (email);
	CREATE INDEX ix_hash ON leden (hash);
	COMMIT;
    
