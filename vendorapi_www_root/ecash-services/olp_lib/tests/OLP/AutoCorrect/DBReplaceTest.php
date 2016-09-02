<?php

/**
 * Test cases for OLP_AutoCorrect_DBReplace
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class OLP_AutoCorrect_DBReplaceTest extends OLP_AutoCorrectTestBase
{
	/**
	 * Returns an instance of OLP_AutoCorrect.
	 *
	 * @param mixed $getAutoCorrectData
	 * @return OLP_AutoCorrect
	 */
	protected function getAutoCorrect($getAutoCorrectData)
	{
		$model = $this->getMock(
			'OLP_Models_CorrectWord',
			array_keys($getAutoCorrectData['mock']),
			array(
			),
			'',
			FALSE
		);
		
		foreach ($getAutoCorrectData['mock'] AS $method => $data)
		{
			$temp = $model->expects($data['expects'])
				->method($method);
			
			if (isset($data['with']))
			{
				$temp = $temp->with($data['with']);
			}
			
			if (isset($data['will']))
			{
				$temp = $temp->will($data['will']);
			}
		}
		
		$auto_correct = new OLP_AutoCorrect_DBReplace($model);
		
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
			// Simple case of one word replacement.
			array(
				'teh',
				'the',
				1,
				array(
					'mock' => array(
						'loadBy' => array(
							'expects' => $this->once(),
							'with' => array('original_word' => 'teh'),
							'will' => $this->returnValue(TRUE),
						),
						'__get' => array(
							'expects' => $this->once(),
							'with' => 'replacement_word',
							'will' => $this->returnValue('the'),
						),
					),
				),
			),
			
			// Simple case of no replacement.
			array(
				'the',
				'the',
				0,
				array(
					'mock' => array(
						'loadBy' => array(
							'expects' => $this->once(),
							'with' => array('original_word' => 'the'),
							'will' => $this->returnValue(FALSE),
						),
						'__get' => array(
							'expects' => $this->NEVER(),
						),
					),
				),
			),
			
			// Verify that it caches database hits.
			array(
				array(
					'teh',
					'teh',
				),
				array(
					'the',
					'the',
				),
				2,
				array(
					'mock' => array(
						'loadBy' => array(
							'expects' => $this->once(),
							'with' => array('original_word' => 'teh'),
							'will' => $this->returnValue(TRUE),
						),
						'__get' => array(
							'expects' => $this->once(),
							'with' => 'replacement_word',
							'will' => $this->returnValue('the'),
						),
					),
				),
			),
		);
	}
}

?>
