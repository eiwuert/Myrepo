SELECT
	COUNT(*)
FROM
    personal p
    ,residence r
    ,campaign_info c
	,application a
WHERE
    (
		p.modified_date >= 20041016000000
		OR
		(p.modified_date BETWEEN 20040812000000 AND 20040911235959)
	)
AND 
	a.application_id = p.application_id
AND
	a.type = 'CUSTOMER'
AND
    p.first_name NOT IN ('', 'test')
AND 
    p.last_name NOT IN ('', 'test')
AND 
    p.home_phone NOT IN ('', '1231231234')
AND 
    p.email != ''
    AND
    p.email LIKE '%@%'
    AND
    p.email NOT LIKE 'TEST%'
    AND
    p.email NOT LIKE '%@TSSMASTERD.COM'
AND 
    r.application_id = p.application_id
AND 
    r.address_1 != ''
AND 
    r.city != ''
AND 
    r.state NOT IN ('', 'CA')
AND 
    r.zip != ''
AND 
    c.application_id = p.application_id
AND 
    c.ip_address != ''
;


INSERT INTO lead_generation.sunshine_2004_10_16
SELECT
    p.email
    ,p.first_name
    ,p.last_name
    ,r.address_1
    ,r.address_2
    ,r.apartment
    ,r.city
    ,r.state
    ,r.zip
    ,p.home_phone
    ,p.modified_date AS datestamp
    ,c.ip_address
FROM
    personal p
    ,residence r
    ,campaign_info c
	,application a
WHERE
    (
		p.modified_date >= 20041016000000
		OR
		(p.modified_date BETWEEN 20040812000000 AND 20040911235959)
	)
AND 
	a.application_id = p.application_id
AND
	a.type != 'CUSTOMER'
AND
    p.first_name NOT IN ('', 'test')
AND 
    p.last_name NOT IN ('', 'test')
AND 
    p.home_phone NOT IN ('', '1231231234')
AND 
    p.email != ''
    AND
    p.email LIKE '%@%'
    AND
    p.email NOT LIKE 'TEST%'
    AND
    p.email NOT LIKE '%@TSSMASTERD.COM'
AND 
    r.application_id = p.application_id
AND 
    r.address_1 != ''
AND 
    r.city != ''
AND 
    r.state NOT IN ('', 'CA')
AND 
    r.zip != ''
AND 
    c.application_id = p.application_id
AND 
    c.ip_address != ''
;

SELECT COUNT(*) FROM lead_generation.sunshine_2004_10_16;

