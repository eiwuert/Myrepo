<?php

/**
 * Test cases for OLP_AutoCorrect
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
abstract class OLP_AutoCorrectTestBase extends PHPUnit_Framework_TestCase
{
	/**
	 * Returns an instance of OLP_AutoCorrect.
	 *
	 * @param mixed $getAutoCorrectData
	 * @return OLP_AutoCorrect
	 */
	abstract protected function getAutoCorrect($getAutoCorrectData);
	
	/**
	 * Data provider for testAutoCorrect().
	 *
	 * @return array
	 */
	abstract public function dataProviderAutoCorrect();
	
	/**
	 * Unit test for the whole of autoCorrect().
	 *
	 * @dataProvider dataProviderAutoCorrect
	 *
	 * @param string|array $word
	 * @param string|array $expected_word
	 * @param int $expected_corrected
	 * @param mixed $getAutoCorrectData
	 * @return void
	 */
	public function testAutoCorrect($word, $expected_word, $expected_corrected, $getAutoCorrectData = NULL)
	{
		$auto_correct = $this->getAutoCorrect($getAutoCorrectData);
		
		$replacement_word = $auto_correct->autoCorrect($word);
		$corrected = $auto_correct->getAutoCorrectedCount();
		
		$this->assertEquals($expected_word, $replacement_word);
		$this->assertEquals($expected_corrected, $corrected);
	}
}

?>
