<?php

require_once('minixml/minixml.inc.php');

class Bureau_Query
{
	public static $CALL_ORDER = array(
					'idv_combined' => 'datax',
					'rework' => 'datax',
					'id' => 'datax',
					'performance' => 'datax',
					'agean-perf' => 'datax',
					'agean-title' => 'datax',
					'aalm-perf' => 'datax',
					'fbod-perf' => 'datax',
					'impact-idve' => 'datax',
					'impactpdl-idve' => 'datax',
					'impactfs-idve' => 'datax',
					'impactcf-idve' => 'datax',
					'impactic-idve' => 'datax',
					'idv_l5' => 'datax',
					'idv_advanced_v2' => 'clverify',
					'idv_advanced' => 'clverify',
					'lcs-perf' => 'datax',
					'idv'        => 'datax',
					'qeasy-perf' => 'datax',
					'idv'        => 'datax',
					'hms_nsc-perf' => 'datax',
					'hms_bgc-perf' => 'datax',
					'hms_ezc-perf' => 'datax',
					'hms_csg-perf' => 'datax',
					'hms_tgc-perf' => 'datax',
					'hms_gtc-perf' => 'datax',
					'hms_obb-perf' => 'datax',
					'hms_cvc-perf' => 'datax',
					'opm_bsc-perf' => 'datax',
					'dmp_mcc-perf' => 'datax',
					);

	public static $DATAX_CALL_MAP = array(	
					'id' => 'idv-l1',
					'performance' => 'perf-l1',
					'idv_combined' => 'idv-l3',
					'rework' => 'idv-rework',
					'idv' => 'pdx-impact',
					'perf_level_cfc' => 'idv-l1',
					'agean-perf' => 'agean-perf',
					'agean-title' => 'agean-title',
					'fbod-perf' => 'idv_l5',
					'aalm-perf' => 'aalm-perf',
					'impact-idve' => 'impact-idve',
					'impactpdl-idve' => 'impactpdl-idve',
					'impactfs-idve' => 'impactfs-idve',
					'impactcf-idve' => 'impactcf-idve',
					'impactic-idve' => 'impactic-idve',
					'lcs-perf' => 'lcs-perf',
					'qeasy-perf' => 'qeasy-perf',
					'hms_nsc-perf' => 'hms_nsc-perf',
					'hms_bgc-perf' => 'hms_bgc-perf',
					'hms_ezc-perf' => 'hms_ezc-perf',
					'hms_csg-perf' => 'hms_csg-perf',
					'hms_tgc-perf' => 'hms_tgc-perf',
					'hms_gtc-perf' => 'hms_gtc-perf',
					'hms_obb-perf' => 'hms_obb-perf',
					'hms_cvc-perf' => 'hms_cvc-perf',
					'opm_bsc-perf' => 'opm_bsc-perf',
					'dmp_mcc-perf' => 'dmp_mcc-perf',
					);

	private $db;
	private $mini;
	private $log;
	private $last_received_packet;

	public function __construct($db)
	{
		$this->db = $db;
		$this->mini = new MiniXMLDoc();
		$this->log = ECash::getLog(); 
	}

	public function Bureau_Call($application_id, $company_id, $company_name, $bureau_name, $inquiry_type, $data, $return_xml = false)
	{
		switch($bureau_name)
		{
			default:
			case "datax":
				$this->log->Write("[App_ID: {$application_id}] Data-X bureau call. Type: ".self::$DATAX_CALL_MAP[$inquiry_type]);
				$datax_response = $this->Datax_Call($application_id, $company_id, $company_name, self::$DATAX_CALL_MAP[$inquiry_type], $data);
				if ($return_xml)
				{
					return array($inquiry_type => $this->last_received_packet);
				}
				else 
				{
					return array($inquiry_type => $datax_response);
				}
			break;
		}
	}

	public function Get_Data($application_id, $company_id, $return_xml = false)
	{
		$data = NULL;

		$type = implode("', '",array_keys(self::$CALL_ORDER));
		//Make string out of the bureau types
		$bureau = implode("', '",array_unique(self::$CALL_ORDER));
		//Get the most recent non-errored IDV record.
			if($data = $this->Get_Package($application_id, $company_id, $bureau, $type, $return_xml))
		{
			return $data;
		}

		return NULL;
	}

