<?php
/**
 * API to encrypt/decrypt data using OLP keys.
 *
 * @author Vinh Trinh 8/17/2007

	SAMPLE CODE
	<?php
	
	require_once('prpc/client.php');
	
	//Talk to brian feaver to get a user account setup. (LOCAL)
	//$olp_enc_api = "prpc://login:password@bfw.1.edataserver.com.ds83.tss:8080/olp_encryption_prpc.php";
	
	//(RC)
	$olp_enc_api = "prpc://login:password@rc.bfw.1.edataserver.com/olp_encryption_prpc.php";
	
	$ssns = array(822718648, 877652330,885922335);
	
	try
	{
		$result = new Prpc_Client($olp_enc_api, FALSE, 32);
		$s_e = $result->encrypt($ssns);
		$s_d = $result->decrypt($s_e);
		
		echo "<pre>";
		echo "Original:\n";
		print_r($ssns);
		echo "Encrypted:\n";
		print_r($s_e);
		echo "Decrypted:\n";
		print_r($s_d);
		echo "</pre>";
	
	}
	catch (Exception $e)
	{
		DIE($e);
	}
	
	
	?>
*/
ini_set('include_path', '.:/virtualhosts:'.ini_get('include_path'));
define('LIB5_DIR', '/virtualhosts/lib5/');
define('LIB_DIR', '/virtualhosts/lib/');
define('BFW_CODE_DIR', '/virtualhosts/bfw.1.edataserver.com/include/code/');
require_once(LIB_DIR . 'mysql.4.php');
require_once(LIB5_DIR . 'prpc/server.php');
require_once(LIB5_DIR . 'prpc/client.php');
require_once(BFW_CODE_DIR . 'server.php');
require_once(BFW_CODE_DIR . 'crypt_config.php');
require_once(BFW_CODE_DIR . 'crypt.singleton.class.php');


class olpEncryptionPRPC extends Prpc_Server
{
	private $crypt_object;
	private $logged_in;
	
	public function __construct()
	{	
		$this->logged_in = self::authenticate();
		$crypt_config = Crypt_Config::Get_Config(MODE);
		$this->crypt_object = Crypt_Singleton::Get_Instance($crypt_config["KEY"],$crypt_config["IV"]);	
		parent::__construct();
	}

	
	
	private function authenticate()
	{

		define('OLP_ENCRYPTION_LOGIN', $_SERVER['PHP_AUTH_USER']);
		define('OLP_ENCRYPTION_PW', $_SERVER['PHP_AUTH_PW']);
		
		
		// Set the mode
		switch( TRUE )
		{
			case preg_match('/^rc\./', $_SERVER['SERVER_NAME']):
				define('MODE', 'RC');
				break;
			case preg_match('/ds\d{1,3}\.tss/', $_SERVER['SERVER_NAME']):
				define('MODE', 'LOCAL');
				break;
			default:
				define('MODE', 'LIVE');
				break;
		}
	
		$login = OLP_ENCRYPTION_LOGIN;
		$pw = OLP_ENCRYPTION_PW;
		$server = Server::Get_Server(MODE,'BLACKBOX');
		$encrypted_pw = md5($login.$pw);
				
		try
		{
			$sql = new MySQL_4($server['host'], $server['user'], $server['password'],FALSE);
			$sql->Connect();
		}
		catch(Exception $e)
		{
			throw $e;
		}
		
		$authentication_query = "
			SELECT
				*
			FROM
				encryption_agent
			WHERE
				login = '$login' AND
				crypt_password = '$encrypted_pw'
		";
		
	
		$result = $sql->Query($server['db'],$authentication_query);
	
		if($sql->Fetch_Row($result))
		{	
			return TRUE;
		}
		else 
		{
			return FALSE;
		}

	}
	
	/**
	 * Decrypt: Decrypt an array or value using OLP Keys.
	 *
	 * @param  string $input - array or value to decrypt
	 *
	 * @return  string - array or value of input decrypted
	 */
	public function decrypt($input)
	{
		
		if($input && $this->logged_in == TRUE)
		{
			if(is_array($input))
			{
				foreach($input as $key => $value)
				{
					$return_array[$key] = $this->crypt_object->decrypt((string)$value);
				}
				return $return_array;
			}
			else
			{
				return $this->crypt_object->decrypt((string)$input);
			}
		}
		else 
		{
			header('HTTP/1.0 401 Unauthorized Username/Password');
			header('WWW-Authenticate: Basic realm="OLP Encryption"');
			return FALSE;
		}
	}

	/**
	 * Decrypt: Decrypt an array or value using OLP Keys.
	 *
	 * @param  string $input - array or value to decrypt
	 *
	 * @return  string - array or value of input encrypted
	 */
			
	public function encrypt($input)
	{		
		if($input && $this->logged_in == TRUE)
		{
			if(is_array($input))
			{
				foreach($input as $key => $value)
				{
					$return_array[$key] = $this->crypt_object->encrypt((string)$value);
				}
				return $return_array;
			}
			else
			{
				return $this->crypt_object->encrypt((string)$input);
			}
		}
		else 
		{
			header('HTTP/1.0 401 Unauthorized Username/Password');
			header('WWW-Authenticate: Basic realm="OLP Encryption"');
			return FALSE;
		}
	}

}


$olp_encryption_prpc = new olpEncryptionPRPC();
$olp_encryption_prpc->_Prpc_Strict = TRUE;
$olp_encryption_prpc->Prpc_Process();	


?>
