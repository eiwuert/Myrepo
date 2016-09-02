<?php

/**
 *
 * @author stephan.soileau <stephan.soileau@sellingsource.com>
 *
 */
interface VendorAPI_IDocument
{
	/**
	 * Creates a document preview
	 *
	 * @param string $template
	 * @param array $tokens
	 * @param VendorAPI_CallContext $context
	 * @return string HTML content
	 */
	public function previewDocument($template, array $tokens, VendorAPI_CallContext $context);
	
	/**
	 * Creates a document preview
	 *
	 * @param Integer $application_id
	 * @param string $template
	 * @param array $tokens
	 * @param VendorAPI_CallContext $context
	 * @return string HTML content
	 */
	public function documentMatchesHash(
		VendorAPI_IApplication $application,
		$template, 
		array $tokens,
		VendorAPI_CallContext $context);
	
	/**
	 * Looks up the document_id in condor.
	 * @param Integer $archive_id
	 * @return VendorAPI_Document
	 */
	public function getByArchiveId($archive_id);

	/**
	 * Creates a new document and returns the archive id
	 * @param String $template
	 * @param VendorAPI_IApplication $application
	 * @param VendorAPI_IApplication $token_provider
	 * @param VendorAPI_CallContext $context
	 * @return VendorAPI_Document
	 */
	public function create(
		$template,
		VendorAPI_IApplication $application,
		VendorAPI_ITokenProvider $token_provider,
		VendorAPI_CallContext $context
	);
	
	/**
	 * Sends a document
	 *
	 * @param $archive id
	 * @param $recp
	 * @return condor result
	 */
	public function sendDocument($archive_id, $recp);

	/**
	 * Sign a document
	 * @param VendorAPI_IApplication $application
	 * @param VendorAPI_Document $document
	 * @param VendorAPI_CallContext $context
	 * @return boolean
	 */
	public function signDocument(
		VendorAPI_IApplication $application,
		VendorAPI_DocumentData $document,
		VendorAPI_CallContext $context
	);

	/**
	 * Find the latest version of a document
	 * for a particular application
	 * @param Integer$application_id
	 * @param String $template
	 * @return Integer
	 */
	public function findDocument($application_id, $template);
}