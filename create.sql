
--
-- Add configuration items for logbook
--

INSERT INTO fac_Config VALUES ('log_BaseDN','','Base DN','string','dc=mydomain,dc=com');
INSERT INTO fac_Config VALUES ('log_LDAPRN','','Control User','string','uid=admin,dc=mydomain,dc=com');
INSERT INTO fac_Config VALUES ('log_LDAPPass','','Control Pass','string','s3krit');
INSERT INTO fac_Config VALUES ('log_LDAPHost','','Directory Server','string','server.mydomain.com');
INSERT INTO fac_Config VALUES ('log_PhotoURL','','Picture Lookup URL','string','server.mydomain.com/photo?user=');
