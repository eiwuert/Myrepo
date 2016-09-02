<?php

require_once(BFW_CODE_DIR . "conditional_map.class.php");

/**
 * @desc A class to handle posting of leads to other vendors
 *
 *
 *
 */

class Vendor_Post
{
	/**
	 * @privatesection
	 */
	protected $sql                 = NULL;
	protected $property_short      = '';
	protected $result              = NULL;
	protected $post_implementation = NULL;
	protected $lead_data           = NULL;
	private   $mode                = '*** UNSET ***';

	static protected $applog = NULL;

	/**
	 * @publicsection
	 */
	public function __construct(&$sql, $property_short, &$lead_data, $mode, &$applog = NULL)
	{

		$this->sql =& $sql;
		$this->mode = $mode;

		self::$applog =& $applog;

		$this->lead_data = &$lead_data;
		$this->Set_Property_Short($property_short);

	}

	public function Post()
	{
		if ($this->post_implementation)
		{
			$start_time = microtime(TRUE);
			$result = ($this->post_implementation->Post());

			// If we have a lender specified target, we need to set time for both the original winner
			//   and the lender specified winner. [LR]
			if (is_array($result))
			{
				foreach ($result as $r)
				{
					$r->Set_Post_Time(microtime(TRUE) - $start_time);
				}
			}
			else
			{
				$result->Set_Post_Time(microtime(TRUE) - $start_time);
			}
        
			$this->Set_Result($result);

		}
		else
		{
			$applog = self::Get_Applog_Instance();
			$applog->Write("Post called without a Vendor Post implementation", LOG_ERR);
		}

		return $this->Get_Result();
	}

	public function Set_Property_Short($property_short)
	{
		$this->property_short = $property_short;
		$this->Reset_State();
	}

	public function Get_Property_Short()
	{
		return $this->property_short;
	}

	/**
	 * @privatesection
	 * @desc Resets internal state so this object can be reused
	 */
	protected function Reset_State()
	{
		$this->post_implementation = $this->Find_Post_Implementation($this->property_short, $this->mode, $this->lead_data);
		$this->result = new Vendor_Post_Result();
		//unset($this->lead_data);
	}

	private function Set_Result($result)
	{
		$this->result = $result;
	}

