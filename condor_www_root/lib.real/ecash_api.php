<?php

/**
* Determines the number of returns where faillures of a pair SC-Principal or SC-Paydown with the same due date 
* is considered as one return
*
* Revision History:
*		alexanderl - 2008-12-24 -  Created this function [#22194]
*	
@param int $acct_id - application id
@param $mysqli

@return int $total_returned
*/	
function Returned_Item_Count($acct_id, $mysqli)
{
	$query = "
		SELECT 
			count(DISTINCT tr.date_effective)  as 'count'
		FROM 
			transaction_register AS tr
		JOIN
			transaction_type AS tt USING (company_id, transaction_type_id)
		WHERE 
			tr.application_id = {$acct_id}
		AND
			tt.clearing_type = 'ach'
		AND
			tr.amount < 0
		AND 
			tr.transaction_status = 'failed'
		";

	$row = $mysqli->Query($query)->Fetch_Object_Row();
	$ach_returned = intval($row->count);

	$query = "
		SELECT 
			count(*) as 'count'
		FROM 
			transaction_register AS tr
		JOIN
			transaction_type AS tt USING (company_id, transaction_type_id)
		WHERE 
			tr.application_id = {$acct_id}
		AND
			tt.clearing_type <> 'ach'
		AND
			tr.amount < 0
		AND 
			tr.transaction_status = 'failed'
		";

	$row = $mysqli->Query($query)->Fetch_Object_Row();
	$non_ach_returned = intval($row->count);

	$total_returned = $ach_returned + $non_ach_returned;
	
	return $total_returned;
}

function Was_In_Collections($acct_id, $mysqli)
{
	$query = "
SELECT count(*) as 'count'
FROM status_history
WHERE application_id = {$acct_id}
AND application_status_id in
    (SELECT application_status_id
     FROM application_status_flat
     WHERE (level1='external_collections' and level0 != 'recovered')
     OR (level2='collections') OR (level1='collections'))";

	$row = $mysqli->Query($query)->Fetch_Object_Row();
	$val = intval($row->count);
	return (($val > 0) ? true : false);
}

function QuickChecks_Pending($acct_id, $mysqli)
{
	$query = "
SELECT count(*) as 'count'
FROM transaction_register
WHERE transaction_status = 'pending'
AND application_id = {$acct_id}
AND transaction_type_id in (SELECT transaction_type_id
                            FROM transaction_type
                            WHERE name_short = 'quickcheck')
";

	$row = $mysqli->Query($query)->Fetch_Object_Row();
	$val = intval($row->count);
	return (($val > 0) ? true : false);
}

function Get_Balance($acct_id, $mysqli)
{
	$query = "
SELECT sum(amount) as 'total'
FROM transaction_register
WHERE transaction_status = 'complete'
AND application_id = {$acct_id}
";
	$row = $mysqli->Query($query)->Fetch_Object_Row();
	$val = floatval($row->total);
	return $val;
}


/**
@publicsection
@public
@brief

* Determine whether the customer has a quickcheck in Inactive Paid application
* Revision History:
*		alexanderl - 01/18/2008 -  added determination of $last_advance_date in cash line [alexander][mantis:13583]
	
@param int $acct_id - Inactive Paid application id
@param $mysqli

@return boolean $result
*/
function Has_Completed_Quickchecks($acct_id, $mysqli)
{
	$query = "
		SELECT count(*) as 'count'
		FROM transaction_register
		WHERE transaction_status = 'complete'
		AND application_id = {$acct_id}
		AND transaction_type_id in (SELECT transaction_type_id
                FROM transaction_type
                WHERE name_short = 'quickcheck')
";
	$row = $mysqli->Query($query)->Fetch_Object_Row();
	$val = intval($row->count);

	if ($val == 0)
	{
		$query = "
	 	-- eCash3.0 ".__FILE__.":".__LINE__.":".__METHOD__."()
	       	select t.transaction_date
		FROM cl_transaction t
		JOIN cl_customer c ON t.customer_id = c.customer_id
		WHERE 
			c.application_id = {$acct_id}
		  AND
			t.transaction_type = 'advance' 
		ORDER BY 
			t.transaction_date DESC
		LIMIT 1
	 	";

		$last_advance_date = $mysqli->Query($query)->Fetch_Object_Row()->transaction_date;


		$query = "
		SELECT count(*) as 'count'
		FROM cl_transaction t
		JOIN cl_customer c ON t.customer_id = c.customer_id
		WHERE t.transaction_type = 'deposited check'
		AND c.application_id = {$acct_id}
		AND t.transaction_date >= '{$last_advance_date}'
		";
		$row = $mysqli->Query($query)->Fetch_Object_Row();
		$val = intval($row->count);
	}

	$result = ($val > 0) ? true : false;
	return $result;
}

