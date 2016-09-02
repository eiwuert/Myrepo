<?php
require_once DIRNAME(__FILE__) . '/../../../db_setup.php';
require_once DIRNAME(__FILE__) . '/../../../config.php';
/**
 * Faking Token values
 */
function tokenArray()
{
	return array(
				'testToken1' => 'oh Hi',
				'testToken2' => 'oh noes');
}
/**
 * Faking Template list from Condor
 */
function templateArray()
{
	return array('testTemplate1', 'testTemplate2');
}
/**
 * Faking Template Tokens from Condor
 */
function templateTokenArray()
{
	return array('testToken1', 'testToken2');
}
/**
 * Faking Document Array returned from Condor
 */
function condorDocumentArray()
{
	$document = new stdclass();
	$document->data = "<html>Boom Baby!</html>";
	$document->content_type = 'html';
	$document->template_name = 'testTemplate1';
	return array('document' => $document, 'archive_id' => 1, 'template_name' => 'testTemplate1');

}
/**
 * Faking Document Obj returned from Condor
 */
function condorDocumentObj()
{
	$document = new stdclass();
	$document->data = "<html>Boom Baby!</html>";
	$document->content_type = 'html';
	$document->template_name = 'testTemplate1';
	$document->archive_id = 1;
	
	return $document;

}
/**
 * ECash_Documents_DocumentTest tests require an actual database behind them
 * 
 */
class ECash_Documents_DocumentTest extends PHPUnit_Framework_TestCase
{
	const DATABASE = './test.db';

	private $_pdo;

