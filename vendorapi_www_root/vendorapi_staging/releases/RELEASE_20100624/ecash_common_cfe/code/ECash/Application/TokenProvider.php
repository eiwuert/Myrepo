<?php
require_once(ECASH_COMMON_DIR . 'Condor/Condor_Commercial.php');
/**
 * Class ECash_Application_TokenProvider
 * Used to generator Tokens for Documents for Ecash Commercial companies
 * 
 * 
 * 
 */

class ECash_Application_TokenProvider implements ECash_Documents_IToken
{
	protected $app;
	protected $tokens;
	protected $loaded;
	protected $generic_email;
	protected $db;
	
	public function __construct(ECash_Application $app, $db)
	{
		$this->app = $app;
		$this->db = $db;
		$loaded = false;
	}
	/**
	 * Set Generic Email
	 * 
	 * Sets inforation to be used with eCasg Eail Queues. Could be used for otehr documents in the future.
	 *
	 * @param unknown_type $sender
	 * @param unknown_type $subject
	 * @param unknown_type $message
	 */
	public function Set_Generic_Email($sender, $subject, $message)
	{
		$this->generic_email	= array("sender" => $sender, "subject" => $subject, "message" => $message);
	}
	/**
	 * load
	 * 
	 * loads the token object 
	 */
	protected function load()
	{
		//@todo:retrieve all data needed to create tokens
		require_once 'config.6.php';
		require_once 'mysql.4.php';
				
		$stat_host = ECash::getConfig()->STAT_MYSQL_HOST;
		$stat_user = ECash::getConfig()->STAT_MYSQL_USER;
		$stat_pass = ECash::getConfig()->STAT_MYSQL_PASS;
		try
		{
			$scdb = new MySQL_4($stat_host, $stat_user, $stat_pass);
			$scdb->Connect();
	
			// The following is a quirk in how Config_6 is using MySQL_4
			$scdb->db_info['db'] = 'management';
	
			$scdb->Select('management');
			$config_6 = new Config_6($scdb);
			$token_gen = new Condor_Commercial($this->db,$config_6,$this->app->getCompanyId(),' ',$this->app);
		
			if(!empty($this->generic_email))
				$token_gen->Set_Generic_Email($this->generic_email['sender'], $this->generic_email['subject'], $this->generic_email['message']);
			
						//doing old tokens last to override new tokens
			foreach($token_gen->Get_Tokens() as $token_name => $token_value)
			{
				$this->tokens[$token_name] = $token_value;
			}

			$token_manager = ECash::getFactory()->getTokenManager();
			$db_tokens = $token_manager->getTokensbyApplicationId($this->app->application_id);

			foreach($db_tokens as $token_name => $token)
			{
				$this->tokens[$token_name] = $token->getValue();

			}

		}
		catch(Exception $e)
		{
			throw $e;	
		}
		$this->loaded = true;
	}
	/**
	 * get Tokens
	 * 
	 * @return stdclass populated with tokens 
	 */
	public function getTokens()
	{
		if(!$this->loaded)
		{
			$this->load();
		}
		return $this->tokens;
	}
	/**
	 * get Token
	 * 
	 * @return string populated with token 
	 */
	public function getToken($name)
	{
		if(!$this->loaded)
		{
			$this->load();
		}
		if(isset($this->tokens->$name))
			return $this->tokens->$name;
		else
			return null;
	}
	
	
	
}



?>
