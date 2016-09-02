
-- this sql script clears out and then fills the lead_generation.vp_400k table up with records from
-- the various olp_* mysql databases

-- beginning of day one week ago
-- SET @early = date_format(date_sub(now(), INTERVAL 7 DAY), '%Y%m%d000000');

-- beginning of day two weeks ago

SET @early = date_format(date_sub(now(), INTERVAL 14 DAY), '%Y%m%d000000');

-- end of yesterday
-- SET @late = date_format(date_sub(now(), INTERVAL 1 DAY), '%Y%m%d235959');

-- end of day one week and one day ago
SET @late = date_format(date_sub(now(), INTERVAL 8 DAY), '%Y%m%d235959');


--SET @early = date_format(date_sub("2004-11-01", INTERVAL 14 DAY),'%Y%m%d000000');
--SET @late = date_format(date_sub("2004-11-01", INTERVAL 8 DAY), '%Y%m%d235959');

USE lead_generation;

-- DROP TABLE IF EXISTS vp_400k;
-- CREATE TABLE vp_400k (
	-- email	VARCHAR(64) NOT NULL
	-- ,first_name VARCHAR(64) NOT NULL
	-- ,last_name VARCHAR(64) NOT NULL
	-- ,address_1 VARCHAR(64) NOT NULL
	-- ,address_2 VARCHAR(64) NOT NULL
	-- ,apartment VARCHAR(32) NOT NULL
	-- ,city VARCHAR(64) NOT NULL
	-- ,state CHAR(2) NOT NULL
	-- ,zip CHAR(10) NOT NULL
	-- ,home_phone VARCHAR(15) NOT NULL
	-- ,datestamp TIMESTAMP NOT NULL
	-- ,ip_address VARCHAR(15) NOT NULL
	-- ,url VARCHAR(64) NOT NULL
	-- ,UNIQUE (email)
-- ) TYPE=MyISAM;
TRUNCATE TABLE vp_400k;

-- ---------------------------------------------------------------------------------------------------------

USE olp_session_harvest;

INSERT IGNORE INTO lead_generation.vp_400k
(email, first_name, last_name, address_1, address_2, apartment, city, state, zip, home_phone, datestamp, ip_address, url)
SELECT
    p.email ,p.first_name ,p.last_name ,r.address_1 ,r.address_2 ,r.apartment ,r.city ,r.state ,r.zip
    ,p.home_phone ,p.modified_date AS datestamp ,c.ip_address ,c.url
FROM personal p ,residence r ,campaign_info c
WHERE p.modified_date BETWEEN @early AND @late
AND p.first_name NOT IN ('', 'test') AND p.last_name NOT IN ('', 'test')
AND p.email != '' AND p.email NOT LIKE 'TEST%' AND p.email NOT LIKE '%@TSSMASTERD.COM'
AND r.application_id = p.application_id
AND r.address_1 != '' AND r.city != '' AND r.state != '' AND r.zip != ''
AND c.application_id = p.application_id
GROUP BY p.email;

SELECT COUNT(*) AS "plus_session_harvest" FROM lead_generation.vp_400k;

-- ---------------------------------------------------------------------------------------------------------

USE olp_pcl_partial;

INSERT IGNORE INTO lead_generation.vp_400k
(email, first_name, last_name, address_1, address_2, apartment, city, state, zip, home_phone, datestamp, ip_address, url)
SELECT
    p.email ,p.first_name ,p.last_name ,r.address_1 ,r.address_2 ,r.apartment ,r.city ,r.state ,r.zip
    ,p.home_phone ,p.modified_date AS datestamp ,'' AS ip_address ,c.url
FROM personal p ,residence r ,campaign_info c
WHERE p.modified_date BETWEEN @early AND @late
AND p.first_name NOT IN ('', 'test') AND p.last_name NOT IN ('', 'test')
AND p.email != '' AND p.email NOT LIKE 'TEST%' AND p.email NOT LIKE '%@TSSMASTERD.COM'
AND r.application_id = p.application_id
AND r.address_1 != '' AND r.city != '' AND r.state != '' AND r.zip != ''
AND c.application_id = p.application_id
GROUP BY p.email;

