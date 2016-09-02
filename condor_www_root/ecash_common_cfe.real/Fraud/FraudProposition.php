<?php

require_once('libolution/Object.1.php');

class FraudProposition extends Object_1
{
	private $fraud_proposition_id;
	private $date_created;
	private $agent_name;
	private $question;
	private $description;
	private $quantify;
	private $file_name;
	private $file_size;
	private $file_type;

	public function __construct($fraud_proposition_id, $date_created, $agent_name, $question, $description, $quantify, $file_name, $file_size, $file_type)
	{
		$this->fraud_proposition_id = $fraud_proposition_id;
		$this->date_created = $date_created;
		$this->agent_name = $agent_name;
		$this->question = $question;
		$this->description = $description;
		$this->quantify = $quantify;
		$this->file_name = $file_name;
		$this->file_size = $file_size;
		$this->file_type = $file_type;
	}

	public function getPropositionID()
	{
		return($this->fraud_proposition_id);
	}

	public function getDateCreated()
	{
		return($this->date_created);
	}

	public function getAgentName()
	{
		return($this->agent_name);
	}

	public function getQuestion()
	{
		return($this->question);
	}

	public function getDescription()
	{
		return($this->description);
	}

	public function getQuantify()
	{
		return($this->quantify);
	}

	public function getFileName()
	{
		return($this->file_name);
	}

	public function getFileSize()
	{
		return($this->file_size);
	}

	public function getFileType()
	{
		return($this->file_type);
	}
}

?>