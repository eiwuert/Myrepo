<?php

	/**
	 * Indicates that the implementor can add itself to an agenda
	 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
	 */
	interface ECash_CFE_ISchedulable
	{
		public function addToAgenda(ECash_CFE_Agenda $a);
	}

?>