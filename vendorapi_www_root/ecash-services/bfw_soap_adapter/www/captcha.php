<?php
require_once('captcha.2.php');
require_once('libolution/AutoLoad.1.php');
AutoLoad_1::addSearchPath(dirname(__FILE__) . '/../code/');

/**
 * This page will display a CAPTCHA image file and store the CAPTCHA code into
 * the session.
 *
 * @author Brian Feaver <brian.feaver@sellingsource.com>
 * @author Bryan Campbell <bryan.campbell@dataxltd.com>
 */
class BFWCaptcha
{
	/**
	 * The lead api client.
	 *
	 * @var SoapClient
	 */
	private $leadApi;

	/**
	 * If a string, the static value to display; otherwise, if true, AAAAA is displayed
	 * @var mixed
	 */
	private $static_value;


	/**
	 * BFWCaptcha constructor.
	 *
	 * @param SoapClient $leadApi ECash Lead API
	 * @param string $session_id a string of the session ID
	 * @param string $static_value If a string, the value to display; if true, AAAAA
	 * 	is displayed; otherwise a random value is chosen
	 * @return void
	 */
	function __construct(SoapClient $leadApi, $static_value = false)
	{
		$this->leadApi = $leadApi;
		$this->static_value = $static_value;
	}

	/**
	 * Displays the CAPTCHA image and saves the display string to the session.
	 *
	 * @param string $size a string of the size of the captcha
	 * @return void
	 */
	public function displayCaptcha($session_id, $size = 'large')
	{
		if ($this->static_value)
		{
			// For Local and RC, we want it to have a static value.
			$value = ($this->static_value == 1) ? 'AAAAA' : $this->static_value;
			$captcha = new Captcha_2($value);
		}
		else
		{
			$captcha = new Captcha_2();
			$captcha->Generate_String(Captcha_2::CHARS_HEX, 5);
		}

		$this->leadApi->setCaptchaLetters($session_id, $captcha->Get_String());

		if ($size === 'small')
		{
			$captcha->Font(realpath(dirname(dirname(dirname(__FILE__)))) . '/lib/ttf/4.ttf', 24, NULL);
			$captcha->Border(1, NULL);
			$captcha->Margin(5, 5, 7, 7);
			$captcha->Shadow_Offset(1, 1);
		}

		$captcha->Display(Captcha_2::OUTPUT_GIF);
	}
}

$config = parse_ini_file('../config/config.ini');
$override_file = '../config/override.ini';
if (file_exists($override_file))
{
	$override = parse_ini_file($override_file);
	$config = array_merge($config, $override);
}

$opt = array();
$user = null;
if (isset($config['username'])) {
	$user = $opt['login'] = $config['username'];
	$opt['password'] = $config['password'];
}
else if (isset($_SERVER['PHP_AUTH_USER']))
{
	$user = $opt['login'] = $_SERVER['PHP_AUTH_USER'];
	$opt['password'] = $_SERVER['PHP_AUTH_PW'];
}

$url = $config['lead_api.wsdl'];
if (!empty($user) && isset($config["$user.lead_api.wsdl"])) {
	$url = $config["$user.lead_api.wsdl"];
}

$leadApi = new SoapClient($url, $opt);

$olp_captcha = new BFWCaptcha($leadApi, $config['captcha.static_value']);
$olp_captcha->displayCaptcha($_GET['unique_id'], isset($_GET['img_size']) ? $_GET['img_size'] : 'large');
