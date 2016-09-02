<?php
/**
 * This class retrieves the Cashbuzz ID. Currently this is all it does.
 *
 * @author Brian Feaver
 * @version 1.0.0
 */

class Cashbuzz_1
{
	/**
	 * Retrieves and returns the Cashbuzz ID.
	 *
	 * @param string $email The user's email
	 *
	 * @return int Returns the cashbuzz id
	 */
	public function Get_Cashbuzz_Id($email)
	{		
		$this->Setup_MySQL();

    	$query = "
    		SELECT
    			user_id
    		FROM
    			dapp_user
    		WHERE
    			email = '".mysql_real_escape_string($email)."'
    		ORDER BY user_id DESC
    		LIMIT 1";

    	$result = $this->mysql->Query($this->server['db'], $query);
    	$id = $this->mysql->Fetch_Column($result, 'user_id');
    	
    	return $id;
	}
	
	/**
	 * Sets up the MySQl connection for Cashbuzz.
	 */
	private function Setup_MySQL()
	{
		$this->server = array(
			'host' => 'db1.lpdataserver.com',
			'user' => 'sellingsource',
			'password' => 'password',
			'db' => 'cashbuzz');
		
		$this->mysql = new MySQL_4( $this->server['host'], $this->server['user'], $this->server['password']);
        $this->mysql->Connect();
	}
	
	private $mysql;
	private $server;
}
?>