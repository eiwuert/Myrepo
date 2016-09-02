<?php
/**
 * Interface for rules that support pulling values 
 * from more than one data source
 * @author stephan soileau <stephan.soileau@sellingsource.com>
 *
 */
interface OLPBlackbox_Rule_IMultiDataSource 
{
	/**
	 * Set the data source to use
	 * @param string $source
	 * @return void
	 */
	public function setDataSource($source);
}