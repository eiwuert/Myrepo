<?php

//for PHPUnit classes
require 'libolution/AutoLoad.1.php';
    
define('TEST_DB_HOST', 'localhost');
define('TEST_DB_USER', 'root');
define('TEST_DB_PASS', '');
define('TEST_DB', 'ldb_schema_only');
//if you have a local db called 'ldb_schema_only'
//mysqldump -d -hreader.ecashufc.ept.tss -uecash -pugd2vRjv | mysql -u root ldb_schema_only

set_include_path(get_include_path() . ':/virtualhosts/ecash_clk/code/');

?>
