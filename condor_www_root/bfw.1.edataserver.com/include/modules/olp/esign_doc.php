<?PHP
require_once(BFW_CODE_DIR.'server.php');
require_once('mysql.5.php');

class eSignature
{
	private $doc_id;
	private $property_short;
	private $mode;
	private $db;
	private $condor_data;
	private $condor_api;

	function __construct($doc_id,$mode,$property_short)
	{
		$this->doc_id = $doc_id;
		$this->property_short = $property_short;
		$this->mode = $mode;

		/*
			Condor templates will be the same as the eCash document names, so there was
			no reason to have a static map.
		*/

		$this->db = Array();
	}
	public function View_Doc()
	{
		$prpc_server = Server::Get_Server($this->mode, 'CONDOR', $this->property_short);
		$this->condor_api = new prpc_client("prpc://".$prpc_server."/condor_api.php");
		$doc = $this->condor_api->Find_By_Archive_Id($this->doc_id);
		return $doc;
	}
	public function Get_App_By_Doc_Id()
	{
		if(($app_id = $this->Get_Condor_Data()) === FALSE)
		{
			return FALSE;
		}
		$app_id = $app_id['application_id'];
		try
		{
			$app = $this->Get_Application_From_OLP($app_id);
			if($app === false)
			{
				$app = $this->Get_Application_From_LDB($app_id);
			}
		}
		catch (MySQL_Exception $e)
		{
			return false;
		}
		return $app;
	}
	private function Get_Application_From_LDB($app_id)
	{
		$this->Setup_Db('ecash');
		$query = "SELECT name_last as last_name,name_first as first_name FROM application
			WHERE application_id=$app_id limit 1";
		$res = $this->db['ecash']->Query($query);
		if($res->Row_Count() < 1)
			return FALSE;
		$row = $res->Fetch_Array_Row(MYSQL_ASSOC);
		return $row;

	}
	private function Get_Application_From_OLP($app_id)
	{
		$this->Setup_Db('olp');
		$query = "SELECT first_name,last_name
			 FROM personal_encrypted WHERE personal_encrypted.application_id='$app_id' LIMIT 1";
		$res = $this->db['olp']->Query($query);
		if($res->Row_Count() < 1)
			return FALSE;
		$row = $res->Fetch_Array_Row(MYSQL_ASSOC);
		
		return $row;
	}
	public function Sign_Doc()
	{
		//Okay, first we have to update ECash

		$this->Setup_Db('ecash');
		//Company Id based on property Id
		$query = "SELECT company_id FROM `company` where name_short='".
				strtolower($this->property_short).'\'';
		$res = $this->db['ecash']->Query($query);
		$row = $res->Fetch_Object_Row();
		$company_id = $row->company_id;
		if(empty($company_id))
		{
			return Array(FALSE,'That company does not exist.',1);
		}

		//Document_list_id based on the the condor subject
		if(!is_array($this->condor_data))
		{
			$this->Get_Condor_Data();
		}

		$query = "
			SELECT
				document_list_id
			FROM
				document_list
			WHERE
				company_id = $company_id
				AND name = '{$this->condor_data['name']}'";

		$res = $this->db['ecash']->Query($query);
		$row = $res->Fetch_Object_Row();
		$doc_list_id = $row->document_list_id;

		if(empty($doc_list_id))
		{
			return Array(FALSE,'That document does not exist.',2);
		}

		$app_id = $this->condor_data['application_id'];
		if(empty($app_id))
		{
			return Array(FALSE,'That application does not exist',3);
		}

		//Now Insert into the document table in ldb
		$query = "
			INSERT INTO document
			SET
				date_modified = NOW(),
				date_created = NOW(),
				company_id = $company_id,
				application_id = $app_id,
				document_list_id = $doc_list_id,
				document_event_type = 'received',
				signature_status = 'esig',
				document_method = 'olp',
				transport_method = 'web',
				archive_id = '$this->doc_id',
				agent_id = (
					SELECT agent_id
					FROM agent
					WHERE login = 'olp' AND active_status = 'ACTIVE')";

		$this->db['ecash']->Query($query);
		if($this->db['ecash']->Insert_Id() < 1)
		{
			return Array(FALSE,'Error updating ECash.',4);
		}
		$doc = $this->View_Doc();
		$this->condor_api->Sign($this->doc_id,$doc->data);
		return Array(TRUE,'There is no error.',5);

	}
	private function Get_Condor_Data()
	{
		$this->Setup_Db('condor');
		$query = "
			SELECT
				d.application_id,
				t.name
			FROM
				document d
				JOIN template t ON d.template_id = t.template_id
			WHERE
				d.document_id = $this->doc_id";

		try
		{
			$res = $this->db['condor']->Query($query);
		}
		catch (MySQL_Exception $e)
		{
			return FALSE;
		}
		$row = $res->Fetch_Array_Row(MYSQL_ASSOC);
		if(!$row || empty($row['application_id']) || empty($row['name']) )
		{
			return FALSE;
		}
		$this->condor_data = $row;
		return $row;
	}
	private function Setup_Db($server)
	{
		$dbna = NULL;
		switch(strtolower($server))
		{
			case 'olp':
				$dbna = 'blackbox';
				break;
			case 'condor':
				$dbna = 'condor';
				break;
			case 'ecash':
				$dbna = 'mysql';
				break;
		}
		if($dbna != NULL && !($this->db[$server] instanceof MySQL_5))
		{
			$dbi = $this->Get_Db_Info($dbna);
			$this->db[$server]= new MySQL_5($dbi['host'],$dbi['user'],
				$dbi['password'],$dbi['db'],$dbi['port']);
		}
	}
	private function Get_Db_Info($server)
	{
		if(strtolower($server) == 'condor')
		{
			return $this->Get_Condor_Db();
		}
		$dbi = Server::Get_Server($this->mode, $server, $this->property_short);
		list($host,$port) = split(':',$dbi['host']);
		if(empty($port) && empty($dbi['port']))
		{
			$port = 3306;
			$dbi['host'] = $host;
			$dbi['port'] = $port;
		}
		return $dbi;
	}
	private function Get_Condor_Db()
	{
		switch($this->mode)
		{
			case 'DEV': case 'LOCAL':
			case 'RC':
				return Array(
					'host'=>'db101.clkonline.com',
					'user'=>'condor',
					'password'=>'password',
					'port'=>3313,
					'db'=>'condor');
				break;
			case 'LIVE':
				return Array(
					'host'=>'writer.condor2.ept.tss',
					'user'=>'condor',
					'password'=>'password',
					'port'=>3308,
					'db'=>'condor');
				break;
		}
	}
};
