<?php

/**
 * Provides the appropriate tokens to the vendor API for generating loan documents
 *
 * @author Andrew Minerd <andrew.minerd@sellingsource.com>
 */
interface VendorAPI_ITokenProvider
{
	/**
	 * Builds tokens for generating loan documents
	 *
 	 * @param VendorAPI_IApplication $application
 	 * @param bool $is_preview Indicates that documents are being previewed
 	 * @param int $loan_amount Preview loan amount
	 * @return array
	 */
	public function getTokens(VendorAPI_IApplication $application, $is_preview, $loan_amount = NULL);
}
