<?php

/**
 * resets specified apps to 2nd tier pending (for [#53649] / [#53232])
 * 
 * @author Justin Foell <justin.foell@sellingsource.com>
 */

require_once dirname(realpath(__FILE__)) . '/../www/config.php';
require_once LIB_DIR . 'common_functions.php';
require_once SQL_LIB_DIR . 'util.func.php';

$factory = ECash::getFactory();
$db = $factory->getDB();
$app_ids = array(
/* PUT APP IDS HERE */
);

foreach($app_ids as $app_id)
{
	echo "Updating $app_id to second tier (pending)\n";
	try
	{
		Update_Status(NULL, $app_id, array('pending', 'external_collections', '*root'), null, null, true);
	}
	catch(ECash_Application_NotFoundException $e)
	{
		echo $e->getMessage() . PHP_EOL;
	}
}

echo "Resetting batch item count\n";
$query = "update ext_collections_batch
set item_count = item_count - ?
where ext_collections_batch_id =
(
   select ext_collections_batch_id
   from ext_collections
   where application_id = ?
)
";
$db->execPrepared($query, array(count($app_ids), $app_ids[0]));

echo "Removing apps from second tier batch\n";
$query = "delete from ext_collections
where application_id in (".join(',', $app_ids).")";
$db->exec($query);



?>