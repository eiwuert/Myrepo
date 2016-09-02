<?php
require_once("prpc/client.php");
/**
 * ECash_Documents_Condor
 * static prpc object for Condor
 * 
 */
class ECash_Documents_Condor
{
	private $prpc;
	private $template_list = array();
	
	public function getPrpc()
	{
		try 
		{
			if (!($this->prpc instanceof Prpc_Client)) 
			{
				$condor_server = ECash::getConfig()->CONDOR_SERVER;
				$this->prpc = new Prpc_Client($condor_server);
			}

			return $this->prpc;
			
		} 
		catch (Exception $e) 
		{
			if (preg_match("//",$e->getMessage())) 
			{
				throw new InvalidArgumentException(__METHOD__ . " Error: " . $condor_server . " is not a valid PRPC resource.");
			}
			
			throw $e;
			
		}

	}
	public function Set_Application_Id($archive_id, $application_id)
	{
		return $this->getPrpc()->Set_Application_Id($archive_id, $application_id);
	}
	public function Send($arch_id, $recp, $type, $sendarr, $sender)
	{
		return $this->getPrpc()->send($arch_id, $recp, $type, $sendarr, $sender);
	}
	public function Create_As_Attachment($body_name, $arch_ids, $type, $tokens, $bool, $appId, $trackId, $other)
	{
		return $this->getPrpc()->Create_As_Attachment($body_name, $arch_ids, $type, $tokens, $bool, $appId, $trackId, $other);
	}
	public function Create($name, $tokens, $preview, $appID, $TrackId, $other, $use_token_spans = FALSE)
	{
		return $this->getPrpc()->Create($name, $tokens, $preview, $appID, $TrackId, $other, $use_token_spans);
	}
	public function Get_Template_Tokens($name)
	{
		return $this->getPrpc()->Get_Template_Tokens($name);
	}
	public function Find_By_Archive_Id($ArchiveID)
	{
		return $this->getPrpc()->Find_By_Archive_Id($ArchiveID);
	}
	public function Get_Template_Names()
	{
		if(empty($this->template_list[ECash::getCompany()->company_id]))
		{
			$this->template_list[ECash::getCompany()->company_id] = $this->getPrpc()->Get_Template_Names();
		}
		return $this->template_list[ECash::getCompany()->company_id];
	}
	
	public function Sign($archive_id, $document, $ip_address = NULL)
	{
		return $this->getPrpc()->Sign($archive_id, $document, $ip_address);
	}
	
	public function Find_By_Application_Id($application_id)
	{
		return $this->getPrpc()->Find_By_Application_Id($application_id);
	}
}

?>
