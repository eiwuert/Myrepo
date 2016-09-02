<?php
/**
 * OLPSecurity 
 *
 * @author Stephan Soileau <stephan.soileau@sellingsource.com>
 */
require_once('security.8.php');
require_once('OLPECashHandler.php');

/**
 * Extend security8 and replace the Find_App_ID which 
 * uses the wrong kind of ecash api stuff by default.
 *
 */
class OLPSecurity extends Security_8
{
	
	/**
	 * Really does the same thing as Security_8::Find_App_ID,
	 * only uses the proper ecash api to find the payoff amount
	 *
	 * @param string $username
	 * @return mixedcd 
	 */
	public function Find_App_ID($username)
	{
		$query = "
			SELECT
				a.application_id
			FROM
				application a
			INNER JOIN customer c
				ON a.customer_id = c.customer_id
			INNER JOIN company co
				ON a.company_id = co.company_id
			INNER JOIN application_status_flat asf
				ON a.application_status_id = asf.application_status_id
			WHERE
				c.login = '" . $this->sql->Escape_String($username) . "'
			AND 
				co.name_short = '" . strtolower($this->sql->Escape_String($this->property_short)) . "'
			AND NOT (
				asf.level1 = 'prospect'
				AND asf.level0 IN ('agree','disagree','confirmed','confirm_declined','pending','expired')
			)
			ORDER BY a.date_created ASC";
                          
			$result = $this->sql->Query($query);
			$count = $result->Row_Count();
			$last_balance_app = false;

			if($count > 1)
			{
				//Check for balance greater than 0
				$last_app = false;
				$prior_balance = false;

				while($row = $result->Fetch_Array_Row())
				{
					//Grab balance info
					$ec2 = OLPECashHandler::getECashAPI($this->property_short, $row['application_id']);
					$balance = (float)$ec2->Get_Payoff_Amount();
					$last_app = $row['application_id'];

					if($balance > 0.0 && !$prior_balance)
					{
						$last_balance_app = $row['application_id'];
						$prior_balance = true;
					} 
					elseif($balance <= 0.0)
					{
						continue;
					}
					else
					{
						//According to the spec must not return the app id if they
						//have multiple loans with balance > 0
						return false;
					}
				}

				//Return last app id
				return $last_balance_app ? $last_balance_app : $last_app;
			}
			elseif($count == 1)
			{
				$row = $result->Fetch_Array_Row();
				return $row['application_id'];
			}
			else
			{
				return false;
			}
	}
}
