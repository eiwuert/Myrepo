
INSERT IGNORE INTO company
(company_id, name_short, originating_system)
VALUES
(1, 'ufc', 'ecash_30'),
(2, 'ucl', 'ecash_30'),
(3, 'd1', 'ecash_30'),
(4, 'ca', 'ecash_30'),
(5, 'pcl', 'ecash_30');

INSERT IGNORE INTO status
(status_id, name_short)
VALUES
(1, 'active'),
(2, 'denied'),
(3, 'paid'),
(4, 'withdrawn'),
(5, 'bankruptcy'),
(6, 'internal_collections'),
(7, 'quickcheck'),
(8, 'external_collections'),
(9, 'unknown'),
(10, 'ignore'),
(11, 'amortization');