	public function setUp()
	{
		$this->_pdo = $this->setupDatabase();
		$db = new TestingDatabase($this->_pdo);
		TestingDBConfig::setConnection($db);
		$class_config_name = 'TestingConfig';
		ECash::setConfig(new $class_config_name());
		parent::setUp();
	}
	public function tearDown()
	{
		@unlink(self::DATABASE);
		parent::tearDown();
	}
	protected function setupDatabase()
	{

		$db = new PDO('sqlite:'.self::DATABASE);
		$sql = file_get_contents(DIRNAME(__FILE__) . '/tables.sql');
  		$db->exec($sql);
//  		$stmt = $db->query('select count(*) from document_package');
//  		$rs = $stmt->fetch(PDO::FETCH_OBJ);
//  		print_r($rs);
  		
  		return $db;
	}
	/**
	 * Builds the mock token provider
	 */
	protected function getMockTokens($app)
	{
		$mockTokens = $this->getMock('ECash_Application_TokenProvider', 
					array('getTokens' , 'getToken'), array($app, $this->_pdo));
					
		$mockTokens->expects($this->any())
				   ->method('getTokens')
				   ->will($this->returnCallback('tokenArray'));
		return $mockTokens;
	}
	/**
	 * Builds the Mock Prpc Object for Condor
	 */
	protected function getMockPrpc()
	{
		$mockPrpc = $this->getMock('ECash_Documents_Condor', 
					array('Set_Application_Id', 'Create_As_Attachment', 
						  'Send', 'Create', 'Get_Template_Tokens', 
						  'Find_By_Archive_Id', 'Get_Template_Names' ));
		
		$mockPrpc->expects($this->any())
				 ->method('Get_Template_Names')
				 ->will($this->returnCallBack('templateArray'));
		$mockPrpc->expects($this->any())
				 ->method('Get_Template_Tokens')
				 ->will($this->returnCallBack('templateTokenArray'));
		$mockPrpc->expects($this->any())
				 ->method('Send')
				 ->will($this->returnValue(true));
		$mockPrpc->expects($this->any())
				 ->method('Set_Application_Id')
				 ->will($this->returnValue(true));		
		$mockPrpc->expects($this->any())
				 ->method('Find_By_Archive_Id')
				 ->will($this->returnCallBack('condorDocumentObj'));	
		$mockPrpc->expects($this->any())
				 ->method('Create_As_Attachment')
				 ->will($this->returnCallBack('condorDocumentArray'));	
		$mockPrpc->expects($this->any())
				 ->method('Create')
				 ->will($this->returnCallBack('condorDocumentArray'));	
		return $mockPrpc;
	}
	public function testCreateTemplateByName()
	{
		$app = ECash::getApplicationById('119701');
		$mockPrpc = $this->getMockPrpc();
		$mockTokens = $this->getMockTokens($app);

 		$doc_app = $app->getDocuments();
  		$doc_app->setTokens($mockTokens);
 		$doc_app->Set_Generic_Email('test', 'test', 'test');
 		$this->assertEquals(array('testToken1' => 'oh Hi',
								  'testToken2' => 'oh noes'),
								   $doc_app->getTokens()->getTokens());
 		$doc_app->setPrpc($mockPrpc);
 		$template = $doc_app->getTemplateByName('testTemplate1');
 		$model = $template->getModel();
 		$this->assertTrue($model instanceof ECash_Models_Reference_Model);
 		$this->assertEquals('testTemplate1', $template->getName());

	}
	public function testCreateTemplatePackageByName()
	{
		$app = ECash::getApplicationById('119701');
		$mockPrpc = $this->getMockPrpc();
		$mockTokens = $this->getMockTokens($app);

 		$doc_app = $app->getDocuments();
 		$doc_app->setTokens($mockTokens);
 		$doc_app->setPrpc($mockPrpc);
 		$template = $doc_app->getPackageByName('test package');

 		$this->assertEquals('test package', $template->getName());
 		
	}	
	public function testCreateTemplateById()
	{
		$app = ECash::getApplicationById('119701');
		$mockPrpc = $this->getMockPrpc();
		$mockTokens = $this->getMockTokens($app);

		$doc_app = $app->getDocuments();
 		$doc_app->setTokens($mockTokens);
  		$doc_app->setPrpc($mockPrpc);
  		
 		$template = $doc_app->getTemplateById(1);
 		$this->assertTrue(is_numeric($template->getEcashID()));
 		$this->assertEquals('testTemplate1', $template->getName());

	}
	public function testgetSendable()
	{
		$app = ECash::getApplicationById('119701');
		$mockPrpc = $this->getMockPrpc();
		$mockTokens = $this->getMockTokens($app);

 		$doc_app = $app->getDocuments();
 		$doc_app->setTokens($mockTokens);
 		$doc_app->setPrpc($mockPrpc);
 		$templates = $doc_app->getSendable();
 		
 		foreach($templates as $template)
 		{
 			$template->setPrpc($mockPrpc);
 			$transports = $template->getTransportTypes();
 			$this->assertTrue(count($transports) > 0);
 			$this->assertEquals(array('testToken1', 'testToken2'), $template->getTemplateTokens());
 		}
	}
	public function testgetEsigable()
	{
		$app = ECash::getApplicationById('119701');
		$mockPrpc = $this->getMockPrpc();
		$mockTokens = $this->getMockTokens($app);

 		$doc_app = $app->getDocuments();
 		$doc_app->setTokens($mockTokens);
 		$doc_app->setPrpc($mockPrpc);
 		$templates = $doc_app->getEsigable();
 		
 		foreach($templates as $template)
 		{
 			$template->setPrpc($mockPrpc);
 			$this->assertEquals('yes', $template->isSignable());
 			$this->assertEquals(array('testToken1', 'testToken2'), $template->getTemplateTokens());
 		}
	}
	public function testgetAll()
	{
		$app = ECash::getApplicationById('119701');
		$mockPrpc = $this->getMockPrpc();
		$mockTokens = $this->getMockTokens($app);

 		$doc_app = $app->getDocuments();
 		$doc_app->setTokens($mockTokens);
 		$doc_app->setPrpc($mockPrpc);
 		$templates = $doc_app->getAll();
 		$this->assertTrue(count($templates) > 0);
 		foreach($templates as $template)
 		{
 			$template->setPrpc($mockPrpc);
 			$this->assertEquals(array('testToken1', 'testToken2'), $template->getTemplateTokens());
 		}
	}
	public function testgetRecievable()
	{
		$app = ECash::getApplicationById('119701');
		$mockPrpc = $this->getMockPrpc();
		$mockTokens = $this->getMockTokens($app);

 		$doc_app = $app->getDocuments();
 		$doc_app->setTokens($mockTokens);
 		$doc_app->setPrpc($mockPrpc);
 		$templates = $doc_app->getRecievable();
 		
 		foreach($templates as $template)
 		{
 			$this->assertEquals('testTemplate1', $template->getName());
 		}
	}
	public function testCreateDocument()
	{
		$app = ECash::getApplicationById('119701');
		$mockPrpc = $this->getMockPrpc();
		$mockTokens = $this->getMockTokens($app);
	
 		$doc_app = $app->getDocuments();
 		$doc_app->setTokens($mockTokens);
 		$doc_app->setPrpc($mockPrpc);
 		
 		$template = $doc_app->getTemplateByName('testTemplate1');
 		$template->setPrpc($mockPrpc);
 		
 		$document = $doc_app->create($template);
 		$document->setPrpc($mockPrpc);
 		
 		$this->assertEquals('<html>Boom Baby!</html>', $document->getContents());
		$this->assertEquals('testTemplate1', $document->getName());
	}
	public function testCreateDocumentPackageByName()
	{
		$app = ECash::getApplicationById('119701');
		$mockPrpc = $this->getMockPrpc();
		$mockTokens = $this->getMockTokens($app);

 		$doc_app = $app->getDocuments();
 		$doc_app->setTokens($mockTokens);
 		$doc_app->setPrpc($mockPrpc);
 		$template = $doc_app->getPackageByName('test package');
 		$template->setPrpc($mockPrpc);
		$package = $doc_app->createPackage($template);
 		$this->assertEquals('test package', $package->getName());
 		
	}	
	public function testCreateDocumentById()
	{
		$app = ECash::getApplicationById('119701');
		$mockPrpc = $this->getMockPrpc();
		$mockTokens = $this->getMockTokens($app);

 		$doc_app = $app->getDocuments();
 		$doc_app->setTokens($mockTokens);
 		$doc_app->setPrpc($mockPrpc);
 		
 		$document = $doc_app->getByID(1);
 		$document->setPrpc($mockPrpc);
 		$this->assertEquals('html', $document->getContentType());
 		$this->assertEquals('<html>Boom Baby!</html>', $document->getContents());

	}
	public function testSendEmailandSaveDocument()
	{
		$app = ECash::getApplicationById('119701');
		$mockPrpc = $this->getMockPrpc();
		$mockTokens = $this->getMockTokens($app);

 		$doc_app = $app->getDocuments();
 		$doc_app->setTokens($mockTokens);
 		$doc_app->setPrpc($mockPrpc);
 		
 		$template = $doc_app->getTemplateByName('testTemplate1');
 		$template->setPrpc($mockPrpc);
 		
 		$document = $doc_app->create($template);
 		$document->setPrpc($mockPrpc);
 		
 		$transports = $document->getTransportTypes();
		$transports['email']->setEmail('rebel75cell@gmail.com');
 		$transports['email']->setPrpc($mockPrpc);
 		
 		$this->assertTrue($document->send($transports['email'], 4));
 		
 		$docs = $doc_app->getSent();
		$this->assertTrue(count($docs) > 0);
		foreach($docs as $doc)
		{
			$doc->setPrpc($mockPrpc);	
			$this->assertEquals('yes', $doc->isSignable());
			$this->assertEquals('<html>Boom Baby!</html>', $doc->getContents());
		}
	}	
	public function testSendFaxandSaveDocument()
	{
		$app = ECash::getApplicationById('119701');
		$mockPrpc = $this->getMockPrpc();
		$mockTokens = $this->getMockTokens($app);

 		$doc_app = $app->getDocuments();
 		$doc_app->setTokens($mockTokens);
 		$doc_app->setPrpc($mockPrpc);
 		
 		$template = $doc_app->getTemplateByName('testTemplate1');
 		$template->setPrpc($mockPrpc);
 		
 		$document = $doc_app->create($template);
 		$document->setPrpc($mockPrpc);
 		
 		$transports = $document->getTransportTypes();
		$transports['fax']->setPhoneNumber('132456789');
		$transports['fax']->setCoverSheet('testTemplate2');
 		$transports['fax']->setPrpc($mockPrpc);
 		
 		$this->assertTrue($document->send($transports['fax'], 4));
 		
 		$docs = $doc_app->getSent();
		$this->assertTrue(count($docs) > 0);
		foreach($docs as $doc)
		{
			$this->assertTrue(is_numeric($doc->getEcashID()));
			$doc->setPrpc($mockPrpc);
			$this->assertEquals('<html>Boom Baby!</html>', $doc->getContents());
		}
	}
	public function testSendDocumentPackageandSave()
	{
		$app = ECash::getApplicationById('119701');
		$mockPrpc = $this->getMockPrpc();
		$mockTokens = $this->getMockTokens($app);

 		$doc_app = $app->getDocuments();
 		$doc_app->setTokens($mockTokens);
 		$doc_app->setPrpc($mockPrpc);
 		$template = $doc_app->getPackageByName('test package');
 		$template->setPrpc($mockPrpc);
 		$this->assertTrue(is_numeric($template->getId()));
 		$this->assertTrue($template->getModel() instanceof ECash_Models_Reference_Model);
 		$this->assertTrue(is_string($template->getBodyName()));
		$package = $doc_app->createPackage($template);
		
		$transports = $package->getTransportTypes();
		$transports['email']->setEmail('test@sellingsource.com');
 		$transports['email']->setPrpc($mockPrpc);
 		
 		$this->assertTrue($package->send($transports['email'], 4));
 		
 		$docs = $doc_app->getSentandRecieved();

		$this->assertTrue(count($docs) > 0);
		foreach($docs as $doc)
		{
			$doc->setPrpc($mockPrpc);
			$this->assertEquals('<html>Boom Baby!</html>', $doc->getContents());
		}
 		
	}	
	public function testDocumentRecieved()
	{
		$app = ECash::getApplicationById('119701');
		$mockPrpc = $this->getMockPrpc();
		$mockTokens = $this->getMockTokens($app);

 		$doc_app = $app->getDocuments();
 		$doc_app->setTokens($mockTokens);
 		$doc_app->setPrpc($mockPrpc);
 		
 		$document = $doc_app->getByID(1);
 		$document->setPrpc($mockPrpc);
 		$this->assertTrue($document->recieved('email', 4, true));

 		$docs = $doc_app->getRecieved();
		$this->assertTrue(count($docs) > 0);
		foreach($docs as $doc)
		{
			$doc->setPrpc($mockPrpc);
			$this->assertTrue($doc->getModelList() != null);
			$this->assertEquals('<html>Boom Baby!</html>', $doc->getContents());
		}

	}		
}

?>