function Second_Tier_Collections_Paid($acct_id, $mysqli)
{
	$query = "
SELECT app.application_status_id 'actual', asf.application_status_id as 'recovered'
FROM application app, application_status_flat asf
WHERE app.application_id = {$acct_id}
AND asf.level0='recovered'
AND asf.level1='external_collections'
AND asf.level2='*root'
";
	$row = $mysqli->Query($query)->Fetch_Object_Row();
	return ($row->actual == $row->recovered);
}

/**
* Determines the fund amount of the most recent application of the customer
*
* Revision History:
*		alexanderl - 2008-07-22 -  Created this function [#8081]
*	
@param int $acct_id - application id
@param $mysqli

@return float $val - fund amount
*/
function Most_Recent_Loan_Amount($acct_id, $mysqli)
{
	if(Previous_Loan_Status($acct_id, $mysqli) == "paid")
	{
		$query = "
			SELECT 
				app.fund_actual 
			FROM 
				application AS app 
			JOIN 
				application AS app_orig ON app.customer_id = app_orig.customer_id AND app.company_id = app_orig.company_id 
			JOIN 
				application_status AS app_status ON app.application_status_id = app_status.application_status_id 
			WHERE 
				app_orig.application_id = {$acct_id} 
			AND 
				name_short IN ('paid', 'recovered') 
			ORDER BY app.date_application_status_set DESC 
			LIMIT 1
			";
	}
	else
	{
		$query = "
			SELECT 
				fund_actual
			FROM 
				application
			WHERE 
				application_id = {$acct_id}
			";
	}

	$row = $mysqli->Query($query)->Fetch_Object_Row();
	$val = floatval($row->fund_actual);
	return $val;
}

function Previous_Loan_Amount($acct_id, $mysqli)
{
	// Inactive Paid Loans should scan for the max loan ammount
	// Based on SSN for Max Possible fun amount (Paid or Recovered)
	if(Previous_Loan_Status($acct_id, $mysqli) == "paid")
	{
		// Mantis #12329 - Extended the query to include cashline history [RM]
		// GForge #8019 - Join on customer_id instead of social security number [RM]
		$query = "
			SELECT
				MAX(app.fund_actual) AS fund_actual_ecash,
				MAX(t.transaction_amount) AS fund_actual_cashline,
				IF (
					MAX(t.transaction_amount) > MAX(app.fund_actual),
						MAX(t.transaction_amount),
						MAX(app.fund_actual)
					) AS fund_actual
			FROM
				application AS app
			JOIN
				application AS app_orig ON app.customer_id = app_orig.customer_id AND app.company_id = app_orig.company_id
			JOIN
				application_status AS app_status ON app.application_status_id = app_status.application_status_id
			LEFT JOIN
				cl_customer AS c ON app.application_id = c.application_id
			LEFT JOIN
				cl_transaction AS t ON c.customer_id = t.customer_id
			WHERE
				app_orig.application_id = {$acct_id}
				AND name_short IN ('paid', 'recovered')
				AND
				(
					t.transaction_type IN ('card advance', 'advance')
					OR t.transaction_type IS NULL
				)";
	}
	else
	{
		$query = "
		SELECT fund_actual
		FROM application
		WHERE application_id = {$acct_id}
		";
	}
	$row = $mysqli->Query($query)->Fetch_Object_Row();
	$val = floatval($row->fund_actual);
	return $val;
}

function Previous_Loan_Status($acct_id, $mysqli)
{
	$query = "
	SELECT name_short
	FROM application
	JOIN application_status using (application_status_id)
	WHERE application_id = {$acct_id}
	";
	$row = $mysqli->Query($query)->Fetch_Object_Row();
	$val = $row->name_short;
	return $val;
}

