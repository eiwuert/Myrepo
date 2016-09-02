<?php

require_once 'maintenance_mode.php';
require_once 'config.php';

require_once 'prpc/server.php';
require_once 'prpc/client.php';

require_once BFW_CODE_DIR . 'setup_db.php';

/**
 * A Brick and Mortar API class.  Will return information
 * about B&M stores.
 * 
 * @author Chris Barmonde <christopher.barmonde@sellingsource.com>
  */
class B_And_M_PRPC extends Prpc_Server
{
	/**
	 * TRUE if maintenance mode is on
	 *
	 * @var bool
	 */
	private $maintenance_mode = FALSE;
	
	/**
	 * A database object to connect to OLP
	 *
	 * @var DB_Database_1
	 */
	private $olp_db;
	
	/**
	 * Constructor.  Sets up the maintenance_mode variable.
	 *
	 */
	public function __construct()
	{
		$maintenance_mode = new Maintenance_Mode();
		$this->maintenance_mode = !$maintenance_mode->Is_Online();
		
		$this->olp_db = Setup_DB::Get_PDO_Instance('blackbox', BFW_MODE);
		
		parent:: __construct();
	}
	
	/**
	 * Returns a store's information by ZIP code.  This function will
	 * throw exceptions under certain conditions with the following
	 * error codes:
	 * 
	 *  0) Maintenance Mode is on.
	 * 	1) Invalid ZIP code (not five integers)
	 *  2) ZIP not found in database
	 *  3) Database error occurs
	 * 
	 *
	 * @param int $zip The five-digit ZIP code to check for.
	 * @return array The array with the store's information.
	 * 			array (
	 * 				'zip_code' => 12345,
	 * 				'store_id' => 123,
	 * 				'address1' => '123 Fake St.'
	 * 				'address2' => 'Suite E',
	 * 				'city' => 'Austin',
	 * 				'state' => 'TX',
	 * 				'phone' => '5552345678',
	 * 				'fax' => '5552345678'
	 * 			)
	 */
	public function getStoreByZip($zip)
	{
		if ($this->maintenance_mode)
		{
			throw new Exception('OLP is currently in maintenance mode.', 0);
		}
		
		if (!preg_match('/^\d{5}$/', $zip))
		{
			throw new Exception("Invalid ZIP Code: {$zip}", 1);
		}

		$data = array();
		try
		{
			$query = "SELECT
					zip_code,
					store_id,
					address1,
					address2,
					city,
					state,
					phone1 AS phone,
					fax
				FROM ace_stores
				WHERE zip_code = ?";
			
			$statement = $this->olp_db->queryPrepared($query, array($zip));
			if ($statement->rowCount() > 0)
			{
				$data = $statement->fetch(PDO::FETCH_ASSOC);
			}
			else
			{
				throw new Exception("No records found for {$zip}", 2);
			}
		}
		catch (PDOException $e)
		{
			throw new Exception("Database error: {$e->getMessage()}", 3);
		}
		
		return $data;
	}
}

$cm_prpc = new B_And_M_PRPC();
$cm_prpc->_Prpc_Strict = TRUE;
$cm_prpc->Prpc_Process();

?>