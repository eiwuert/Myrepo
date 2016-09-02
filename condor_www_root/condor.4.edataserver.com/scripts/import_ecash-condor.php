<?PHP

require_once('mysqli.1.php');
require_once('/virtualhosts/condor.4.edataserver.com/lib/condor.class.php');

define('ECASH_DB_HOST','reader.ecasholp.ept.tss');
define('ECASH_DB_USER','ecash');
define('ECASH_DB_PASS','ugd2vRjv');
define('ECASH_DB_NAME','ldb');
define('ECASH_DB_PORT',3306);

define('CONDOR_DB_HOST','localhost');
define('CONDOR_DB_USER','root');
define('CONDOR_DB_PASS','');
define('CONDOR_DB_NAME','condor_2_import');
define('CONDOR_DB_PORT',3306);

define('DAYS_TO_IMPORT',90);
define('BIN_TIFF2PDF', 'tiff2pdf');
define('COPIA_MOUNT','mnt/copia/');


$usage = "Usage: php ".basename(__FILE__)." document_type ecash_company condor_agent
	document_type - Type of Document to Import
		-Possible Values: sent,received,both,files
	ecash_company - Company 'name_short' in the ecash database
	condor_agent - The agent login for the condor api'
";
$method = strtolower($argv[1]);
if($method == 'sent' || $method == 'received' || $method == 'both' || $method == 'files')
{
	if(!empty($argv[2]))
	{
		$ecash_company = mysql_escape_string($argv[2]);
	}
	else
	{
		echo($usage);
		exit;
	}
	if(!empty($argv[3]))
	{
		$condor_agent = mysql_escape_string($argv[3]);
	}
	else
	{
		echo($usage);
		exit;
	}
	$import_obj = new Import_Documents($method,$ecash_company,$condor_agent);
	$import_obj->Import_Documents();
}
else
{
	echo($usage);
	exit;
}
	

class Import_Documents
{
	protected $ecash_db;
	protected $condor_db;
	protected $page_id_map;
	protected $ecash_company_id;
	protected $condor_user_id;
	protected $condor_company_id;
	protected $statusText;
	protected $total_rows;
	protected $minimum_id;
	protected $after_this_date;
	protected $get_data_query;
	protected $method;
	protected $files;
	
	const STATPRO_KEY ='clk';
	const STATPRO_PASS = 'dfbb7d578d6ca1c136304c845';
	const STATPRO_MODE = 'test';

    const CA_PAGE_ID = 39413;
    const D1_PAGE_ID = 39121;
    const UCL_PAGE_ID = 39417;
    const UFC_PAGE_ID = 17212;
    const PCL_PAGE_ID = 1807;