	public function Get_Package($application_id, $company_id, $bureau_name, $inquiry_type, $return_xml = false)
	{
		$query = '-- /* SQL LOCATED IN file=' . __FILE__ . ' line=' . __LINE__ . ' method=' . __METHOD__ . " */
				SELECT
					(
						CASE
							WHEN uncompress(bi.received_package) IS NOT NULL
							THEN uncompress(bi.received_package)
							ELSE bi.received_package
						END
					) as received_package,
					bi.inquiry_type,
					b.name_short
				  FROM
					  bureau_inquiry bi,
					  bureau b
				  WHERE
				  	  bi.company_id		= {$company_id}
				  AND bi.application_id	= {$application_id}
				  AND bi.bureau_id		= b.bureau_id
				  AND  b.name_short		IN ('{$bureau_name}')
				  AND bi.inquiry_type	IN  ('{$inquiry_type}')
				  AND bi.error_condition IS NULL
				  ORDER BY
				  	bi.date_modified DESC
				  LIMIT 1
		";

		$st = $this->db->query($query);
		$package = null;
		if ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			switch($row->name_short)
			{
				case "clverify":
					
					
					if ($return_xml)
					{
						$package = $row->received_package;
					}
					else
					{
						$row->received_package = str_replace("-", "_", $row->received_package);
						$this->mini->fromString($row->received_package);
						$array = $this->mini->toArray();
	
						if($inquiry_type == 'idv_advanced_v2')
						{
							if(!empty($array['DataxResponse']))
							{
								$package = $array['DataxResponse'];
							}
							else if (!empty($array['clv_response']))
							{
								$package = $array['clv_response'];
							}
						}
						else if(!empty($array['clv_response']))
						{
							$package = $array['clv_response'];
						}
					}
					break;

				case "datax":
					if ($return_xml)
					{
						$package = $row->received_package;
					}
					else 
					{
						$this->mini->fromString($row->received_package );
						$array = $this->mini->toArray();
						if( !empty($array['DataxResponse']) )
							$package = $array['DataxResponse'];
					}
					break;
				
				default: $package = $row->received_package;
			}
		}
		return array($row->inquiry_type => $package);

