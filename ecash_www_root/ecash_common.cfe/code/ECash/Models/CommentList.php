<?php

	/**
	 * @package Ecash.Models
	 */
	class ECash_Models_CommentList extends ECash_Models_IterativeModel
	{
		public function getClassName()
		{
			return 'ECash_Models_Comment';
		}

		public function getTableName()
		{
			return 'comment';
		}
	}
?>