	function __construct($method,$ecash_company_short,$condor_agent_login)
	{	
		$this->ecash_db = NULL;
		$this->condor_db = NULL;
		$this->ecash_company_id = -1;
		$this->condor_user_id = -1;
		$this->statusText = NULL;
		$this->total_rows = -1;
		$this->minimum_id = 0;
		$this->after_this_time = NULL;
		$this->method = $method;
		$this->condor_company_id = -1;
		$this->files = false;
		switch($this->method)
		{
			case 'sent':
				echo("Setting up to import 'Sent' documents.\n");
				$this->get_data_query = "SELECT DISTINCT
					document.document_event_type,
					document.company_id,document.application_id,
					document.document_id_ext,campaign_info.promo_id,
					campaign_info.promo_sub_code,company.name_short,
					document.date_created,document.document_id,
					application.phone_fax,application.email,
					document_list.name FROM document
					JOIN campaign_info ON campaign_info.campaign_info_id = (
						SELECT MAX(campaign_info_id) FROM campaign_info WHERE
						campaign_info.application_id = document.application_id)
					JOIN company ON document.company_id = company.company_id
					JOIN application ON application.application_id = 
				   		document.application_id
					JOIN document_list ON 
					document.document_list_id = document_list.document_list_id
					WHERE document.document_id > %%min_id%% AND
					document.document_event_type='sent' AND 
					document.document_method IN ('copia_fax','copia_email')
					AND document.company_id = %%ecash_company_id%%
					LIMIT 0,5000;";
				break;
			case 'files':
				echo("Setting up tar command to move ecash files to condor.\n");
				$this->method='received';
				$this->files = Array();;
			case 'received':
				if(!is_array($this->files))
					echo("Setting up to import 'Received' documents.\n");
				$this->get_data_query = "SELECT DISTINCT document.company_id,
					document.document_event_type,
					document.application_id,document.document_id_ext,
					campaign_info.promo_id,campaign_info.promo_sub_code,
					company.name_short,document.date_created,
					document.document_id,document_list.name FROM document
					JOIN campaign_info ON campaign_info.campaign_info_id = (
						SELECT MAX(campaign_info_id) FROM campaign_info WHERE
						campaign_info.application_id = document.application_id)
					JOIN company ON document.company_id = company.company_id
					JOIN document_list ON 
					document.document_list_id = document_list.document_list_id
					WHERE document.document_id > %%min_id%% AND
					document.document_event_type = 'received' AND
					document.document_method IN ('copia_fax','copia_email')
					AND document.company_id = %%ecash_company_id%%
					LIMIT 0,5000";
				break;
			case 'both':
				echo("Setting up to import 'Received' and 'Sent' documents.\n");
				                $this->get_data_query = "SELECT DISTINCT
                    document.company_id,document.application_id,
					document.document_event_type,
                    document.document_id_ext,campaign_info.promo_id,
                    campaign_info.promo_sub_code,company.name_short,
                    document.date_created,document.document_id,
                    application.phone_fax,application.email,
					document_list.name FROM document
                    JOIN campaign_info ON campaign_info.campaign_info_id = (
                        SELECT MAX(campaign_info_id) FROM campaign_info WHERE
                        campaign_info.application_id = document.application_id)
                    JOIN company ON document.company_id = company.company_id
                    JOIN application ON application.application_id =
                        document.application_id
					JOIN document_list ON
					document_list.document_list_id = document.document_list_id
                    WHERE document.document_id > %%min_id%% AND
                    document.document_event_type IN ('sent','received') AND
                    document.document_method IN ('copia_fax','copia_email')
					AND document.company_id = %%ecash_company_id%%
                    LIMIT 0,5000;";
				break;

			default:
				echo("Unknown import method. Valid ones are sent or received.\n");
				exit(1);
				break;
		}
		$this->page_id_map = array(
            "D1" => self::D1_PAGE_ID,
            "CA" => self::CA_PAGE_ID,
            "UFC" => self::UFC_PAGE_ID,
            "UCL" => self::UCL_PAGE_ID,
            "PCL" => self::PCL_PAGE_ID
        );
		echo("Finding company id in eCASH for $ecash_company_short\n");
		$this->findECashCompanyId($ecash_company_short);
		if($this->ecash_company_id > 0)
		{
			echo("eCASH company id is {$this->ecash_company_id}\n");
		}
		else
		{
			echo("Could not find eCASH company.\n");
			exit;
		}
		echo("Finding user_id for Condor agent login $condor_agent_login\n");
		$this->findCondorUserId($condor_agent_login);
		if($this->condor_user_id > 0)
		{
			echo("Condor user id is {$this->condor_user_id}\n");
		}
		else
		{
			echo("Could not find Condor user_id.\n");
			exit;
		}
		echo("Finding the first document\n");
		$this->findFirstDocument($this->method);
		if($this->minimum_id > 0)
		{
			echo("There are a total of $this->total_rows documents.\n");
			echo("The first document has id $this->minimum_id.\n");
		}
		else
		{
			echo("Could not find the first document.\n");
			exit;
		}
		
	}
	public function Import_Documents()
	{
		if($this->method == 'sent' || $this->method == 'both')
		{
			echo("Importing document templates.\n");
			$this->Import_Templates();
		}
		$more_data = true;
		$docs_processed = 1;
		while($more_data)
		{
			$query = str_replace(Array("%%min_id%%","%%ecash_company_id%%"),
						Array($this->minimum_id,$this->ecash_company_id),
						$this->get_data_query);
			$res = $this->ecash_db->Query($query);
			$docs_this_chunk = 0;
			while($row = $res->Fetch_Object_Row())
			{
				$docs_this_chunk++;
				$this->updateStatusText($docs_processed,$this->total_rows);
				$row->page_id=$this->page_id_map[strtoupper($row->name_short)];
				if($row->document_event_type == 'sent')
				{
					$this->Import_Sent_Document($row);
				}
				else if($row->document_event_type == "received")
				{
					if(is_array($this->files))
					{
						$this->Import_Document_eCASH_Files($row);
					}
					else
					{
						$this->Import_Received_Document($row);
					}
				}
				if($row->document_id > $this->minimum_id)
					$this->minimum_id = $row->document_id;
				$docs_processed++;
			}
			if($docs_this_chunk > 0)
				$more_data = true;
			else
				$more_data = false;
		}
		echo(" ...Import complete.\n");
		//if we're looking for a dump of files
		//This is it.
		if(is_array($this->files))
		{
			$tarFile = "ecash_to_condor_import";
			if(@file_exists($tarFile.".tar"))
			{	
				$cnt = 2;
				while(@file_exists($tarFile-$cnt.".tar"))
				{
					$cnt++;
				}
				$tarFile = $tarFile."-".$cnt;
			}
			$tarFile = $tarFile.".tar";
			$file = fopen("ecash_to_condor_import.sh","w");
			fwrite($file,"#!/bin/bash\n");
			fwrite($file,"## tars/gzcompresses all files ".
						"for ecash company $this->ecash_company_id\n");
			fwrite($file,"## They'll all be stored in $tarFile.gz\n");
			fwrite($file,"## Auto-Generated by ".basename(__FILE__)."\n");
			fwrite($file,"## Generation Time: ".date("Y-m-d H:i:s")."\n");
		
			$command = "tar -cvf $tarFile";
			fwrite($file,"cd /\n");	
			$j = 1;
			$cnt = count($this->files);
			$total =ceil($cnt / 45);
			$group = 1;
			foreach($this->files as $val)
			{
				$dir = substr($val,0,strrpos($val,"/"));
				//create the dirs and touch the files for
				//testing purposes.
				if(!is_dir($dir))
					mkdir($dir,0755,TRUE);
				if(!@file_exists($val))
					touch($val);
				if($j % 45 == 0 || $j == 1)
				{
					fwrite($file,"\necho 'Adding group $group of $total to archive.'\n");
					fwrite($file,$command);
					$group++;
				}
				fwrite($file," ".substr($val,1));
				$command = "tar -rf $tarFile";
				$j++;
			}
			fwrite($file,"\necho 'All files added. Compressing archive.'\n");
			fwrite($file,"gzip $tarFile\n");
			fwrite($file,"echo 'Archiving complete.'\n");
			fclose($file);
			echo("Script to compress files is in ecash_to_condor_import.sh\n");
		}
	}
		