	//	return NULL;
	}

	public function Get_Newest_All($application_id, $company_id)
	{
		// MySQL 5 ...  what a hunk of dung ... has bug relating to doing the decompression on multiple rows in a single query.
		//	Took out the subquery and instead we build up a list of the "latest" bureau inquiry data which we use in the main query.
		//  Also had to put the main query in a loop such that only one row is retrieved at a time.

		$subquery = "
					SELECT
						max(bureau_inquiry_id) as bureau_inquiry_id
					FROM
						bureau_inquiry
					WHERE
							company_id		= {$company_id}
						AND application_id	= {$application_id}
						AND error_condition IS NULL
					GROUP BY inquiry_type
		";

		$st = $this->db->query($subquery);

		$bureau_inquiry_id_ary = array();
		while ($row = $st->fetch(PDO::FETCH_OBJ))
		{
			$bureau_inquiry_id_ary[] = $row->bureau_inquiry_id;
		}

		if (count($bureau_inquiry_id_ary) == 0)
		{
			return array();
		}

		$inquiries = array();

		//mantis:1405 - uncompress(bi.sent_package)
		for ($i=0; $i<count($bureau_inquiry_id_ary); $i++)
		{
			$query = "
				SELECT
					b.name,
					b.name_short,
					UNIX_TIMESTAMP(bi.date_created) as date_created,
					bi.inquiry_type,
					(
						CASE
							WHEN uncompress(bi.sent_package) IS NOT NULL
							THEN uncompress(bi.sent_package)
							ELSE bi.sent_package
						END
					) as sent_package,
					(
						CASE
							WHEN uncompress(bi.received_package) IS NOT NULL
							THEN uncompress(bi.received_package)
							ELSE bi.received_package
						END
					) as received_package
				  FROM
					 bureau_inquiry bi,
					 bureau b
				  WHERE bi.bureau_inquiry_id = " . $bureau_inquiry_id_ary[$i] . "
				    AND bi.bureau_id = b.bureau_id
			";

			//echo $query;

			$st = $this->db->query($query);
			
			if ($row = $st->fetch(PDO::FETCH_OBJ))
			{
				$inquiries[] = $row;
			}
		}

		return $inquiries;
	}

	private function Datax_Call($application_id, $company_id, $company_name, $inquiry_type, $data)
	{
		//echo '<pre>', print_r(debug_backtrace(), TRUE); die;
		require_once("datax.2.php");
		$datax = new Data_X();
		$bureau_data = array("trackid" => htmlentities($application_id),
		"namefirst" => $data->name_first,
		"namelast" => $data->name_last,
		"street1" => $data->street,
		"city" => $data->city,
		"state" => $data->state,
		"zip" => $data->zip,
		"homephone" => $data->phone_home,
		"email" => $data->customer_email,
		"ipaddress" => $data->ip_address,
		"legalid" => $data->legal_id_number,
		"legalstate" => $data->legal_id_state,
		"dobyear" => $data->dob_year,
		"dobmonth" => $data->dob_month,
		"dobday" => $data->dob_day,
		"ssn" => $data->ssn,
		"namemiddle" => $data->name_middle,
		"street2" => $data->unit,
		"bankname" => $data->bank_name,
		"bankacct" => $data->bank_account,
		"bankaba" => $data->bank_aba,
		"banktype" => $data->bank_account_type,
		"directdeposit" => (int) (strtoupper($data->income_direct_deposit) == 'YES'),
		"payperiod" => $data->income_frequency,
		"phonework" => $data->phone_work,
		"phoneext" => $data->phone_work_ext,
		"employername" => $data->employer_name,
		"source" => $data->web_url,
		"promo" => $data->promo_id,
		"monthlyincome" => intval($data->income_monthly),
		"amountrequested" => ($data->fund_actual > 0) ? $data->fund_actual : $data->fund_qualified,
		"trackhash" => empty($data->track_hash) ? '' : $data->track_hash
		);

		//hack so that we are inserting a payperiod that datax can use!
		switch ($data->payperiod)
		{
			case 'twice_monthly':
				$bureau_data['payperiod'] = 'SEMI_MONTHLY';
			break;
	
			default:
				$bureau_data['payperiod'] = htmlentities(strtoupper($data->payperiod));
			break;
		}
		foreach ($bureau_data as $field => $value) {
			$bureau_data[$field] = htmlentities($value);
		}

		//echo '<pre>', print_r($bureau_data, TRUE); die;
		$array = $datax->Datax_Call($inquiry_type, $bureau_data, EXECUTION_MODE, $company_name);
		if($array)
		{
			$outcome = empty($array['Response']['General']['AuthenticationScoreSet']['AuthenticationScore']) ? NULL :
			$array['Response']['General']['AuthenticationScoreSet']['AuthenticationScore'];

			$error = NULL;
			if(!empty($array['Response']['ErrorCode']) && $array['Response']['ErrorCode'])
			{
				//possibilities: 'other','timeout','malformed request'
				switch($array['Response']['ErrorCode'])
				{
					case "G-002":
					$error = "malformed request";
					break;

					default:
					$error = 'other';
					break;
				}
			}

			//echo "<pre>error: {$error}</pre>";
			$this->last_received_packet = $datax->Get_Received_Packet();

			$this->Insert_Inquiry($application_id, $company_id, 'datax', array_search($inquiry_type, self::$DATAX_CALL_MAP), $datax->Get_Sent_Packet(), $datax->Get_Received_Packet(), $outcome, $error);
		}
		else
		{
			$this->log->Write(__METHOD__ . ": No Data Returned From Data-X.");	
		}
		return $array;
	}

	private function Insert_Inquiry($app_id, $company_id, $bureau_name, $inquiry_type, $sent, $received, $outcome, $error = NULL, $trace_info = NULL)
	{
		$error_column = "";
		$error_value = "";
		if($error)
		{
			$error_column = " error_condition, ";
			$error_value = " " . $this->db->quote($error) . ", ";
		}

		$trace_column = "";
		$trace_value = "";
		if($trace_info)
		{
			$trace_column = " trace_info, ";
			$trace_value = " " . $this->db->quote($trace_info) . ", ";
		}
		
		//mantis:1405 - compress('" . $this->db->quote($sent) ."'),
		$query = "
					INSERT INTO bureau_inquiry
				  (
					date_created,
					company_id,
					application_id,
					bureau_id,
					inquiry_type,
					sent_package,
					received_package,
					{$trace_column}
					{$error_column}
					outcome
				  )
				  VALUES
				  (
					now(),
					{$company_id},
					{$app_id},
					(SELECT bureau_id FROM bureau WHERE bureau.name_short = " . $this->db->quote($bureau_name) ."),
					" . $this->db->quote($inquiry_type) .",
					compress(" . $this->db->quote($sent) ."),
					compress(" . $this->db->quote($received) ."),
					{$trace_value}
					{$error_value}
					" . $this->db->quote($outcome) ."
				  )";

		//echo "<pre>" . $query;

		$this->db->exec($query);
		$this->log->Write("[App_ID: {$app_id}] Bureau Inquiry Stored.");

	}

}

?>
