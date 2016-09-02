<?php

class VendorAPI_CFE_DocumentLink
{
	protected $title;
	protected $template;
	protected $anchor;

	public function __construct($title, $template, $anchor)
	{
		$this->title = $title;
		$this->template = $template;
		$this->anchor = $anchor;
	}

	public function asArray()
	{
		return array(
			'text' => $this->title,
			'template' => $this->template,
			'anchor' => $this->anchor,
		);
	}
}

?>