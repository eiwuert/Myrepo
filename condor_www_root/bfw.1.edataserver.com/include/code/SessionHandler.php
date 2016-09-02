<?php
require_once('session.8.php');
require_once('crypt.singleton.class.php');
require_once('crypt_config.php');
/**
 * SessionHandler extends the Session_8 class and implements the Garbage_Collection function for
 * session deletions.
 *
 * @author Brian Feaver
 */
class SessionHandler extends Session_8
{
	/**
	 * Class singleton crypt object abstraction of libolution security which is used to encrypt and decrypt.
	 *
	 * @var Crypt_Singleton
	 */
	private $crypt;
	
	/**
	 * SessionHandler constructor.
	 *
	 * @param MySQL_4 $sql
	 * @param string $database
	 * @param string $table
	 * @param string $sid
	 * @param string $name
	 * @param string $compression
	 * @param bool $autocall_session_write_close
	 * @param int $max_size
	 */
	public function __construct(
		&$sql,
		$database,
		$table,
		$sid = NULL,
		$name = 'ssid',
		$compression = 'gz',
		$autocall_session_write_close = false,
		$max_size = 1000000
	)
	{
		$crypt_config = Crypt_Config::Get_Config(BFW_MODE);
		$this->crypt = Crypt_Singleton::Get_Instance($crypt_config['KEY'],$crypt_config['IV']);
		if ($autocall_session_write_close) register_shutdown_function(array($this, 'Remove_Sensitive_Data'));
		parent::__construct(
			$sql,
			$database,
			$table,
			$sid,
			$name,
			$compression,
			$autocall_session_write_close,
			$max_size
		);
		
		self::Retrieve_Sensitive_Data();
	}
	
	/**
	 * Session garbage collection function.
	 * 
	 * Deletes all sessions that are older than current time minus $session_life.
	 *
	 * @param int $session_life
	 */
	public function Garbage_Collection($session_life)
	{
		$expire_date = date('Y-m-d H:i:s', time() - $session_life);
		$query = "DELETE LOW_PRIORITY FROM {$this->table} WHERE date_modified < '{$expire_date}' LIMIT 200";
		$this->sql->Query($this->database, $query);
		return TRUE;
	}
	

		
	/**
	 * Returns nothing but populates the session with the unencoded versions
	 * of sensitive data
	 */
	public function Retrieve_Sensitive_Data()
	{
		if($_SESSION['data']['use_encrypt'])
		{
			if(isset($_SESSION['data']['social_security_number_encrypted']))
			{
				$_SESSION['data']['social_security_number'] = $this->crypt->decrypt($_SESSION['data']['social_security_number_encrypted']);
				
				$_SESSION['data']['ssn_part_1']=substr($_SESSION['data']['social_security_number'],0,3);
				$_SESSION['data']['ssn_part_2']=substr($_SESSION['data']['social_security_number'],3,2);
				$_SESSION['data']['ssn_part_3']=substr($_SESSION['data']['social_security_number'],5,4);
			}
			
			if(isset($_SESSION['data']['dob_encrypted']))
			{
				$_SESSION['data']['dob'] = $this->crypt->decrypt($_SESSION['data']['dob_encrypted']);
				
				list($m1,$d1,$y1) = explode('/',$_SESSION['data']['dob']);
				$_SESSION['data']['date_dob_m']=$m1;
				$_SESSION['data']['date_dob_d']=$d1;
				$_SESSION['data']['date_dob_y']=$y1;
			}
			
			if(isset($_SESSION['data']['bank_aba_encrypted']))
			{
				$_SESSION['data']['bank_aba'] = $this->crypt->decrypt($_SESSION['data']['bank_aba_encrypted']);
			}
			
			if(isset($_SESSION['data']['bank_account_encrypted']))
			{
				$_SESSION['data']['bank_account'] =$this->crypt->decrypt($_SESSION['data']['bank_account_encrypted']);
			}
		}
	}
	
	/**
	 * Removes sensitive data from the session and sets a flag labeling a session as encrypted
	 *
	 */
	public function Remove_Sensitive_Data()
	{
		
		if(isset($_SESSION['data']['dob_encrypted'])) 
		{
			unset($_SESSION['data']['date_dob_y']);
			unset($_SESSION['data']['date_dob_m']);
			unset($_SESSION['data']['date_dob_d']);
			unset($_SESSION['data']['ssn_part_1']);
			unset($_SESSION['data']['ssn_part_2']);
			unset($_SESSION['data']['ssn_part_3']);
			unset($_SESSION['data']['bank_aba']);
			unset($_SESSION['data']['bank_account']);
			unset($_SESSION['data']['dob']);
			unset($_SESSION['data']['social_security_number']); 
			unset($_SESSION['suppression_results']);  //Added in for bug #7510
			$_SESSION['data']['use_encrypt'] = true;
		}
	}
}

?>
