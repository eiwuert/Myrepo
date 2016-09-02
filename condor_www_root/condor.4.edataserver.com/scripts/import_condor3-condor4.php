<?PHP
// Grab 90 days worth of documents
// From condor 3, then add them to condor 4

require_once('mysqli.1.php');
require_once('/virtualhosts/condor.4.edataserver.com/lib/condor.class.php');

define('CHUNK_SIZE',2500); //maximum chunk of data to grab at a time
define('DAYS_TO_IMPORT',90); //The number of days worth of data to import

define('CONDOR3_DB_HOST','db101.clkonline.com');
define('CONDOR3_DB_USER','condor');
define('CONDOR3_DB_PASS','Nzt04g8a');
define('CONDOR3_DB_PORT',3308);
define('CONDOR3_DB_DB','condor');

define('CONDOR4_DB_HOST','localhost');
define('CONDOR4_DB_USER','root');
define('CONDOR4_DB_PASS','');
define('CONDOR4_DB_PORT',3306);
define('CONDOR4_DB_DB','condor_2_import');

define('COMPANY_SHORT','ufc');
if(empty($argv[1]))
{
	echo("Please supply the company_short name.\n");
	exit;
}

$x = new Import_Document($argv[1]);
$x->Run();
class Import_Document
{
	private $condor3_db;
	private $condor4_db;
	private $user_id;
	private $company_short;

    const STATPRO_KEY = 'clk';
    const STATPRO_PASS = 'dfbb7d578d6ca1c136304c845';
    const STATPRO_MODE = 'test';

    const CA_PAGE_ID = 39413;
    const D1_PAGE_ID = 39121;
    const UCL_PAGE_ID = 39417;
    const UFC_PAGE_ID = 17212;
    const PCL_PAGE_ID = 1807;

