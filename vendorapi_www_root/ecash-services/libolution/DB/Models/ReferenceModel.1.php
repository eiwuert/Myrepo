<?php

	/**
	 * Represents a row in a reference table
	 *
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 * @deprecated Implement DB_Models_IReferenceModel_1 instead It is more flexible
	 * @see DB_Models_IReferenceModel_1
	 */
	abstract class DB_Models_ReferenceModel_1 extends DB_Models_WritableModel_1 implements DB_Models_IReferenceModel_1 
	{

		/**
		 * Returns the ID value
		 * @return int
		 */
		public function getID()
		{
			return $this->{$this->getColumnID()};
		}

		/**
		 * Returns the name value
		 * @return string
		 */
		public function getName()
		{
			return $this->{$this->getColumnName()};
		}
	}

?>