USE olp_pcl_visitor;

INSERT IGNORE INTO lead_generation.vp_400k
(email, first_name, last_name, address_1, address_2, apartment, city, state, zip, home_phone, datestamp, ip_address, url)
SELECT
    p.email ,p.first_name ,p.last_name ,r.address_1 ,r.address_2 ,r.apartment ,r.city ,r.state ,r.zip
    ,p.home_phone ,p.modified_date AS datestamp ,c.ip_address ,c.url
FROM personal p ,residence r ,campaign_info c
WHERE p.modified_date BETWEEN @early AND @late
AND p.first_name NOT IN ('', 'test') AND p.last_name NOT IN ('', 'test')
AND p.email != '' AND p.email NOT LIKE 'TEST%' AND p.email NOT LIKE '%@TSSMASTERD.COM'
AND r.application_id = p.application_id
AND r.address_1 != '' AND r.city != '' AND r.state != '' AND r.zip != ''
AND c.application_id = p.application_id
GROUP BY p.email;

SELECT COUNT(*) AS "plus_session_harvest" FROM lead_generation.vp_400k;

-- ---------------------------------------------------------------------------------------------------------

USE olp_ucl_partial;

INSERT IGNORE INTO lead_generation.vp_400k
(email, first_name, last_name, address_1, address_2, apartment, city, state, zip, home_phone, datestamp, ip_address, url)
SELECT
    p.email ,p.first_name ,p.last_name ,r.address_1 ,r.address_2 ,r.apartment ,r.city ,r.state ,r.zip
    ,p.home_phone ,p.modified_date AS datestamp ,'' AS ip_address ,c.url
FROM personal p ,residence r ,campaign_info c
WHERE p.modified_date BETWEEN @early AND @late
AND p.first_name NOT IN ('', 'test') AND p.last_name NOT IN ('', 'test')
AND p.email != '' AND p.email NOT LIKE 'TEST%' AND p.email NOT LIKE '%@TSSMASTERD.COM'
AND r.application_id = p.application_id
AND r.address_1 != '' AND r.city != '' AND r.state != '' AND r.zip != ''
AND c.application_id = p.application_id
GROUP BY p.email;

USE olp_ucl_visitor;

INSERT IGNORE INTO lead_generation.vp_400k
(email, first_name, last_name, address_1, address_2, apartment, city, state, zip, home_phone, datestamp, ip_address, url)
SELECT
    p.email ,p.first_name ,p.last_name ,r.address_1 ,r.address_2 ,r.apartment ,r.city ,r.state ,r.zip
    ,p.home_phone ,p.modified_date AS datestamp ,c.ip_address ,c.url
FROM personal p ,residence r ,campaign_info c
WHERE p.modified_date BETWEEN @early AND @late
AND p.first_name NOT IN ('', 'test') AND p.last_name NOT IN ('', 'test')
AND p.email != '' AND p.email NOT LIKE 'TEST%' AND p.email NOT LIKE '%@TSSMASTERD.COM'
AND r.application_id = p.application_id
AND r.address_1 != '' AND r.city != '' AND r.state != '' AND r.zip != ''
AND c.application_id = p.application_id
GROUP BY p.email;

SELECT COUNT(*) AS "plus_ucl" FROM lead_generation.vp_400k;

-- ---------------------------------------------------------------------------------------------------------

USE olp_ca_partial;

INSERT IGNORE INTO lead_generation.vp_400k
(email, first_name, last_name, address_1, address_2, apartment, city, state, zip, home_phone, datestamp, ip_address, url)
SELECT
    p.email ,p.first_name ,p.last_name ,r.address_1 ,r.address_2 ,r.apartment ,r.city ,r.state ,r.zip
    ,p.home_phone ,p.modified_date AS datestamp ,'' AS ip_address ,c.url
