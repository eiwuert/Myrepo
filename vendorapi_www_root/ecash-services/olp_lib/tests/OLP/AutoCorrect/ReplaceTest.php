<?php

/**
 * Test cases for OLP_AutoCorrect_Replace
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_AutoCorrect_ReplaceTest extends OLP_AutoCorrectTestBase
{
	/**
	 * Returns an instance of OLP_AutoCorrect.
	 *
	 * @param mixed $getAutoCorrectData
	 * @return OLP_AutoCorrect
	 */
	protected function getAutoCorrect($getAutoCorrectData)
	{
		$replacement_data = array(
			'teh' => 'the',
			'i' => 'I',
			"i'll" => "I will",
		);
		
		$auto_correct = new OLP_AutoCorrect_Replace($replacement_data);
		
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
				'',
				'',
				0,
			),
			
			array(
				'Nothing',
				'Nothing',
				0,
			),
			
			array(
				'teh',
				'the',
				1,
			),
			
			array(
				array(
					'pass',
					'i',
				),
				array(
					'pass',
					'I',
				),
				1,
			),
			
			array(
				array(
					'pass',
					'pass',
				),
				array(
					'pass',
					'pass',
				),
				0,
			),
			
			array(
				array(
					'teh',
					'i',
					"i'll",
				),
				array(
					'the',
					'I',
					'I will',
				),
				3,
			),
			
			array(
				'I',
				'I',
				0,
			),
			
			array(
				"I'll",
				"I'll",
				0,
			),
		);
	}
}

?>
