<?php

/**
 * Test the functionality of a Blackbox Target Model.
 *
 * @author Dan Ostrowski <dan.ostrowski@sellingsource.com>
 * @package Blackbox
 * @group requires_blackbox
 */
class Blackbox_Models_TargetTest extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection 
	 * @see PHPUnit_Extensions_Database_TestCase::getConnection()
	 */
	protected function getConnection() 
	{
		return $this->createDefaultDBConnection(
			TEST_DB_PDO(TEST_BLACKBOX), 
			TEST_GET_DB_INFO(TEST_BLACKBOX)->name
		);
	}
	
	/**
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet 
	 * @see PHPUnit_Extensions_Database_TestCase::getDataSet()
	 */
	protected function getDataSet()
	{
		return $this->createXMLDataSet(
			dirname(__FILE__) . '/_fixtures/TargetTest.xml'
		);
	}

	/**
	 * Tests the quoting function of the Queryset object which is passed to 
	 * the where object.
	 *
	 * @return void
	 */
	public function testQuoteFunction()
	{
		$unquoted = "who hath quell'ed the quote?";
		$quoted = '\'who hath quell\\\'ed the quote?\'';
		
		$target = new Blackbox_Models_Target(TEST_DB_CONNECTOR(TEST_BLACKBOX));
		
		$this->assertEquals(
			$quoted, 
			$target->quote($unquoted), 
			sprintf('quoted result [[%s]] does not match expected [[%s]]', $quoted, $target->quote($unquoted))
		);
	}
	
	/**
	 * Provide data for Blackbox_Models_Target producing rule querysets.
	 *
	 * @return void
	 */
	public function testRuleQuerysetProduction()
	{
		$target = new Blackbox_Models_Target(TEST_DB_CONNECTOR(TEST_BLACKBOX));
		
		$this->assertTrue(
			$target->getAndRuleQueryset() instanceof Blackbox_Models_TargetRuleQueryset
		);
		$this->assertTrue(
			$target->getOrRuleQueryset() instanceof Blackbox_Models_TargetRuleQueryset
		);
	}
}

?>
