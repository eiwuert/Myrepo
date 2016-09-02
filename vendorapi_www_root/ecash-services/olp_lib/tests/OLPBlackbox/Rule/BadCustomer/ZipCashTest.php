<?php
/**
 * Test case for ZipCash bad customer rule.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 */
class OLPBlackbox_Rule_BadCustomer_ZipCashTest extends OLPBlackbox_Rule_BadCustomerTestBase
{
	/**
	 * Tests that we get back the correct cache key.
	 *
	 * @return void
	 */
	public function testGetCacheKey()
	{
		$data = array('social_security_number' => '555668888', 'email_primary' => 'john.doe@example.com');
		
		$rule = new OLPBlackbox_Rule_BadCustomer_ZipCash();
		
		$this->assertEquals(
			$this->getCacheKey($data['email_primary'], $data['social_security_number']),
			$rule->getCacheKey($data)
		);
	}
	
	/**
	 * Returns the name part of the email and the last 4 of the SSN, concatenated with a pipe.
	 *
	 * @param string $email
	 * @param string $ssn
	 * @return string
	 */
	protected function getCacheKey($email, $ssn)
	{
		return 'RULE:BC:ZC:' . substr($email, 0, strpos($email, '@')) . '|' . substr($ssn, -4);
	}
}
