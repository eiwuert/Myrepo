<?php

	class ECash_Models_TokensList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_Tokens';
		}

		public function getTableName()
		{
			return 'tokens';
		}

		public function loadByCompany($companyId, $getLowerTier = true)
		{
			$where_args = array('company_id' => $companyId);
			
			if($getLowerTier)
			{
				$extra_sql = ' and loan_type_id = 0 or (company_id = 0 and loan_type_id = 0)';
			}
			else
			{
				$extra_sql = ' and loan_type_id = 0';
			}
			$query = "SELECT * FROM tokens " . self::buildWhere($where_args) . $extra_sql;
			$this->statement = DB_Util_1::queryPrepared(
					$this->getDatabaseInstance(),
					$query,
					$where_args
			);
		}
		public function loadByLoanType($companyId, $loanTypeId, $getLowerTier = true)
		{
			$where_args = array('loan_type_id' => $loanTypeId);
			if($getLowerTier)
			{
				$extra_sql = ' or ((company_id = 0 or company_id =' . $companyId . ') and loan_type_id = 0)';
			}
			else
			{
				$extra_sql = ' and company_id = ' . $companyId;
			}			
			
			$query = "SELECT * FROM tokens " . self::buildWhere($where_args) . $extra_sql;
			$this->statement = DB_Util_1::queryPrepared(
					$this->getDatabaseInstance(),
					$query,
					$where_args
			);
		}
	}

?>
