<?php

class ClassConstantsTest extends PHPUnit_Framework_TestCase
{
	const MaTch_CaSeInSenSiTiVe = 'case insensitive match';
	const MATCH_CASE_SENSITIVE = 'case sensitive match';
	
	/**
	 * @dataProvider keyStartsWithProvider
	 */
	public function testKeyStartsWith($class, $prefix, $expected_constants, $search_flags = NULL)
	{
		$constants = new ClassConstants($class);
		
		$diff = array_diff_assoc(
			$expected_constants, 
			$constants->keyStartsWith($prefix, $search_flags)
		);
		$this->assertEquals(
			array(), $diff, 'Difference between arrays was ' . print_r($diff, TRUE)
		);
	}
	
	public static function keyStartsWithProvider()
	{
		$uppercase_match = array('MATCH_CASE_SENSITIVE' => self::MATCH_CASE_SENSITIVE);
		$allcase_match = array(
			'MATCH_CASE_SENSITIVE' => self::MATCH_CASE_SENSITIVE, 
			'MaTch_CaSeInSenSiTiVe' => self::MaTch_CaSeInSenSiTiVe
		);
		return array(
			array(__CLASS__, 'MATCH', $uppercase_match),
			array(__CLASS__, 'MATCH', $allcase_match, ClassConstants::CASE_INSENSITIVE),
		);
	}
}

?>