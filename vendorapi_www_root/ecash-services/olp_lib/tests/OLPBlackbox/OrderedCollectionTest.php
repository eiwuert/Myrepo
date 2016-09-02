<?php

/**
 * PHPUnit test class for the OLPBlackbox_OrderedCollectionTest class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_OrderedCollectionTest extends OLPBlackbox_OrderedCollectionTestBase
{
	/**
	 * Returns a new collection
	 *
	 * @param string $name 
	 * @return OLPBlackbox_OrderedCollection
	 */
	protected function getCollection($name = 'test')
	{
		return new OLPBlackbox_OrderedCollection($name);
	}
}
