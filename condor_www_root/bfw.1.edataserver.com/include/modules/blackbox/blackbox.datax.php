<?php

	/**
		
		@desc Abstracted interface to DataX for BlackBox that
			does some of the more heavy lifting. It also handles
			persistance of the trackhash throughout the entire
			application process: i.e., carrying the same hash from
			the prequal call to the actual IDV call.
			
		@author Andrew Minerd
		@version 1.0
		
	*/
	
	class BlackBox_DataX
	{
		//Data-X Call Types
		const TYPE_IDV_CLK		= 'idv-l1';
		const TYPE_IDV_PREQUAL	= 'idv-l5';
		const TYPE_IDV_PW		= 'idv-l7';
		const TYPE_IDV_REWORK	= 'idv-rework';
		const TYPE_IDVE_IMPACT	= 'impact-idve';		// Change IC to use impact-idve - GForge 5576 [DW]
		const TYPE_IDVE_IFS		= 'impactfs-idve';
		const TYPE_IDVE_IPDL	= 'impactpdl-idve';
		const TYPE_IDVE_ICF		= 'impactcf-idve';
		const TYPE_PDX_REWORK	= 'pdx-impactrework';
		const TYPE_PERF			= 'perf-l3';
		const TYPE_DF_PHONE		= 'df-phonetype';
		const TYPE_IDV_CCRT		= 'idv-compucredit';
		const TYPE_PERF_MLS 	= 'aalm-perf';
		
		const TIMER_LIMIT = 2;	// Time that the call must take before the timer logs the call

		const SOURCE_NONE = 0;
		const SOURCE_CLK = 1;
		const SOURCE_IMP = 2;
		const SOURCE_CCRT = 3;
		const SOURCE_MLS = 4;
		const SOURCE_IFS = 5;
		const SOURCE_ICF = 6;
		const SOURCE_IPDL = 7;
		
		static protected $track_hash;
		
		protected $datax;
		
		protected $config;
		protected $account;
		protected $force;
		protected $decision;
		protected $score;
		protected $reason;
		protected $history = array();
		
		protected $response;
		protected $last_call;
		protected $elapsed;
		
		protected $decisions;
		protected $result;
		
		public function __construct(&$config)
		{
			// get our config object
			$this->config = &$config;
			
			// get an instance of the DataX library
			$this->datax = new Data_X();
			
			if (isset($_SESSION['datax']) && is_array($_SESSION['datax']))
			{
				// pick-up our old trackhash
				if (isset($_SESSION['datax']['trackhash'])) self::$track_hash = $_SESSION['datax']['trackhash'];
				if (isset($_SESSION['datax']['account'])) $this->account = $_SESSION['datax']['account'];
				if (isset($_SESSION['datax']['history'])) $this->history = $_SESSION['datax']['history'];
			}
			
			$this->decisions = array();
			$this->result = NULL;
			
		}
		
		public function __destruct()
		{
			// save this!
			if (!isset($_SESSION['datax'])) $_SESSION['datax'] = array();
			$_SESSION['datax']['trackhash'] = self::$track_hash;
			$_SESSION['datax']['account'] = $this->account;
			$_SESSION['datax']['history'] = $this->history;
			
		}
		
		public function Force_Pass($force = NULL)
		{
			
			if (is_bool($force)) $this->force = $force;
			elseif (is_null($force)) $force = $this->force;
			else $force = NULL;
			
			return($force);
			
		}
		
		public function Account($account = NULL)
		{
			
			if (is_string($account))
			{
				
				$this->account = $account;
				
				if (strtolower($account) == 'pw')
				{
						
					// can't use the same track hash
					// for a different account
					self::$track_hash = NULL;
					
				}
				
			}
			elseif (is_null($account))
			{
				$account = $this->account;
			}
			else
			{
				$account = FALSE;
			}
			
			return($account);
			
		}
		
		public function Reset()
		{
			if (isset($_SESSION['datax']) && is_array($_SESSION['datax']))
			{
				unset($_SESSION['datax']);
			}			
		}
		
		public function Received()
		{
			return($this->datax->Get_Received_Packet());
		}
		
		public function Sent()
		{
			return($this->datax->Get_Sent_Packet());
		}
		
		public function Response()
		{
			return($this->response);
		}
		
		public function Decision()
		{
			return($this->decision);
		}
		
		public function Score()
		{
			return($this->score);
		}
		
		public function Reason()
		{
			return($this->reason);
		}
		
		public function Elapsed()
		{
			return($this->elapsed);
		}
		
		public function History($type = NULL)
		{
			
			$decision = NULL;
			
			if ((!is_null($type)) && (isset($this->history[$type]) || array_key_exists($type, $this->history)))
			{
				$decision = $this->history[$type];
			}
			elseif (is_null($type))
			{
				$decision = $this->history;
			}
			
			return($decision);
			
		}
		
		public function Last_Call()
		{
			return($this->last_call);
		}
		
		public function Track_Hash()
		{
			return(self::$track_hash);
		}
		
		protected function Build_IDV($data)
		{
			
			// Change ID Issued State [RL]
			$issued_state = ($data['state_issued_id']) ? @$data['state_issued_id'] : @$data['home_state'];
			
			// prepare our data

			$query = array(
				'trackid'		=> @$this->config->application_id,
				'ssn' 			=> trim(@$this->config->data['ssn_part_1'].@$this->config->data['ssn_part_2'].@$this->config->data['ssn_part_3']),
				'namefirst' 	=> @$data['name_first'],
				'namelast'  	=> @$data['name_last'],
				'street1'		=> preg_replace("/[&#;]/", " ", @$data['home_street']), //Mantis #5334 - strip this stuff out for experian
				'city'			=> @$data['home_city'],
				'state'			=> @$data['home_state'],
				'zip'			=> @$data['home_zip'],
				'homephone'		=> @$data['phone_home'],
				'cellphone'		=> @$data['phone_cell'],
				'email'			=> @$data['email_primary'],
				'ipaddress'		=> @$data['client_ip_address'],
				'legalid'		=> @$data['state_id_number'],
				'legalstate'	=> $issued_state,
				'dobyear'		=> @$data['date_dob_y'],
				'dobmonth'		=> @$data['date_dob_m'],
				'dobday'		=> @$data['date_dob_d'],
				'namemiddle'	=> @$data['name_middle'],
				'street2'		=> @$data['home_unit'],
				'phonework'		=> @$data['phone_work'],
				'workext'		=> @$data['ext_work'],
				'bankname'		=> @$data['bank_name'],
				'bankaba'		=> @$data['bank_aba'],
				'bankacct'		=> @$data['bank_account'],
				'banktype'		=> @$data['bank_account_type'],
				'employername'	=> @$data['employer_name'],
				'promo'			=> @$this->config->config->promo_id,
				'payperiod'		=> @$data['paydate_model']['income_frequency']
			);
			
			// get our source URL
			$url = @$data['client_url_root'];
			
			// remove the http://, if it exists
			if (preg_match('/^(https?:\/\/)/', $url, $matched))
			{
				$url = substr($url, strlen($matched[1]));
			}
			
			$query['source'] = $url;
			
			return($query);
			
		}
		
		protected function Build_Performance($data)
		{

			$query = NULL;
			
			// we should always have a previous hash
			if (self::$track_hash)
			{
				$query = array('ssn' => @$data['social_security_number']);
			}
			
			return($query);
			
		}
		
		protected function Build_Query($type, $data)
		{
			if($type == self::TYPE_PERF)
			{
				$query = $this->Build_Performance($data);
			}
			else
			{
				// cleanup bad chars prior to building datax query
				$query = $this->Build_IDV($data);
			}
			
			return $query;
		}
		
		public function Call($type, $data)
		{
			$decision = NULL;
			$query = NULL;
			
			// reset these
			$this->last_response = NULL;
			$this->decision = NULL;
			$this->score = NULL;
			$this->reason = NULL;

			if($this->account)
			{
				$query = $this->Build_Query($type, $data);

				if($query)
				{
					// re-use our trackhash, if we have one
					if(self::$track_hash)
					{
						$query['trackhash'] = self::$track_hash;
					}
					
					// are we forcing a pass?
					//$force = ($this->force) ? 'PASS' : NULL;

					//Disable this for now, since passing it in brings back a bad track hash which
					//will result in errors for every subsequent call.  Also, we already force a 'pass'
					//in Start_Call if this->force is true.
					$force = NULL;
					$call_type_override = NULL;
					
					// time the calls
					$timer = new Timer($this->config->applog, self::TIMER_LIMIT);
					$timer->Timer_Start($type);

					// actually do the calling
					$result = $this->datax->Datax_Call($type, $query, $this->config->mode, $this->account, $force, $call_type_override);
					$this->last_response = $result;

					// stop timer
					$timer->Timer_Stop($type);
					$this->elapsed = $timer->Get_Elapsed($type);
					
					if(isset($result['Response']['ErrorCode']))
					{
						/**
						 * All idv-l5 and idv-l1 experian errors will be in the following format: E-###
						 * we want to throw a special DataX_API_Exception for these if UFC.
						 */
						if(strcasecmp(substr($result['Response']['ErrorCode'],0,1),'E') === 0) /// Deadly error and UFC
						{
							throw new DataX_Exception('ERROR: ['.$result['Response']['ErrorCode'].'] '.$result['Response']['ErrorMsg']);
						}
						else
						{
							throw new DataX_API_Exception('ERROR: ['.$result['Response']['ErrorCode'].'] '.$result['Response']['ErrorMsg']);
						}
					}
					elseif(!empty($result))
					{
						$this->Find_Decision($type, $result);
					}
					else
					{
						throw new DataX_API_Exception('Invalid DataX response.');
					}
					
					if(!is_null($this->decision))
					{
						$decision = ($this->decision == 'Y');
					}
					else
					{
						// oops!
						throw new DataX_API_Exception('Error while making DataX call');
					}
					
					/* Project: Decisioning Report
					 * Author: Ryan Murphy
					 *
					 * We need to hit stats based upon the reason for a DataX failure.
					 * I should update all the other DataX MiniXML using features to
					 * instead use the MagicDataxParser class, but since Blackbox is
					 * already being rewritten, just leave this as is.
					 *
					 * If you are rewriting blackbox and reading this, you might want
					 * to look into the parser class to make the job of figuring out
					 * failure codes and other parts of blackbox much, much easier.
					 */
					$domdoc = new MagicDataxParser($this->datax->Get_Received_Packet());
					
					$target_stats = OLPStats_Spaces::getInstance(
						$this->config->mode,
						$this->config->getTargetID($this->account), // May be 0
						$this->config->bb_mode,
						$this->config->config->page_id,
						$this->config->config->promo_id,
						$this->config->config->promo_sub_code
					);
					
					$bucket_stats = array(
						'idv-l1' => array(
							'C_ASL_OFAC_FAIL_1' => 'ofac_results1',
							'C_ASL_SSNFORMAT_FAIL_1' => 'ssn_valid_format',
							'C_ASL_SSN_DECEASED_FAIL_1' => 'ssn_deceased',
							'C_ASL_SSN_ISSUE_RESULT_FAIL_1' => 'ssn_issued_or_opened',
							'D_ASL_SSN_RESULT_DOB_FAIL_1' => 'ssn_matches',
							'D_ASL_SSN_RESULT_DOB_FAIL_2' => 'ssn_matches',
							// Yes, these below are the number 1 and not the letter L.
							'C_AS1_ADDRESS_VERIFICATION_FAIL' => 'address_verification',
							'C_AS1_ADDRESS_TYPE_FAIL' => 'address_type',
							'C_AS1_PHONE_VERIFICATION_FAIL' => 'phone_verification',
						),
						
						'idv-l3' => array(
							'C_ASL_OFAC_FAIL_1' => 'ofac_results1',
							'C_ASL_SSNFORMAT_FAIL_1' => 'ssn_valid_format',
							'C_ASL_SSN_DECEASED_FAIL_1' => 'ssn_deceased',
							'C_ASL_SSN_ISSUE_RESULT_FAIL_1' => 'ssn_issued_or_opened',
							'D_ASL_SSN_RESULT_DOB_FAIL_1' => 'ssn_matches',
							'D_ASL_SSN_RESULT_DOB_FAIL_2' => 'ssn_matches',
							'CRA_INQ4MO_FAIL_1' => 'inquiries_cra',
							'C_CRA_3ABA_7DAY_FAIL' => 'back_accounts',
							'C_CRA_RETURN_3DAY_FAIL' => 'payment_returns',
							'C_CRA_BANK_ADDRESS_CONFLICT_FAIL' => 'bank_account_address',
							'C_CRA_RETURN_REASON_FAIL' => 'last_return_fatal',
							'TLT_INQCA4MO_FAIL_1' => 'inquiries_tt',
							'TLT_OCOCA_INQCA4MO_FAIL_1' => 'chargeoffs',
							'TLT_BK_OCOCA_FAIL_1' => 'bankruptcies_chargeoffs',
							// Yes, these below are the number 1 and not the letter L.
							'C_AS1_ADDRESS_VERIFICATION_FAIL' => 'address_verification',
							'C_AS1_ADDRESS_TYPE_FAIL' => 'address_type',
							'C_AS1_PHONE_VERIFICATION_FAIL' => 'phone_verification',
							'C_CRA_NONCLK_CO_INQ4MO_FAIL' => 'nonclk_inq4mo',
						),
						
						'idv-l5' => array(
							'C_ASL_OFAC_FAIL_1' => 'ofac_results5',
							'DOBMATCH_RESULT_FAIL_1' => 'dob_match',
							'D_ASL_SSN_RESULT_FAIL_1' => 'ssn_match',
						),
						
						'perf-l3' => array(
							'CRA_INQ4MO_FAIL_1' => 'inquiries_cra',
							'C_CRA_3ABA_7DAY_FAIL' => 'back_accounts',
							'C_CRA_RETURN_3DAY_FAIL' => 'payment_returns',
							'C_CRA_BANK_ADDRESS_CONFLICT_FAIL' => 'bank_account_address',
							'C_CRA_RETURN_REASON_FAIL' => 'last_return_fatal',
							'TLT_INQCA4MO_FAIL_1' => 'inquiries_tt',
							'TLT_OCOCA_INQCA4MO_FAIL_1' => 'chargeoffs',
							'TLT_BK_OCOCA_FAIL_1' => 'bankruptcies_chargeoffs',
							'C_CRA_NONCLK_CO_INQ4MO_FAIL' => 'nonclk_inq4mo',
						),
					);
					
					$result_array = explode(',', $domdoc->getDecisionCode());
					
					// Hit a stat for the failure reason
					foreach ($result_array AS $bucket)
					{
						if (isset($bucket_stats[$type][$bucket]))
						{
							if ($target_stats) $target_stats->hitStat($bucket_stats[$type][$bucket] . '_fail');
						}
					}
					
					// Hit a global stat for pass/fail
					if (isset($bucket_stats[$type]))
					{
						$stat_name = 'datax_' . str_replace('-', '_', $type);
						if ($domdoc->getDecisionResult() == 'Y')
						{
							$stat_name .= '_pass';
						}
						else
						{
							$stat_name .= '_fail';
						}
						
						if ($target_stats) $target_stats->hitStat($stat_name);
					}
				}
				else
				{
					throw new DataX_API_Exception('Could not build a DataX query.');
				}
				
				// save these
				$this->history[$type] = $decision;
				$this->last_call = $type;
			}
			else
			{
				throw new DataX_Exception('No account was found.');
			}
			
			// If if the datax type used was a rework, set the rework_ran flag so we don't run reworks again. GForge [#5732] [DW]
			if(in_array($type, array(self::TYPE_PDX_REWORK, self::TYPE_IDV_REWORK)))
			{
				$_SESSION['REWORK_RAN'] = true;
			}
			
			return $decision;
		}
		
		
		protected function Find_Decision($type, $result)
		{
			switch($type)
			{
				// Change all Impact companies to use new packet layout - GForge 5576 [DW]
				case self::TYPE_PDX_REWORK:
				case self::TYPE_IDVE_IMPACT:
				case self::TYPE_IDVE_IFS:
				case self::TYPE_IDVE_IPDL:
				case self::TYPE_IDVE_ICF:
					// save our track hash for re-use
					self::$track_hash = $result['Transaction']['TrackHash'];
					
					// get the DataX decision
					$this->decision = strtoupper($result['Response']['Detail']['GlobalDecision']['Result']);
					$this->score = $result['Response']['Detail']['ConsumerIDVerificationSegment']['AuthenticationScore'];
					
					if(isset($result['Response']['Detail']['GlobalDecision']['CRABucket']))
					{
						$this->reason = strtoupper($result['Response']['Detail']['GlobalDecision']['CRABucket']);
					}
					else 
					{
						$this->reason = strtoupper($result['Response']['Detail']['GlobalDecision']['IDVBucket']);
					}
					break;

				case self::TYPE_IDV_CLK:
				case self::TYPE_IDV_PW:
				case self::TYPE_IDV_PREQUAL:
				case self::TYPE_PERF:
				case self::TYPE_IDV_REWORK:
				case self::TYPE_IDV_CCRT:
					// save our track hash for re-use
					self::$track_hash = $result['TrackHash'];
					// get the DataX decision
					$this->decision = strtoupper($result['Response']['Summary']['Decision']);
					
					if($type == self::TYPE_PERF)
					{
						$this->reason = strtoupper($result['Response']['Summary']['DecisionBuckets']['Bucket']);//GForge #4550 [MJ]
					}
					else
					{
						$this->reason = strtoupper($result['Response']['Summary']['DecisionBucket']);
					}
					
					$this->score = $result['Response']['General']['AuthenticationScoreSet']['AuthenticationScore'];							
				break;
				
				case self::TYPE_PERF_MLS:
					self::$track_hash = $result['TrackHash'];
					$this->decision = strtoupper($result['Response']['Detail']['GlobalDecision']['Result']);
					$this->reason = strtoupper("idv-".$result['Response']['Detail']['GlobalDecision']['IDVBucket']);
					$this->score = $result['Response']['Detail']['ConsumerIDVerificationSegment']['AuthenticationScore'];
					//If it's a fail, AND CRA failed, hit adverse action stat
					if($this->decision == 'N' &&
						strtoupper($result['Response']['Detail']['CRASegment']['Decision']['Result']) == 'N'
					)
					{
						Stats::Hit_Stats('aa_aalm_cra_denial', $this->config->session, $this->config->log, $this->config->applog, $this->config->application_id);
					}
				break;
				case self::TYPE_DF_PHONE:

					$phone_type = $result['DatafluxData']['PhoneChk']['Phone_Type'];

					$this->decision = ($phone_type == 'Cell') ? 'Y' : 'N';
					$this->reason = $phone_type;
				
				break;

				default:
					throw new Exception('Invalid DataX response.');
				break;
			}
		}
	
		
		
		
		public function Run($datax_type, $account, $source = self::SOURCE_NONE, &$target = NULL)
		{
			$valid = FALSE;

			// set a few things up
			$this->Set_Force($datax_type);
			$this->Account($account);
			unset($this->result);

			// decide what kind of call we're going to make
			$call_type = $this->Get_DataX_Type($datax_type, $source);

			//If it's an IDV call, make sure IDV calls are enabled before
			//making it.
			if($call_type != self::TYPE_PERF
				&& (!defined('USE_DATAX_IDV') || USE_DATAX_IDV == false))
			{
				$valid = TRUE;
				$name = ($target) ? $target->Name() : NULL;
				//make sure we log the event
				$this->config->Log_Event($datax_type,'VERIFY',$name);
			}
			elseif($call_type)
			{
				$valid = $this->Start_Call($datax_type, $call_type, $target);
				$valid = ($valid === NULL) ? FALSE : $valid;//If valid is null, set it to false.
				$valid = $this->Finish_Call($valid, $call_type, $target);
				$result = $this->Result();

				// did we pass or fail?
				if(!isset($result) && !$this->Force_Pass())
				{
					$result = ($valid) ? EVENT_PASS : EVENT_FAIL;
				}
				elseif($this->Force_Pass())
				{
					// we forced it through
					$result = EVENT_SKIP;
				}
				
				$this->Result($result);

				// who are we hitting this for?
				$name = ($target) ? $target->Name() : NULL;
				// log this event
				$this->config->Log_Event($datax_type, $result, $name);
			}
			else
			{
				$valid = TRUE;
			}

			return $valid;
		}
		
		

		

		protected function Start_Call($type, $call_type, &$target = NULL)
		{
			$valid = NULL;

			// we can reuse the CLK call
			if($call_type == self::TYPE_IDV_PW)
			{
				// Check if rework is being used and a rework hasn't already been run yet. GForge [#5732] [DW]
				if($_SESSION['IDV_REWORK'] && !$_SESSION['REWORK_RAN'])
				{
					$valid = $this->History(self::TYPE_IDV_REWORK);							
				}
				else
				{
					$valid = $this->History(self::TYPE_IDV_CLK);
				}
			}

			// we only want to run datax IDV once per app
			if($call_type && is_null($this->History($call_type)) && is_null($valid))
			{
				try
				{
					// make the call
					$valid = $this->Call($call_type, $this->config->data);
					if ($this->force) $valid = TRUE;
				}
				catch (DataX_API_Exception $e)
				{
					// let us pass
					$this->Result('ERROR');

					// log this correctly
					if($call_type == self::TYPE_PERF_MLS || 
						$call_type == self::TYPE_DF_PHONE || 
						$this->config->config->site_type == 'ecash_yellowbook')
					{
						$this->decision = 'N';
						$valid = FALSE;
					}
					else
					{
						$this->decision = 'Y';
						$valid = TRUE;
					}
					
					$this->reason = $e->getMessage();
				}
				catch(DataX_Exception $e)
				{
					$valid = FALSE;
				}

				// insert the response into the authentication table
				$auth = new Authentication(
					$this->config->sql,
					$this->config->database,
					$this->config->applog
				);
				$auth->Insert_Record(
					$this->config->application_id,
					$this->Get_Source_ID($call_type),
					$this->Sent(),
					$this->Received(),
					$this->Decision(),
					$this->Reason(),
					$this->Elapsed(),
					$this->Score()
				);

				// neophyte, establish class data_x decision for either test
				$this->decisions[$type] = $this->Decision();

				$this->Hit_Stats($valid, $call_type);
			}
			elseif(is_null($valid))
			{
				// get our old response
				$valid = $this->History($call_type);
				if ($this->force) $valid = TRUE;
			}

			return $valid;
		}

		protected function Get_Source_ID($call_type)
		{
			$source_id = null;
			
			switch($call_type)
			{
				case self::TYPE_IDV_PREQUAL:$source_id = 0; break;
				case self::TYPE_IDV_CLK:	$source_id = 1; break;
				case self::TYPE_PERF:		$source_id = 2; break;
				case self::TYPE_IDV_PW:		$source_id = 3; break;
				case self::TYPE_IDV_REWORK:	$source_id = 4; break;
				case self::TYPE_IDVE_IMPACT:$source_id = 5; break; // Change IC to use impact-idve - GForge 5576 [DW]
				case self::TYPE_PDX_REWORK:	$source_id = 6; break;
				case self::TYPE_DF_PHONE:	$source_id = 7; break;
				case self::TYPE_IDV_CCRT:	$source_id = 8; break;
				case self::TYPE_PERF_MLS:   $source_id = 11; break;
				case self::TYPE_IDVE_IFS:	$source_id = 12; break;
				case self::TYPE_IDVE_IPDL:	$source_id = 13; break;
				case self::TYPE_IDVE_ICF:	$source_id = 14; break;
			}
			
			return $source_id;
		}
		
		protected function Finish_Call($valid, $call_type, &$target)
		{
			if($target && $target->Tier() != 1)
			{
				// we're valid if we passed it before, or if
				// we want people who failed or we don't care
				$valid = ($valid === $target->IDV() || is_null($target->IDV()));
				if($this->Force_Pass())
				{
					$valid = TRUE;
				}
			}
			
			return $valid;
		}
		
	
		protected function Set_Force($type)
		{
			$force = FALSE;
			
			switch($type)
			{
				case EVENT_DATAX_PDX_IMPACT:
				case EVENT_DATAX_IDV:
					$force = ($this->config->debug->Debug_Option(DEBUG_RUN_DATAX_IDV) === FALSE);
					break;
				case EVENT_DATAX_PERF:
					$force = ($this->config->debug->Debug_Option(DEBUG_RUN_DATAX_PERF) === FALSE);
					break;
			}
			
			$this->Force_Pass($force);
			
			return $force;
		}
		
		protected function Hit_Stats($valid, $call_type)
		{
			$stat = array();
			
			
			switch($call_type)
			{
				case self::TYPE_IDV_REWORK:
					if($_SESSION["return_visitor"])
					{
						$stat[] = ($valid) ? 'idv_rework_return' : 'idv_rework_fail_return';
					}
					else
					{						
						$stat[] = ($valid) ? 'idv_rework_pass' : 'idv_rework_fail';
					}
					break;
					
				case self::TYPE_PDX_REWORK:
					if($_SESSION["return_visitor"])
					{
						$stat[] = ($valid) ? 'pdx_rework_return' : 'pdx_rework_fail_return';
					}
					else
					{						
						$stat[] = ($valid) ? 'pdx_rework_pass' : 'pdx_rework_fail';
					}
					break;
				
				case self::TYPE_IDV_PW:
					$stat[] = ($valid) ? 'pw_idv_pass' : 'pw_idv_fail';
					break;
				
				case self::TYPE_IDV_CLK:
					$stat[] = ($valid) ? 'idv_l1_pass' : 'idv_l1_fail';
					break;
					
				// Change IC to use impact-idve - GForge 5576 [DW]
				/*case self::TYPE_PDX_IMPACT:
					$stat[] = ($valid) ? 'pdx_impact_pass' : 'pdx_impact_fail';
					break;*/
				case self::TYPE_IDVE_IMPACT:
					$stat[] = ($valid) ? 'idve_ic_pass' : 'idve_ic_fail';
					break;
				
				case self::TYPE_PERF_MLS:
					$stat[] = ($valid) ? 'aalm_perf_pass' : 'aalm_perf_fail';
					break;
				
				case self::TYPE_IDV_PREQUAL:
					$stat[] = ($valid) ? 'prequal_clv_pass' : 'prequal_clv_fail';
					$stat[] = ($valid) ? 'idv_l5_pass' : 'idv_l5_fail';
					break;
				
				case self::TYPE_PERF:
					$stat[] = ($valid) ? 'bb_clv_pass' : 'bb_clv_fail';
					$stat[] = ($valid) ? 'perf_l3_pass' : 'perf_l3_fail';
					break;
				
				case self::TYPE_IDVE_IFS:
					$stat[] = ($valid) ? 'idve_ifs_pass' : 'idve_ifs_fail';
					break;
					
				case self::TYPE_IDVE_IPDL:
					$stat[] = ($valid) ? 'idve_ipdl_pass' : 'idve_ipdl_fail';
					break;
					
				case self::TYPE_IDVE_ICF:
					$stat[] = ($valid) ? 'idve_icf_pass' : 'idve_icf_fail';
					break;
			}



			// don't hit stats if we're in mode_online_confirmation because
			// we're using the enterprise config and these are bb stats
			if (!empty($stat) && $this->config->bb_mode !== MODE_ONLINE_CONFIRMATION)
			{
				// hit the stat
				for($st = 0; $st < count($stat); $st++)
				{
					Stats::Hit_Stats($stat[$st], $this->config->session, $this->config->log, $this->config->applog, $this->config->application_id);
				}
			}
		}
		
		
		public function Result($result = NULL)
		{
			if(!is_null($result)) $this->result = $result;
			
			return $this->result;
		}
		
		
		public function Get_Decisions()
		{
			return $this->decisions;
		}
		
		
		
		public function Get_DataX_Type($type, $source = self::SOURCE_NONE)
		{
			$call_type = FALSE;

			switch ($type)
			{
				//DataFlux cellphone check
				case EVENT_DATAFLUX_PHONE:
					$call_type = self::TYPE_DF_PHONE;
					break;
				
				//Impact IDVE calls
				// Change IC to use impact-idve - GForge 5576 [DW]
				case EVENT_DATAX_IC_IDVE:
					$call_type = self::TYPE_IDVE_IMPACT;
					break;
				case EVENT_DATAX_PDX_REWORK:
					$call_type = self::TYPE_PDX_REWORK;
					break;
				case EVENT_DATAX_IFS_IDVE:
					$call_type = self::TYPE_IDVE_IFS;
					break;
				case EVENT_DATAX_IPDL_IDVE:
					$call_type = self::TYPE_IDVE_IPDL;
					break;
				case EVENT_DATAX_ICF_IDVE:
					$call_type = self::TYPE_IDVE_ICF;
					break;
				// IF Rework verfication call
				case EVENT_DATAX_IDV_REWORK:
					$call_type = self::TYPE_IDV_REWORK;
					break;
				case EVENT_DATAX_AALM:
					$call_type = self::TYPE_PERF_MLS;
					break;
				// Performance call
				case EVENT_DATAX_PERF:

					switch ($this->config->bb_mode)
					{

						// excluded modes
						case MODE_CONFIRMATION:
						case MODE_ONLINE_CONFIRMATION:
						case MODE_PREQUAL:
							break;

						default:
							$call_type = self::TYPE_PERF;
							break;

					}

					break;

			}
			

			if(($source == self::SOURCE_CLK || $source == self::SOURCE_IMP)
				&& ($type == EVENT_DATAX_IDV || $type == EVENT_DATAX_PDX_IMPACT))
			{
				// figure out who we are
				$ssn = trim(@$this->config->data['ssn_part_1'].@$this->config->data['ssn_part_2'].@$this->config->data['ssn_part_3']);
				
				// Check if rework is being used and a rework hasn't already been run yet. GForge [#5732] [DW]
				if($_SESSION["IDV_REWORK"] && !$_SESSION['REWORK_RAN'])
				{
					$call_type = ($type == EVENT_DATAX_IDV) ? self::TYPE_IDV_REWORK : self::TYPE_PDX_REWORK;
				}
				// Need to make sure we also have the address before running the IDV_PREQUAL check [BrianF]
				elseif($this->config->bb_mode !== MODE_CONFIRMATION &&
					($this->config->bb_mode === MODE_DEFAULT ||
						(strlen($ssn) >= 4 && strlen($this->config->data['home_street']) > 1)))
				{
					$return_type = ($source == self::SOURCE_CLK) ? self::TYPE_IDV_CLK : self::TYPE_PDX_IMPACT;
					$call_type = ($this->config->bb_mode == MODE_PREQUAL) ? self::TYPE_IDV_PREQUAL : $return_type;
				}
				elseif($this->config->bb_mode === MODE_PREQUAL && $this->config->config->site_type == 'ecash_yellowbook')
				{
					$call_type = BlackBox_DataX::TYPE_IDV_PREQUAL;
				}
			}
			elseif($source == self::SOURCE_NONE && $type == EVENT_DATAX_IDV)
			{
				$call_type = self::TYPE_IDV_PW;
			}
			elseif($source == self::SOURCE_CCRT)
			{
				$call_type = self::TYPE_IDV_CCRT;
			}

			return $call_type;
		}
		
	}
	

	
	
	class BlackBox_DataX_Agean extends BlackBox_DataX
	{
		const EVENT_AGEAN_PERF = 'DATAX_AGEAN_PERF';
		const EVENT_AGEAN_TITLE = 'DATAX_AGEAN_TITLE';
		
		const TYPE_AGEAN_PERF	= 'agean-perf';
		const TYPE_AGEAN_TITLE	= 'agean-title';
		
		const SOURCE_AGEAN		= 4;
		
		protected static $call_history = array();
		protected $aa_hit = false;
		protected $trigger_hit = array();
		
		protected $response_map = array(
			'idv' => array(
				//Hard fails
				'D1' => 'SSN is invalid',
				'D2' => 'SSN is deceased',
				'D3' => 'SSN not open for issue or issued',
				'D4' => 'OFAC hit',
				'D5' => 'SSN name/address/dob matches failed',
				'D6' => 'SSN issue before DOB',
				'D7' => 'Not 18 years old according to Experian',
				
				//Soft Pass
				'R1' => 'DOB >= 1991 and SSN Issuance > (DOB + 2 years)',
				'R2' => 'DOB < 1991 and SSN Issuance > (DOB + 18 years)',
				'R3' => 'Phone invalid and type not cellular, mobile or PCS',
				'R4' => 'Work phone type fail.'
			),
			
			'bav' => array(
				'D1' => 'ABA not valid or ACH return code is 401 or 101',
				'R1' => 'Bank type is savings or credit union',
			),
			
			'dpb' => array(
				'D1' => 'Score less than 480',
				'D2' => 'Multiple SSNs associated with individual',
				'D3' => 'SSN mismatch with DDA name and address information',
				'D4' => 'Reported as applying for loans with different SSNs',
				'D5' => 'Consumer\'s SSN may have been used by another individual',
				'D6' => 'Bank account associated with fraud',
				'D7' => 'Unpaid, defaulted loans originated in the last 180 days',
				'D8' => 'ABA has a high negative loan default',
				'D9' => '2 or more bank accounts associated with payday loan applications',
				'D10' => 'Open loans > 0',//Agean CRA/DPB-(Changed description)[MJ]
				'D11' => 'Number of inquiries in the last 60 days > 19',
				'D12' => 'MICR > 2',//Agean CRA/DPB-(Added)[MJ]

				'R1' => 'Last name from result does not match last name from input',
				'R2' => 'Number of inquiries in last 60 days between 1 and 19',
				'R3' => 'Number of open loans between 1 and 2',
				'R4' => 'Number of chargeoffs older than 180 days > 0',
				'R5' => 'Number of open loans is null',//Agean CRA/DPB-(Added)[MJ]
				'R6' => 'Work phone not valid'//Agean CRA/DPB-(Added)[MJ]
		),

			'cra' => array(//Agean CRA/DPB-(Added)[MJ]
				'D10' => '(SSN Match = Current Tradeline) >= 3',
				'D11' => 'Daily inquiries in the last 60 days > 20',
				'D12' => 'Charge offs in the last 180 days found',
				'D13' => 'Daily inquiries in the last 7 days >= 4',
				'D14' => 'ACH (last) = Returned',
				'D15' => 'ACH returns in the last 60 days >= 3',

				'R2' => '(SSN Match = Tradeline) >=1 <=2',
				'R3' => 'Daily Inquiries in the last 60 days >=1 <=19',
				'R4' => 'Charge offs older than 180 days found',
		),
			
			'tt' => array(
				'R1' => 'Customer has recent inquiries.',
				'R2' => 'Customer has recent chargeoffs.',
				'R3' => 'Customer has open loans.',
			)
		);
		
		protected $triggers = array(
			'idv' => array(
				'R1' => array(5, 13),
				'R2' => array(6, 13),
				'R3' => array(2),
				'R4' => array(3)
			),

			'bav' => array(
				'R1' => array(8)
			),

			'dpb' => array(
				'R1' => array(15, 13),
				'R2' => array(10),
				'R3' => array(9),
				'R4' => array(11),
				'R5' => array(21)//Agean CRA/DPB-(Added)[MJ]
			),
			
			'tt' => array(
				'R1' => array(18),
				'R2' => array(19),
				'R3' => array(17)
			),
		);

		protected function Build_Query($type, $data)
		{
			return $this->Build_IDV($data);
		}
		
		protected function Build_IDV($data)
		{
			$query = parent::Build_IDV($data);
			$query['bankaccttype'] = (strcasecmp($data['bank_account_type'], 'savings') === 0) ? 'savings' : 'checking';
			
			return $query;
		}

		protected function Get_Source_ID($call_type)
		{
			$source_id = null;
			
			switch($call_type)
			{
				case self::TYPE_AGEAN_PERF:	$source_id = 9; break;
				case self::TYPE_AGEAN_TITLE:$source_id = 10; break;
			}
			
			return $source_id;
		}
		
		protected function Find_Decision($type, $result)
		{
			self::$track_hash = $result['TrackHash'];
			
			switch($type)
			{
				case self::TYPE_AGEAN_PERF: $this->Find_Perf_Decision($result); break;
				case self::TYPE_AGEAN_TITLE:$this->Find_Title_Decision($result); break;
			}
		}
		
		private function Find_Perf_Decision($result)
		{
			$detail = (isset($result['Response']['Detail']['GlobalDecision'])) ? $result['Response']['Detail'] : $result['Response']; 
			
			//$detail = $result['Response']['Detail'];
			$this->decision = strtoupper($detail['GlobalDecision']['Result']);
			
			$buckets = $detail['GlobalDecision']['Buckets'];

			$reasons = array();
			$seg_name = '';
			foreach($buckets as $seg_name => $bucket)
			{
				$seg_name = strtolower($seg_name);
				$bucket = $bucket['Bucket']; 
				$bucket_count = (is_array($bucket) && !empty($bucket['_num'])) ? intval($bucket['_num']) : 1;
			
				//Stupid hack because if there is only one result, instead of creating an indexed
				//array, it just returns the content, which will break everything.
				if($bucket_count == 1)
				{
					$bucket = array($bucket);
				}
				elseif(is_array($bucket))
				{
					unset($bucket['_num']);
				}

				foreach($bucket as $seg_bucket)
				{
					if(isset($this->response_map[$seg_name]) && isset($this->response_map[$seg_name][$seg_bucket]))
					{
						if($seg_bucket[0] == 'D')
						{
							switch($seg_name)
							{
								case 'idv': $aa_stat = 'datax'; break;
								case 'bav': $aa_stat = 'creditbureau'; break;
								case 'dpb': $aa_stat = 'clverify'; break;
								case 'cra': $aa_stat = 'cra'; break;//Agean CRA/DPB-(Added)[MJ]
							}
							
							$this->Adverse_Action("aa_mail_{$aa_stat}");
							$this->decision = 'N';
							$reasons[] = $seg_name . '-' . $seg_bucket;
						}
						elseif($seg_bucket[0] == 'R' && !empty($this->triggers[$seg_name][$seg_bucket]))
						{
							foreach($this->triggers[$seg_name][$seg_bucket] as $trigger)
							{
								$this->Trigger($trigger);
							}
						}
					}
				}					
			}

			if($this->decision == 'N')
			{
				$this->reason = (!empty($reasons)) ? implode('+', $reasons) : $seg_name;
			}
			else
			{
				$this->Hit_Triggers();
			}
		}
		
		private function Find_Title_Decision($result)
		{
			$this->decision = strtoupper($result['Response']['Summary']['Decision']);
			$this->reason = '';
			
			$buckets = $result['Response']['Summary']['Buckets']['Bucket'];
			$bucket_count = (is_array($buckets) && !empty($buckets['_num'])) ? intval($buckets['_num']) : 1;

			//Stupid hack because if there is only one result, instead of creating an indexed
			//array, it just returns the content, which will break everything.
			if($bucket_count == 1)
			{
				$buckets = array($buckets);
			}
			elseif(is_array($buckets))
			{
				unset($buckets['_num']);
			}

			$reasons = array();
			$seg_name = 'tt';
			foreach($buckets as $seg_bucket)
			{
				if($this->decision == 'Y' && $seg_bucket[0] == 'R' && !empty($this->triggers[$seg_name][$seg_bucket]))
				{
					foreach($this->triggers[$seg_name][$seg_bucket] as $trigger)
					{
						$this->Trigger($trigger);
					}
				}
				elseif($this->decision == 'N')
				{
					$reasons[] = $seg_bucket;
				}
			}

			if($this->decision == 'N')
			{
				$this->reason = implode('+', $reasons);
				$this->Adverse_Action('aa_mail_veritrac');
			}
			else
			{
				$this->Hit_Triggers();
			}
		}
		
		protected function Adverse_Action($stat)
		{
			if(!$this->aa_hit)
			{
				Stats::Hit_Stats(
					$stat,
					$this->config->session,
					$this->config->log,
					$this->config->applog,
					$this->config->application_id
				);
				
				$this->aa_hit = true;
			}
		}
		
		public function Is_Adverse_Action()
		{
			return $this->aa_hit;
		}
		
		protected function Trigger($trigger)
		{
			if(!empty($trigger))
			{
				if(empty($this->trigger_hit[$trigger]))
				{
					$this->trigger_hit[$trigger] = true;
				}
			}
		}
		
		protected function Hit_Triggers()
		{
			foreach($this->trigger_hit as $trigger => $true)
			{
				Agean_Triggers::Log_Trigger($this->config->log, $this->config->bb_mode, $trigger);
			}
		}
		
		protected function Hit_Stats($valid, $call_type)
		{
			$stats = array();
			
			
			switch($call_type)
			{
				case self::TYPE_AGEAN_PERF:
				{
					$stats[] = ($valid) ? 'agean_perf_pass' : 'agean_perf_fail';
					break;
				}
			}


			// don't hit stats if we're in mode_online_confirmation because
			// we're using the enterprise config and these are bb stats
			if(!empty($stat) && $this->config->bb_mode !== MODE_ONLINE_CONFIRMATION)
			{
				// hit the stat
				foreach($stats as $stat)
				{
					Stats::Hit_Stats(
						$stat,
						$this->config->session,
						$this->config->log,
						$this->config->applog,
						$this->config->application_id
					);
				}
			}
		}
		
		
		public function Get_DataX_Type($type, $source = self::SOURCE_NONE)
		{
			return ($this->config->title_loan) ? self::TYPE_AGEAN_TITLE : self::TYPE_AGEAN_PERF;
		}
	}
	
	
	class Agean_Triggers
	{
		protected static $emails = array(
			1 => 1,
			2 => 2,
			3 => 3,
			4 => 4,
			5 => 5,
		);
		
		protected static $triggers = array(

			2 => array(
				'name' => 'HOME_PHONE_INVALID',
				'description' => 'Home phone number is invalid (may be a work phone).',
				'email' => 3
			),
			
			3 => array(
				'name' => 'WORK_PHONE_UNLISTED',
				'description' => 'Work phone type fail.'
			),

			5 => array(
				'name' => 'SSN_AFTER_2ND_BDAY',
				'description' => 'SSN issued after second birthday.',
				'email' => 1
			),

			6 => array(
				'name' => 'SSN_AFTER_18TH_BDAY',
				'description' => 'SSN issued after 18th birthday.',
				'email' => 1
			),

			8 => array(
				'name' => 'NON_PPS',
				'description' => 'Bank account is a credit union or savings account.'
			),
			
			9 => array(
				'name' => 'DPB_OPEN_LOANS',
				'description' => 'Number of open loans between 1 and 2.'
			),
			
			10 => array(
				'name' => 'DPB_RECENT_INQUIRIES',
				'description' => 'Number of inquiries in last 60 days between 1 and 19.'
			),
			
			11 => array(
				'name' => 'DPB_OLD_CHARGEOFFS',
				'description' => 'Number of chargeoffs older than 180 days > 0',
			),
			
			13 => array(
				'name' => 'RUN_VERITRAC',
				'description' => 'Lacking information needed for underwriting process.'
			),
		
			14 => array(
				'name' => 'NON_JOB_INCOME',
				'description' => 'Income type not from job.',
				'email' => 5
			),

			15 => array(
				'name' => 'DPB_LAST_NAME_MISMATCH',
				'description' => 'Mismatch on last name from DPB.',
				'email' => 1
			),
			
			17 => array(
				'name' => 'TT_OPEN_LOAN',
				'description' => 'Customer has open loans.',
			),
			
			18 => array(
				'name' => 'TT_RECENT_INQUIRIES',
				'description' => 'Customer has inquiries 90 or less.',
			),
			
			19 => array(
				'name' => 'TT_CHARGEOFFS',
				'description' => 'Customer has charge-offs 90 or younger.',
			),
			
			20 => array(
				'name' => 'DPB_RECENT_INQUIRY',
				'description' => 'Number of inquiries in last 60 days <= 1'
			),

			21 => array(//Agean CRA/DPB-(Added)[MJ]
				'name' => 'DPB_NO_UNDERWRITING_DATA',
				'description' => 'Data missing from electronic underwriting'
			),

			/*1 => array(// Unused
				'name' => 'SSN_NAME_MISMATCH',
				'email' => 1
			),

			3 => array(// Unused
				'name' => 'WORK_PHONE_UNLISTED',
				'email' => 3
			),

			4 => array(// Unused
				'name' => 'MAIL_ALL_INFO',
				'email' => 4
			),
			
			
			7 => array(// Unused
				'name' => 'BANK_ACCT_FRAUD',
				'email' => 2
			),*/
		);
		
		public static function Log_Trigger($event_log, $bb_mode, $trigger)
		{
			static $mail_sent;
			
			$trigger = intval($trigger);
			if(!empty($trigger) && isset($event_log) && !empty(self::$triggers[$trigger]))
			{
				$events = array('AGEAN_TRIGGER_' . $trigger);
				if(!empty(self::$triggers[$trigger]['email']))
				{
					$email_trigger = 'AGEAN_TRIGGER_EMAIL_' . self::$triggers[$trigger]['email'];
					
					if(empty($mail_sent[$email_trigger]))
					{
						$events[] = $email_trigger;
						$mail_sent[$email_trigger] = true;
					}
				}

				foreach($events as $event)
				{
					$event_log->Log_Event($event, 'VERIFY', null, null, $bb_mode);
				}
			}
		}
		
		public static function Get_Loan_Action($trigger)
		{
			$loan_action = null;
			
			$trigger = intval($trigger);
			if(!empty($trigger) && !empty(self::$triggers[$trigger]))
			{
				$loan_action = self::$triggers[$trigger]['name'];
				
			}
			
			return $loan_action;
		}
		
		public static function Get_Email($trigger)
		{
			$email = null;
			
			$trigger = intval($trigger);
			if(!empty($trigger) && !empty(self::$emails[$trigger]))
			{
				$email = self::$emails[$trigger];
			}
			
			return $email;
		}
		
		public static function Get_Description($trigger)
		{
			$desc = null;
			
			$trigger = intval($trigger);
			if(!empty($trigger) && !empty(self::$triggers[$trigger]))
			{
				$desc = self::$triggers[$trigger]['description'];
				
			}
			
			return $desc;
		}
	}

	class BlackBox_DataX_Impact extends BlackBox_DataX
	{
		/**
		* Type of datax call that failed [#5576] [DW]
		*
		* @var string
		*/
		protected $fail_type = null;
		
		public function __construct(&$config)
		{
			parent::__construct($config);
		}
		protected function Find_Decision($type, $result)
		{
			parent::Find_Decision($type,$result);
			
			if($this->decision == 'N')
			{
				if(isset($result['Response']['Detail']['GlobalDecision']['CRABucket']))
				{
					$this->fail_type = 'CRA';
				}
				else 
				{
					$this->fail_type = 'IDV';
				}
			}
		}
		
		public function Fail_Type()
		{
			return($this->fail_type);
		}
		
		protected function Start_Call($type, $call_type, &$target = NULL)
		{
			$valid = parent::Start_Call($type, $call_type, $target);
			
			$this->decisions['DATAX_IDV_IMPACT_ALL'] = $this->Decision();
			$this->decisions['FAIL_TYPE'] = $this->Fail_Type();
			
			return $valid;
		}
	}
	
	/**
	 * DataX_Exception was created to differentiate errors caused by datax and not the api when catching.
	 */
	class DataX_Exception extends Exception 
	{	
	}
	/**
	 * DataX_API_Exception was created to differentiate errors caused by the datax api instead of datax when catching.
	 */
	class DataX_API_Exception extends Exception
	{
	}
	
?>