	/**
	 * @desc Finds the name of the class that should be loaded to handle the actual posting
	 */
	public function Find_Post_Implementation($property_short, $mode, &$lead_data)
	{
		// For now this will be hard coded
		$class_name = NULL;
		
		switch (strtolower($property_short))
		{
			case 'plc':
				$class_name = 'Vendor_Post_Impl_PLC';
				break;
			case 'cac':
			case 'cac2':
			case 'cac3':
			case 'cac4':
				$class_name = 'Vendor_Post_Impl_CAC';
				break;
			case 'cg4':
			case 'cg4b':
			case 'cg_nd':
			case 'cgdd_nd':
			case 'cgt4':
			case 'cgt5':
			case 'cg_rm1':
			case 'cg_rml2':
			case 'cg_sp1':
			case 'cg_sp2':
			case 'cg_sp3':
			case 'cg_sp4':
			case 'cg_sp5':
			case 'cg_sp6':
			case 'cg_nd_t1':
			case 'cg_nd_t2':
				$class_name = 'Vendor_Post_Impl_CG_NEW';
				break;
			case 'cgdd':
			case 'cg':
			case 'cg2':
				$class_name = 'Vendor_Post_Impl_CG';
				break;
			case 'cg_uk':
			case 'cg_uk2':
				$class_name = 'Vendor_Post_Impl_CG_UK';
				break;
			case 'ct4u':
			case 'ct4u_cr':
			case 'ct4u2':
				$class_name = 'Vendor_Post_Impl_CT4U';
				break;
			case 'bmg172':
			case 'bmg178':
			case 'bmg178_2':
			case 'efm':
				$class_name = 'Vendor_Post_Impl_BMG';
				break;
			case 'gr':
			case 'gr2':
				$class_name = 'Vendor_Post_Impl_GR';
				break;
			case 'sun':
			case 'sun2':
			case 'sun3':
				$class_name = 'Vendor_Post_Impl_SUN';
				break;
			case 'vp':
			case 'vp2_t4':
			case 'vp1_5':
			case 'vp3':
			case 'vp4':
			case 'vp5':
			case 'vp12':
			case 'vp14':
				$class_name = 'Vendor_Post_Impl_VP';
				break;
			case 'ezm4':
			case 'ezmpan':
			case 'ezmcr':
			case 'ezmcr40':
			case 'ezmpan40':
				$class_name = 'Vendor_Post_Impl_EZM';
				break;
			case 'ame':
			case 'amedd':
				$class_name = 'Vendor_Post_Impl_AME';
				break;
			case 'test':
				$class_name = 'Vendor_Post_Impl_SKEL';
				break;
			case 'pdo1':
			case 'pdo_sf':
			case 'pdo_tc': // added Task # 11552 [AuMa]
			case 'pdo4':
			case 'pdo6':
				$class_name = 'Vendor_Post_Impl_PDO';
				break;
			case 'pdo2':
			case 'pdo3':
			case 'pdo5':
				$class_name = 'Vendor_Post_Impl_PDO2';
				break;
			case 'hm1':
			case 'hm2':
			case 'hm3':
			case 'hm4':
				$class_name = 'Vendor_Post_Impl_HM';
				break;
			case 'tcf':
			case 'tcf2':
			case 'tcf3':
			case 'tcf_we':
			case 'tcf_we2':
			case 'tcf_t1':
			case 'can':
			case 'can2':
			case 'can3':
			case 'can_we':
			case 'can_we2':
				$class_name = "Vendor_Post_Impl_TCF";
				break;
			case 'sis':
				$class_name = "Vendor_Post_Impl_SIS";
				break;
			case 'mca':
				$class_name = "Vendor_Post_Impl_MCA";
				break;
			case 'bi':
			case 'bi2':
			case 'bi3':
				$class_name = "Vendor_Post_Impl_BI";
				break;
			case 'bi_uk':
				$class_name = "Vendor_Post_Impl_BI_UK";
				break;
			case 'ntl':
			case 'ntl2':
			case 'ntl3':
			case 'ntl4':
			case 'ntl5':
			case 'ntl6':
			case 'ntl_t1':
			case 'ntl_t2':
				$class_name = 'Vendor_Post_Impl_NTL';
				break;
			case 'ilt':
			case 'iln':
			case 'apd':
			case 'apd2':
			case 'apd_t1':
			case 'fwc_al':
			case 'fwc_mo':
			case 'fwc_mo2':
			case 'fwc_sd':
			case 'fwc_sd2':
			case 'fwc_ut':
			case 'fwc_ut2':
			case 'fwc_mt':
			case 'fwc_co':
			case 'fwc_wi':
			case 'fwc_wi2':
			case 'fwc_wy':
			case 'fwc_az':
			case 'fwc_az2':
			case 'fwc_id':
			case 'fwc_id2':
			case 'fwc_nd':
			case 'fwc_de':
			case 'fwc_de2':
			case 'fwc_pa':
			case 'fwc_pa2':
			case 'fwc_mn':
			case 'fwc_mn2':
			case 'fwc_la':
			case 'fwc_la2':
			case 'fwc_ri':
			case 'fwc_ri2':
			case 'fwc_hi': //GForge #3982 [MJ]
			case 'fwc_ks': //GForge #3982 [MJ]
			case 'fwc_wa': //GForge #3982 [MJ]
			case 'fwc_ak': //GForge #3982 [MJ]	
			case 'wp':
			case 'ps':
			case 'wp_t1':
			case 'ps_t1':
			case 'py_t1':
			case 'ps2':
			case 'cs':
			case 'cs_t1':
			case 'cs2':
			case 'sdw':
			case 'frca_t1':
			case 'zip_t1'://GForge #3360 [MJ]
			case 'zip_t2'://GForge #4701 [AuMa]
			case 'zip1':
			case 'zip2':
			case 'py':
			case 'py2':
			case 'gecc':
			case 'gecc_t1': //Gforge 4699 [TF]
			case 'pts':
			case 'pts_t2':				
			case 'abs':
			case 'udc':
			case 'lls':
			case 'lls_1'://Mantis 12064 [MJ]
			case 'ufpd':
			case 'ufpd_t1': // GForge #8889 [AuMa]
			case 'gcm':
			case 'pd2g':
			case 'egf':
			case 'egf_1':
			case 'egf_we':
			case 'icd_fl':
			case 'icd_tx':
			case 'icd_ut':
			case 'aal':
			case 'mpt':	// Mantis #11965 [DY]
			case 'mpt2':
			case 'mpt3':
			case 'tbf':
			case 'exc':
			case 'exc1':
			case 'exc2':	
			case 'exc3':
			case 'exc4':
			case 'ace_t1':
			case 'ace_t1b':				
				$class_name = 'Vendor_Post_Impl_transdotcom';
				break;
			case 'ace': // GForge #9999 [AuMa]
				// ACE Brick and Mortor Campaign
				$class_name = 'Vendor_Post_Impl_ACE_B_AND_M';
				break;
			case 'iaw':
			//case 'iaw2': //GForge 10907 [TF]
			case 'iaw_we':
			case 'iaw_we2':
			case 'iaw3':
			case 'iaw4':
				$class_name = 'Vendor_Post_Impl_IAW';
				break;
			case 'pol':
				$class_name = 'Vendor_Post_Impl_POL';
				break;
			case 'grv':
			case 'grv2':
			case 'grv_t1':	
			case 'grv_t2': // tier 1
			case 'grv3':   // tier 2
			case 'grv4':   // tier 2 GForge #9473 [AuMa]
				$class_name = 'Vendor_Post_Impl_GRV';
				break;
			case 'cc1':
			case 'cc2':
			case 'cc3':
			case 'cc4':
				$class_name = 'Vendor_Post_Impl_DFS';
				break;
			case 'vs':
			case 'vs2':
				$class_name = 'Vendor_Post_Impl_VS';
				break;
			case 'cl':
				$class_name = 'Vendor_Post_Impl_CL';
				break;
			case 'bsc':
				$class_name = 'Vendor_Post_Impl_BSC';
				break;
			case 'lbp':
				$class_name = 'Vendor_Post_Impl_LBP';
				break;
			//case 'cm2': //pulled 'cm2', changed to use vendor_post_impl_dynamic GForge [#5963] [TF]
			//case 'cm2a':	//GFORGE_10124 converted to vendor_post_impl_dynamic [TF]
			//case 'cm1':		//...
			//case 'cm1a':	//...
			//	$class_name = 'Vendor_Post_Impl_CM1';
			//	break;
			case 'mte':
			case 'mte2':
				$class_name = 'Vendor_Post_Impl_MTE';
				break;
			case 'csx':
				$class_name = 'Vendor_Post_Impl_CSX';
				break;
			case 'ckt':
				$class_name = 'Vendor_Post_Impl_CKT';
				break;
			case 'imc':
				$class_name = 'Vendor_Post_Impl_IMC';
				break;
			case 'plc':
				$class_name = 'Vendor_Post_Impl_PLC';
				break;
			case 'smf':
				$class_name = 'Vendor_Post_Impl_SMF';
				break;
			case 'ica':
				$class_name = 'Vendor_Post_Impl_ICA';
				break;
			case 'ccrt':
			case 'ccrt1':
				$class_name = 'Vendor_Post_Impl_CCRT';
				break;
			case 'mct':
				$class_name = 'Vendor_Post_Impl_MCT';
				break;
			case 'pd1': // SW Ventures, LLC
				$class_name = 'Vendor_Post_Impl_PD';
				break;
			case 'utc':
				$class_name = 'Vendor_Post_Impl_UTC';
				break;
			case 'prs':
				$class_name = 'Vendor_Post_Impl_PRS';
				break;
			case 'lrrt':
			case 'lrrt2': 
				$class_name = 'Vendor_Post_Impl_LRRT';
				break;
			case 'pwrd1': // GForge #7159 [DY]
				$class_name = 'Vendor_Post_Impl_PWRD'; 
				break;
			case 'pap':
				$class_name = 'Vendor_Post_Impl_PAP';
				break;
			case 'mem_uk':
				$class_name = 'Vendor_Post_Impl_MEM_UK';
				break;
			case 'mdt':
				$class_name = 'Vendor_Post_Impl_MDT';
				break;
			case 'afs_t1':
			case 'afs2':
			case 'afs3':
			case 'afs4':
			case 'afs5':
				$class_name = 'Vendor_Post_Impl_AFS';
				break;
			case 'cfetest':
				$class_name = 'Vendor_Post_Impl_CFETest'; //Gforge #3878 [TF]
				break;
			case 'pwarb':
				$class_name = 'Vendor_Post_Impl_PWARB'; 
				break;
			case 'lcca': // GForge #8576 [BA]
				$class_name = 'Vendor_Post_Impl_LCCA';
				break;
		}

		

 	    	//@@TF Gforge #1483 10/09/2007 generic dynamic vendor post impl
 	    	//instantiate Conditional_Map class and try to make juice from concentrate
 	    	if (strlen($class_name)<5)
 	    	{

 	    		$condmap=new Conditional_Map();
 	    		$currentmode=$this->mode;

 	    		//transpose LOCAL or anything non-LIVE to RC, there's no interface for Monster
 	    		if(strcmp($currentmode,'LIVE')!=0){
 	    			$currentmode='RC';
 	    		}
 	    		$sqlconn=Setup_DB::Get_Instance('blackbox', $currentmode);
 	    		$mydb = $sqlconn->db_info['db'];
 	    		$sqlconn->Connect();
 	    		if($condmap->propExists($sqlconn, $this->property_short, $mydb)){
 	    			//map exists for this property short
 	    			$class_name = "Vendor_Post_Impl_DYNAMIC";
 	    		}
 	    		else {
 	    			//the dynamic search failed-- not good
 	    			$session_id = session_id();
 	    			$applog = self::Get_Applog_Instance();
 	    			$applog->Write("Missing Dynamic Vendor Post Implementation for {$property_short} ({$session_id}) " . __FILE__ . ':' . __LINE__, LOG_ERR);

 	    		}
 	    	}


		if ($class_name)
		{

			include_once(strtolower($class_name) . '.php');
			$post_implementation = new $class_name($lead_data, $mode, $property_short);

		}
		else
		{

			$session_id = session_id();

			$applog = self::Get_Applog_Instance();
			$applog->Write("Couldn't Find Vendor Post Implementation for {$property_short} ({$session_id}) " . __FILE__ . ':' . __LINE__, LOG_ERR);

			$post_implementation = NULL;

		}

		if (!$post_implementation) $post_implementation = FALSE;
		return($post_implementation);

	}

