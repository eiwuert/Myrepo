<?php

/**
 * Test cases for OLP_AutoCorrect_Email
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_AutoCorrect_EmailTest extends OLP_AutoCorrectTestBase
{
	/**
	 * Returns an instance of OLP_AutoCorrect.
	 *
	 * @param mixed $getAutoCorrectData
	 * @return OLP_AutoCorrect
	 */
	protected function getAutoCorrect($getAutoCorrectData)
	{
		$tld_replacement_data = array(
			'coom' => 'com',
		);
		
		$domain_replacement_data = array(
			'yahooo' => 'yahoo',
		);
		
		$tld_auto_correct = new OLP_AutoCorrect_Replace($tld_replacement_data);
		$domain_auto_correct = new OLP_AutoCorrect_Replace($domain_replacement_data);
		
		$auto_correct = new OLP_AutoCorrect_Email($tld_auto_correct, $domain_auto_correct);
		
		return $auto_correct;
	}
	
	/**
	 * Data provider for testAutoCorrect().
	 *
	 * @return array
	 */
	public function dataProviderAutoCorrect()
	{
		return array(
			array(
				'user@aol.coom',
				'user@aol.com',
				1,
			),
			
			array(
				'icannottype@yahooo.com',
				'icannottype@yahoo.com',
				1,
			),
			
			array(
				'this@is.ok',
				'this@is.ok',
				0,
			),
			
			array(
				'ireallycannottype@yahooo.coom',
				'ireallycannottype@yahoo.com',
				1,
			),
			
			array(
				'ordertest@coom.yahooo',
				'ordertest@coom.yahooo',
				0,
			),
		);
	}
}

?>
