<?php
/**
 * This page will display a CAPTCHA image file and store the CAPTCHA code into
 * the session.
 * 
 * @author Brian Feaver
 */

require_once('captcha.2.php');
require_once('session.8.php');
require_once('mysql.4.php');
require_once('libolution/AutoLoad.1.php');
require_once('mode_test.php');
require_once('/virtualhosts/bfw.1.edataserver.com/include/code/server.php');

class Olp_Captcha_1
{
	private $sql;
	private $database;
	private $server;
	private $session;
	private $session_id;
	private $captcha;
	private $mode;
	
	function __construct()
	{
		self::Setup_Db();
	}
	
	/**
	 * Sets up the session object.
	 *
	 * @param string $session_id
	 */
	public function Setup_Session($session_id)
	{
		$this->session_id = $session_id;
		
		// Don't set the cookie, it shows up as the back end server, which isn't really
		// that bad, but doesn't need to be set.
		ini_set('session.use_cookies', '0');
		$this->session = new Session_8(
			$this->sql,
			$this->database,
			'session',
			$this->session_id,
			'ssid',
			'gz',
			TRUE
		);
	}
	
	/**
	 * Displays the CAPTCHA image and saves the display string to the session.
	 *
	 * @param string $text
	 */
	public function Display_Captcha($size = 'large')
	{
		if(strcasecmp($this->mode, 'LOCAL') == 0 || strcasecmp($this->mode, 'RC') == 0)
		{
			// For Local and RC, we want it to have a static value.
			$this->captcha = new Captcha_2('AAAAA');
		}
		else
		{
			$this->captcha = new Captcha_2();
			$this->captcha->Generate_String(Captcha_2::CHARS_HEX, 5);
		}
		
		$_SESSION['captcha'] = $this->captcha->Get_String();
		
		if($size === 'small')
		{
			$this->captcha->Font('/virtualhosts/lib/ttf/4.ttf', 24, NULL);
			$this->captcha->Border(1, NULL);
			$this->captcha->Margin(5, 5, 7, 7);
			$this->captcha->Shadow_Offset(1, 1);
		}
		
		$this->captcha->Display(Captcha_2::OUTPUT_GIF);
	}
	
	/**
	 * Sets up the database connection for the session.
	 */
	private function Setup_Db()
	{
		
		//$auto_mode = new Auto_Mode();
		$this->mode = Mode_Test::Get_Mode_As_String(); //$auto_mode->Fetch_Mode($_SERVER['SERVER_NAME']);
		
		if(strcasecmp($this->mode, 'UNKNOWN') == 0) $this->mode = 'LIVE';
		if(strcasecmp($this->mode, 'NW') == 0) $this->mode = 'RC'; //For New World
		
		$this->server = Server::Get_Server($this->mode, 'BLACKBOX');
		
		try
		{
			if(isset($this->server['port']) && strpos($this->server['host'],':') === false)
			{
				$this->server['host'] = $this->server['host'].':'.$this->server['port'];
			}
			$this->sql = new MySQL_4($this->server['host'], $this->server['user'], $this->server['password']);
			$this->sql->Connect();
		}
		catch(Exception $e)
		{
			// Do nothing for now
			$e->getMessage();
		}
		
		$this->database = $this->server['db'];
	}
}

$olp_captcha = new Olp_Captcha_1();
$olp_captcha->Setup_Session($_GET['unique_id']);
$olp_captcha->Display_Captcha(isset($_GET['img_size']) ? $_GET['img_size'] : 'large');

?>
