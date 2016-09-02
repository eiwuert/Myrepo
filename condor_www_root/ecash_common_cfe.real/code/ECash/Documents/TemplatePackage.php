<?php
/**
 * ECash_Documents_TemplatePackage
 * represents a Package without having to create it
 * 
 */
class ECash_Documents_TemplatePackage extends ECash_Documents_TemplateList
{
	protected $name;
	protected $package_body;
	public function __construct(array $templates, $name, $package_body)
	{
		$this->name = $name;
		$this->templates = $templates;
		$this->package_body = $package_body;
	}
	public function getName()
	{
		return $this->name;
	}
	public function getBodyName()
	{
		return $this->package_body;
	}
	public function create(ECash_Documents_IToken $tokens, $preview = false)
	{
		$docs = array();
		foreach($this->templates as $template)
		{
			if($doc = $template->create($tokens, $preview))
				$docs[] = $doc;
		}
		return new ECash_Documents_DocumentPackage($docs, $this->name, $this->package_body);
	}
	
	
}



?>