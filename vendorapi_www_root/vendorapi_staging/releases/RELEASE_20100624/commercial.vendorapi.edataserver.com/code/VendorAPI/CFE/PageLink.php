<?php

class VendorAPI_CFE_PageLink
{
	protected $title;
	protected $page;

	public function __construct($title, $page)
	{
		$this->title = $title;
		$this->page = $page;
	}

	public function asArray()
	{
		return array(
			'text' => $this->title,
			'page' => $this->page,
		);
	}
}

?>