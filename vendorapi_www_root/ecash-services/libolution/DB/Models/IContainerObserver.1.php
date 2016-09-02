<?php
/**
 * Observer interface for DB_Models_Container_1 
 *
 * @author Adam Englander <adam.englander@sellingsource.com>
 */
interface DB_Models_IContainerObserver_1
{
	/**
	 * Begin the observation process
	 *
	 * @param DB_Models_IContainer_1 $observed
	 * @return void
	 */
	public function update(DB_Models_IContainer_1 $observed);	
}
?>