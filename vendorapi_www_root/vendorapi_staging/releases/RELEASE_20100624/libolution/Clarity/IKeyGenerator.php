<?php
/**
 * Interface for designing classes that will generate unique keys.
 * 
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
interface TSS_IKeyGenerator
{
	/**
	 * Generates and returns the unique key.
	 * 
	 * @return string 
	 */
	public function generate();
}