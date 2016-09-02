<?php

/**
 * Class to handle authentication with eCash when a customer uses
 * a login link of some sort.
 * 
 * @author Chris Barmonde <chris.barmonde@sellingsource.com>
 */
class OLP_ECashClient_Authentication extends OLP_ECashClient_RPC1
{
	const STATUS_PASS = 'pass';
	const STATUS_FAIL = 'fail';
	const STATUS_LOCKED = 'locked';
	const STATUS_LAST_ATTEMPT = 'fail_one_remaining';
	
	/**
	 * RPC Connection timeout
	 * 
	 * @var int
	 */
	protected $connection_timeout = 10;

	/**
	 * Cached API variable
	 * 
	 * @var Rpc_Client_1
	 */
	protected $api;
	
	/**
	 * Result from eCash client
	 * 
	 * @var string
	 */
	protected $result;
	
	/**
	 * Whether or not a validate() call has been made.
	 * 
	 * @var bool
	 */
	protected $call_made = FALSE;
	
	/**
	 * Returns a verbose description of the driver in human readable form.
	 *
	 * @return string
	 */
	public function getDriverDescription()
	{
		return 'API to access eCash authentication.';
	}
	
	/**
	 * The filename of the API.
	 *
	 * @return string
	 */
	protected function getURLFilename()
	{
		return 'olp.2.php';
	}
	
	/**
	 * Returns a Rpc_Client_1 of eCash's API.
	 *
	 * @return RPC_Client_1
	 */
	protected function getAPI()
	{
		if (!$this->api instanceof Rpc_Client_1)
		{
			$this->api = parent::getAPI();
			// Replace the above line with this one if you only need to
			// test the OLP functionality and the eCash API is unavailable.
			//$this->api = new Mock_OLP_ECashClient_AuthenticationAPI();
		}
		
		return $this->api;
	}
	

	
	/**
	 * Validates authentication. Returns TRUE on a pass response.
	 *
	 * @param int $application_id,
	 * @param string $phone_work
	 * @param string $dob
	 * @param string $page
	 * @return bool
	 */
	public function validate($application_id, $phone_work, $dob, $page = NULL)
	{
		if (empty($this->result))
		{
			$this->result = $this->getAPI()->validate($application_id, $phone_work, $dob, $page);
			$this->call_made = TRUE;
		}
		
		return ($this->result == self::STATUS_PASS);
	}
	
	/**
	 * Returns TRUE if this app has one auth attempt left.
	 *
	 * @return bool
	 */
	public function isLastAttempt()
	{
		return $this->result == self::STATUS_LAST_ATTEMPT;
	}
	
	/**
	 * Whether or not the validate() call did not return properly.
	 *
	 * @return bool
	 */
	public function callFailed()
	{
		return $this->call_made && !in_array(
			$this->result,
			array(
				self::STATUS_FAIL,
				self::STATUS_LAST_ATTEMPT,
				self::STATUS_LOCKED,
				self::STATUS_PASS
			)
		);
	}
	
	/**
	 * Whether or not the application is locked out
	 *
	 * @param int $application_id
	 * @param string $page
	 * @return bool
	 */
	public function isLocked($application_id, $page = NULL)
	{
		$locked_out = ($this->result == self::STATUS_LOCKED);
		
		if (empty($this->result))
		{
			if ($this->getAPI()->isLocked($application_id, $page))
			{
				$this->result = self::STATUS_LOCKED;
				$locked_out = TRUE;
			}
		}
		
		return $locked_out;
	}
}