	protected  function updateStatusText($currentRow,$totalRows)
	{
		if($this->statusText)
		{
			$stat_len = strlen($this->statusText);
            for($i = 0;$i<$stat_len;$i++)
            {
            	echo("\033[D");
                echo("\033[K");
            }
		}
		$tperc = sprintf("%03.02f%%",($currentRow / $totalRows) * 100);
		$this->statusText = "Processed $currentRow of $totalRows ($tperc)";
		echo $this->statusText;
	}
	protected function findECashCompanyId($prop_short)
	{
		$this->setupDatabase();
		$query ="SELECT company_id from company 
				 WHERE name_short='$prop_short';";
		$res = $this->ecash_db->Query($query);
		$row = $res->Fetch_Object_Row();
		$this->ecash_company_id = $row->company_id;
	}
	protected function findCondorUserId($login)
	{
		$this->setupDatabase();
		$query = "SELECT company_id,agent_id FROM condor_admin.agent
				  WHERE company_id=(SELECT company_id FROM condor_admin.agent 
					WHERE login='{$login}' AND system_id=2);";
		$res = $this->condor_db->Query($query);
		$row = $res->Fetch_Object_Row();
		$this->condor_user_id = $row->agent_id;
		$this->condor_company_id = $row->company_id;
	}
	protected function findFirstDocument($type)
	{
		if($type == 'both')
		{
			$what_type = "document.document_event_type IN ('sent','received')";
		}
		else
		{
			$what_type = "document.document_event_type = '$type'";
		}
		$this->after_this_date = (time() - (86400 * DAYS_TO_IMPORT));
		$query = "SELECT count(*) as cnt, MIN(document_id) as min FROM
				 document WHERE document.date_created >= '".
				 date("Y-m-d H:i:s",$this->after_this_date)."' AND 
				 document.company_id={$this->ecash_company_id} AND
				 $what_type AND
				 document.document_method IN ('copia_fax','copia_email');";
		$res = $this->ecash_db->Query($query);
		$row = $res->Fetch_Object_Row();
		$this->minimum_id = $row->min;
		$this->total_rows = $row->cnt;
	}	
	protected function Generate_Space_Key($page_id,$promo_id,$promo_sub_code)
	{
		$mode = strtoupper(IMPORT_MODE) !== 'LIVE' ? $mode='test' : 'live';
		$bin = '/opt/statpro/bin/spc_'.self::STATPRO_KEY.'_'.$mode;
		$statpro = new StatPro_Client($bin,'',self::STATPRO_KEY,self::STATPRO_PASS);
		return $statpro->Get_Space_Key($page_id,$promo_id,$promo_sub_code);
	}
	protected function Import_Templates()
	{
		$this->setupDatabase();
		$this->condor_db->Query("LOCK TABLES template WRITE");
		$query = "SELECT count(*) as cnt,name,UCASE(active_status) as status,
				  date_created
				  FROM document_list WHERE 
				  company_id={$this->ecash_company_id} GROUP BY name";
		$res = $this->ecash_db->Query($query);
		$templates = Array();
		$cur_tpl = 0;
		while($row = $res->Fetch_Object_Row())
		{
			$query = "SELECT count(*) as cnt FROM template WHERE name='{$row->name}' AND company_id='{$this->condor_company_id}'";
			$res2 = $this->condor_db->Query($query);
			$row2 = $res2->Fetch_Object_Row();
			$this->updateStatusText($cur_tpl,$row->cnt);
			if($row2->cnt == 0)
			{
				$templates[] = "('{$row->name}',$this->condor_company_id,
							$this->condor_user_id,'{$row->status}',
							'{$row->date_created}','Temporary Subject')";
			}
			$cur_tpl++;
		}
		if(count($templates) > 0)
		{
			$query = "INSERT INTO template (name,company_id,user_id,status,
						date_created,subject) VALUES ".join(',',$templates).";";
			$this->condor_db->Query($query);
		}
		$this->condor_db->Query("UNLOCK TABLES");
	}
	protected function Import_Sent_Document($doc)
	{
		$space_key = $this->Generate_Space_Key($doc->page_id,
			$doc->promo_id,$doc->promo_sub_code);
		$query = "INSERT INTO document (date_created,date_modified,template_id,
					type,user_id,application_id,space_key,track_key) VALUES (
					'{$doc->date_created}',NOW(),
					(SELECT template_id FROM template WHERE name='{$doc->name}'
					 AND company_id={$this->condor_company_id})
				 	,'OUTGOING',{$this->condor_user_id},{$doc->application_id},
					'{$space_key}','{$doc->track_id}')";
		$this->condor_db->Query($query);
		$doc_id = $this->condor_db->Insert_Id();
		if(strtolower($doc->document_method) == 'copia_fax')
		{
			$transport = "FAX";	
			$recipient = $doc->phone_fax;
		}
		else
		{
			$transport = "EMAIL";
			$recipient = $doc->email;
		}
		$query = "INSERT INTO document_dispatch (document_id,date_created,
				transport,recipient,user_id) VALUES ({$doc_id},
					'{$doc->date_created}','{$transport}','{$recipient}',
					{$this->condor_user_id});";
		$this->condor_db->Query($query);
	}
	protected function Import_Received_Document($doc)
	{
		$space_key = $this->Generate_Space_Key($doc->page_id,$doc->promo_id,
			$doc->promo_sub_code);
		$track_key = isset($doc->track_key) ? "'{$doc->track_key}'" : 'NULL';
		$app_id = !empty($doc->application_id) ? $doc->application_id : 0;
		$query = "INSERT into document (date_created,date_modified,
			type,subject,user_id,application_id,space_key,track_key,template_id)
			VALUES ( '{$doc->date_created}',NOW(),'INCOMING','Loan Documents',
			{$this->condor_user_id},$app_id,'$space_key','$track_key',
			(SELECT template_id FROM template WHERE name='$doc->name' 
				AND company_id={$this->condor_company_id}))";
		$this->condor_db->Query($query);
		$doc_id = $this->condor_db->Insert_Id();
		list($dnis,$tiff) = explode(',',$doc->document_id_ext);
		$query = "INSERT INTO part SET date_created = NOW(),
			date_modified = NOW(),
			content_type= 'application/pdf',
			compression='GZ'";
		$this->condor_db->Query($query);
		$root_id = $this->condor_db->Insert_Id();
		$dir = Condor::Get_Directory();
		$condor_filename = $dir.sprintf(Condor::FILENAME_FORMAT,$doc_id,$root_id);
		$tiff_file = COPIA_MOUNT."faxfacts/MAIL/{$dnis}/printed/{$tiff}.TIF";
		if(@file_exists($tiff_file))
		{
			$compressed_file = gzcompress(TIFF_To_PDF(COPIA_MOUNT."faxfacts/MAIL/{$dnis}/printed/{$tiff}.TIF"));
			file_put_contents($condor_filename,$compressed_file);
		}
		$query = "UPDATE part SET file_name='{$condor_filename}' 
			WHERE part_id={$root_id}";
		$this->condor_db->Query($query);
		$query = "UPDATE document SET root_id={$root_id}
			WHERE document_id={$doc_id}";
		$this->condor_db->Query($query);
		$query = "INSERT into document_part (document_id,part_id) VALUES(
			{$doc_id},{$root_id});";
		$this->condor_db->Query($query);
	}
	protected function Import_Document_eCASH_Files($doc)
	{
		list($dnis,$tiff) = explode(',',$doc->document_id_ext);
		$tiff_filename = "/mnt/copia/faxfacts/MAIL/{$dnis}/printed/{$tiff}.TIF";
		$this->files[] = $tiff_filename;
		usleep(1);
	}
		
	private function setupDatabase()
	{
		if(!($this->ecash_db instanceof MySQLi_1))
		{
			$this->ecash_db = new MySQLi_1(ECASH_DB_HOST,ECASH_DB_USER,
										   ECASH_DB_PASS,ECASH_DB_NAME,
										   ECASH_DB_PORT);
		}
		if(!($this->condor_db instanceof MySQLi_1))
		{
			$this->condor_db = new MySQLi_1(CONDOR_DB_HOST,CONDOR_DB_USER,
											CONDOR_DB_PASS,CONDOR_DB_NAME,
											CONDOR_DB_PORT);
		}
	}
	public function Test()
	{
		$this->findECashCompanyId(ECASH_PROP_SHORT);
		$this->findCondorUserId(CONDOR_LOGIN);
		$this->findFirstDocument('sent');
		echo("ECASH company_id is {$this->ecash_company_id}.\n");
		echo("Condor user_id is {$this->condor_user_id}.\n");
		echo("First document id {$this->minimum_id}.\n");
		echo("Total documents {$this->total_rows}.\n");
	}
									
};
function TIFF_To_PDF($file)
{
	$pdf = FALSE;
	if (is_file($file))
	{
		// Convert the TIFF to a PDF first
		$cmd = BIN_TIFF2PDF.' '.$file;
		$process = popen($cmd, 'r');
		if (is_resource($process))
		{
			$pdf = stream_get_contents($process);
			pclose($process);
		}
	}
	return $pdf;
}
