<?php

/** Marks a class as being able to have reference models attached to it.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
interface DB_Models_IReferenceable_1
{
	/** Attaches reference tables to this model and returns the new referenced model.
	 *
	 * @param DB_Models_ModelFactory_1 $factory
	 * @return DB_Models_Decorator_ReferencedWritableModel_1
	 */
	public function getReferencedModel(DB_Models_ModelFactory_1 $factory);
}

?>
