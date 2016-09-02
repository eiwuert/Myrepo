<?php
class VendorAPI_CFE_Actions_AddUnsignedDocument extends ECash_CFE_Base_BaseAction implements ECash_CFE_IExpression
{
	/**
	 * Returns a name short that can be used to identify this rule in the database
	 * @return string
	 */
	public function getType()
	{
	}

	/**
	 * Returns an array of required parameters with format name=>type
	 * @return array
	 */
	public function getParameters()
	{
	}

	public function evaluate(ECash_CFE_IContext $c)
	{
		return $this->execute($c);
	}

	/**
	 * Executes the action
	 *
	 * @param ECash_CFE_IContext $c
	 */
	public function execute(ECash_CFE_IContext $c)
	{
		$params = $this->evalParameters($c);

		$page_data = $c->getAttribute('page_data');

		$tokens = array();
		foreach ($params as $name=>$doc)
		{
			if ($doc instanceof VendorAPI_CFE_DocumentLink
				|| $doc instanceof VendorAPI_CFE_PageLink)
			{
				$tokens[$name] = $doc->asArray();
			}
		}

		$document = new ArrayObject();
		$document['template']     = $params['text'];
		$document['tokens'] = $tokens;

		if (!$page_data['unsigned_documents'] instanceof ArrayObject)
		{
			$page_data['unsigned_documents'] = new ArrayObject();
		}
		$page_data['unsigned_documents']->append($document);


		if ($doc instanceof VendorAPI_CFE_DocumentLink)
		{
			if (!$page_data['document_templates'] instanceof ArrayObject)
			{
				$page_data['document_templates'] = new ArrayObject();
			}
			$d = $doc->asArray();
			if (!in_array($d['template'], (array)$page_data['document_templates']))
			{
				$page_data['document_templates']->append($d['template']);
			}
		}
	}
}