FROM personal p ,residence r ,campaign_info c
WHERE p.modified_date BETWEEN @early AND @late
AND p.first_name NOT IN ('', 'test') AND p.last_name NOT IN ('', 'test')
AND p.email != '' AND p.email NOT LIKE 'TEST%' AND p.email NOT LIKE '%@TSSMASTERD.COM'
AND r.application_id = p.application_id
AND r.address_1 != '' AND r.city != '' AND r.state != '' AND r.zip != ''
AND c.application_id = p.application_id
GROUP BY p.email;

USE olp_ca_visitor;

INSERT IGNORE INTO lead_generation.vp_400k
(email, first_name, last_name, address_1, address_2, apartment, city, state, zip, home_phone, datestamp, ip_address, url)
SELECT
    p.email ,p.first_name ,p.last_name ,r.address_1 ,r.address_2 ,r.apartment ,r.city ,r.state ,r.zip
    ,p.home_phone ,p.modified_date AS datestamp ,c.ip_address ,c.url
FROM personal p ,residence r ,campaign_info c
WHERE p.modified_date BETWEEN @early AND @late
AND p.first_name NOT IN ('', 'test') AND p.last_name NOT IN ('', 'test')
AND p.email != '' AND p.email NOT LIKE 'TEST%' AND p.email NOT LIKE '%@TSSMASTERD.COM'
AND r.application_id = p.application_id
AND r.address_1 != '' AND r.city != '' AND r.state != '' AND r.zip != ''
AND c.application_id = p.application_id
GROUP BY p.email;

SELECT COUNT(*) AS "plus_ca" FROM lead_generation.vp_400k;

-- ---------------------------------------------------------------------------------------------------------

USE olp_bb_partial;

INSERT IGNORE INTO lead_generation.vp_400k
(email, first_name, last_name, address_1, address_2, apartment, city, state, zip, home_phone, datestamp, ip_address, url)
SELECT
    p.email ,p.first_name ,p.last_name ,r.address_1 ,r.address_2 ,r.apartment ,r.city ,r.state ,r.zip
    ,p.home_phone ,p.modified_date AS datestamp ,c.ip_address ,c.url
FROM personal p ,residence r ,campaign_info c
WHERE p.modified_date BETWEEN @early AND @late
AND p.first_name NOT IN ('', 'test') AND p.last_name NOT IN ('', 'test')
AND p.email != '' AND p.email NOT LIKE 'TEST%' AND p.email NOT LIKE '%@TSSMASTERD.COM'
AND r.application_id = p.application_id
AND r.address_1 != '' AND r.city != '' AND r.state != '' AND r.zip != ''
AND c.application_id = p.application_id
GROUP BY p.email;

USE olp_bb_visitor;

INSERT IGNORE INTO lead_generation.vp_400k
(email, first_name, last_name, address_1, address_2, apartment, city, state, zip, home_phone, datestamp, ip_address, url)
SELECT
    p.email ,p.first_name ,p.last_name ,r.address_1 ,r.address_2 ,r.apartment ,r.city ,r.state ,r.zip
    ,p.home_phone ,p.modified_date AS datestamp ,c.ip_address ,c.url
FROM personal p ,residence r ,campaign_info c
WHERE p.modified_date BETWEEN @early AND @late
AND p.first_name NOT IN ('', 'test') AND p.last_name NOT IN ('', 'test')
AND p.email != '' AND p.email NOT LIKE 'TEST%' AND p.email NOT LIKE '%@TSSMASTERD.COM'
AND r.application_id = p.application_id
AND r.address_1 != '' AND r.city != '' AND r.state != '' AND r.zip != ''
AND c.application_id = p.application_id
GROUP BY p.email;

SELECT COUNT(*) AS "plus_bb" FROM lead_generation.vp_400k;