	public function Run()
	{
		$this->createDatabase();
		$this->find_user_id();
		if(empty($this->user_id))
		{
			echo("There is no condor api user for ".$this->company_short."\n");
			exit;
		}
		$import_after_time = (time() - (86400 * DAYS_TO_IMPORT));
		$more_data = TRUE;
		$query = "SELECT COUNT(*) as cnt,MIN(document_archive_id) as min FROM ".
				 "`document_archive` WHERE date_created >= '".
					date("Y-m-d H:i:s",$import_after_time)."';";
		$query = "SELECT COUNT(*) as cnt,MIN(da.document_archive_id) as min ".
				 "FROM `document_archive` da INNER JOIN signature sig ON ".
				 "(sig.document_archive_id = da.document_archive_id AND ".
				 "sig.property_short='".$this->company_short."')".
				 " WHERE da.date_created >= '".
				  date("Y-m-d H:i:s",$import_after_time)."';";
		$res = $this->condor3_db->Query($query);
		$row = $res->Fetch_Object_Row();
		$minId = ($row->min - 1);
		$totalRows = $row->cnt;
		echo("There are $totalRows total documents to import.\n");
		$totalChunks = sprintf("%d",$totalRows / CHUNK_SIZE);
		$totalChunks = $totalRows % CHUNK_SIZE > 0 ? $totalChunks + 1 : $totalChunks;
		$docsProcessed = 0;
		$current_chunk = 0;
		$timer = new Timer();
		$timer->start();
		while($more_data)
		{
			$current_chunk++;
			$query = "SELECT document_archive.document_archive_id as id,".
					 " document_archive.document as doc, ".
					 " document_archive.date_created,".
					 " signature.application_id as app_id ".
					 " FROM document_archive ".
					 " INNER JOIN signature ON ".
					 " (document_archive.document_archive_id = signature.".
					 "document_archive_id AND ".
					 " signature.property_short='".$this->company_short."')".
					 " WHERE document_archive.document_archive_id > ".$minId.
					 " LIMIT 0,".CHUNK_SIZE.";";
			$res = $this->condor3_db->Query($query);
			
			
			$rCnt = 0;
			$numRows = $res->Row_Count();
			if($numRows < 1)
			{
				echo("Import complete.\n");
				exit(1);
			}
			else
			{
				echo("Grabbed chunk $current_chunk of $totalChunks\n");
			}
			$stat_len = 0;
			while($row = $res->Fetch_Object_Row())
			{
				$rCnt++;
				$docsProcessed++;
				if($row->id > $minId)
				{
					$minId = $row->id;
				}
                for($i = 0;$i<$stat_len;$i++)
                {
                	echo("\033[D");
                    echo("\033[K");
                }
                $cperc = sprintf("%03.02f%%",
                $rCnt / $numRows  * 100);
                $tperc = sprintf("%03.02f%%",
                	($docsProcessed) / $totalRows * 100);
                $stat_str = "Processing $rCnt of $numRows documents....Chunk: $cperc Total: $tperc";
                $stat_len = strlen($stat_str);
                echo("$stat_str");	
				$this->import_doc($row);
				usleep(0);
			}
			$timer->stop();
            for($i = 0;$i<$stat_len;$i++)
            {
           		echo("\033[D");
                echo("\033[K");
            }
            echo("Chunk Complete.Took ".$timer->getTime()." seconds to complete.\n");
			if($rCnt > 0)
			{
				$more_data = true;
			}
			else
			{
				$more_data = false;
			}
		}
		
	}
	private function find_user_id()
	{
		$this->condor4_db->Change_Database('condor_admin');
		$query = "SELECT agent_id FROM agent WHERE company_id=(SELECT company_id FROM agent WHERE login='".$this->company_short."' AND system_id=2);";
		$res = $this->condor4_db->Query($query);
		$row = $res->Fetch_Object_Row();
		$this->user_id = $row->agent_id;
		$this->condor4_db->Change_Database(CONDOR4_DB_DB);
	}
	private function Insert_Doc($date,$app_id)
	{
		$query = "INSERT INTO `document` (date_created,date_modified,type,".
				 "subject,application_id,user_id)".
				 " VALUES('{$date}',NOW(),'OUTGOING','Loan Documents',".
				 "{$app_id},{$this->user_id});";
		$this->condor4_db->Query($query);
		return $this->condor4_db->Insert_Id();
	}
	private function Insert_Part(&$doc,$doc_id)
	{
		$query = 'INSERT INTO part SET date_created=NOW(),'.
				 'date_modified=NOW(),'.
				 'content_type=\'text/html\','.
				 'compression=\'GZ\'';

		$this->condor4_db->Query($query);
		$part_id = $this->condor4_db->Insert_Id();
		$dir = Condor::Get_Directory();
		$filename = $dir.sprintf(Condor::FILENAME_FORMAT,$doc_id,$part_id);
		file_put_contents($filename,$doc->doc);
		$query = "UPDATE part SET file_name='{$filename}' WHERE part_id={$part_id}";
		$this->condor4_db->Query($query);
		$query = "UPDATE document SET root_id={$part_id} WHERE document_id={$doc_id}";
		$this->condor4_db->Query($query);
		$query = "INSERT INTO document_part (document_id,part_id) VALUES(".
				 "{$doc_id},{$part_id});";
		$this->condor4_db->Query($query);
	}
	private function import_doc(&$doc)
	{
		//Insert the document into all of the condor 4 tables
		// 1) Insert into `document` and get insert_id
		// 2) Insert into `part` and get the part_id
		// 3) update `document` with the root_id
		// 4) Insert into document_part the doc_id and root_id
		$doc_id = $this->Insert_Doc($doc->date_created,$doc->app_id);
		$this->Insert_Part($doc,$doc_id);
		
	}
 	
	function __construct($c_short)
	{
		$this->company_short = $c_short;
		$this->page_id_map = array(
            "D1" => self::D1_PAGE_ID,
            "CA" => self::CA_PAGE_ID,
            "UFC" => self::UFC_PAGE_ID,
            "UCL" => self::UCL_PAGE_ID,
            "PCL" => self::PCL_PAGE_ID
        );
	}

	private function createDatabase()
	{
	
		$this->condor4_db = new MySQLi_1(CONDOR4_DB_HOST,
								  CONDOR4_DB_USER,
								  CONDOR4_DB_PASS,
								  CONDOR4_DB_DB,
								  CONDOR4_DB_PORT);
		$this->condor3_db = new MySQLi_1(CONDOR3_DB_HOST,
								  CONDOR3_DB_USER,
								  CONDOR3_DB_PASS,
								  CONDOR3_DB_DB,
								  CONDOR3_DB_PORT);
	}

};
class Timer
{
    private $start;
    private $end;

    public function start()
    {
        $this->start =microtime(TRUE);
    }
    public function stop()
    {
        $this->end = microtime(TRUE);
    }
    public function getTime($format="%02.02f")
    {
        $this->time = sprintf($format,($this->end - $this->start));
        return $this->time;
    }
};

?>

