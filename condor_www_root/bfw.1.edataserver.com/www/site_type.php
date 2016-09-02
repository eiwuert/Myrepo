<?php
/**
	@publicsection
	@public
	@brief
		Site_Type_Server
	
	PRPC service used to retreive site types from the database

	@version 
		Check CVS for version - Don Adriano
*/


	// Required files
	require_once ('config.php');
	require_once ('prpc/server.php');
	require_once ('prpc/client.php');
	require_once (BFW_CODE_DIR.'OLP_Applog_Singleton.php');
	include_once (BFW_CODE_DIR.'server.php');
	include_once (BFW_CODE_DIR.'site_type_manager.php');
	include_once (BFW_CODE_DIR.'setup_db.php');


	class Site_Type_Server extends Prpc_Server
	{
		
		public function __construct()
		{
			parent:: __construct();
		}
		
		public function Get_Site_Type($site_type, $mode = 'LIVE')
		{	
			// retreive application data
			if ( $site_type )
			{
				// get db conn
				$sql = Setup_DB::Get_Instance("site_types", $mode);
				
				// instantiate site_type manager
				$this->site_type_manager = new Site_Type_Manager($sql, $sql->db_info['db']);
				
				// get site_type_obj
				if ($site_type_obj = $this->site_type_manager->Get_Site_Type($site_type))
				{
					// return site type object
					return $site_type_obj;
				}
			}
			return FALSE;
		}
	}

	$cm_ivr = new Site_Type_Server();
	$cm_ivr->_Prpc_Strict = TRUE;
	$cm_ivr->Prpc_Process();
	