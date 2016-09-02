<?php

// Directory paths
define('LIB_DIR', '/virtualhosts/lib/');
define('LIBOLUTION_DIR', '/virtualhosts/libolution/');
define('LIB5_DIR', '/virtualhosts/lib5/');
define('OLP_LIB', '/virtualhosts/olp_lib/');
define('BASE_DIR', dirname(__FILE__) . '/../');
define('CODE_DIR', dirname(__FILE__) . '/../include/code/');
define('OLP_DIR', dirname(__FILE__) . '/../../bfw.1.edataserver.com/include/modules/olp/');

// AutoLoad (below) requires that the blackbox path be in the root
ini_set('include_path', ini_get('include_path') . ':' . BASE_DIR . ':' . CODE_DIR . ':' . OLP_DIR . ':' . OLP_LIB . ':' . LIB5_DIR . ':' . LIB_DIR);

require_once(LIBOLUTION_DIR.'AutoLoad.1.php');
require_once(OLP_DIR . 'list_mgmt_collect.php');
require_once(LIB_DIR . 'mysql.4.php');

/**
 * PHPUnit test class for the List_Mgmt_Collect class.
 *
 * @author Rob Voss <rob.voss@sellingsource.com>
 */
class ListMgmtCollectTest extends PHPUnit_Extensions_Database_TestCase
{
	public static function insertIntoListMgmtBufferDataProvider()
	{
		return array(
			array(
				'application_id' 	=> '900021881',
				'email' 			=> 'rob.voss@sellingsource.com',
				'first_name' 		=> 'Testfirst',
				'last_name' 		=> 'Testlast',
				'ole_site_id' 		=> 123,
				'ole_list_id' 		=> 431,
				'group_id' 			=> 2,
				'mode' 				=> 'RC',
				'license_key' 		=> 'afsd3fadsf3rrfadsf',
				'address_1' 		=> '1234 Street St.',
				'apartment' 		=> '#714',
				'city' 				=> 'Cityplace',
				'state' 			=> 'NV',
				'zip' 				=> '89052',
				'url' 				=> 'http://www.blah.com',
				'phone_home' 		=> '7025554321',
				'phone_cell' 		=> '7025554322',
				'date_of_birth' 	=> '03/23/1977',
				'promo_id' 			=> '6789',
				'bb_vendor_bypass' 	=> 0,
				'tier' 				=> 0
				)/*,
			array(
				'application_id' 	=> '',
				'email' 			=> 'rob.voss@sellingsource.com',
				'first_name' 		=> 'Testfirst',
				'last_name' 		=> 'Testlast',
				'ole_site_id' 		=> '',
				'ole_list_id' 		=> '',
				'group_id' 			=> '',
				'mode' 				=> '',
				'license_key' 		=> '',
				'address_1' 		=> '1234 Street St.',
				'apartment' 		=> '#714',
				'city' 				=> 'Cityplace',
				'state' 			=> 'NV',
				'zip' 				=> '89052',
				'url' 				=> '',
				'phone_home' 		=> '7025554321',
				'phone_cell' 		=> '7025554322',
				'date_of_birth' 	=> '03/23/1977',
				'promo_id' 			=> '',
				'bb_vendor_bypass' 	=> 0,
				'tier' 				=> 0,
				)*/
			);
	}
	
	/**
	 * Enter description here...
	 *
	 * @dataProvider insertIntoListMgmtBufferDataProvider
	 */
	public function testInsertIntoListMgmtBuffer(
		$application_id,
		$email,
		$first_name,
		$last_name,
		$ole_site_id,
		$ole_list_id,
		$group_id,
		$mode,
		$license_key,
		$address_1,
		$apartment,
		$city,
		$state,
		$zip,
		$url,
		$phone_home,
		$phone_cell,
		$date_of_birth,
		$promo_id,
		$bb_vendor_bypass,
		$tier
		)
	{
		// Set up the DB
		$db = $this->TEST_DB_MYSQL4();

		$list_management = new List_Mgmt_Collect($db, 'olp');
		
		$tmp = $list_management->Insert_Into_List_Mgmt_Buffer(
			$application_id,
			$email,
			$first_name,
			$last_name,
			$ole_site_id,
			$ole_list_id,
			$group_id,
			$mode,
			$license_key,
			$address_1,
			$apartment,
			$city,
			$state,
			$zip,
			$url,
			$phone_home,
			$phone_cell,
			$date_of_birth,
			$promo_id,
			$bb_vendor_bypass,
			$tier
			);
			
		$this->assertEquals($expected_data, $tmp);
	}
	
		/**
	 * Gets the database connection for this test.
	 *
	 * @return PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
	 */
	protected function getConnection()
	{
		return $this->createDefaultDBConnection($this->TEST_DB_PDO(), $this->TEST_GET_DB_INFO()->name);
	}

	/**
	 * Gets the data set for this test.
	 *
	 * @return PHPUnit_Extensions_Database_DataSet_XmlDataSet
	 */
	protected function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/ListMgmt.fixture.xml');
	}
	
	/**
	 * Returns the test database information.
	 *
	 * @return object
	 */
	function TEST_GET_DB_INFO()
	{
		$db_info = new stdClass();
	
		$db_info->host = 'localhost';
		$db_info->port = 3306;
		$db_info->user = 'root';
		$db_info->pass = '';
		$db_info->name = 'olp';
		$db_info->ldb_name = 'list_mgmt_buffer';
	
		return $db_info;
	}
	
	/**
	 * Returns a new MySQL_4 connection for the test db.
	 *
	 * @return MySQL_4
	 */
	function TEST_DB_MYSQL4()
	{
		$db_info = $this->TEST_GET_DB_INFO();
	
		$db = new MySQL_4("{$db_info->host}:{$db_info->port}", $db_info->user, $db_info->pass);
		
		$db->Connect();
	
		return $db;
	}
	
	/**
	 * Returns a PDO connection object to the test database.
	 *
	 * @return PDO
	 */
	function TEST_DB_PDO()
	{
		$db_info = $this->TEST_GET_DB_INFO();
	
		return new PDO(
			"mysql:host={$db_info->host};port={$db_info->port};dbname={$db_info->name}",
			$db_info->user,
			$db_info->pass
		);
	}

}
?>
