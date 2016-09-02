<?php


class Bureau_Handler
{
	public static $BUREAU_LIST = array(	"Teletrack"	=> "tlt",
										"IDVerify"	=> "idv",
										"ID"		=> "asl" );

	private static $status_to_call_datax = array('prospect','applicant');

	private $acl;
	private $company_id;

	public function __construct($acl, $company_id)
	{
		$this->acl = $acl;
		$this->company_id = $company_id;
	}

	public function Format_IDV_XML($app_row, $response_type, $xmldoc)
	{
		$app_row->idv_label_1 = "";
		$app_row->idv_label_2 = "";
		$app_row->idv_label_3 = "";
		$app_row->idv_label_4 = "";
		$app_row->idv_label_5 = "";
		$app_row->idv_label_6 = "";

		$app_row->idv_value_1 = "";
		$app_row->idv_value_2 = "";
		$app_row->idv_value_3 = "";
		$app_row->idv_value_4 = "";
		$app_row->idv_value_5 = "";
		$app_row->idv_value_6 = "";

		$app_row->idv_trapped_error = "";
		$app_row->idv_full_record = "&nbsp";
		$app_row->idv_acl_link = "&nbsp";

		//we'll hold this for id-rechecks
		$app_row->track_hash = '';


		// We only provide the bureau recheck button if the current application status
		//	belongs to prospect or applicant branch...
		if (	in_array($app_row->level1, self::$status_to_call_datax) && $app_row->level2 == '*root'	||
				in_array($app_row->level2, self::$status_to_call_datax) && $app_row->level3 == '*root'	||
				in_array($app_row->level3, self::$status_to_call_datax) && $app_row->level4 == '*root'	||
				in_array($app_row->level4, self::$status_to_call_datax) && $app_row->level5 == '*root'
		)
		{
			// If agent has ACL access for recheck generate the html
			if ($this->acl->Acl_Access_Ok('id_recheck', $this->company_id))
			{
				$app_row->idv_acl_link ='<form method="post" action="/" name="id_recheck" class="no_padding">
					<input type="hidden" name="action" value="id_recheck" /><nobr>
					<input type="hidden" name="application_id" value="' . $app_row->application_id . '" />
                	<input type="submit" value="ID Recheck" class="button" />
                	</nobr></form>';
			}
		}

		if ( $this->acl->Acl_Access_Ok('idv_full_record', $this->company_id) )
		{
			// Set link to see full IDV record
			$app_row->idv_full_record = "View Entire Record";
		}

		if(is_null($xmldoc) || strlen($xmldoc) == 0)
		{
			$app_row->idv_trapped_error = "Failed to connect to bureau.";
		}
		else if ($response_type == 'external_call_suppressed')
		{
			$app_row->idv_trapped_error = "No bureau data on file and decision has already been made.";
		}
		else
		{
			// Remove all occurrences of <?xml .. > from the xml document
			// You're probably asking yourself why. You're in luck. I'll tell you.
			// It would seem that DataX returns a rather broken XML document
			// That is, DataX's return packet has two <?xml .. > start tags
			// DataX just appends a sub-providers data (Experian) without cleaning
			// out the start tag.  So, we just remove them all. No start tags
			// is a lot more compliant than two start tags.
			$xmldoc = preg_replace("/\\<\\?xml[^>]*>/", "", $xmldoc);

			// Get the root element
			$doc = @new SimpleXMLElement($xmldoc);
			switch($response_type)
			{
				case "idv_advanced_v2": //idv_advanced_v2 can possibly be wrappered by Datax
					if(isset($doc->Response))
					{
						$app_row->idv_label_1 = "Warning:";
						$app_row->idv_value_1 = (isset($doc->{"aa-item"}) && count($doc->{"aa-item"}->children())) ? "Yes" : "No";

						$app_row->idv_label_2 = "SSN Valid:";
						$app_row->idv_value_2 = $this->Format_Yes_No((string) $doc->{'ssn-valid-code'});

						$app_row->idv_label_3 = "SSN/Name Match:";
						$app_row->idv_value_3 = $this->Format_Yes_No((string) $doc->{'name-ssn-match-code'});

						$app_row->idv_label_4 = "Score:";
						$app_row->idv_value_4 = (string) $doc->score;

						if(isset($doc->Response->ErrorMsg))
						{
							$app_row->idv_trapped_error = (string) $doc->Response->ErrorMsg;
						}
					}
					else
					{
						$app_row->idv_label_1 = "Pass:";

						if (isset($doc->inquiry))
						{
							$app_row->idv_value_1 = isset($doc->approved) ? $this->Format_Yes_No($doc->inquiry->approved) : "";
							if (isset($doc->inquiry->error))
							{
								$app_row->idv_trapped_error = (string) $doc->inquiry->error->hint;
							}
						}
					}
					break;

				case "idv_l5":
					$app_row->idv_label_1 = "Decision: ";
					$app_row->idv_value_1 = (string) $doc->Response->Detail->GlobalDecision->Result;
					$app_row->track_hash = (string)$doc->TrackHash;
					$app_row->idv_label_2 = "Phone Number:";
					$phone_prefix = (string) $doc->Response->Detail->TransUnionSegment->AreaCode;
					$phone_suffix = (string) $doc->Response->Detail->TransUnionSegment->PhoneNumber;
					if(empty($phone_prefix) && empty($phone_suffix))
					{
						$app_row->idv_value_2 = "No phone number returned";
					}
					else
					{
						$phone_suffix = substr($phone_suffix, 0, 3) . "-" . substr($phone_suffix,3,4);
						if(!empty($phone_prefix)) $app_row->idv_value_2 = "($phone_prefix)";
						$app_row->idv_value_2 .= $phone_suffix;
					}
					break;

				case "idv_combined":
				case "id":
				case "performance":
				case "rework":
					$app_row->track_hash = isset($doc->TrackHash) ? ((string) $doc->TrackHash) : '';
					$app_row->idv_label_1 = "Decision:";

					//if(!empty($package['Response']['Summary']['Decision']))
					if (isset($doc->Response->Summary->Decision))
					{
						//$app_row->idv_value_1 = $this->Format_Yes_No($package['Response']['Summary']['Decision']);
						$app_row->idv_value_1 = $this->Format_Yes_No((string) $doc->Response->Summary->Decision);
					}
					//if(!empty($package['Response']['Summary']['Decision']) && $package['Response']['Summary']['Decision'] == "N")
					if (isset($doc->Response->Summary->Decision) && $doc->Response->Summary->Decision == "N")
					{
						$app_row->idv_label_2 = "Failure Reason:";
						//$app_row->idv_value_2 = $this->Get_Bureau($package['Response']['Summary']['DecisionBucket']);
						$app_row->idv_value_2 = $this->Get_Bureau((string) $doc->Response->Summary->DecisionBucket);
					}
					//if(!empty($package['Response']['ErrorMsg']))
					if (isset($doc->Response->ErrorMsg))
					{
						//$app_row->idv_trapped_error = $package['Response']['ErrorMsg'];
						$app_row->idv_trapped_error = (string) $doc->Response->ErrorMsg;
					}
					break;

				case 'hms_csg-perf':
				case 'hms_cvc-perf':
				case 'hms_ezc-perf':
				case 'hms_gtc-perf':
				case 'hms_nsc-perf':
				case 'hms_obb-perf':
				case 'hms_tgc-perf':
					// #18905 -- increase loan amount based on DataX decision
					if (isset($doc->Response->Detail->GlobalDecision))
					{
						$app_row->idv_increase_eligible = (isset($doc->Response->Detail->GlobalDecision->LoanAmount)
							&& strtolower($doc->Response->Detail->GlobalDecision->LoanAmount) == 'increase');
					}
					//break;

				case "idv":
				case "impact":
				case "agean-perf":
				default :
					$app_row->track_hash = isset($doc->TrackHash) ? ((string) $doc->TrackHash) : '';
					$app_row->idv_label_1 = "Decision:";
					if (isset($doc->Idv->CustomDecision))
					{
						$app_row->idv_value_1 = $this->Format_Yes_No((string) $doc->Idv->CustomDecision->Result);
					}
					if (isset($doc->Idv->CustomDecision) && $doc->Idv->CustomDecision->Result == "N")
					{
						$app_row->idv_label_2 = "Failure Reason:";
						$app_row->idv_value_2 = $this->Get_Bureau((string) $doc->Idv->CustomDecision->DecisionBucket);
					}
					if (isset($doc->Response->ErrorMsg))
					{
						$app_row->idv_trapped_error = (string) $doc->Response->ErrorMsg;
					}
					// Agean Perf
					if(isset($doc->Response->Detail->GlobalDecision))
					{
						$app_row->idv_value_1 = $this->Format_Yes_No((string) $doc->Response->Detail->GlobalDecision->Result);
					}

					break;
			}
		}
	}

