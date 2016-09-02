<?php
/**
 * Test case for the TSS_DataX_Call class.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class TSS_DataX_CallTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Tests that execute() returns a TSS_DataX_Result object.
	 *
	 * @return void
	 */
	public function testExecute()
	{
		$request = $this->getMock('TSS_DataX_IRequest');
		$response = $this->getMock('TSS_DataX_IResponse');
		
		$call = $this->getMock('TSS_DataX_Call', array('makeRequest'), array('test', $request, $response));
		
		$this->assertType('TSS_DataX_Result', $call->execute(array()));
	}
}
