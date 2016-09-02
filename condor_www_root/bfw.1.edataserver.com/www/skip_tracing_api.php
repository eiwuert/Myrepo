<?php

/**
 * Contains Skip_Trace class
 * 
 * @author Matthew Jump [MJ] <matthew.jump@sellingsource.com>
 */

define('BFW_CODE_DIR', '/virtualhosts/bfw.1.edataserver.com/include/code/');

// Required files
require_once ('prpc/server.php');
require_once ('prpc/client.php');

include_once (BFW_CODE_DIR.'server.php');
include_once (BFW_CODE_DIR.'setup_db.php');	
		
/**
 * Gathers contact information related to social security numbers.
 * 
 * @author Matthew Jump [MJ] <matthew.jump@sellingsource.com>
 */
class Skip_Trace extends Prpc_Server
{
	/**
     * Sql object
     * 
     * @var string
     */
	private $sql;
	
	/**
     * Holds encrypted/decrypted values for ssn
     * 
     * @var array
     */
	private $encryption = array();
	
	/**
	 * Gathers contact information for passed in socials
	 * 
	 * For each social passed in this function will get all applications that CLK accepted or applications
	 * that ran a teletrack call (datax_perf) for CLK in the last 60 days. It will then gather home phone, 
	 * cell_phone, email, work phone and extension, and reference phone numbers for those applications 
	 * and return the info in a nested array.
	 * 
	 * @param string/array $socials		The social(s) that are to be run
	 * @return array/bool $result_arr	Array containing requested information in [social][app_id][info] format
	 * 									This function may also return FALSE if there are no results or it can not run
	 */
	public function runSkipTrace($socials)
	{

		//Parameter checking and formatting.
		if ((is_array($socials) && count($socials) == 0) || trim($socials) == '')
		{
			//Nothing was passed in.
			return FALSE;
		}
		
		$mode = $this->getMode();
		
		/**
		 * Run on RC for dev and local.
		 * If being run on live use reader(SLAVE).
		 */
		$mode = (strcasecmp($mode, 'LIVE') !== 0)?'RC':'SLAVE';
		$this->sql = Setup_DB::Get_Instance("blackbox", $mode);
		
		//Able to locate any applications for these socials?
		if (!($applications = $this->getApplications($socials)))
		{
			return FALSE;
		}
		$query_applications = $this->createWhere($applications);

		unset($applications);

		$query ='
		SELECT DISTINCT
			p.application_id,
			p.social_security_number,
			p.home_phone, 
			p.cell_phone,
			p.email,
			p.fax_phone,
			p.alt_email,
			e.work_phone,
			e.work_ext,
			c.phone
			
		FROM 
			personal_encrypted p
			LEFT JOIN employment e 
				ON (e.application_id = p.application_id)
			LEFT JOIN personal_contact c 
				ON (c.application_id = p.application_id)
		WHERE 
			p.application_id '.$query_applications;
		$result = $this->sql->Query($this->sql->db_info['db'], $query);

		//Create result array
		$social_info = array();
		
		//Add personal data
		while ($temp_result = $this->sql->Fetch_Array_Row($result))
		{
			$decrypted_ssn = isset($this->encryption[$temp_result['social_security_number']]) ? 
				$this->encryption[$temp_result['social_security_number']] : 
				$this->decrypt($temp_result['social_security_number']);
			//Home Phone
			if (isset($temp_result['home_phone']) && trim($temp_result['home_phone']) != '')
			{		
				$social_info[$decrypted_ssn][$temp_result['application_id']]['home_phone'] = $temp_result['home_phone'];
			}
			//Cell Phone
			if (isset($temp_result['cell_phone']) && trim($temp_result['cell_phone']) != '')
			{
				$social_info[$decrypted_ssn][$temp_result['application_id']]['cell_phone'] = $temp_result['cell_phone'];
			}
			//Email
			if (isset($temp_result['email']) && trim($temp_result['email']) != '')
			{
				$social_info[$decrypted_ssn][$temp_result['application_id']]['email'] = $temp_result['email'];
			}
			//Alt Email
			if (isset($temp_result['alt_email']) && trim($temp_result['alt_email']) != '')
			{
				$social_info[$decrypted_ssn][$temp_result['application_id']]['alt_email'] = $temp_result['alt_email'];
			}
			//Fax Phone
			if (isset($temp_result['fax_phone']) && trim($temp_result['fax_phone']) != '')
			{
				$social_info[$decrypted_ssn][$temp_result['application_id']]['fax_phone'] = $temp_result['fax_phone'];
			}
			//Work phone
			if (isset($temp_result['work_phone']) && trim($temp_result['work_phone']) != '')
			{
				$social_info[$decrypted_ssn][$temp_result['application_id']]['work_phone'] = $temp_result['work_phone'];
			}
			//Work ext
			if (isset($temp_result['work_ext']) && trim($temp_result['work_ext']) != '')
			{
				$social_info[$decrypted_ssn][$temp_result['application_id']]['work_ext'] = $temp_result['work_ext'];
			}
			//Reference phone
			if (isset($temp_result['phone']) && trim($temp_result['phone']) != '')
			{
				$name = (!isset($social_info[$decrypted_ssn][$temp_result['application_id']]['ref1_phone']))? 'ref1_phone' : 'ref2_phone';
				$social_info[$decrypted_ssn][$temp_result['application_id']][$name] = $temp_result['phone'];
			}
		}
		return $social_info;
	}
	