	public function Get_Result()
	{
		return $this->result;
	}

	/**
	 * @desc Singleton function to cache an applog instance only if it's needed
	 * @return Object An Applog object
	 */
	public static function Get_Applog_Instance()
	{

		if (!self::$applog)
		{
			self::$applog = new OLP_Applog(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, 'Vendor Post Class', 'OLP', 'FALSE', APPLOG_UMASK);
		}

		return self::$applog;

	}


	public function Post_Timeout_Exceeded()
	{
		return $this->post_implementation->timeout_exceeded;
	}

}

class Vendor_Post_Result
{
	protected $post_time         = 0;
	protected $message           = 'Post not yet attempted';
	protected $success           = FALSE;
	protected $empty_response    = FALSE;
	protected $data_sent         = '';
	protected $data_received     = '';
	protected $thank_you_content = '';
	protected $next_page         = '';
	protected $decision          = 'FAILED';
	protected $reason            = '';

	public function Get_Message()
	{
		return $this->message;
	}

	public function Set_Message($message)
	{
		$this->message = $message;
	}

	public function Set_Data_Sent($data_sent)
	{
		$this->data_sent = $data_sent;
	}

	public function Get_Data_Sent()
	{
		return $this->data_sent;
	}

	public function Set_Data_Received($data_received)
	{
		$this->data_received = $data_received;
	}

