<?php

/** Tests VendorAPI Blackbox Winner.
 *
 * @author Raymond Lopez <raymond.lopez@sellingsource.com>
 */
class VendorAPI_Blackbox_WinnerTest extends PHPUnit_Framework_TestCase
{
	protected $winner;
	
	protected function setUp()
	{
		$target = new VendorAPI_Blackbox_Target();
		$history = new ECash_CustomerHistory();
		$this->winner = new VendorAPI_Blackbox_Winner($target, $history);
	}	
	
	protected function tearDown()
	{
		$this->winner = NULL;
	}
	
	public function testGetHistory()
	{
		$this->assertThat($this->winner->getCustomerHistory(), $this->isinstanceOf("ECash_CustomerHistory"));
	}
	
	public function testGetIsReact()
	{
		$this->assertEquals($this->winner->getIsReact(), FALSE);
	}

	public function testGetReactID()
	{
		$this->assertEquals($this->winner->getReactID(), FALSE);
	}	
	
	public function testGetDoNotLoan()
	{
		$this->assertEquals($this->winner->getDoNotLoan(), array());
	}	

	public function testGetDoNotLoanOverride()
	{
		$this->assertEquals($this->winner->getDoNotLoanOverride(), array());
	}
	
	public function testGetLoanActions()
	{
		$this->assertThat($this->winner->getLoanActions(), $this->isType('array'));
	}
		
}

?>