	/**
	 * Gets application_ids for passed in social(s) over the last 60 days if sold to CLK or did teletrack on CLK.
	 *
	 * @param array/string $socials		An array of socials or a single social
	 * @return array/bool $result_arr	An array of application_ids
	 * 									This function may also return FALSE if there are no results or it can not run
	 */
	private function getApplications($socials)
	{
		if (is_array($socials))
		{
			//An array was passed in.
			$socials = array_unique($socials);
			$encrypted_socials = array();
			$pos = 0;
			//Check and encrypt any decrypted socials
			foreach ($socials as $social)
			{
				if (preg_match('/^[0-9]*$/', $social))
				{
					//Social is not encrypted
					$encrypted_socials[$pos] = $this->encrypt($social);
					$this->encryption[$encrypted_socials[$pos]] = $social;
				}
				else
				{
					$encrypted_socials[$pos] = mysql_real_escape_string($social);
				}
				$pos++;
			}
			$socials = $encrypted_socials;
		}
		else
		{
			//A single value was passed in.
			if (preg_match('/^[0-9]*$/', $socials))
			{
				//Social appears to not be encrypted
				$encrypted_socials = $this->encrypt($socials);
				$this->encryption[$encrypted_socials] = $socials;
				$socials = $encrypted_socials;
			}
			else
			{
				$socials = array(mysql_real_escape_string($socials));
			}
		}
		
		//Create array from results.
		$result_arr = array();
		
		//Run one social at a time
		foreach ($socials as $social)
		{
			//Get applications sold to CLK in the last 60 days.
			$query = "
			SELECT DISTINCT 
				a.application_id
			FROM
				personal_encrypted p
				LEFT JOIN application a 
					ON a.application_id = p.application_id
			WHERE
				a.target_id IN (28,29,30,31,32)
				AND a.application_type IN ('PENDING','CONFIRMED','AGREED','EXPIRED')
				AND p.social_security_number = '$social'";

			$result = $this->sql->Query($this->sql->db_info['db'], $query);
	
			while ($temp_result = $this->sql->Fetch_Array_Row($result))
			{
				array_push($result_arr, $temp_result['application_id']);
			}
	
			//Get applications that ran datax perf for clk.
			$query ="
			SELECT DISTINCT 
				p.application_id
			FROM 
				personal_encrypted p
				LEFT JOIN authentication a 
					ON a.application_id = p.application_id
			WHERE 
				a.authentication_source_id = 2
				AND p.social_security_number = '$socials'";

			$result = $this->sql->Query($this->sql->db_info['db'], $query);
			
			while ($temp_result = $this->sql->Fetch_Array_Row($result))
			{
				array_push($result_arr, $temp_result['application_id']);
			}
		}
		//We did not get any results from either query.
		if (count($result_arr) == 0)
		{
			return FALSE;
		}
		return(array_unique($result_arr));
	}

