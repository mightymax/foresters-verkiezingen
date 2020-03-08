PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;
CREATE TABLE params(key text NOT NULL, val TEXT NOT NULL);
CREATE INDEX ix_params ON params (key);
INSERT INTO params VALUES ('power', 'off');

CREATE TABLE votes(vote int CHECK (vote IN (0, 1)) NULL, voted_on DATETIME NULL);
CREATE INDEX ix_votes ON votes (vote);

CREATE TABLE codes (code CHAR(7), voted int CHECK (voted IN (0, 1)));
CREATE INDEX ix_code ON codes (code);

CREATE TABLE throttle (ip TEXT, attempts INT NOT NULL DEFAULT 1, last_seen DATETIME NULL);
CREATE INDEX ix_throttle_ip ON throttle (ip);
COMMIT;
