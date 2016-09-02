<?php
	
	define('CL_PREFIX', 'cl_');

	class Cashline_View
	{	
		private $sql = null;
		private $db = null;
		public $request;
		private $data_array;
		
		public function __construct(Server $server, $request)
		{
			$this->server = $server;
			$this->db = ECash_Config::getMasterDbConnection();
			$this->request = $request;
			$this->data_array = array();
		}

		public function Get_Customer()
		{
			//print "<pre>" . print_r($this->request,1) . "</pre>";
			$query = "
				select
					c.*
				from ".CL_PREFIX."customer c
				join application a ON a.archive_cashline_id = c.cashline_id AND a.company_id = c.company_id
				where
					a.application_id = {$this->request->application_id} AND
					a.company_id = {$_SESSION['company_id']}
				";
						
			$rs = $this->db->query($query);
			
			if ($rs->rowCount() < 1)
			{
				$this->data_array['error'] = 1;
				$this->data_array['error_code'] = 100;
				$this->data_array['error_msg'] = "No Cashline data present.";
				ECash::getTransport()->Set_Data($this->data_array);
				return;
			}
			else if ($rs->rowCount() > 1)
			{
				$this->data_array['error'] = 1;
				$this->data_array['error_code'] = 101;
				$this->data_array['error_msg'] = "Duplicate data.";
				ECash::getTransport()->Set_Data($this->data_array);
				return;
			}
			
			$customer = $rs->fetch(PDO::FETCH_OBJ);
			
			
			$query = "
				select
					*, UNIX_TIMESTAMP(cl_transaction.transaction_date) control_date, 'trans' as type
				from
					".CL_PREFIX."transaction
				where customer_id = ".$customer->customer_id . " order by transaction_date asc, transaction_type";

			$rs = $this->db->query($query);
			
			$transactions = array();
			while ($transaction = $rs->fetch(PDO::FETCH_OBJ))
			{
				$transactions[$transaction->transaction_id] = $transaction;
			}
			
			$query = "
				select
					*,UNIX_TIMESTAMP(cl_notes.note_date) control_date, 'note' as type, 'note' as transaction_type
				from
					".CL_PREFIX."notes
				where customer_id = ".$customer->customer_id;
			
			$rs = $this->db->query($query);
			
			$notes = array();
			
			while ($note = $rs->fetch(PDO::FETCH_OBJ))
			{
				$notes[$note->note_id] = $note;
			}
			
			$sorted_all = array_merge($notes, $transactions);
			
			
			$sort_method = '';
			
			if ( $this->request->viewmode == 'transactions' )
			{
				$sort_method = 'cashline_usort_transactional_r';
			}
			else 
			{
				$sort_method = 'cashline_usort_transactional';
			}
			
			
			usort($sorted_all, $sort_method);
			usort($notes, 'cashline_usort');
			usort($transactions, $sort_method);
			
			$this->data_array['error'] = 0;
			$this->data_array['data'] = array();
			$this->data_array['data']['customer'] = $customer;
			$this->data_array['data']['customer']->application_id = $this->request->application_id;
			$this->data_array['data']['transactions'] = $transactions;
			$this->data_array['data']['all'] = $sorted_all;
			$this->data_array['data']['notes'] = $notes;
			
			$this->data_array['viewmode'] = $this->request->viewmode;
			
			
			ECash::getTransport()->Set_Data($this->data_array);
		}
		
		public function Get_Note()
		{
			if (!$this->request->note_id || !is_numeric($this->request->note_id))
			{
				$this->data_array['error'] = 1;
				$this->data_array['error_code'] = 200;
				$this->data_array['error_msg'] = "Note ID not provided.";
				ECash::getTransport()->Set_Data($this->data_array);
				return;
			}
			$query = "
				select
					*
				from
					".CL_PREFIX."notes
				where note_id=" . $this->request->note_id;
			
			$rs = $this->db->query($query);
			$note_data = $rs->fetch(PDO::FETCH_OBJ);
			
			$this->data_array['error'] = 0;
			$this->data_array['data'] = array();
			$this->data_array['data']['note_detail'] = $note_data;
			
			ECash::getTransport()->Set_Data($this->data_array);
		}
		
		public function Get_Data()
		{
			return $this->data_array;
		}
	}
	
	function cashline_usort($a,$b)
	{
		if ($a->control_date == $b->control_date)
		{
			return 0;
		}
   		return ($a->control_date > $b->control_date) ? -1 : 1;
	}
	
	function cashline_usort_transactional($a,$b)
	{
		if ($a->control_date == $b->control_date)
		{
			if ($a->transaction_type == $b->transaction_type)
			{
				return 0;
			}
			return ($a->transaction_type < $b->transaction_type) ? -1 : 1;
		}
   		return ($a->control_date > $b->control_date) ? -1 : 1;
	}
	
	function cashline_usort_transactional_r($a,$b)
	{
		if ($a->control_date == $b->control_date)
		{
			if ($a->transaction_type == 'advance') return -1;
			if ($b->transaction_type == 'advance') return 1;
			
			if ($a->transaction_type == $b->transaction_type)
			{
				return 0;
			}
			return ($a->transaction_type < $b->transaction_type) ? -1 : 1;
		}
   		return ($a->control_date < $b->control_date) ? -1 : 1;
	}	
?>
