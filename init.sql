PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;
CREATE TABLE kandidaat(code CHAR(7), hoort_by CHAR(7) NULL, naam, rol varchar (25), bio text, aangemeld_op DATETIME, is_group int(1) DEFAULT 0, hash VARCHAR(150) NULL, votes INT DEFAULT 0, PRIMARY KEY (code));
CREATE TABLE leden(nummer CHAR(7), email VARCHAR(100), voted_on DATETIME NULL, voted_for INT NULL, hash CHAR(6) NULL, login_on DATETIME, PRIMARY KEY (nummer));
CREATE TABLE votes (kandidaat code CHAR(7), voted_on DATETIME);
CREATE INDEX ix_leden_email ON leden (email);
CREATE INDEX ix_hash ON leden (hash);
COMMIT;
