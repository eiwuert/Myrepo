<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_DocumentListPackageList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_DocumentListPackage';
		}

		public function getTableName()
		{
			return 'document_list_package';
		}
	}
?>
