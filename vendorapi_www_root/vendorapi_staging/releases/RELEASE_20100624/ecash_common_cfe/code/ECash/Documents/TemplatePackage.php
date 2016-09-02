<?php
/**
 * ECash_Documents_TemplatePackage
 * represents a Package without having to create it
 * 
 */
class ECash_Documents_TemplatePackage extends ECash_Documents_TemplateList
{
	protected $name;
	protected $name_short;
	protected $package_body;
	protected $package_id;
	protected $package_body_model;
	protected $prpc;
	/**
	 * ECash_Documents_TemplatePackage
	 * 
	 *@param array $templates an array of ECash_Documents_Template that represent all the documents in a package
	 *@param string $name name of package
	 *@param int    $package_id id of package
	 *@param ECash_Models_Reference_Model $package_body_model document list model of package body 
	 *
	 */
	public function __construct(array $templates, $name, $name_short, $package_id, ECash_Models_ObservableWritableModel $package_body_model)
	{
		$this->name = $name;
		$this->name_short = $name_short;
		$this->package_id = $package_id;
		$this->templates = $templates;
		$this->package_body = $package_body_model->name;
		$this->package_body_model = $package_body_model;
		$this->prpc = null;
	}
	/**
	 * This sets the prpc used in this class
	 * 
	 * @param object
	 */
	public function setPrpc($prpc)
	{
		$this->prpc = $prpc;
	}
	/**
	 * getModel
	 * 
	 * @return ECash_Documents_TemplatePackage returns documentlist model that represents the package body
	 */
	public function getModel()
	{
		return $this->package_body_model;
	}
	/**
	*getName
	* 
	* @return string 
	* 
	*/
	public function getName()
	{
		return $this->name;
	}
	/**
	*getNameShort
	* 
	* @return string 
	* 
	*/
	public function getNameShort()
	{
		return $this->name_short;
	}
	/**
	*getId
	* 
	* @return int 
	* 
	*/
	public function getId()
	{
		return $this->package_id;
	}
	/**
	* getBodyName
	* 
	* @return string 
	* 
	*/
	public function getBodyName()
	{
		return $this->package_body;
	}
	/**
	 * create
	 * 
	 * @param ECash_Documents_IToken $tokens
	 * @param bool $preview 
	 * 
	 * @return ECash_Documents_DocumentPackage
	 */
	public function create(ECash_Documents_IToken $tokens, $preview = false)
	{
		$docs = array();
		foreach($this->templates as $template)
		{
			if(!empty($this->prpc))
				$template->setPrpc($this->prpc);
			if($doc = $template->create($tokens, $preview))
				$docs[] = $doc;
		}
		return new ECash_Documents_DocumentPackage($docs, $this->name, $this->name_short, $this->package_body);
	}
	
	
}



?>