function Scheduled_Payments_Left($acct_id, $mysqli)
{

       $query = "
       select count(DISTINCT(es.date_effective)) as payments_left
       from event_schedule as es
       join event_type as et on (et.event_type_id = es.event_type_id)
       where
       es.event_status = 'scheduled' and
       et.name_short IN ('payment_service_chg', 'repayment_principal') and
       application_id = {$acct_id}
       ";
       $row = $mysqli->Query($query)->Fetch_Object_Row();
       $val = $row->payments_left;
       return $val;
}

function Application_Process_Type($acct_id, $mysqli)
{
       $query = "
       select olp_process
       from application
       where
       application_id = {$acct_id}
       ";
       $row = $mysqli->Query($query)->Fetch_Object_Row();
       $val = $row->olp_process;
       return $val;
}

function Pending_Payments($acct_id, $mysqli)
{

       $query = "
               select count(transaction_register_id) as pending_payments
               from transaction_register as tr
        join transaction_type  as tt on (tt.transaction_type_id = tr.transaction_type_id)
               where
               tr.transaction_status = 'pending'
        and tr.application_id = {$acct_id}
       ";
       $row = $mysqli->Query($query)->Fetch_Object_Row();
       $val = $row->pending_payments;
       return $val;
}

function Pending_Payment_Total_Amount($acct_id, $mysqli)
{
	$val = 0;

	$query = "
               select sum(tr.amount) as pending_amount
               from transaction_register as tr
        join transaction_type  as tt on (tt.transaction_type_id = tr.transaction_type_id)
               where
               tr.transaction_status = 'pending'
        and tr.application_id = {$acct_id}
       ";
	try
	{
		$row = $mysqli->Query($query)->Fetch_Object_Row();
		$val = $row->pending_amount;
	}
	catch (Exception $e)
	{
		$val = 0;
	}

	return $val;
}

/* This function is a combination of the above two please use it
 * instead of those if you're planning on getting both the remaining
 * payment amount and number of payments in a short space of time --
 * JRF
 */
function Pending_Payment_Info($acct_id, $mysqli)
{
	$query = "
               select sum(tr.amount) as pending_amount,
			   count(transaction_register_id) as pending_payments,
			   MAX(tt.pending_period) as pending_period,
               MAX(tr.date_effective) as date_effective
               from transaction_register as tr
        join transaction_type  as tt on (tt.transaction_type_id = tr.transaction_type_id)
               where
               tr.transaction_status = 'pending'
        and tr.application_id = {$acct_id}
       ";

	return $mysqli->Query($query)->Fetch_Object_Row();
}

/**
 * Returns the card provider's card id if the customer identified by the given 
 * $ssn and $company_short has an active card. Otherwise returns false.
 *
 * @param string $ssn
 * @param string $company_short
 * @param MySQLi $mysqli
 * @return int
 */
function SSN_Has_Active_Card($ssn, $company_short, $mysqli)
{
	$query = "
		SELECT provider_card_id 
		FROM
			card
			JOIN customer c ON c.customer_id = card.customer_id
			JOIN card_status cs ON cs.card_status_id = card.card_status_id
		WHERE
			c.ssn = '{$ssn}' AND
			c.company_id = (SELECT company_id FROM company WHERE name_short = '{$company_short}') AND
			cs.is_fundable
		LIMIT 1
	";

	$result = $mysqli->Query($query);

	if ($row = $result->Fetch_Object_Row())
	{
		return $row->provider_card_id;
	}
	else
	{
		return false;
	}
}


/**
 * Returns the card provider's card id if the customer identified by the given 
 * $ssn and $company_short has a valid card. Otherwise returns false.
 *
 * @param string $ssn
 * @param string $company_short
 * @param MySQLi $mysqli
 * @return integer|boolean
 */
function get_Card_Id_By_SSN($ssn, $company_short, $mysqli)
{
	$query = "
		SELECT
			card.provider_card_id,
			cs.is_valid
		FROM card
			JOIN customer c ON (c.customer_id = card.customer_id)
			JOIN card_status cs ON (cs.card_status_id = card.card_status_id)
		WHERE c.ssn = '{$ssn}'
			AND c.company_id = (
				SELECT company_id
				FROM company
				WHERE name_short = '{$company_short}'
			)
		ORDER BY card.date_created DESC
		LIMIT 1
	";

	$result = $mysqli->Query($query);
	$row = $result->Fetch_Object_Row();

	if ($row && $row->is_valid)
	{
		return $row->provider_card_id;
	}
	else
	{
		return FALSE;
	}
}


?>
