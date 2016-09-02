<?php
class TestDb
{
	protected static $instance;
	
	public static function getInstance()
	{
		if (!self::$instance)
		{
			$config = new DB_SQLiteConfig_1(':memory:');
			self::$instance = $config->getConnection();
			self::initialize();
		}
		
		return self::$instance;
	}
	
	protected function initialize()
	{
		self::$instance->exec("
			CREATE TABLE document (
				document_id integer primary key autoincrement,
				date_created text,
				password text,
				secure_data blob,
				unsecure_data blob
			)
		");
	}
}

class Document extends OLP_Models_WritableModel
{
	public function getDatabaseInstance($db_inst = DB_Models_DatabaseModel_1::DB_INST_WRITE)
	{
		return TestDb::getInstance();
	}
	
	public function getColumns()
	{
		static $columns = array(
			'document_id',
			'date_created',
			'password',
			'secure_data',
			'unsecure_data',
		);
		
		return $columns;
	}
	
	public function getPrimaryKey()
	{
		return array('document_id');
	}
	
	public function getAutoIncrement()
	{
		return 'document_id';
	}
	
	public function getTableName()
	{
		return 'document';
	}
	
	public function getRequiredColumns()
	{
		return array('password');
	}
	
	public function getEncryptedColumns()
	{
		return array('secure_data', 'password');
	}
	
	public function getCompressedColumns()
	{
		return array('secure_data', 'unsecure_data');
	}
}

$document = new Document();
$document->date_created = time();
$document->password = 'Secure Password';
$document->save();

$result = TestDb::getInstance()->prepare("SELECT date_created FROM document WHERE document_id = ?");
$result->execute(array($document->document_id));
if ($date_created = $result->fetchColumn())
{
	printf("Plaintext date created: %s\n",
		$document->date_created
	);
	printf("DB raw date_created: %s\n",
		preg_replace('/\W/', '#', $date_created)
	);
}
printf("\n");

$result = TestDb::getInstance()->prepare("SELECT password FROM document WHERE document_id = ?");
$result->execute(array($document->document_id));
if ($password = $result->fetchColumn())
{
	printf("Plaintext password: %s\n",
		$document->password
	);
	printf("DB raw password: %s\n",
		preg_replace('/\W/', '#', $password)
	);
}
printf("\n");

$document->secure_data = "Highly-sensitive, compressable data.";
$document->save();

$result = TestDb::getInstance()->prepare("SELECT secure_data FROM document WHERE document_id = ?");
$result->execute(array($document->document_id));
if ($secure_data = $result->fetchColumn())
{
	printf("Plaintext secure data: %s\n",
		$document->secure_data
	);
	printf("DB raw secure data: %s\n",
		preg_replace('/\W/', '#', $secure_data)
	);
}
printf("\n");

$document->unsecure_data = "Public, compressable data.";
$document->save();

$result = TestDb::getInstance()->prepare("SELECT unsecure_data FROM document WHERE document_id = ?");
$result->execute(array($document->document_id));
if ($unsecure_data = $result->fetchColumn())
{
	printf("Plaintext unsecure data: %s\n",
		$document->unsecure_data
	);
	printf("DB raw unsecure_data: %s\n",
		preg_replace('/\W/', '#', $unsecure_data)
	);
}
printf("\n");

$document2 = new Document();
if ($document2->loadBy(array('document_id' => $document->document_id)))
{
	printf("Loaded document2.\n");
	printf("Date Created: %s\n",
		$document2->date_created
	);
	printf("Password: %s\n",
		$document2->password
	);
	printf("Secure Data: %s\n",
		$document2->secure_data
	);
	printf("Unsecure Data: %s\n",
		$document2->unsecure_data
	);
}
printf("\n");

?>
