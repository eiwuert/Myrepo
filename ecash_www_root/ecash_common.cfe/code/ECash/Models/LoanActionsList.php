<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_LoanActionsList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_LoanActions';
		}
		
		public function getTableName()
		{
			return 'loan_actions';
		}

		/**
		 * Wrapper for getBy which allows you to grab all the active loan_actions of a certain type.
		 *
		 * @param string $type - the type of loan actions to list.  If none is specified, all are grabbed.
		 * @param array $override_dbs
		 * @return ECash_Models_LoanActionsList
		 */
		public function loadActiveByType($type = NULL, array $override_dbs = NULL)
		{
			$where_args = array('status'	=> 'ACTIVE');
			if($type)
			{
				$where_args['type']	= $type;
			}
			$this->loadBy($where_args, $override_dbs);
		}
	}
?>