	/**
     * Takes in a signle value or an array and formats it for use in a where statement
     * 
     * @param string/array $data	The data to format
     * @return string	$query_data The formatted data
     */
	private function createWhere($data)
	{
		//Create application_id where statement
		if (is_array($data) && count($data) > 1)
		{
			//An array was passed in.
			$query_data = "IN ('".implode("','", $data)."')";
		}
		else
		{
			//A single value was returned.
			$query_data = "= '".$data[0]."'";
		}
		return $query_data;
	}

	/**
     * Encrypts passed in data.
     *
     * @param string/array $data	Data to be encrypted
     * @param string $mode			What mode to use with the encryption API
     * @return string/array $encrypted_data	Encrypted Data
     */
	private function encrypt($data, $mode = MODE)
	{
			require_once('prpc/client.php');
			$olp_enc_api = $this->getCryptApi($mode);
			try
			{
				$result = new Prpc_Client($olp_enc_api, FALSE, 32);
				$encrypted_data = $result->encrypt($data);
				return $encrypted_data;
			}
			catch (Exception $e)
			{
				DIE($e);
			}
	}
	
	/**
     * Dencrypts passed in data.
     *
     * @param string/array $data	Data to be decrypted
     * @param string $mode			What mode to use with the decryption API
     * @return string/array $decrypted_data	Decrypted Data
     */
	private function decrypt($data, $mode = MODE)
	{
		require_once('prpc/client.php');
		
		$olp_enc_api = $this->getCryptApi($mode);
		try
		{
			$result = new Prpc_Client($olp_enc_api, FALSE, 32);
			$decrypted_data = $result->decrypt($data);
			return $decrypted_data;
		}
		catch (Exception $e)
		{
			DIE($e);
		}
	}
	
	/**
     * Gets url to use to make PRPC call to encryption
     *
     * @param string $mode	What mode to use.
     * @return string $olp_enc_api What url to use to make the prpc call
     */
	private function getCryptApi($mode)
	{
		$mode = 'LIVE';
		switch (strtoupper($mode))
		{
			case 'LIVE';
				$olp_enc_api = "prpc://callcenter:4w#8_G@bfw.1.edataserver.com/olp_encryption_prpc.php";	
				break;
			case 'RC':
			case 'DEV':
			case 'LOCAL':
			default:
				$olp_enc_api = "prpc://callcenter:test321@rc.bfw.1.edataserver.com/olp_encryption_prpc.php";
				break;
		}
		return $olp_enc_api;
	}
	
	/**
	 * Detects and sets MODE.
	 *
	 * @return string $mode What mode was assigned to MODE
	 */
	private function getMode()
	{
		// Set the mode
		switch ( TRUE )
		{
			case preg_match('/^rc\./', $_SERVER['SERVER_NAME']):
				define('MODE', 'RC');
				$mode = 'RC';
				break;
			case preg_match('/ds\d{1,3}\.tss/', $_SERVER['SERVER_NAME']):
				define('MODE', 'LOCAL');
				$mode = 'LOCAL';
				break;
			default:
				define('MODE', 'LIVE');
				$mode = 'LIVE';
				break;
		}
		return $mode;
	}
}

	$skip_trace_prpc = new Skip_Trace();
	$skip_trace_prpc->_Prpc_Strict = TRUE;
	$skip_trace_prpc->Prpc_Process();
?>