	public function Set_Next_Page($page)
	{
		$this->next_page = $page;
	}

	public function Is_Next_Page()
	{
		return $this->next_page;
	}

	public function Get_Data_Received()
	{
		return $this->data_received;
	}

	public function Is_Success()
	{
		return $this->success;
	}

	public function Is_Empty_Response()
	{
		return $this->empty_response;
	}

	public function Set_Success($is_success)
	{
		$this->success = (boolean)$is_success;
	}

	public function Set_Post_Time($post_time)
	{
		$this->post_time = $post_time;
	}

	public function Get_Post_Time()
	{
		return $this->post_time;
	}

	public function Set_Thank_You_Content($thank_you_content)
	{
		$this->thank_you_content = $thank_you_content;
	}

	public function Get_Thank_You_Content()
	{
		return $this->thank_you_content;
	}

	public function Set_Winner($winner)
	{
		// If we have a lender specified sales target, record it here as new_winner
		// Only specific implementations will use this. [LR]
		if ($_SESSION['blackbox']['winner'] != $winner)
		{
			$_SESSION['blackbox']['new_winner'] = $winner;
		}
	}

	public function Empty_Response()
	{
		$this->Set_Success(FALSE);
		$this->Set_Message("No response from vendor's server");
		$this->empty_response = TRUE;
	}

	public function Set_Vendor_Decision($decision = 'FAILED')
	{
		$this->decision = $decision;
	}

	public function Set_Vendor_Reason($reason = '')
	{
		$this->reason = $reason;
	}

	public function Get_Vendor_Decision()
	{
		return $this->decision;
	}

	public function Get_Vendor_Reason()
	{
		return $this->reason;
	}
}
