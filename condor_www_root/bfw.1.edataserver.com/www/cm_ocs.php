<?php
	require_once('config.php');
	require_once('mysqli.1.php');
	require_once(BFW_CODE_DIR.'OLP_Applog_Singleton.php');
	
	require_once(BFW_CODE_DIR . 'server.php');
	require_once(BFW_MODULE_DIR . 'ocs/ocs.php');

	require_once('prpc/server.php');
	require_once('prpc/client.php');


	class CM_OCS extends Prpc_Server
	{
		function CM_OCS()
		{
			parent::__construct();
		}
	
		function Get_Reservation($rnum, $zip = NULL, $caller)
		{
			$ocs = new OCS($caller, BFW_MODE);

			return $ocs->Get_Reservation($rnum, $zip);
		}
		
		function Get_Fail_Promo($rnum, $caller)
		{
			$ocs = new OCS($caller, BFW_MODE);

			return $ocs->Get_Fail_Promo($rnum);
		}

		function getPromoByReservation($reservation_id)
		{
			$ocs = new OCS('', BFW_MODE);
			return $ocs->getPromoByReservation($reservation_id);
		}
	}


	$cm_ocs = new CM_OCS();
	$cm_ocs->_Prpc_Strict = TRUE;
	$cm_ocs->Prpc_Process();

?>
