CREATE TABLE emails(relatiecode text PRIMARY KEY NOT NULL, naam TEXT NOT NULL, email TEXT NOT NULL, leeftijd INT NOT NULL, code TEXT NULL);
CREATE INDEX ix_emails_code ON emails (code);