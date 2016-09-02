<?php

require_once(ECASH_COMMON_DIR . 'ecash_api/ecash_api.2.php');
require_once(OLP_DIR . 'ent_cs.mysqli.php');

/**
 * Class for the Verify_React functionality of Agean sites
 * 
 * @author Rob.Voss
 *
 */
class Verify_React
{
	/**
	 * Checks if a react has been paid for more than 45 days, or if the ABA or Account number have changed, we'll need to re-run DataX
	 *
	 * @param array $data
	 * 
	 * @return bool
	 */
	public function verifyReact($data)
	{
		$verified = TRUE;

		if(!empty($data['react_app_id']))
		{
			$sql = Setup_DB::Get_Instance('mysql', BFW_MODE, $data['ecashapp']);
			$ecash_api = OLPECashHandler::getECashAPI($data['ecashapp'], $data['react_app_id'], BFW_MODE);
			
			$date = $ecash_api->Get_Status_Date('paid', 'paid::customer::*root');

			$date_valid = (empty($date) || strtotime('+45 days', strtotime($date)) >= time()); 

			$old_process = $_SESSION['config']->use_new_process;
			$_SESSION['config']->use_new_process = false;
			$user_data = Ent_CS_MySQLi::Get_The_Kitchen_Sink($sql, null, $data['react_app_id']);
			$_SESSION['config']->use_new_process = $old_process;

			$bank_info_valid = ($user_data['bank_aba'] == $data['bank_aba']
								&& ltrim($user_data['bank_account'], '0') == ltrim($data['bank_account'], '0'));
			
			// If either of these are false then we send back FALSE to indicate we need to run DataX
			if(!($date_valid && $bank_info_valid))
			{
				$verified = FALSE;
			}
		}
		
		return $verified;
	}

}

?>
