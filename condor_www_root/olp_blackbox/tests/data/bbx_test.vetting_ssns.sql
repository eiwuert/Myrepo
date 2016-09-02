DROP TABLE IF EXISTS vetting_ssns;
CREATE TABLE IF NOT EXISTS vetting_ssns (
  id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  application_id INTEGER UNSIGNED NOT NULL,      -- FK on application
  ssn_encrypted VARCHAR(250) NOT NULL,
  date_created DATE NOT NULL,
  date_modified DATE NOT NULL,
  PRIMARY KEY(id)
);
CREATE INDEX vetting_ssns_ssn_idx ON vetting_ssns (ssn_encrypted);
CREATE INDEX vetting_ssns_app_id_idx ON vetting_ssns (application_id);
CREATE INDEX vetting_ssns_last_seen_idx ON vetting_ssns (date_modified);