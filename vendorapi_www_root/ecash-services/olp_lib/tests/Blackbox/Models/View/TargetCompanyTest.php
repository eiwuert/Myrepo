<?php
/**
 * Test the TargetData view.
 *
 * @group blackbox_models
 * @author David Watkins <david.watkins@sellingsource.com>
 */
class Blackbox_Models_View_TargetCompanyTest extends PHPUnit_Extensions_Database_TestCase
{
	/**
	 * Test the method of Blackbox_Models_View_TargetData which gets targetdata
	 * based on property short.
	 * @return void
	 */
	public function testGetTargets()
	{
		$view = new Blackbox_Models_View_TargetCompany(
			TEST_DB_CONNECTOR(TEST_BLACKBOX)
		);
		
		$views = $view->getTargets();
		
		foreach ($views as $view)
		{
			$this->assertEquals($view->property_short, 'ex1');
			$this->assertEquals($view->company_name, 'example inc');
			$this->assertEquals($view->type, 'CAMPAIGN');
		}
	}
	/**
	 * 
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection 
	 * @see PHPUnit_Extensions_Database_TestCase::getConnection()
	 */
	protected function getConnection ()
	{
		return $this->createDefaultDBConnection(
			TEST_DB_PDO(TEST_BLACKBOX), 
			TEST_GET_DB_INFO(TEST_BLACKBOX)->name
		);
	}
	/**
	 * 
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet 
	 * @see PHPUnit_Extensions_Database_TestCase::getDataSet()
	 */
	protected function getDataSet ()
	{
		return $this->createXMLDataSet(dirname(__FILE__).'/_fixtures/TargetCompany.xml');
	}
}
?>
