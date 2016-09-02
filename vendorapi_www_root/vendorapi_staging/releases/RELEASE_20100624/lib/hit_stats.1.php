<?php
# vim: set ts=4:

require_once("config.4.php");
require_once("setstat.1.php");

/*

EXAMPLE:

Hit::Stats(
	array(
		"license_key" => LICENSE_KEY,
		"sql" => $sql,
		"promo_id" => 10000,
		"promo_sub_code" => "",
		"column" => "visitors",
		"hits" => 1
	)
);

*/

class Hit
{
	function Stats($args)
	{
		assert(is_array($args));
		assert(isset($args["license_key"]));
		assert(isset($args["sql"]));
			assert(is_object($args["sql"]));
			assert(is_a($args["sql"], "MySQL_3"));
		assert(array_key_exists("promo_id", $args));
		assert(array_key_exists("promo_sub_code", $args));
		assert(isset($args["column"]));
			assert(is_string($args["column"]));
		!isset($args["hits"]) && $args["hits"] = 1;
		assert(isset($args["hits"]));
		assert(is_numeric($args["hits"]));

		#print_r($args);

    	# get config object for site by license key
    	$cfg = Config_4::Get_Site_Config(
			$args["license_key"],
        	$args["promo_id"],
        	$args["promo_sub_code"]
    	);
		#print_r($cfg);
    	# create stat object
    	$stat = new Set_Stat_1();
    	# pass in all this shit and get the block_id back
    	$pizzle = $stat->Setup_Stats(
        	$cfg->site_id, // uh...
        	$cfg->vendor_id, // uh...
        	$cfg->page_id, // uh...
        	$args["promo_id"], // duh
        	$args["promo_sub_code"], // sub code
        	$args["sql"], // sql obj
        	$cfg->stat_base, // database
        	$cfg->promo_status // no idea
    	);      
    	#die("<pre>" . print_r($pizzle));
		#print_r($pizzle);
    	# set stat with all the various config
    	$rs = $stat->Set_Stat(
        	$pizzle->block_id, // block_id
        	$pizzle->tablename, // table
        	$args["sql"], // sql object
        	$cfg->stat_base, // db
        	$args["column"], // column
        	$args["hits"] // one #
    	);
		#print_r($rs);

    	return true;
	}

	function Stats_Promoless($license_key, $sql, $col, $hits=1)
	{
		return Hit::Stats(
			array(
				"license_key" => $license_key,
				"sql" => $sql,
				"promo_id" => 10000,
				"promo_sub_code" => "",
				"column" => $col,
				"hits" => $hits
			)
		);
	}
}

?>