	public function Format_IDV($app_row, $package_array)
	{
		$app_row->idv_label_1 = "";
		$app_row->idv_label_2 = "";
		$app_row->idv_label_3 = "";
		$app_row->idv_label_4 = "";
		$app_row->idv_label_5 = "";
		$app_row->idv_label_6 = "";

		$app_row->idv_value_1 = "";
		$app_row->idv_value_2 = "";
		$app_row->idv_value_3 = "";
		$app_row->idv_value_4 = "";
		$app_row->idv_value_5 = "";
		$app_row->idv_value_6 = "";

		$app_row->idv_trapped_error = "";
		$app_row->idv_full_record = "&nbsp";
		$app_row->idv_acl_link = "&nbsp";

		//we'll hold this for id-rechecks
		$app_row->track_hash = '';

		// We only provide the bureau recheck button if the current application status
		//	belongs to prospect or applicant branch...
		if (	in_array($app_row->level1, self::$status_to_call_datax) && $app_row->level2 == '*root'	||
				in_array($app_row->level2, self::$status_to_call_datax) && $app_row->level3 == '*root'	||
				in_array($app_row->level3, self::$status_to_call_datax) && $app_row->level4 == '*root'	||
				in_array($app_row->level4, self::$status_to_call_datax) && $app_row->level5 == '*root'
		)
		{
			// If agent has ACL access for recheck generate the html
			if ($this->acl->Acl_Access_Ok('id_recheck', $this->company_id))
			{

				$app_row->idv_acl_link ='<form method="post" action="/" name="id_recheck" class="no_padding">
					<input type="hidden" name="action" value="id_recheck" /><nobr>
                	<input type="submit" value="ID Recheck" class="button" />
                	</nobr></form>';
			}
		}


		if ( $this->acl->Acl_Access_Ok('idv_full_record', $this->company_id) )
		{
			// Set link to see full IDV record
			$app_row->idv_full_record = "View Entire Record";
		}

		//echo "<pre>" . print_r($package_array, TRUE) . "</pre>";
		list($type, $package) = each($package_array);
		if(!$package)
		{
			$app_row->idv_trapped_error = "Failed to connect to bureau.";
		}
		elseif($type == 'external_call_suppressed')
		{
			$app_row->idv_trapped_error = "No bureau data on file and decision has already been made.";
		}
		else
		{
			switch($type)
			{
				case "idv_advanced_v2": //idv_advanced_v2 can possibly be wrappered by Datax
				if(!empty($package['Response']))
				{
					$app_row->idv_label_1 = "Warning:";
					$app_row->idv_value_1 = (!empty($package['aa_item']) && count($package['aa_item'])) ? "Yes" : "No";
					$app_row->idv_label_2 = "SSN Valid:";
					$app_row->idv_value_2 = $this->Format_Yes_No($package['ssn_valid_code']);
					$app_row->idv_label_3 = "SSN/Name Match:";
					$app_row->idv_value_3 = $this->Format_Yes_No($package['name_ssn_match_code']);
					$app_row->idv_label_4 = "Score:";
					$app_row->idv_value_4 = $package['score'];
					if(!empty($package['Response']['ErrorMsg']))
					{
						$app_row->idv_trapped_error = $package['Response']['ErrorMsg'];
					}
					break;
				}
				else
				{
					$app_row->idv_label_1 = "Pass:";
					$app_row->idv_value_1 = empty($package['inquiry']['approved']) ? "" : $this->Format_Yes_No($package['inquiry']['approved']);
					if(!empty($package['inquiry']['error']))
					{
						$app_row->idv_trapped_error = $package['inquiry']['error']['hint'];
					}
					break;
				}

				case "idv_combined":
				case "id":
				case "performance":
				case "rework":
				$app_row->track_hash = empty($package['TrackHash']) ? '' : $package['TrackHash'];
				$app_row->idv_label_1 = "Decision:";
				if(!empty($package['Response']['Summary']['Decision']))
				{
					$app_row->idv_value_1 = $this->Format_Yes_No($package['Response']['Summary']['Decision']);
				}
				if(!empty($package['Response']['Summary']['Decision']) && $package['Response']['Summary']['Decision'] == "N")
				{
					$app_row->idv_label_2 = "Failure Reason:";
					$app_row->idv_value_2 = $this->Get_Bureau($package['Response']['Summary']['DecisionBucket']);
				}
				if(!empty($package['Response']['ErrorMsg']))
				{
					$app_row->idv_trapped_error = $package['Response']['ErrorMsg'];
				}
				break;


				case "idv_advanced":
				$app_row->idv_label_1 = "Warning:";
				$app_row->idv_value_1 = (!empty($package['inquiry']['aa_item']) && count($package['inquiry']['aa_item'])) ? "Yes" : "No";
				$app_row->idv_label_2 = "SSN Valid:";
				$app_row->idv_value_2 = $this->Format_Yes_No($package['inquiry']['id_verification']['ssn_valid_code']);
				$app_row->idv_label_3 = "SSN/Name Match:";
				$app_row->idv_value_3 = $this->Format_Yes_No($package['inquiry']['id_verification']['name_ssn_match_code']);
				$app_row->idv_label_4 = "Score:";
				$app_row->idv_value_4 = $package['inquiry']['score'];
				if(!empty($package['inquiry']['error']))
				{
					$app_row->idv_trapped_error = $package['inquiry']['error']['description'];
				}
				break;
			}
		}
	}

	private function Get_Bureau($abbrev)
	{
		// This sucks, datax should send back the bureau that
		// triggered a failure.  Instead Todd sent us instructions for
		// ghetto hacking the bureau out of the error bucket with a
		// strpos.  This is not by choice.

		foreach(self::$BUREAU_LIST as $bureau => $search)
		{
			if( strpos($abbrev, $search) !== FALSE)
			{
				return $bureau;
			}
		}
		return "Unknown";
	}

	private function Format_Yes_No($string)
	{
		if($string == "Y")
		return "Yes";
		return "No";
	}
}

?>
