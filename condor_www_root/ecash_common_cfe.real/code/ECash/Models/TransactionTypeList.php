<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_TransactionTypeList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_TransactionType';
		}
		public function createInstance(array $db_row, array $override_dbs = NULL)
		{
			$model = new ECash_Models_TransactionType();
			$model->setOverrideDatabases($override_dbs);
			$model->fromDbRow($db_row);

			return $model;
		}

	}

?>